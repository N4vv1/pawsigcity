<?php
/**
 * Send OTP - FIXED VERSION (Consistent naming)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

error_log("=== SEND OTP REQUEST ===");
error_log("POST data: " . print_r($_POST, true));

if (!file_exists('../../db.php')) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database configuration file not found'
    ]);
    exit;
}

require_once '../../db.php';

$autoload_paths = ['./vendor/autoload.php'];
$autoload_found = false;

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoload_found = true;
        break;
    }
}

if (!$autoload_found) {
    echo json_encode([
        'success' => false, 
        'message' => 'PHPMailer not installed'
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
$purpose = trim($_POST['purpose'] ?? '');

error_log("Parsed - Email: $email, Purpose: $purpose");

// Validate purpose - accept both naming conventions
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (empty($purpose) || !in_array($purpose, ['registration', 'reset_password', 'forgot_password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid purpose']);
    exit;
}

// Normalize purpose naming (use forgot_password to match database constraint)
if ($purpose === 'reset_password') {
    $purpose = 'forgot_password';
}

error_log("Normalized purpose: $purpose");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Check email based on purpose
    if ($purpose === 'registration') {
        error_log("Checking if email exists for registration...");
        $check_query = "SELECT user_id FROM users WHERE email = $1";
        $check_result = pg_query_params($conn, $check_query, [$email]);
        
        if (!$check_result) {
            error_log("Registration check query failed: " . pg_last_error($conn));
            throw new Exception('Database query failed');
        }
        
        if (pg_num_rows($check_result) > 0) {
            error_log("Email already exists - cannot register");
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }
        error_log("Registration check passed - email doesn't exist");
    }
    
    // For password reset, email MUST exist - CHECK AFTER NORMALIZATION
    if ($purpose === 'forgot_password') {  // ✅ FIXED: Check for 'forgot_password' instead of 'reset_password'
        error_log("Checking if email exists for password reset...");
        
        $check_query = "SELECT user_id, email FROM users WHERE email = $1";
        $check_result = pg_query_params($conn, $check_query, [$email]);
        
        if (!$check_result) {
            error_log("Password reset check query failed: " . pg_last_error($conn));
            throw new Exception('Database query failed: ' . pg_last_error($conn));
        }
        
        $row_count = pg_num_rows($check_result);
        error_log("Query returned $row_count rows for email: $email");
        
        // Exit if email NOT found
        if ($row_count === 0) {
            error_log("Email NOT found in database - cannot reset password");
            echo json_encode([
                'success' => false, 
                'message' => 'No account found with this email address'
            ]);
            exit;
        }
        
        $user = pg_fetch_assoc($check_result);
        error_log("User found for password reset: User ID = " . $user['user_id']);
    }
    
    // Rate limiting
    error_log("Checking rate limit...");
    $rate_limit_query = "SELECT COUNT(*) as count FROM otp_verifications 
                         WHERE email = $1 AND created_at > NOW() - INTERVAL '10 minutes'";
    $rate_result = pg_query_params($conn, $rate_limit_query, [$email]);
    
    if ($rate_result) {
        $rate_data = pg_fetch_assoc($rate_result);
        error_log("Recent OTP count: " . $rate_data['count']);
        
        if ($rate_data['count'] >= 3) {
            echo json_encode([
                'success' => false, 
                'message' => 'Too many requests. Please try again in 10 minutes.'
            ]);
            exit;
        }
    }
    
    // Delete old OTPs for this email and purpose (check both type and purpose fields)
    error_log("Deleting old OTPs for email: $email, purpose: $purpose");
    $delete_query = "DELETE FROM otp_verifications 
                     WHERE email = $1 
                     AND (purpose = $2 OR type = $2)
                     AND is_verified = FALSE 
                     AND is_used = FALSE";
    $delete_result = pg_query_params($conn, $delete_query, [$email, $purpose]);
    
    if ($delete_result) {
        $deleted_count = pg_affected_rows($delete_result);
        error_log("Deleted $deleted_count old OTPs");
    }
    
    // Generate OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    error_log("Generated OTP: $otp for purpose: $purpose");
    
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Insert OTP - set BOTH type and purpose to the same value for consistency
    error_log("Inserting OTP into database...");
    $insert_query = "INSERT INTO otp_verifications 
                     (email, otp, type, purpose, expires_at, ip_address, is_used, is_verified, created_at, attempts) 
                     VALUES ($1, $2, $3, $4, $5, $6, FALSE, FALSE, CURRENT_TIMESTAMP, 0) 
                     RETURNING otp_id";
    
    $insert_result = pg_query_params($conn, $insert_query, [
        $email,
        $otp,
        $purpose,      // type = purpose (both set to 'forgot_password' or 'registration')
        $purpose,      // purpose = purpose
        $expires_at,
        $ip_address
    ]);
    
    if (!$insert_result) {
        error_log("Insert failed: " . pg_last_error($conn));
        throw new Exception('Failed to generate OTP: ' . pg_last_error($conn));
    }
    
    $otp_record = pg_fetch_assoc($insert_result);
    error_log("OTP inserted with ID: " . $otp_record['otp_id'] . " (type=$purpose, purpose=$purpose)");
    
    // Send email
    error_log("Preparing to send email...");
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: 'iigy qtnu ojku ktsx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0; // Set to 0 for production
        
        // Verify credentials are set
        if (empty($mail->Username) || empty($mail->Password)) {
            throw new Exception('SMTP credentials not configured');
        }
        
        error_log("SMTP Config - Host: smtp.gmail.com, Port: 587, User: " . $mail->Username);
        
        $mail->setFrom($mail->Username, 'PAWsig City');
        $mail->addAddress($email);
        
        // Add reply-to
        $mail->addReplyTo($mail->Username, 'PAWsig City');
        
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code - PAWsig City';
        $mail->CharSet = 'UTF-8';
        
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
        
        error_log("Attempting to send email...");
        $mail->send();
        error_log("✓ Email sent successfully to $email for purpose: $purpose");
        
        echo json_encode([
            'success' => true, 
            'message' => 'OTP sent successfully to your email',
            'otp_id' => $otp_record['otp_id']
        ]);
        
    } catch (Exception $e) {
        error_log("✗ Mail Error: " . $mail->ErrorInfo);
        error_log("✗ Mail Exception: " . $e->getMessage());
        error_log("✗ Stack Trace: " . $e->getTraceAsString());
        
        // Clean up failed OTP
        $delete_failed = "DELETE FROM otp_verifications WHERE otp_id = $1";
        pg_query_params($conn, $delete_failed, [$otp_record['otp_id']]);
        error_log("Cleaned up failed OTP record");
        
        // Return detailed error for debugging
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send email. Please try again.',
            'error_detail' => $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    error_log("Send OTP Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred. Please try again.'
    ]);
}

if (isset($conn)) {
    pg_close($conn);
}
?>