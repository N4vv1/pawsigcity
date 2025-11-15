<?php
/**
 * Send Booking Confirmation Email
 * Filename: send-booking-confirmation.php
 * Place this file in: E:\xampp\htdocs\Purrfect-paws\appointment\
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendBookingConfirmation($conn, $appointment_id) {
    try {
        // Fetch appointment details with all related information
        $query = "
            SELECT 
                a.appointment_id,
                a.appointment_date,
                a.notes,
                a.recommended_package,
                u.user_id,
                u.first_name,
                u.last_name,
                u.email,
                p.pet_id,
                p.name as pet_name,
                p.breed,
                p.species,
                p.age,
                p.size,
                p.weight,
                pkg.name as package_name,
                pp.price as package_price,
                g.groomer_name,
                g.groomer_id
            FROM appointments a
            JOIN users u ON a.user_id = u.user_id
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN packages pkg ON a.package_id = pkg.package_id
            LEFT JOIN package_prices pp ON a.package_id = pp.package_id 
                AND p.species = pp.species 
                AND p.size = pp.size 
                AND p.weight BETWEEN pp.min_weight AND pp.max_weight
            LEFT JOIN groomer g ON a.groomer_id = g.groomer_id
            WHERE a.appointment_id = $1
        ";
        
        $result = pg_query_params($conn, $query, [$appointment_id]);
        
        if (!$result || pg_num_rows($result) === 0) {
            error_log("Appointment not found: $appointment_id");
            return ['success' => false, 'message' => 'Appointment not found'];
        }
        
        $appointment = pg_fetch_assoc($result);
        
        // Format appointment date and time
        $appointment_datetime = new DateTime($appointment['appointment_date']);
        $formatted_date = $appointment_datetime->format('l, F j, Y');
        $formatted_time = $appointment_datetime->format('g:i A');
        
        // Calculate estimated end time (assume 1.5 hours for grooming)
        $end_datetime = clone $appointment_datetime;
        $end_datetime->modify('+1.5 hours');
        $formatted_end_time = $end_datetime->format('g:i A');
        
        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'johnbernardmitra25@gmail.com';
        $mail->Password   = 'iigy qtnu ojku ktsx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0;
        
        // Email settings
        $mail->setFrom($mail->Username, 'PAWsig City');
        $mail->addAddress($appointment['email'], $appointment['first_name'] . ' ' . $appointment['last_name']);
        $mail->addReplyTo($mail->Username, 'PAWsig City');
        
        $mail->isHTML(true);
        $mail->Subject = '🐾 Booking Confirmed - PAWsig City Grooming Appointment';
        $mail->CharSet = 'UTF-8';
        
        // Build special instructions section
        $special_instructions = '';
        if (!empty($appointment['notes'])) {
            $special_instructions = "
                <tr>
                    <td style='padding: 20px 30px 0 30px;'>
                        <div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;'>
                            <p style='margin: 0; font-weight: 600; color: #856404; font-size: 14px;'>📝 Special Instructions:</p>
                            <p style='margin: 8px 0 0 0; color: #856404; font-size: 14px;'>" . htmlspecialchars($appointment['notes']) . "</p>
                        </div>
                    </td>
                </tr>
            ";
        }
        
        // Build recommended package badge
        $recommended_badge = '';
        if (!empty($appointment['recommended_package'])) {
            $recommended_badge = "<br><div style='display: inline-block; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #856404; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-top: 8px;'>⭐ Recommended for {$appointment['breed']}</div>";
        }
        
        // Format price
        $price_display = !empty($appointment['package_price']) 
            ? "₱" . number_format($appointment['package_price'], 2) 
            : "To be confirmed";
        
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
                    
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%); padding: 40px 30px; text-align: center;'>
                            <h1 style='color: #2d5f4a; margin: 0 0 10px 0; font-size: 32px;'>🐾 PAWsig City</h1>
                            <p style='color: #2d5f4a; margin: 0; font-size: 16px; font-weight: 500;'>Premium Pet Grooming Services</p>
                        </td>
                    </tr>
                    
                    <!-- Success Badge -->
                    <tr>
                        <td style='padding: 30px; text-align: center;'>
                            <div style='display: inline-block; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 15px 30px; border-radius: 50px; border: 3px solid #28a745;'>
                                <p style='margin: 0; color: #155724; font-size: 18px; font-weight: 700;'>✓ Booking Confirmed!</p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Greeting -->
                    <tr>
                        <td style='padding: 0 30px;'>
                            <h2 style='color: #2d5f4a; margin: 0 0 15px 0; font-size: 24px;'>Hello {$appointment['first_name']}! 👋</h2>
                            <p style='color: #666; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;'>
                                Great news! Your grooming appointment for <strong style='color: #2d5f4a;'>{$appointment['pet_name']}</strong> has been successfully confirmed.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Appointment Details Card -->
                    <tr>
                        <td style='padding: 0 30px;'>
                            <table width='100%' cellpadding='0' cellspacing='0' style='background: linear-gradient(135deg, #e8fff3 0%, #d4f5e5 100%); border-radius: 12px; border: 2px solid #A8E6CF; overflow: hidden;'>
                                <tr>
                                    <td style='padding: 20px;'>
                                        <h3 style='color: #2d5f4a; margin: 0 0 20px 0; font-size: 20px; border-bottom: 2px solid #A8E6CF; padding-bottom: 10px;'>📅 Appointment Details</h3>
                                        
                                        <!-- Date & Time -->
                                        <table width='100%' cellpadding='8' cellspacing='0'>
                                            <tr>
                                                <td width='40%' style='color: #666; font-size: 14px; font-weight: 600;'>📆 Date:</td>
                                                <td style='color: #2d5f4a; font-size: 15px; font-weight: 700;'>{$formatted_date}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>⏰ Time:</td>
                                                <td style='color: #2d5f4a; font-size: 15px; font-weight: 700;'>{$formatted_time} - {$formatted_end_time}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>🐕 Pet:</td>
                                                <td style='color: #2d5f4a; font-size: 15px; font-weight: 700;'>{$appointment['pet_name']} ({$appointment['breed']})</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>📦 Package:</td>
                                                <td style='color: #2d5f4a; font-size: 15px; font-weight: 700;'>
                                                    {$appointment['package_name']}{$recommended_badge}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>💰 Price:</td>
                                                <td style='color: #2d5f4a; font-size: 15px; font-weight: 700;'>{$price_display}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #666; font-size: 14px; font-weight: 600;'>👨‍⚕️ Groomer:</td>
                                                <td style='color: #2d5f4a; font-size: 15px; font-weight: 700;'>{$appointment['groomer_name']}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Special Instructions -->
                    {$special_instructions}
                    
                    <!-- Important Information -->
                    <tr>
                        <td style='padding: 20px 30px;'>
                            <div style='background: #fff3cd; padding: 20px; border-radius: 12px; border-left: 5px solid #ff9800;'>
                                <h4 style='color: #856404; margin: 0 0 15px 0; font-size: 16px;'>⚠️ Important Reminders</h4>
                                <ul style='color: #856404; font-size: 14px; line-height: 1.8; margin: 0; padding-left: 20px;'>
                                    <li>Please arrive <strong>10 minutes early</strong> for check-in</li>
                                    <li>Bring your pet's vaccination records if available</li>
                                    <li>Ensure your pet is well-fed before the appointment</li>
                                    <li>Contact us immediately if you need to reschedule</li>
                                    <li>Payment is due upon completion of service</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Location Info -->
                    <tr>
                        <td style='padding: 20px 30px;'>
                            <div style='background: #e3f2fd; padding: 20px; border-radius: 12px; border-left: 5px solid #2196f3;'>
                                <h4 style='color: #1565c0; margin: 0 0 15px 0; font-size: 16px;'>📍 Visit Us</h4>
                                <p style='color: #1565c0; font-size: 14px; line-height: 1.6; margin: 0;'>
                                    <strong>PAWsig City</strong><br>
                                    2F Hampton Gardens Arcade<br>
                                    C. Raymundo, Maybunga<br>
                                    Pasig City, Metro Manila, Philippines
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Contact Section -->
                    <tr>
                        <td style='padding: 20px 30px 30px 30px;'>
                            <div style='text-align: center; background: #f8f9fa; padding: 20px; border-radius: 12px;'>
                                <h4 style='color: #2d5f4a; margin: 0 0 15px 0; font-size: 16px;'>Need to make changes?</h4>
                                <p style='color: #666; font-size: 14px; margin: 0 0 15px 0;'>Contact us for rescheduling or cancellations</p>
                                <p style='color: #2d5f4a; font-size: 14px; font-weight: 600; margin: 10px 0;'>
                                    📞 <a href='tel:09544760085' style='color: #2d5f4a; text-decoration: none;'>0954 476 0085</a><br>
                                    📧 <a href='mailto:johnbernardmitra25@gmail.com' style='color: #2d5f4a; text-decoration: none;'>johnbernardmitra25@gmail.com</a><br>
                                    💬 <a href='https://facebook.com/pawsigcity' style='color: #2d5f4a; text-decoration: none;'>Message us on Facebook</a>
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style='background: #2d5f4a; padding: 30px; text-align: center;'>
                            <p style='color: #A8E6CF; margin: 0 0 10px 0; font-size: 14px;'>Thank you for choosing PAWsig City!</p>
                            <p style='color: #7FD4B3; margin: 0; font-size: 12px;'>© 2025 PAWsig City. All rights reserved.</p>
                            <p style='color: #7FD4B3; margin: 10px 0 0 0; font-size: 11px;'>
                                This is an automated confirmation email. Please do not reply directly to this message.
                            </p>
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
PAWsig City - Booking Confirmation

Hello {$appointment['first_name']}!

Your grooming appointment has been confirmed!

APPOINTMENT DETAILS:
Booking ID: {$appointment['appointment_id']}
Date: {$formatted_date}
Time: {$formatted_time} - {$formatted_end_time}
Pet: {$appointment['pet_name']} ({$appointment['breed']})
Package: {$appointment['package_name']}
Price: {$price_display}
Groomer: {$appointment['groomer_name']}

" . (!empty($appointment['notes']) ? "SPECIAL INSTRUCTIONS:\n{$appointment['notes']}\n\n" : "") . "

IMPORTANT REMINDERS:
- Arrive 10 minutes early for check-in
- Bring vaccination records if available
- Ensure your pet is well-fed
- Contact us if you need to reschedule
- Payment is due upon completion

LOCATION:
PAWsig City
2F Hampton Gardens Arcade
C. Raymundo, Maybunga
Pasig City, Metro Manila, Philippines

CONTACT US:
Phone: 0954 476 0085
Email: johnbernardmitra25@gmail.com
Facebook: facebook.com/pawsigcity

Thank you for choosing PAWsig City!

---
This is an automated confirmation. Please do not reply to this email.
© 2025 PAWsig City. All rights reserved.
        ";
        
        // Send the email
        $mail->send();
        
        error_log("✓ Booking confirmation email sent to {$appointment['email']} for appointment #{$appointment_id}");
        
        return [
            'success' => true, 
            'message' => 'Confirmation email sent successfully',
            'recipient' => $appointment['email']
        ];
        
    } catch (Exception $e) {
        error_log("✗ Booking confirmation email failed for appointment #{$appointment_id}");
        error_log("✗ Error: " . $e->getMessage());
        
        return [
            'success' => false, 
            'message' => 'Failed to send confirmation email',
            'error' => $e->getMessage()
        ];
    }
}
?>