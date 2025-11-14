<?php
session_start();
include '../db.php';

// Load PHPMailer
$phpmailer_loaded = false;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $phpmailer_loaded = true;
        error_log("✓ PHPMailer loaded successfully");
    }
} else {
    error_log("✗ Autoload not found at: " . __DIR__ . '/vendor/autoload.php');
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
    // FIXED: Proper join using LPAD to match user_id formats
    // Appointments has user_id as integer (3, 5)
    // Users has user_id as string ('00003', '00005')
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
        LEFT JOIN users u ON LPAD(a.user_id::text, 5, '0') = u.user_id
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
    
    // Debug logging
    error_log("Appointment data: user_id={$appointment['user_id']}, email={$appointment['user_email']}, name={$appointment['user_name']}");
    
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
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug  = 0;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug level $level: $str");
            };
            
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'johnbernardmitra25@gmail.com';
            $mail->Password   = 'iigy qtnu ojku ktsx';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 30;
            
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
            
            // Send the email
            $mail->send();
            $email_sent = true;
            error_log("✓ Cancellation email sent successfully to {$appointment['user_email']}");
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $email_error = $e->getMessage();
            error_log("✗ PHPMailer Exception: " . $e->getMessage());
            if (isset($mail)) {
                error_log("✗ PHPMailer ErrorInfo: " . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            $email_error = $e->getMessage();
            error_log("✗ General Exception: " . $e->getMessage());
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