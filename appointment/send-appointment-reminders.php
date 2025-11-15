<?php
/**
 * Appointment Reminder Cron Job
 * Filename: send-appointment-reminders.php
 * Location: E:\xampp\htdocs\Purrfect-paws\appointment\
 * 
 * This script should be run every 5 minutes via cron job
 * It sends email reminders 1 hour before appointments
 */

// Prevent direct browser access (optional security)
if (php_sapi_name() !== 'cli') {
    // Allow browser access for testing, but log it
    error_log("Reminder script accessed via browser - should use cron");
}

require_once '../db.php';
require_once '../homepage/login/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_log("=== APPOINTMENT REMINDER CRON STARTED ===");
error_log("Current time: " . date('Y-m-d H:i:s'));

// First, add reminder_sent column if it doesn't exist
$check_column = pg_query($conn, "
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name='appointments' AND column_name='reminder_sent'
");

if (pg_num_rows($check_column) === 0) {
    error_log("Adding reminder_sent column to appointments table...");
    $add_column = pg_query($conn, "
        ALTER TABLE appointments 
        ADD COLUMN reminder_sent BOOLEAN DEFAULT FALSE,
        ADD COLUMN reminder_sent_at TIMESTAMP
    ");
    
    if ($add_column) {
        error_log("✓ reminder_sent column added successfully");
    } else {
        error_log("✗ Failed to add reminder_sent column: " . pg_last_error($conn));
    }
}

/**
 * Find appointments that need reminders
 * Criteria:
 * - Status is 'confirmed' (NOT no_show, cancelled, or completed)
 * - Appointment is between 55-65 minutes from now (1 hour window with 5-min buffer)
 * - Reminder has NOT been sent yet
 * - Appointment is in the FUTURE (prevents sending reminders for past appointments)
 */
$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.notes,
        a.recommended_package,
        u.first_name,
        u.last_name,
        u.email,
        p.name as pet_name,
        p.breed,
        p.species,
        pkg.name as package_name,
        g.groomer_name
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN packages pkg ON a.package_id = pkg.package_id
    LEFT JOIN groomer g ON a.groomer_id = g.groomer_id
    WHERE a.status = 'confirmed'
    AND a.reminder_sent = FALSE
    AND a.appointment_date > NOW()
    AND a.appointment_date BETWEEN 
        (NOW() + INTERVAL '55 minutes') 
        AND (NOW() + INTERVAL '65 minutes')
    ORDER BY a.appointment_date
";

$result = pg_query($conn, $query);

if (!$result) {
    error_log("✗ Query failed: " . pg_last_error($conn));
    exit(1);
}

$reminder_count = pg_num_rows($result);
error_log("Found {$reminder_count} appointments needing reminders");

if ($reminder_count === 0) {
    error_log("No reminders to send. Exiting.");
    exit(0);
}

// Process each appointment
$sent_count = 0;
$failed_count = 0;

while ($appointment = pg_fetch_assoc($result)) {
    $appointment_id = $appointment['appointment_id'];
    
    error_log("Processing appointment #{$appointment_id} for {$appointment['email']}");
    
    try {
        // Send reminder email
        $email_sent = sendReminderEmail($appointment);
        
        if ($email_sent) {
            // Mark reminder as sent in database
            $update_query = "
                UPDATE appointments 
                SET reminder_sent = TRUE, 
                    reminder_sent_at = CURRENT_TIMESTAMP 
                WHERE appointment_id = $1
            ";
            
            $update_result = pg_query_params($conn, $update_query, [$appointment_id]);
            
            if ($update_result) {
                error_log("✓ Reminder sent and marked for appointment #{$appointment_id}");
                $sent_count++;
            } else {
                error_log("✗ Email sent but failed to update database for #{$appointment_id}");
                $failed_count++;
            }
        } else {
            error_log("✗ Failed to send reminder for appointment #{$appointment_id}");
            $failed_count++;
        }
        
    } catch (Exception $e) {
        error_log("✗ Exception for appointment #{$appointment_id}: " . $e->getMessage());
        $failed_count++;
    }
}

error_log("=== REMINDER CRON COMPLETED ===");
error_log("Sent: {$sent_count}, Failed: {$failed_count}");

pg_close($conn);

/**
 * Send reminder email to customer
 */
function sendReminderEmail($appointment) {
    try {
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'johnbernardmitra25@gmail.com';
        $mail->Password = 'iigy qtnu ojku ktsx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0;
        
        // Email settings
        $mail->setFrom($mail->Username, 'PAWsig City');
        $mail->addAddress($appointment['email'], $appointment['first_name'] . ' ' . $appointment['last_name']);
        $mail->addReplyTo($mail->Username, 'PAWsig City');
        
        $mail->isHTML(true);
        $mail->Subject = '⏰ Reminder: Your Appointment is in 1 Hour - PAWsig City';
        $mail->CharSet = 'UTF-8';
        
        // Format appointment time
        $appointment_datetime = new DateTime($appointment['appointment_date']);
        $formatted_time = $appointment_datetime->format('g:i A');
        $formatted_date = $appointment_datetime->format('l, F j, Y');
        
        // Build special instructions
        $special_instructions = '';
        if (!empty($appointment['notes'])) {
            $special_instructions = "
                <tr>
                    <td style='padding: 15px 30px;'>
                        <div style='background: #fff3cd; padding: 12px 16px; border-radius: 8px; border-left: 3px solid #ff9800;'>
                            <p style='margin: 0; font-size: 13px; color: #856404;'><strong>📝 Your Special Instructions:</strong><br>{$appointment['notes']}</p>
                        </div>
                    </td>
                </tr>
            ";
        }
        
        // HTML Email Body
        $mail->Body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f7fa;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f5f7fa; padding: 20px 0;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); max-width: 100%;'>
                    
                    <!-- Header with Alert -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); padding: 30px; text-align: center;'>
                            <h1 style='color: white; margin: 0 0 10px 0; font-size: 28px;'>⏰ Appointment Reminder</h1>
                            <p style='color: white; margin: 0; font-size: 18px; font-weight: 600;'>Your appointment is in 1 HOUR!</p>
                        </td>
                    </tr>
                    
                    <!-- Urgent Banner -->
                    <tr>
                        <td style='padding: 20px 30px; background: #fff3cd; border-bottom: 3px solid #ff9800;'>
                            <p style='margin: 0; text-align: center; color: #856404; font-size: 16px; font-weight: 600;'>
                                ⚡ Please arrive 10 minutes early for check-in
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Greeting -->
                    <tr>
                        <td style='padding: 25px 30px 15px 30px;'>
                            <h2 style='color: #2c3e50; margin: 0 0 10px 0; font-size: 22px;'>Hi {$appointment['first_name']}! 👋</h2>
                            <p style='color: #666; font-size: 15px; line-height: 1.6; margin: 0;'>
                                This is a friendly reminder that <strong style='color: #2c3e50;'>{$appointment['pet_name']}</strong>'s grooming appointment is coming up soon!
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Appointment Details -->
                    <tr>
                        <td style='padding: 15px 30px;'>
                            <table width='100%' cellpadding='0' cellspacing='0' style='background: linear-gradient(135deg, #e8fff3 0%, #d4f5e5 100%); border-radius: 12px; border: 2px solid #A8E6CF;'>
                                <tr>
                                    <td style='padding: 20px;'>
                                        <h3 style='color: #2c3e50; margin: 0 0 15px 0; font-size: 18px; border-bottom: 2px solid #A8E6CF; padding-bottom: 8px;'>📅 Appointment Details</h3>
                                        
                                        <table width='100%' cellpadding='6' cellspacing='0'>
                                            <tr>
                                                <td width='35%' style='color: #666; font-size: 14px; font-weight: 600;'>⏰ Time:</td>
                                                <td style='color: #2c3e50; font-size: 16px; font-weight: 700;'>{$formatted_time}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>📆 Date:</td>
                                                <td style='color: #2c3e50; font-size: 14px; font-weight: 600;'>{$formatted_date}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>🐕 Pet:</td>
                                                <td style='color: #2c3e50; font-size: 14px; font-weight: 600;'>{$appointment['pet_name']} ({$appointment['breed']})</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>📦 Service:</td>
                                                <td style='color: #2c3e50; font-size: 14px; font-weight: 600;'>{$appointment['package_name']}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>👨‍⚕️ Groomer:</td>
                                                <td style='color: #2c3e50; font-size: 14px; font-weight: 600;'>{$appointment['groomer_name']}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Special Instructions -->
                    {$special_instructions}
                    
                    <!-- Checklist -->
                    <tr>
                        <td style='padding: 15px 30px;'>
                            <div style='background: #e3f2fd; padding: 18px; border-radius: 10px; border-left: 4px solid #2196f3;'>
                                <h4 style='color: #1565c0; margin: 0 0 12px 0; font-size: 15px;'>✅ Before You Leave Home:</h4>
                                <ul style='color: #1565c0; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 18px;'>
                                    <li>Bring vaccination records (if available)</li>
                                    <li>Ensure your pet is well-fed</li>
                                    <li>Bring a leash or carrier</li>
                                    <li>Arrive 10 minutes early</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Location -->
                    <tr>
                        <td style='padding: 15px 30px;'>
                            <div style='background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center;'>
                                <h4 style='color: #2d5f4a; margin: 0 0 10px 0; font-size: 15px;'>📍 We're Located At:</h4>
                                <p style='color: #666; font-size: 13px; line-height: 1.6; margin: 0;'>
                                    <strong>PAWsig City</strong><br>
                                    2F Hampton Gardens Arcade<br>
                                    C. Raymundo, Maybunga<br>
                                    Pasig City, Metro Manila
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Need to Cancel/Reschedule -->
                    <tr>
                        <td style='padding: 15px 30px 25px 30px;'>
                            <div style='background: #fff3cd; padding: 15px; border-radius: 10px; text-align: center;'>
                                <p style='color: #856404; font-size: 13px; margin: 0 0 10px 0;'>
                                    <strong>Need to reschedule?</strong> Please call us ASAP:
                                </p>
                                <p style='margin: 0;'>
                                    <a href='tel:09544760085' style='display: inline-block; background: #ff9800; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;'>
                                        📞 Call 0954 476 0085
                                    </a>
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style='background: #2d5f4a; padding: 20px; text-align: center;'>
                            <p style='color: #A8E6CF; margin: 0; font-size: 13px;'>See you soon at PAWsig City! 🐾</p>
                            <p style='color: #7FD4B3; margin: 8px 0 0 0; font-size: 11px;'>© 2025 PAWsig City. All rights reserved.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ";
        
        // Plain text alternative
        $mail->AltBody = "
⏰ APPOINTMENT REMINDER - 1 HOUR NOTICE

Hi {$appointment['first_name']}!

Your grooming appointment for {$appointment['pet_name']} is in 1 HOUR!

APPOINTMENT DETAILS:
Time: {$formatted_time}
Date: {$formatted_date}
Pet: {$appointment['pet_name']} ({$appointment['breed']})
Service: {$appointment['package_name']}
Groomer: {$appointment['groomer_name']}

BEFORE YOU LEAVE:
✓ Bring vaccination records
✓ Ensure your pet is well-fed
✓ Bring leash or carrier
✓ Arrive 10 minutes early

LOCATION:
PAWsig City
2F Hampton Gardens Arcade
C. Raymundo, Maybunga
Pasig City, Metro Manila

Need to reschedule? Call: 0954 476 0085

See you soon!
PAWsig City Team
        ";
        
        // Send email
        $mail->send();
        
        error_log("✓ Reminder email sent to {$appointment['email']}");
        return true;
        
    } catch (Exception $e) {
        error_log("✗ Failed to send reminder: " . $e->getMessage());
        return false;
    }
}
?>