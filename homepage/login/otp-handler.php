<?php
session_start();
require_once '../../db.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../login/vendor/autoload.php'; // Make sure to install PHPMailer via Composer

header('Content-Type: application/json');

// Function to send email with OTP using Gmail SMTP
function sendOtpEmail($email, $otp, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'johnbernardmitra25@gmail.com'; // Your Gmail address
        $mail->Password   = 'htvt wkwg thhq ohtg';     // Your Gmail App Password (NOT regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('johnbernardmitra25@gmail.com', 'PAWsig City');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                 body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 0;
                    background-color: #ffffff;
                }
                .header { 
                    background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%); 
                    color: #2d5f4a; 
                    padding: 50px 30px; 
                    text-align: center; 
                    border-radius: 0;
                }
                .header h1 {
                    margin: 0 0 10px 0;
                    font-size: 42px;
                    font-weight: bold;
                    color: #2d5f4a;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                }
                .header p {
                    margin: 0;
                    font-size: 18px;
                    color: #2d5f4a;
                    font-weight: 500;
                }
                .content { 
                    background: #ffffff; 
                    padding: 40px 30px; 
                }
                .content p {
                    font-size: 16px;
                    color: #333;
                    margin: 0 0 20px 0;
                }
                .otp-code { 
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border: 3px solid #A8E6CF; 
                    padding: 25px; 
                    text-align: center; 
                    font-size: 42px; 
                    font-weight: bold; 
                    color: #2d5f4a; 
                    letter-spacing: 8px; 
                    margin: 30px 0; 
                    border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .footer { 
                    background: #2d5f4a; 
                    padding: 25px; 
                    text-align: center; 
                    font-size: 13px; 
                    color: #A8E6CF;
                }
                .footer p {
                    margin: 5px 0;
                }
                .warning { 
                    background: #fff3cd; 
                    border-left: 5px solid #ffc107; 
                    padding: 18px; 
                    margin: 25px 0; 
                    color: #856404;
                    border-radius: 5px;
                }
                .warning strong {
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>PAWsig City</h1>
                    <p>$subject</p>
                </div>
                <div class='content'>
                    <p>$message</p>
                    <div class='otp-code'>$otp</div>
                    <div class='warning'>
                        <strong>⚠️ Important:</strong> This OTP will expire in 10 minutes. Do not share this code with anyone.
                    </div>
                    <p>If you didn't request this code, please ignore this email or contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " PAWsig City. All rights reserved.</p>
                    <p>We care for your pets when they need it most.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Generate 6-digit OTP
function generateOtp() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Store OTP in database
function storeOtp($conn, $email, $otp, $type) {
    $expires = date("Y-m-d H:i:s", strtotime('+10 minutes'));
    
    // Delete old OTPs for this email and type
    pg_query_params($conn, "DELETE FROM otp_verifications WHERE email = $1 AND type = $2", [$email, $type]);
    
    // Insert new OTP
    $query = "INSERT INTO otp_verifications (email, otp, type, expires_at, created_at) VALUES ($1, $2, $3, $4, NOW())";
    $result = pg_query_params($conn, $query, [$email, $otp, $type, $expires]);
    
    return $result !== false;
}

// Verify OTP
function verifyOtp($conn, $email, $otp, $type) {
    $query = "SELECT * FROM otp_verifications WHERE email = $1 AND otp = $2 AND type = $3 AND expires_at > NOW() AND is_used = FALSE";
    $result = pg_query_params($conn, $query, [$email, $otp, $type]);
    
    if ($result && pg_num_rows($result) > 0) {
        // Mark OTP as used
        pg_query_params($conn, "UPDATE otp_verifications SET is_used = TRUE WHERE email = $1 AND otp = $2 AND type = $3", [$email, $otp, $type]);
        return true;
    }
    
    return false;
}

// Main handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_forgot_otp':
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit;
            }
            
            // Check if email exists
            $check = pg_query_params($conn, "SELECT user_id FROM users WHERE email = $1", [$email]);
            if (!$check || pg_num_rows($check) === 0) {
                echo json_encode(['success' => false, 'message' => 'Email not found in our system']);
                exit;
            }
            
            // Generate and send OTP
            $otp = generateOtp();
            
            if (storeOtp($conn, $email, $otp, 'forgot_password')) {
                $subject = "Password Reset OTP - PAWsig City";
                $message = "You have requested to reset your password. Please use the following OTP to proceed:";
                
                if (sendOtpEmail($email, $otp, $subject, $message)) {
                    echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate OTP. Please try again']);
            }
            break;
            
        case 'verify_forgot_otp':
            $email = trim($_POST['email'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            
            if (empty($email) || empty($otp)) {
                echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
                exit;
            }
            
            if (verifyOtp($conn, $email, $otp, 'forgot_password')) {
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_verified'] = true;
                echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
            }
            break;
            
        case 'reset_password':
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Verify session
            if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified'] || $_SESSION['reset_email'] !== $email) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized request']);
                exit;
            }
            
            if (empty($password) || strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit;
            }
            
            // Update password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $update = pg_query_params($conn, "UPDATE users SET password = $1 WHERE email = $2", [$hashed_password, $email]);
            
            if ($update) {
                // Clear session
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_verified']);
                
                // Delete used OTPs
                pg_query_params($conn, "DELETE FROM otp_verifications WHERE email = $1 AND type = 'forgot_password'", [$email]);
                
                echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
            }
            break;
            
        case 'send_register_otp':
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit;
            }
            
            // Check if email already exists
            $check = pg_query_params($conn, "SELECT user_id FROM users WHERE email = $1", [$email]);
            if ($check && pg_num_rows($check) > 0) {
                echo json_encode(['success' => false, 'message' => 'Email is already registered']);
                exit;
            }
            
            // Generate and send OTP
            $otp = generateOtp();
            
            if (storeOtp($conn, $email, $otp, 'registration')) {
                $subject = "Email Verification OTP - PAWsig City";
                $message = "Welcome to PAWsig City! Please use the following OTP to verify your email address:";
                
                if (sendOtpEmail($email, $otp, $subject, $message)) {
                    echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate OTP. Please try again']);
            }
            break;
            
        case 'verify_register_otp':
            $email = trim($_POST['email'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            
            if (empty($email) || empty($otp)) {
                echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
                exit;
            }
            
            if (verifyOtp($conn, $email, $otp, 'registration')) {
                // Delete used OTPs
                pg_query_params($conn, "DELETE FROM otp_verifications WHERE email = $1 AND type = 'registration'", [$email]);
                echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>