<?php
session_start();
header('Content-Type: application/json');
require_once '../../db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $purpose = $_POST['purpose']; // 'registration' or 'reset_password'
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
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
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    
    // Store OTP in session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_purpose'] = $purpose;
    $_SESSION['otp_timestamp'] = time();
    
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
                    <p>© 2024 PAWsig City. All rights reserved.</p>
                </div>
            </div>
        ";
        
        $mail->AltBody = "Your OTP code is: {$otp}. This code will expire in 10 minutes.";
        
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent successfully to your email']);
        
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>