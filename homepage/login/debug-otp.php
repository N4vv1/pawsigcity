<?php
/**
 * OTP Debug Tool - Test each step of the process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check 1: Database connection
$results['checks']['database'] = [
    'name' => 'Database Connection',
    'status' => 'checking'
];

try {
    if (!file_exists('../../db.php')) {
        $results['checks']['database']['status'] = 'error';
        $results['checks']['database']['message'] = 'db.php file not found';
    } else {
        require_once '../../db.php';
        
        if (!isset($conn) || !$conn) {
            $results['checks']['database']['status'] = 'error';
            $results['checks']['database']['message'] = 'Database connection failed';
        } else {
            // Test query
            $test_query = "SELECT COUNT(*) as count FROM users";
            $test_result = pg_query($conn, $test_query);
            
            if ($test_result) {
                $row = pg_fetch_assoc($test_result);
                $results['checks']['database']['status'] = 'success';
                $results['checks']['database']['message'] = 'Connected (Users: ' . $row['count'] . ')';
            } else {
                $results['checks']['database']['status'] = 'error';
                $results['checks']['database']['message'] = pg_last_error($conn);
            }
        }
    }
} catch (Exception $e) {
    $results['checks']['database']['status'] = 'error';
    $results['checks']['database']['message'] = $e->getMessage();
}

// Check 2: PHPMailer
$results['checks']['phpmailer'] = [
    'name' => 'PHPMailer Library',
    'status' => 'checking'
];

$autoload_paths = [
    './vendor/autoload.php',
    '../vendor/autoload.php',
    '../../vendor/autoload.php'
];

$autoload_found = false;
foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoload_found = true;
        $results['checks']['phpmailer']['status'] = 'success';
        $results['checks']['phpmailer']['message'] = 'Found at: ' . $path;
        break;
    }
}

if (!$autoload_found) {
    $results['checks']['phpmailer']['status'] = 'error';
    $results['checks']['phpmailer']['message'] = 'Composer autoload not found. Run: composer install';
} else {
    // Check if PHPMailer class exists
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $results['checks']['phpmailer']['status'] = 'error';
        $results['checks']['phpmailer']['message'] = 'PHPMailer class not found';
    }
}

// Check 3: SMTP Credentials
$results['checks']['smtp'] = [
    'name' => 'SMTP Configuration',
    'status' => 'checking'
];

$smtp_user = getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com';
$smtp_pass = getenv('SMTP_PASSWORD') ?: 'iigy qtnu ojku ktsx';

$results['checks']['smtp']['username'] = $smtp_user;
$results['checks']['smtp']['password_set'] = !empty($smtp_pass);
$results['checks']['smtp']['password_length'] = strlen($smtp_pass);

if (empty($smtp_pass)) {
    $results['checks']['smtp']['status'] = 'error';
    $results['checks']['smtp']['message'] = 'SMTP password not set';
} else {
    $results['checks']['smtp']['status'] = 'warning';
    $results['checks']['smtp']['message'] = 'Credentials configured (test send required)';
}

// Check 4: Test OTP table structure
$results['checks']['otp_table'] = [
    'name' => 'OTP Table Structure',
    'status' => 'checking'
];

try {
    if (isset($conn) && $conn) {
        $table_query = "SELECT column_name, data_type 
                        FROM information_schema.columns 
                        WHERE table_name = 'otp_verifications'
                        ORDER BY ordinal_position";
        
        $table_result = pg_query($conn, $table_query);
        
        if ($table_result) {
            $columns = [];
            while ($row = pg_fetch_assoc($table_result)) {
                $columns[] = $row['column_name'] . ' (' . $row['data_type'] . ')';
            }
            
            $results['checks']['otp_table']['status'] = 'success';
            $results['checks']['otp_table']['columns'] = $columns;
            $results['checks']['otp_table']['message'] = count($columns) . ' columns found';
        } else {
            $results['checks']['otp_table']['status'] = 'error';
            $results['checks']['otp_table']['message'] = 'Table not found or query failed';
        }
    }
} catch (Exception $e) {
    $results['checks']['otp_table']['status'] = 'error';
    $results['checks']['otp_table']['message'] = $e->getMessage();
}

// Check 5: Test Email Send (optional - only if test_email parameter is provided)
if (isset($_GET['test_email']) && $autoload_found) {
    $results['checks']['email_send'] = [
        'name' => 'Email Send Test',
        'status' => 'testing'
    ];
    
    try {
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        
        $test_email = $_GET['test_email'];
        
        if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            $results['checks']['email_send']['status'] = 'error';
            $results['checks']['email_send']['message'] = 'Invalid test email format';
        } else {
            $mail = new PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPDebug  = 0;
            
            $mail->setFrom($smtp_user, 'PAWsig City Test');
            $mail->addAddress($test_email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Test Email - PAWsig City';
            $mail->Body = '<h1>Test Email</h1><p>If you received this, email sending is working!</p>';
            
            $mail->send();
            
            $results['checks']['email_send']['status'] = 'success';
            $results['checks']['email_send']['message'] = 'Test email sent to ' . $test_email;
        }
    } catch (Exception $e) {
        $results['checks']['email_send']['status'] = 'error';
        $results['checks']['email_send']['message'] = $e->getMessage();
        $results['checks']['email_send']['mailer_error'] = $mail->ErrorInfo ?? 'N/A';
    }
}

// Check 6: Recent OTP attempts
$results['checks']['recent_otps'] = [
    'name' => 'Recent OTP Records',
    'status' => 'checking'
];

try {
    if (isset($conn) && $conn) {
        $recent_query = "SELECT otp_id, email, type, purpose, is_verified, is_used, 
                                created_at, expires_at 
                         FROM otp_verifications 
                         ORDER BY created_at DESC 
                         LIMIT 5";
        
        $recent_result = pg_query($conn, $recent_query);
        
        if ($recent_result) {
            $recent_otps = [];
            while ($row = pg_fetch_assoc($recent_result)) {
                $recent_otps[] = $row;
            }
            
            $results['checks']['recent_otps']['status'] = 'success';
            $results['checks']['recent_otps']['records'] = $recent_otps;
            $results['checks']['recent_otps']['message'] = count($recent_otps) . ' recent records found';
        }
    }
} catch (Exception $e) {
    $results['checks']['recent_otps']['status'] = 'error';
    $results['checks']['recent_otps']['message'] = $e->getMessage();
}

// Overall status
$has_errors = false;
foreach ($results['checks'] as $check) {
    if (isset($check['status']) && $check['status'] === 'error') {
        $has_errors = true;
        break;
    }
}

$results['overall_status'] = $has_errors ? 'ERRORS FOUND' : 'ALL CHECKS PASSED';
$results['note'] = 'Add ?test_email=your@email.com to URL to test email sending';

echo json_encode($results, JSON_PRETTY_PRINT);

if (isset($conn)) {
    pg_close($conn);
}
?>