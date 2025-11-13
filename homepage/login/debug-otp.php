<?php
/**
 * Trace Forgot Password Flow
 * This simulates the entire forgot password process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: text/html; charset=utf-8');

require_once '../../db.php';
require_once './vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password Flow Trace</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .step { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #007bff; }
        .step.success { border-left-color: #28a745; }
        .step.error { border-left-color: #dc3545; background: #fff5f5; }
        .step.warning { border-left-color: #ffc107; background: #fffbf0; }
        h3 { margin: 0 0 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .form-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        input { padding: 10px; width: 300px; border: 2px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🔍 Forgot Password Flow Tracer</h1>
    <p>This tool simulates what happens when you use forgot password</p>

    <div class="form-section">
        <h3>Test Forgot Password Flow</h3>
        <form method="POST">
            <input type="email" name="test_email" placeholder="Enter email to test" required value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>">
            <button type="submit" name="test_flow">Trace Flow</button>
        </form>
    </div>

    <?php
    if (isset($_POST['test_flow']) && !empty($_POST['test_email'])) {
        $email = trim($_POST['test_email']);
        echo "<h2>Testing flow for: " . htmlspecialchars($email) . "</h2>";
        
        // STEP 1: Validate Email Format
        echo '<div class="step ' . (filter_var($email, FILTER_VALIDATE_EMAIL) ? 'success' : 'error') . '">';
        echo '<h3>Step 1: Validate Email Format</h3>';
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo '✅ Email format is valid';
        } else {
            echo '❌ Invalid email format';
        }
        echo '</div>';
        
        // STEP 2: Check if User Exists
        echo '<div class="step">';
        echo '<h3>Step 2: Check if User Exists in Database</h3>';
        $check_query = "SELECT user_id, email, first_name, last_name FROM users WHERE email = $1";
        $check_result = pg_query_params($conn, $check_query, [$email]);
        
        if (!$check_result) {
            echo '<div class="error">❌ Database query failed: ' . pg_last_error($conn) . '</div>';
        } else {
            $row_count = pg_num_rows($check_result);
            echo '<pre>Query: ' . $check_query . "\nParameter: " . $email . "\nRows found: " . $row_count . '</pre>';
            
            if ($row_count === 0) {
                echo '<div class="error">❌ No user found with this email<br>';
                echo 'This is why OTP cannot be sent!</div>';
                echo '</div>';
                
                // Show what would happen in send-otp.php
                echo '<div class="step error">';
                echo '<h3>Result: send-otp.php would return</h3>';
                echo '<pre>' . json_encode(['success' => false, 'message' => 'No account found with this email address'], JSON_PRETTY_PRINT) . '</pre>';
                echo '</div>';
                
            } else {
                $user = pg_fetch_assoc($check_result);
                echo '<div class="success">✅ User found!<br>';
                echo 'User ID: ' . $user['user_id'] . '<br>';
                echo 'Name: ' . $user['first_name'] . ' ' . $user['last_name'] . '</div>';
                echo '</div>';
                
                // STEP 3: Generate OTP
                echo '<div class="step success">';
                echo '<h3>Step 3: Generate OTP</h3>';
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                echo '✅ Generated OTP: <strong>' . $otp . '</strong>';
                echo '</div>';
                
                // STEP 4: Insert OTP into Database
                echo '<div class="step">';
                echo '<h3>Step 4: Insert OTP into Database</h3>';
                $purpose = 'reset_password';
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                
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
                    echo '<div class="error">❌ Failed to insert OTP: ' . pg_last_error($conn) . '</div>';
                } else {
                    $otp_record = pg_fetch_assoc($insert_result);
                    echo '<div class="success">✅ OTP inserted successfully<br>';
                    echo 'OTP ID: ' . $otp_record['otp_id'] . '<br>';
                    echo 'Purpose: ' . $purpose . '<br>';
                    echo 'Expires: ' . $expires_at . '</div>';
                }
                echo '</div>';
                
                // STEP 5: Send Email
                echo '<div class="step">';
                echo '<h3>Step 5: Send Email via SMTP</h3>';
                try {
                    $mail = new PHPMailer(true);
                    
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com';
                    $mail->Password   = getenv('SMTP_PASSWORD') ?: 'iigy qtnu ojku ktsx';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->SMTPDebug  = 0;
                    
                    $mail->setFrom($mail->Username, 'PAWsig City');
                    $mail->addAddress($email);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP - PAWsig City';
                    $mail->Body = "
                        <h2>Password Reset Request</h2>
                        <p>Your OTP code is: <strong style='font-size: 24px;'>{$otp}</strong></p>
                        <p>This code will expire in 10 minutes.</p>
                    ";
                    
                    $mail->send();
                    
                    echo '<div class="success">✅ Email sent successfully to ' . $email . '</div>';
                    echo '<div style="background: #d4edda; padding: 10px; margin-top: 10px; border-radius: 4px;">';
                    echo '<strong>✓ FORGOT PASSWORD WORKING!</strong><br>';
                    echo 'Check the email inbox for OTP: <strong>' . $otp . '</strong>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="error">❌ Failed to send email<br>';
                    echo 'Error: ' . $e->getMessage() . '<br>';
                    echo 'SMTP Error: ' . ($mail->ErrorInfo ?? 'N/A') . '</div>';
                }
                echo '</div>';
                
                // Show Final Result
                echo '<div class="step success">';
                echo '<h3>✅ Complete Flow Result</h3>';
                echo '<pre>' . json_encode([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'otp_id' => $otp_record['otp_id'] ?? null,
                    'email' => $email,
                    'purpose' => 'reset_password'
                ], JSON_PRETTY_PRINT) . '</pre>';
                echo '</div>';
            }
        }
    } else {
        echo '<p style="color: #666; font-style: italic;">Enter an email and click "Trace Flow" to test</p>';
    }
    
    pg_close($conn);
    ?>

    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px;">
        <h3>💡 Common Issues:</h3>
        <ul>
            <li><strong>Email not in database:</strong> The user must be registered first</li>
            <li><strong>Wrong email:</strong> Make sure you're testing with a registered email</li>
            <li><strong>JavaScript error:</strong> Check browser console for errors</li>
            <li><strong>Wrong endpoint:</strong> Make sure send-otp.php exists and is accessible</li>
        </ul>
    </div>
</body>
</html>