<?php
session_start();
include '../db.php';

// Function to find and load PHPMailer
function loadPHPMailer() {
    // Try to find composer autoload
    $possible_paths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../../vendor/autoload.php',
        dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php',
        $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php',
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                error_log("✓ PHPMailer loaded from: " . realpath($path));
                return true;
            }
        }
    }
    
    // Try direct include as fallback
    $direct_paths = [
        __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
        __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
        __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php',
    ];
    
    foreach ($direct_paths as $path) {
        if (file_exists($path)) {
            $base_path = dirname($path);
            require_once $path;
            require_once $base_path . '/Exception.php';
            require_once $base_path . '/SMTP.php';
            error_log("✓ PHPMailer loaded directly from: " . realpath($path));
            return true;
        }
    }
    
    error_log("✗ PHPMailer not found. Current dir: " . __DIR__);
    return false;
}

// Load PHPMailer
$phpmailer_loaded = loadPHPMailer();

// Import classes if loaded via composer
if ($phpmailer_loaded) {
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
}

// Validate appointment ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No appointment ID provided.";
    header("Location: receptionist_home.php");
    exit();
}

$appointment_id = intval($_GET['id']);

if ($appointment_id <= 0) {
    $_SESSION['error_message'] = "Invalid appointment ID.";
    header("Location: receptionist_home.php");
    exit();
}

// Start transaction
pg_query($conn, "BEGIN");

try {
    // Get appointment details
    $query = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.status,
            a.user_id,
            u.email AS user_email,
            CONCAT(u.first_name, ' ', u.last_name) AS user_name,
            p.name AS package_name,
            pet.name AS pet_name,
            pet.breed AS pet_breed
        FROM appointments a
        LEFT JOIN packages p ON a.package_id::text = p.package_id
        LEFT JOIN pets pet ON a.pet_id = pet.pet_id
        LEFT JOIN users u ON a.user_id::text = u.user_id
        WHERE a.appointment_id = $1
    ";
    
    $result = pg_query_params($conn, $query, array($appointment_id));
    
    if (!$result) {
        throw new Exception("Database error: " . pg_last_error($conn));
    }
    
    if (pg_num_rows($result) == 0) {
        throw new Exception("Appointment not found.");
    }
    
    $appointment = pg_fetch_assoc($result);
    
    if ($appointment['status'] === 'cancelled') {
        throw new Exception("This appointment is already cancelled.");
    }
    
    // Update appointment status
    $update_query = "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE appointment_id = $1";
    $update_result = pg_query_params($conn, $update_query, array($appointment_id));
    
    if (!$update_result || pg_affected_rows($update_result) == 0) {
        throw new Exception("Failed to cancel appointment.");
    }
    
    // Commit the cancellation
    pg_query($conn, "COMMIT");
    
    // Try to send email notification
    $email_sent = false;
    $email_error = '';
    
    if ($phpmailer_loaded && !empty($appointment['user_email'])) {
        try {
            $mail = new PHPMailer(true);
            
            // Enable verbose debug output for troubleshooting
            $mail->SMTPDebug  = 0; // Set to 2 for debugging
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug level $level: $str");
            };
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'johnbernardmitra25@gmail.com';
            $mail->Password   = 'iigy qtnu ojku ktsx'; // App password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            
            // Timeout settings
            $mail->Timeout    = 30;
            $mail->SMTPKeepAlive = false;
            
            // Recipients
            $mail->setFrom('johnbernardmitra25@gmail.com', 'PAWsig City');
            $mail->addAddress($appointment['user_email'], $appointment['user_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Cancellation - PAWsig City';
            
            $appointment_date = date('F d, Y g:i A', strtotime($appointment['appointment_date']));
            
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #FF6B6B 0%, #FF4949 100%); padding: 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0;'>PAWsig City</h1>
                </div>
                <div style='padding: 30px; background: #ffffff;'>
                    <h2 style='color: #FF6B6B; margin-top: 0;'>Appointment Cancelled</h2>
                    <p style='font-size: 16px; color: #666;'>Dear {$appointment['user_name']},</p>
                    <p style='font-size: 16px; color: #666;'>Your appointment has been cancelled.</p>
                    
                    <div style='background: #fff5f5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FF6B6B;'>
                        <h3 style='color: #FF6B6B; margin-top: 0;'>Appointment Details</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>Appointment ID:</td>
                                <td style='padding: 8px 0; color: #333;'>#{$appointment_id}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>Pet Name:</td>
                                <td style='padding: 8px 0; color: #333;'>{$appointment['pet_name']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>Breed:</td>
                                <td style='padding: 8px 0; color: #333;'>{$appointment['pet_breed']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>Package:</td>
                                <td style='padding: 8px 0; color: #333;'>{$appointment['package_name']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>Date:</td>
                                <td style='padding: 8px 0; color: #333;'>{$appointment_date}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style='font-size: 16px; color: #666;'>If you have questions, please contact us.</p>
                    <p style='font-size: 16px; color: #333; margin-top: 30px;'>Best regards,<br><strong>PAWsig City Team</strong></p>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                    <p style='margin: 0;'>© 2025 PAWsig City. All rights reserved.</p>
                </div>
            </div>
            ";
            
            $mail->AltBody = "Dear {$appointment['user_name']},\n\nYour appointment has been cancelled.\n\nAppointment ID: #{$appointment_id}\nPet: {$appointment['pet_name']} ({$appointment['pet_breed']})\nPackage: {$appointment['package_name']}\nDate: {$appointment_date}\n\nIf you have questions, please contact us.\n\nBest regards,\nPAWsig City Team";
            
            if ($mail->send()) {
                $email_sent = true;
                error_log("✓ Email sent successfully to {$appointment['user_email']}");
            } else {
                $email_error = $mail->ErrorInfo;
                error_log("✗ Email send failed: " . $mail->ErrorInfo);
            }
            
        } catch (Exception $e) {
            $email_error = $e->getMessage();
            error_log("✗ Email exception: " . $e->getMessage());
            if (isset($mail)) {
                error_log("✗ PHPMailer Error: " . $mail->ErrorInfo);
            }
        }
    } else {
        if (!$phpmailer_loaded) {
            $email_error = "PHPMailer not loaded";
            error_log("✗ PHPMailer not loaded");
        }
        if (empty($appointment['user_email'])) {
            $email_error = "No user email found";
            error_log("✗ No user email for appointment #{$appointment_id}");
        }
    }
    
    // Set success message
    if ($email_sent) {
        $_SESSION['success_message'] = "Appointment #{$appointment_id} cancelled successfully. Email notification sent to {$appointment['user_email']}.";
    } else {
        $_SESSION['success_message'] = "Appointment #{$appointment_id} cancelled successfully.";
        if (!empty($email_error)) {
            $_SESSION['success_message'] .= " (Email notification failed: $email_error)";
        }
    }
    
    header("Location: receptionist_home.php");
    exit();
    
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    error_log("Cancellation error: " . $e->getMessage());
    
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: receptionist_home.php");
    exit();
}

if (isset($conn)) {
    pg_close($conn);
}
?>