<?php
session_start();
include '../db.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check for autoload in multiple possible locations
$autoload_paths = [
    '../vendor/autoload.php',
    '../../vendor/autoload.php',
    './vendor/autoload.php',
    '../../../vendor/autoload.php'
];

$autoload_found = false;
foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoload_found = true;
        break;
    }
}

if (!$autoload_found) {
    $_SESSION['error_message'] = "Email system not configured. Appointment cancelled but notification not sent.";
    // Continue anyway - cancellation is more important than email
}

// Validate appointment ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No appointment ID provided.";
    header("Location: receptionist_dashboard.php");
    exit();
}

// Sanitize input - use intval for safety
$appointment_id = intval($_GET['id']);

if ($appointment_id <= 0) {
    $_SESSION['error_message'] = "Invalid appointment ID.";
    header("Location: receptionist_dashboard.php");
    exit();
}

// Start transaction for data consistency
pg_query($conn, "BEGIN");

try {
    // Get appointment details and user email BEFORE canceling
    $query = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.status,
            a.user_id,
            u.email AS user_email,
            u.name AS user_name,
            p.name AS package_name,
            pet.name AS pet_name,
            pet.breed AS pet_breed
        FROM appointments a
        JOIN packages p ON a.package_id = p.package_id
        JOIN pets pet ON a.pet_id = pet.pet_id
        JOIN users u ON a.user_id = u.user_id
        WHERE a.appointment_id = $1
    ";
    
    // Use parameterized query to prevent SQL injection
    $result = pg_query_params($conn, $query, array($appointment_id));
    
    if (!$result) {
        throw new Exception("Database error: " . pg_last_error($conn));
    }
    
    if (pg_num_rows($result) == 0) {
        throw new Exception("Appointment not found.");
    }
    
    $appointment = pg_fetch_assoc($result);
    
    // Check if already cancelled
    if ($appointment['status'] === 'cancelled') {
        throw new Exception("This appointment is already cancelled.");
    }
    
    // Update appointment status to cancelled - USE PARAMETERIZED QUERY
    $update_query = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = $1";
    $update_result = pg_query_params($conn, $update_query, array($appointment_id));
    
    if (!$update_result) {
        throw new Exception("Failed to cancel appointment: " . pg_last_error($conn));
    }
    
    // Check if any rows were actually updated
    if (pg_affected_rows($update_result) == 0) {
        throw new Exception("No appointment was updated. It may have been already cancelled.");
    }
    
    // Send email notification (only if PHPMailer is available)
    $email_sent = false;
    if ($autoload_found && !empty($appointment['user_email'])) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings - Same as your working OTP configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com';
            $mail->Password   = getenv('SMTP_PASSWORD') ?: 'iigy qtnu ojku ktsx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPDebug  = 0;
            $mail->CharSet    = 'UTF-8';
            
            // Recipients
            $mail->setFrom($mail->Username, 'PAWsig City');
            $mail->addAddress($appointment['user_email'], $appointment['user_name']);
            $mail->addReplyTo($mail->Username, 'PAWsig City');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Cancellation Notice - PAWsig City';
            
            $appointment_date = date('F d, Y g:i A', strtotime($appointment['appointment_date']));
            
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #FF6B6B 0%, #FF4949 100%); padding: 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0;'>PAWsig City</h1>
                </div>
                <div style='padding: 30px; background: #ffffff;'>
                    <h2 style='color: #FF6B6B; margin-top: 0;'>Appointment Cancelled</h2>
                    <p style='font-size: 16px; color: #666;'>Dear {$appointment['user_name']},</p>
                    <p style='font-size: 16px; color: #666;'>We are here to inform you that your appointment has been cancelled.</p>
                    
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
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>Scheduled Date:</td>
                                <td style='padding: 8px 0; color: #333;'>{$appointment_date}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>Status:</td>
                                <td style='padding: 8px 0;'><span style='color: #FF6B6B; font-weight: bold;'>CANCELLED</span></td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style='font-size: 16px; color: #666;'>If you have any questions or would like to reschedule, please contact us.</p>
    
                    
                    <p style='font-size: 16px; color: #333; margin-top: 30px;'>Best regards,<br><strong>PAWsig City Team</strong></p>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                    <p style='margin: 0;'>This is an automated message. Please do not reply to this email.</p>
                    <p style='margin: 10px 0 0 0;'>© 2025 PAWsig City. All rights reserved.</p>
                </div>
            </div>
            ";
            
            $mail->AltBody = "Dear {$appointment['user_name']},\n\n" .
                            "Your appointment has been cancelled.\n\n" .
                            "Appointment Details:\n" .
                            "ID: #{$appointment_id}\n" .
                            "Pet: {$appointment['pet_name']} ({$appointment['pet_breed']})\n" .
                            "Package: {$appointment['package_name']}\n" .
                            "Date: {$appointment_date}\n" .
                            "Status: CANCELLED\n\n" .
                            "If you have any questions, please contact us.\n\n" .
                            "Best regards,\nPAWsig City Team";
            
            $mail->send();
            $email_sent = true;
            error_log("✓ Cancellation email sent to {$appointment['user_email']} for appointment #{$appointment_id}");
            
        } catch (Exception $e) {
            // Log email error but don't stop the cancellation
            error_log("✗ Email notification failed for appointment #{$appointment_id}: {$mail->ErrorInfo}");
            error_log("✗ Exception: " . $e->getMessage());
        }
    }
    
    // Commit transaction - cancellation is successful
    pg_query($conn, "COMMIT");
    
    // Set appropriate success message
    if ($email_sent) {
        $_SESSION['success_message'] = "Appointment #{$appointment_id} cancelled successfully. Email notification sent to {$appointment['user_email']}.";
    } else {
        $_SESSION['success_message'] = "Appointment #{$appointment_id} cancelled successfully. Note: Email notification could not be sent.";
    }
    
    // Redirect to dashboard
    header("Location: receptionist_dashboard.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on any error
    pg_query($conn, "ROLLBACK");
    error_log("Cancellation Error for appointment #{$appointment_id}: " . $e->getMessage());
    
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: receptionist_dashboard.php");
    exit();
}

// Close database connection
if (isset($conn)) {
    pg_close($conn);
}
?>