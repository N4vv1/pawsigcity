<?php
/**
 * OTP Debug Tool - Test each step of the process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Debug Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2d5f4a 0%, #1e4433 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .check-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #ddd;
        }
        .check-item.success { border-left-color: #28a745; }
        .check-item.error { border-left-color: #dc3545; }
        .check-item.warning { border-left-color: #ffc107; }
        .check-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .status-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .status-icon.success { background: #28a745; }
        .status-icon.error { background: #dc3545; }
        .status-icon.warning { background: #ffc107; color: #000; }
        .check-title { font-size: 18px; font-weight: 600; }
        .check-message { color: #666; margin-top: 8px; }
        .details {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
        }
        .overall-status {
            text-align: center;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .overall-status.success { background: #d4edda; color: #155724; }
        .overall-status.error { background: #f8d7da; color: #721c24; }
        .test-form {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }
        .test-form h3 { margin-bottom: 15px; color: #856404; }
        .test-form input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .test-form button {
            background: #ffc107;
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        .test-form button:hover { background: #ffca2c; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 OTP System Diagnostics</h1>
            <p>Checking all components of the OTP system</p>
            <p style="font-size: 12px; margin-top: 10px;">Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        <div class="content">
            <?php
            
            $results = [];
            $has_errors = false;

            // Check 1: Database connection
            echo '<div class="check-item ';
            try {
                if (!file_exists('../../db.php')) {
                    echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">Database Connection</div></div>';
                    echo '<div class="check-message">❌ db.php file not found</div>';
                    $has_errors = true;
                } else {
                    require_once '../../db.php';
                    
                    if (!isset($conn) || !$conn) {
                        echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">Database Connection</div></div>';
                        echo '<div class="check-message">❌ Database connection failed</div>';
                        $has_errors = true;
                    } else {
                        $test_query = "SELECT COUNT(*) as count FROM users";
                        $test_result = pg_query($conn, $test_query);
                        
                        if ($test_result) {
                            $row = pg_fetch_assoc($test_result);
                            echo 'success"><div class="check-header"><div class="status-icon success">✓</div><div class="check-title">Database Connection</div></div>';
                            echo '<div class="check-message">✅ Connected successfully (Users in database: ' . $row['count'] . ')</div>';
                        } else {
                            echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">Database Connection</div></div>';
                            echo '<div class="check-message">❌ Query failed: ' . pg_last_error($conn) . '</div>';
                            $has_errors = true;
                        }
                    }
                }
            } catch (Exception $e) {
                echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">Database Connection</div></div>';
                echo '<div class="check-message">❌ Exception: ' . $e->getMessage() . '</div>';
                $has_errors = true;
            }
            echo '</div>';

            // Check 2: PHPMailer
            echo '<div class="check-item ';
            $autoload_paths = ['./vendor/autoload.php', '../vendor/autoload.php', '../../vendor/autoload.php'];
            $autoload_found = false;
            $found_path = '';
            
            foreach ($autoload_paths as $path) {
                if (file_exists($path)) {
                    require $path;
                    $autoload_found = true;
                    $found_path = $path;
                    break;
                }
            }

            if (!$autoload_found) {
                echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">PHPMailer Library</div></div>';
                echo '<div class="check-message">❌ Composer autoload not found. Run: <code>composer install</code></div>';
                echo '<div class="details">Searched paths:<br>';
                foreach ($autoload_paths as $path) {
                    echo '• ' . realpath('.') . '/' . $path . ' - ' . (file_exists($path) ? 'Found' : 'Not found') . '<br>';
                }
                echo '</div>';
                $has_errors = true;
            } else {
                $class_exists = class_exists('PHPMailer\PHPMailer\PHPMailer');
                if ($class_exists) {
                    echo 'success"><div class="check-header"><div class="status-icon success">✓</div><div class="check-title">PHPMailer Library</div></div>';
                    echo '<div class="check-message">✅ PHPMailer loaded successfully from: ' . $found_path . '</div>';
                } else {
                    echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">PHPMailer Library</div></div>';
                    echo '<div class="check-message">❌ PHPMailer class not found after loading autoload</div>';
                    $has_errors = true;
                }
            }
            echo '</div>';

            // Check 3: SMTP Credentials
            echo '<div class="check-item ';
            $smtp_user = getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com';
            $smtp_pass = getenv('SMTP_PASSWORD') ?: 'iigy qtnu ojku ktsx';

            if (empty($smtp_pass)) {
                echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">SMTP Configuration</div></div>';
                echo '<div class="check-message">❌ SMTP password not set</div>';
                $has_errors = true;
            } else {
                echo 'warning"><div class="check-header"><div class="status-icon warning">!</div><div class="check-title">SMTP Configuration</div></div>';
                echo '<div class="check-message">⚠️ Credentials configured (actual send test required)</div>';
                echo '<div class="details">';
                echo 'Username: ' . htmlspecialchars($smtp_user) . '<br>';
                echo 'Password: ' . str_repeat('*', strlen($smtp_pass)) . ' (' . strlen($smtp_pass) . ' characters)<br>';
                echo 'Host: smtp.gmail.com<br>';
                echo 'Port: 587<br>';
                echo 'Encryption: STARTTLS';
                echo '</div>';
            }
            echo '</div>';

            // Check 4: OTP Table Structure
            echo '<div class="check-item ';
            try {
                if (isset($conn) && $conn) {
                    $table_query = "SELECT column_name, data_type, is_nullable 
                                    FROM information_schema.columns 
                                    WHERE table_name = 'otp_verifications'
                                    ORDER BY ordinal_position";
                    
                    $table_result = pg_query($conn, $table_query);
                    
                    if ($table_result && pg_num_rows($table_result) > 0) {
                        echo 'success"><div class="check-header"><div class="status-icon success">✓</div><div class="check-title">OTP Table Structure</div></div>';
                        echo '<div class="check-message">✅ Table found with ' . pg_num_rows($table_result) . ' columns</div>';
                        echo '<div class="details"><table style="width:100%; border-collapse: collapse;">';
                        echo '<tr style="background:#f0f0f0; font-weight:bold;"><td style="padding:8px; border:1px solid #ddd;">Column</td><td style="padding:8px; border:1px solid #ddd;">Type</td><td style="padding:8px; border:1px solid #ddd;">Nullable</td></tr>';
                        while ($row = pg_fetch_assoc($table_result)) {
                            echo '<tr><td style="padding:8px; border:1px solid #ddd;">' . $row['column_name'] . '</td>';
                            echo '<td style="padding:8px; border:1px solid #ddd;">' . $row['data_type'] . '</td>';
                            echo '<td style="padding:8px; border:1px solid #ddd;">' . $row['is_nullable'] . '</td></tr>';
                        }
                        echo '</table></div>';
                    } else {
                        echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">OTP Table Structure</div></div>';
                        echo '<div class="check-message">❌ Table "otp_verifications" not found</div>';
                        $has_errors = true;
                    }
                }
            } catch (Exception $e) {
                echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">OTP Table Structure</div></div>';
                echo '<div class="check-message">❌ Exception: ' . $e->getMessage() . '</div>';
                $has_errors = true;
            }
            echo '</div>';

            // Check 5: Recent OTP Records
            echo '<div class="check-item ';
            try {
                if (isset($conn) && $conn) {
                    $recent_query = "SELECT otp_id, email, type, purpose, is_verified, is_used, 
                                            created_at, expires_at 
                                     FROM otp_verifications 
                                     ORDER BY created_at DESC 
                                     LIMIT 5";
                    
                    $recent_result = pg_query($conn, $recent_query);
                    
                    if ($recent_result) {
                        echo 'success"><div class="check-header"><div class="status-icon success">✓</div><div class="check-title">Recent OTP Records</div></div>';
                        echo '<div class="check-message">✅ Found ' . pg_num_rows($recent_result) . ' recent OTP records</div>';
                        
                        if (pg_num_rows($recent_result) > 0) {
                            echo '<div class="details"><table style="width:100%; border-collapse: collapse; font-size: 11px;">';
                            echo '<tr style="background:#f0f0f0; font-weight:bold;">';
                            echo '<td style="padding:6px; border:1px solid #ddd;">ID</td>';
                            echo '<td style="padding:6px; border:1px solid #ddd;">Email</td>';
                            echo '<td style="padding:6px; border:1px solid #ddd;">Type</td>';
                            echo '<td style="padding:6px; border:1px solid #ddd;">Purpose</td>';
                            echo '<td style="padding:6px; border:1px solid #ddd;">Verified</td>';
                            echo '<td style="padding:6px; border:1px solid #ddd;">Used</td>';
                            echo '<td style="padding:6px; border:1px solid #ddd;">Created</td>';
                            echo '</tr>';
                            
                            while ($row = pg_fetch_assoc($recent_result)) {
                                echo '<tr>';
                                echo '<td style="padding:6px; border:1px solid #ddd;">' . $row['otp_id'] . '</td>';
                                echo '<td style="padding:6px; border:1px solid #ddd;">' . htmlspecialchars($row['email']) . '</td>';
                                echo '<td style="padding:6px; border:1px solid #ddd;">' . $row['type'] . '</td>';
                                echo '<td style="padding:6px; border:1px solid #ddd;">' . $row['purpose'] . '</td>';
                                echo '<td style="padding:6px; border:1px solid #ddd;">' . ($row['is_verified'] === 't' ? '✓' : '✗') . '</td>';
                                echo '<td style="padding:6px; border:1px solid #ddd;">' . ($row['is_used'] === 't' ? '✓' : '✗') . '</td>';
                                echo '<td style="padding:6px; border:1px solid #ddd;">' . $row['created_at'] . '</td>';
                                echo '</tr>';
                            }
                            echo '</table></div>';
                        }
                    }
                }
            } catch (Exception $e) {
                echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">Recent OTP Records</div></div>';
                echo '<div class="check-message">❌ Exception: ' . $e->getMessage() . '</div>';
            }
            echo '</div>';

            // Check 6: Email Send Test (if test_email parameter is provided)
            if (isset($_GET['test_email']) && $autoload_found && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                echo '<div class="check-item ';
                $test_email = $_GET['test_email'];
                
                try {
                    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                        echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">Email Send Test</div></div>';
                        echo '<div class="check-message">❌ Invalid email format</div>';
                    } else {
                        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                        
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $smtp_user;
                        $mail->Password   = $smtp_pass;
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->SMTPDebug  = 0;
                        
                        $mail->setFrom($smtp_user, 'PAWsig City Test');
                        $mail->addAddress($test_email);
                        
                        $mail->isHTML(true);
                        $mail->Subject = 'Test Email - PAWsig City';
                        $mail->Body = '<h1>✓ Email Test Successful</h1><p>If you received this email, your SMTP configuration is working correctly!</p>';
                        
                        $mail->send();
                        
                        echo 'success"><div class="check-header"><div class="status-icon success">✓</div><div class="check-title">Email Send Test</div></div>';
                        echo '<div class="check-message">✅ Test email sent successfully to ' . htmlspecialchars($test_email) . '</div>';
                    }
                } catch (Exception $e) {
                    echo 'error"><div class="check-header"><div class="status-icon error">✗</div><div class="check-title">Email Send Test</div></div>';
                    echo '<div class="check-message">❌ Failed to send test email</div>';
                    echo '<div class="details">';
                    echo '<strong>Error:</strong> ' . $e->getMessage() . '<br><br>';
                    if (isset($mail) && $mail->ErrorInfo) {
                        echo '<strong>SMTP Error:</strong> ' . $mail->ErrorInfo;
                    }
                    echo '</div>';
                    $has_errors = true;
                }
                echo '</div>';
            }

            // Overall Status
            echo '<div class="overall-status ' . ($has_errors ? 'error' : 'success') . '">';
            if ($has_errors) {
                echo '❌ ERRORS FOUND - Please fix the issues above';
            } else {
                echo '✅ ALL CHECKS PASSED - System appears to be working correctly';
            }
            echo '</div>';

            // Test Form
            if (!isset($_GET['test_email'])) {
                echo '<div class="test-form">';
                echo '<h3>📧 Test Email Sending</h3>';
                echo '<p style="margin-bottom: 15px; color: #856404;">Enter your email address to send a test email and verify SMTP is working:</p>';
                echo '<form method="GET">';
                echo '<input type="email" name="test_email" placeholder="your@email.com" required>';
                echo '<button type="submit">Send Test Email</button>';
                echo '</form>';
                echo '</div>';
            }

            if (isset($conn)) {
                pg_close($conn);
            }
            ?>
        </div>
    </div>
</body>
</html>