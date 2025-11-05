<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once '../../db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
header('Content-Type: application/json');

if (!$conn) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed'
    ]);
    exit;
}

// ✅ IMPROVED: SendGrid with better timeout and error handling
function sendOtpEmailAPI($email, $otp, $subject, $message) {
    $sendgrid_api_key = getenv('SENDGRID_API_KEY');
    
    error_log("Attempting SendGrid for: $email");
    
    if (!$sendgrid_api_key) {
        error_log("SendGrid API key not configured");
        return ['success' => false, 'error' => 'SendGrid not configured'];
    }
    
    $emailBody = buildEmailTemplate($otp, $subject, $message);

    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $email]],
                'subject' => $subject
            ]
        ],
        'from' => ['email' => 'noreply@pawsigcity.com', 'name' => 'PAWsig City'],
        'content' => [
            [
                'type' => 'text/html',
                'value' => $emailBody
            ]
        ]
    ];

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $sendgrid_api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    error_log("SendGrid Response Code: $http_code");

    if ($error) {
        error_log("SendGrid cURL Error: $error");
        return ['success' => false, 'error' => $error];
    }

    if ($http_code >= 200 && $http_code < 300) {
        error_log("✅ SendGrid email sent successfully!");
        return ['success' => true];
    }

    error_log("❌ SendGrid failed: HTTP $http_code - $response");
    return ['success' => false, 'error' => "SendGrid HTTP $http_code"];
}

// ✅ IMPROVED: SMTP with port 587 (TLS) - better for cloud hosting
function sendOtpEmailSMTP($email, $otp, $subject, $message) {
    error_log("Attempting SMTP for: $email");
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not found");
        return ['success' => false, 'error' => 'PHPMailer not installed'];
    }
    
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'johnbernardmitra25@gmail.com';
        $mail->Password   = 'htvt wkwg thhq ohtg'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // ✅ CRITICAL: Increase timeouts for cloud hosting
        $mail->Timeout    = 60;
        $mail->SMTPDebug  = 2; // Enable debug output for troubleshooting
        
        // ✅ IMPORTANT: More permissive SSL options for cloud environments
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients
        $mail->setFrom('johnbernardmitra25@gmail.com', 'PAWsig City');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = buildEmailTemplate($otp, $subject, $message);
        $mail->AltBody = strip_tags("Your OTP is: $otp. This code will expire in 10 minutes.");

        $mail->send();
        error_log("✅ SMTP email sent successfully!");
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("❌ SMTP Error: {$mail->ErrorInfo}");
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// ✅ NEW: Mailgun API as third fallback (free tier: 5000 emails/month)
function sendOtpEmailMailgun($email, $otp, $subject, $message) {
    $mailgun_api_key = getenv('MAILGUN_API_KEY');
    $mailgun_domain = getenv('MAILGUN_DOMAIN'); // e.g., 'sandboxXXX.mailgun.org'
    
    error_log("Attempting Mailgun for: $email");
    
    if (!$mailgun_api_key || !$mailgun_domain) {
        error_log("Mailgun not configured");
        return ['success' => false, 'error' => 'Mailgun not configured'];
    }
    
    $emailBody = buildEmailTemplate($otp, $subject, $message);
    
    $ch = curl_init("https://api.mailgun.net/v3/{$mailgun_domain}/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => "api:{$mailgun_api_key}",
        CURLOPT_POSTFIELDS => [
            'from' => 'PAWsig City <noreply@' . $mailgun_domain . '>',
            'to' => $email,
            'subject' => $subject,
            'html' => $emailBody
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("Mailgun Response Code: $http_code");
    
    if ($error) {
        error_log("Mailgun cURL Error: $error");
        return ['success' => false, 'error' => $error];
    }
    
    if ($http_code >= 200 && $http_code < 300) {
        error_log("✅ Mailgun email sent successfully!");
        return ['success' => true];
    }
    
    error_log("❌ Mailgun failed: HTTP $http_code - $response");
    return ['success' => false, 'error' => "Mailgun HTTP $http_code"];
}

// ✅ Email template builder (DRY principle)
function buildEmailTemplate($otp, $subject, $message) {
    return "
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
                <p>If you didn't request this code, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " PAWsig City. All rights reserved.</p>
                <p>We care for your pets when they need it most.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// ✅ IMPROVED: Multi-fallback email sending with priority order
function sendOtpEmail($email, $otp, $subject, $message) {
    error_log("=== Starting email send for: $email ===");
    
    // Priority 1: Try SendGrid (best for cloud hosting)
    if (getenv('SENDGRID_API_KEY')) {
        error_log("Trying SendGrid (Priority 1)...");
        $result = sendOtpEmailAPI($email, $otp, $subject, $message);
        if ($result['success']) {
            return $result;
        }
        error_log("SendGrid failed, trying next method...");
    }
    
    // Priority 2: Try Mailgun (reliable alternative)
    if (getenv('MAILGUN_API_KEY') && getenv('MAILGUN_DOMAIN')) {
        error_log("Trying Mailgun (Priority 2)...");
        $result = sendOtpEmailMailgun($email, $otp, $subject, $message);
        if ($result['success']) {
            return $result;
        }
        error_log("Mailgun failed, trying next method...");
    }
    
    // Priority 3: Try SMTP (may not work on cloud hosting due to firewall)
    error_log("Trying SMTP (Priority 3)...");
    $result = sendOtpEmailSMTP($email, $otp, $subject, $message);
    
    if (!$result['success']) {
        error_log("❌ ALL EMAIL METHODS FAILED");
    }
    
    return $result;
}

function generateOtp() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function storeOtp($conn, $email, $otp, $type) {
    try {
        error_log("Storing OTP for $email, type: $type");
        $expires = date("Y-m-d H:i:s", strtotime('+10 minutes'));
        
        pg_query_params($conn, "DELETE FROM otp_verifications WHERE email = $1 AND type = $2", [$email, $type]);
        
        $query = "INSERT INTO otp_verifications (email, otp, type, expires_at, created_at) VALUES ($1, $2, $3, $4, NOW())";
        $result = pg_query_params($conn, $query, [$email, $otp, $type, $expires]);
        
        if ($result === false) {
            error_log("Database error: " . pg_last_error($conn));
            return false;
        }
        
        error_log("✅ OTP stored successfully");
        return true;
    } catch (Exception $e) {
        error_log("Store OTP exception: " . $e->getMessage());
        return false;
    }
}

function verifyOtp($conn, $email, $otp, $type) {
    try {
        error_log("Verifying OTP for $email, type: $type");
        $query = "SELECT * FROM otp_verifications WHERE email = $1 AND otp = $2 AND type = $3 AND expires_at > NOW() AND is_used = FALSE";
        $result = pg_query_params($conn, $query, [$email, $otp, $type]);
        
        if ($result && pg_num_rows($result) > 0) {
            pg_query_params($conn, "UPDATE otp_verifications SET is_used = TRUE WHERE email = $1 AND otp = $2 AND type = $3", [$email, $otp, $type]);
            error_log("✅ OTP verified successfully");
            return true;
        }
        
        error_log("❌ OTP verification failed");
        return false;
    } catch (Exception $e) {
        error_log("Verify OTP exception: " . $e->getMessage());
        return false;
    }
}

// Main handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    error_log("=== OTP Handler Request === Action: $action");
    
    try {
        switch ($action) {
            case 'send_forgot_otp':
                $email = trim($_POST['email'] ?? '');
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                    exit;
                }
                
                $check = pg_query_params($conn, "SELECT user_id FROM users WHERE email = $1", [$email]);
                if (!$check || pg_num_rows($check) === 0) {
                    echo json_encode(['success' => false, 'message' => 'Email not found']);
                    exit;
                }
                
                $otp = generateOtp();
                
                if (storeOtp($conn, $email, $otp, 'forgot_password')) {
                    $subject = "Password Reset OTP - PAWsig City";
                    $message = "You have requested to reset your password. Please use the following OTP:";
                    
                    $emailResult = sendOtpEmail($email, $otp, $subject, $message);
                    
                    if ($emailResult['success']) {
                        echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Failed to send email. Please try again later.'
                        ]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP']);
                }
                break;
                
            case 'verify_forgot_otp':
                $email = trim($_POST['email'] ?? '');
                $otp = trim($_POST['otp'] ?? '');
                
                if (verifyOtp($conn, $email, $otp, 'forgot_password')) {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_verified'] = true;
                    echo json_encode(['success' => true, 'message' => 'OTP verified']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
                }
                break;
                
            case 'reset_password':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_email'] !== $email) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit;
                }
                
                if (strlen($password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Password too short']);
                    exit;
                }
                
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $update = pg_query_params($conn, "UPDATE users SET password = $1 WHERE email = $2", [$hashed, $email]);
                
                if ($update) {
                    unset($_SESSION['reset_email'], $_SESSION['reset_verified']);
                    pg_query_params($conn, "DELETE FROM otp_verifications WHERE email = $1 AND type = 'forgot_password'", [$email]);
                    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
                }
                break;
                
            case 'send_register_otp':
                $email = trim($_POST['email'] ?? '');
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email']);
                    exit;
                }
                
                $check = pg_query_params($conn, "SELECT user_id FROM users WHERE email = $1", [$email]);
                if ($check && pg_num_rows($check) > 0) {
                    echo json_encode(['success' => false, 'message' => 'Email already registered']);
                    exit;
                }
                
                $otp = generateOtp();
                
                if (storeOtp($conn, $email, $otp, 'registration')) {
                    $subject = "Email Verification - PAWsig City";
                    $message = "Welcome! Please verify your email with this OTP:";
                    
                    $emailResult = sendOtpEmail($email, $otp, $subject, $message);
                    
                    if ($emailResult['success']) {
                        echo json_encode(['success' => true, 'message' => 'OTP sent']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP']);
                }
                break;
                
            case 'verify_register_otp':
                $email = trim($_POST['email'] ?? '');
                $otp = trim($_POST['otp'] ?? '');
                
                if (verifyOtp($conn, $email, $otp, 'registration')) {
                    pg_query_params($conn, "DELETE FROM otp_verifications WHERE email = $1 AND type = 'registration'", [$email]);
                    echo json_encode(['success' => true, 'message' => 'Email verified']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>