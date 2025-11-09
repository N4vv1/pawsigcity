<?php
/**
 * Send OTP - Verified and slightly improved
 */

// Disable error display, only log errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Check if database connection file exists
if (!file_exists('../../db.php')) {
    echo json_encode(['success' => false, 'message' => 'Database configuration file not found']);
    exit;
}

require_once '../../db.php';

// Try to find vendor/autoload.php in multiple locations
$autoload_paths = [
    './vendor/autoload.php',
];

$autoload_found = false;
foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoload_found = true;
        error_log("PHPMailer autoload found at: " . $path);
        break;
    }
}

if (!$autoload_found) {
    error_log("PHPMailer autoload not found. Tried paths: " . implode(', ', $autoload_paths));
    error_log("Current directory: " . __DIR__);
    echo json_encode([
        'success' => false, 
        'message' => 'PHPMailer not installed. Run: composer install',
        'debug' => [
            'current_dir' => __DIR__,
            'tried_paths' => $autoload_paths
        ]
    ]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$purpose = trim($_POST['purpose'] ?? ''); // 'registration' or 'reset_password'

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (empty($purpose) || !in_array($purpose, ['registration', 'reset_password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid purpose']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // For registration, check if email already exists
    if ($purpose === 'registration') {
        $check_query = "SELECT user_id FROM users WHERE email = $1";
        $check_result = pg_query_params($conn, $check_query, [$email]);
        
        if ($check_result && pg_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }
    }
    
    // For reset password, check if email exists
    if ($purpose === 'reset_password') {
        $check_query = "SELECT user_id FROM users WHERE email = $1";
        $check_result = pg_query_params($conn, $check_query, [$email]);
        
        if ($check_result && pg_num_rows($check_result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Email not found']);
            exit;
        }
    }
    
    // Check rate limiting (max 3 OTP requests per email per 10 minutes)
    $rate_limit_query = "SELECT COUNT(*) as count FROM otp_verifications 
                         WHERE email = $1 AND created_at > NOW() - INTERVAL '10 minutes'";
    $rate_result = pg_query_params($conn, $rate_limit_query, [$email]);
    
    if ($rate_result) {
        $rate_data = pg_fetch_assoc($rate_result);
        if ($rate_data['count'] >= 3) {
            echo json_encode([
                'success' => false, 
                'message' => 'Too many requests. Please try again in 10 minutes.'
            ]);
            exit;
        }
    }
    
    // Delete old unverified/unused OTPs for this email and purpose
    $delete_query = "DELETE FROM otp_verifications 
                     WHERE email = $1 
                     AND (purpose = $2 OR type = $2)
                     AND is_verified = FALSE 
                     AND is_used = FALSE";
    pg_query_params($conn, $delete_query, [$email, $purpose]);
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    
    // Calculate expiration time (10 minutes from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Get client IP
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Store OTP in database
    $insert_query = "INSERT INTO otp_verifications 
                     (email, otp, type, purpose, expires_at, ip_address, is_used, is_verified, created_at, attempts) 
                     VALUES ($1, $2, $3, $4, $5, $6, FALSE, FALSE, CURRENT_TIMESTAMP, 0) 
                     RETURNING otp_id";
    
    $insert_result = pg_query_params($conn, $insert_query, [
        $email,
        $otp,
        $purpose,
        $purpose,
        $expires_at,
        $ip_address
    ]);
    
    if (!$insert_result) {
        throw new Exception('Failed to generate OTP: ' . pg_last_error($conn));
    }
    
    $otp_record = pg_fetch_assoc($insert_result);
    
    // Send OTP via email
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: 'iigy qtnu ojku ktsx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0; // Set to 2 for debugging
        
        $mail->setFrom(getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com', 'PAWsig City');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code - PAWsig City';
        
        $purpose_text = $purpose === 'registration' ? 'complete your registration' : 'reset your password';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%); padding: 20px; text-align: center;'>
                    <h1 style='color: #2d5f4a; margin: 0;'>PAWsig City</h1>
                </div>
                <div style='padding: 30px; background: #ffffff;'>
                    <h2 style='color: #2d5f4a;'>Your Verification Code</h2>
                    <p style='font-size: 16px; color: #666;'>Use the following OTP to {$purpose_text}:</p>
                    <div style='background: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;'>
                        <h1 style='color: #2d5f4a; font-size: 36px; letter-spacing: 8px; margin: 0;'>{$otp}</h1>
                    </div>
                    <p style='font-size: 14px; color: #999;'>This OTP will expire in 10 minutes.</p>
                    <p style='font-size: 14px; color: #999;'>If you didn't request this, please ignore this email.</p>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                    <p>© 2025 PAWsig City. All rights reserved.</p>
                </div>
            </div>
        ";
        
        $mail->AltBody = "Your OTP code is: {$otp}. This code will expire in 10 minutes.";
        
        $mail->send();
        
        error_log("OTP sent successfully to: " . $email);
        
        echo json_encode([
            'success' => true, 
            'message' => 'OTP sent successfully to your email',
            'otp_id' => $otp_record['otp_id']
        ]);
        
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        error_log("Mail Exception: " . $e->getMessage());
        
        // Delete the OTP since email failed
        $delete_failed = "DELETE FROM otp_verifications WHERE otp_id = $1";
        pg_query_params($conn, $delete_failed, [$otp_record['otp_id']]);
        
        // Return more detailed error for debugging
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send OTP. Please check your email configuration.',
            'debug_error' => $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    error_log("Send OTP Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

if (isset($conn)) {
    pg_close($conn);
}
?>