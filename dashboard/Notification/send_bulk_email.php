<?php
/**
 * Send Bulk Email - Robust Version
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

error_log("=== SEND BULK EMAIL REQUEST ===");
error_log("POST data: " . print_r($_POST, true));

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check database configuration
if (!file_exists('../../db.php')) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database configuration file not found'
    ]);
    exit;
}

require_once '../../db.php';

// Check for PHPMailer
$autoload_paths = ['/homepage/login/vendor/autoload.php', '../Notification/vendor/autoload.php', '../../vendor/autoload.php'];
$autoload_found = false;

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoload_found = true;
        error_log("PHPMailer autoload found at: $path");
        break;
    }
}

if (!$autoload_found) {
    echo json_encode([
        'success' => false, 
        'message' => 'PHPMailer not installed. Please install via Composer: composer require phpmailer/phpmailer'
    ]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$template_type = trim($_POST['template_type'] ?? '');
$send_to = trim($_POST['send_to'] ?? '');
$custom_subject = trim($_POST['custom_subject'] ?? '');
$custom_content = trim($_POST['custom_content'] ?? '');

error_log("Template Type: $template_type");
error_log("Send To: $send_to");

// Validate template type
if (empty($template_type)) {
    echo json_encode(['success' => false, 'message' => 'Template type is required']);
    exit;
}

// Load email templates
if (!file_exists('email_templates.php')) {
    echo json_encode([
        'success' => false, 
        'message' => 'Email templates file not found'
    ]);
    exit;
}

$templates = include('email_templates.php');

// Get email subject and content
$email_subject = '';
$email_content = '';

if ($template_type === 'custom' || $template_type === 'announcement') {
    // For custom and announcement templates, use custom fields
    if (empty($custom_subject) || empty($custom_content)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Subject and content are required for custom/announcement emails'
        ]);
        exit;
    }
    $email_subject = $custom_subject;
    $email_content = $custom_content;
    error_log("Using custom content - Subject: $email_subject");
} else {
    // For predefined templates
    if (!isset($templates[$template_type])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid template type'
        ]);
        exit;
    }
    
    $template = $templates[$template_type];
    $email_subject = $template['subject'];
    $email_content = $template['content'];
    error_log("Using template: $template_type");
}

try {
    // Get recipients based on selection
    $recipients = [];
    
    if ($send_to === 'all') {
        error_log("Fetching all active users...");
        $query = "SELECT user_id, email, first_name, last_name 
                  FROM users 
                  WHERE deleted_at IS NULL 
                  AND email IS NOT NULL 
                  AND email != ''
                  ORDER BY first_name ASC";
        $result = pg_query($conn, $query);
        
        if (!$result) {
            error_log("Query failed: " . pg_last_error($conn));
            throw new Exception('Failed to fetch recipients: ' . pg_last_error($conn));
        }
        
        while ($user = pg_fetch_assoc($result)) {
            $recipients[] = $user;
        }
        
    } else if ($send_to === 'specific') {
        // Get specific users
        if (empty($_POST['recipient_ids']) || !is_array($_POST['recipient_ids'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'No recipients selected'
            ]);
            exit;
        }
        
        $recipient_ids = array_map('intval', $_POST['recipient_ids']);
        error_log("Fetching specific users: " . implode(', ', $recipient_ids));
        
        $placeholders = [];
        for ($i = 1; $i <= count($recipient_ids); $i++) {
            $placeholders[] = '$' . $i;
        }
        
        $query = "SELECT user_id, email, first_name, last_name 
                  FROM users 
                  WHERE user_id IN (" . implode(',', $placeholders) . ")
                  AND deleted_at IS NULL 
                  AND email IS NOT NULL 
                  AND email != ''
                  ORDER BY first_name ASC";
        
        $result = pg_query_params($conn, $query, $recipient_ids);
        
        if (!$result) {
            error_log("Query failed: " . pg_last_error($conn));
            throw new Exception('Failed to fetch recipients: ' . pg_last_error($conn));
        }
        
        while ($user = pg_fetch_assoc($result)) {
            $recipients[] = $user;
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid recipient selection'
        ]);
        exit;
    }
    
    $recipient_count = count($recipients);
    error_log("Total recipients to send: $recipient_count");
    
    if ($recipient_count === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'No valid recipients found'
        ]);
        exit;
    }
    
    // Prepare email template wrapper
    $email_html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%); padding: 20px; text-align: center;'>
                <h1 style='color: #2d5f4a; margin: 0;'>PAWsig City</h1>
            </div>
            <div style='padding: 30px; background: #ffffff;'>
                {$email_content}
            </div>
            <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                <p>© 2025 PAWsig City. All rights reserved.</p>
                <p>You received this email because you are a registered user of PAWsig City.</p>
            </div>
        </div>
    ";
    
    // Send emails
    $success_count = 0;
    $failed_count = 0;
    $failed_emails = [];
    
    foreach ($recipients as $recipient) {
        $recipient_email = $recipient['email'];
        $recipient_name = trim($recipient['first_name'] . ' ' . $recipient['last_name']);
        
        error_log("Sending to: $recipient_email ($recipient_name)");
        
        $mail = new PHPMailer(true);
        
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USERNAME') ?: 'johnbernardmitra25@gmail.com';
            $mail->Password   = getenv('SMTP_PASSWORD') ?: 'iigy qtnu ojku ktsx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPDebug  = 0; // Set to 2 for debugging
            $mail->Timeout    = 30; // 30 seconds timeout
            
            // Verify credentials are set
            if (empty($mail->Username) || empty($mail->Password)) {
                throw new Exception('SMTP credentials not configured');
            }
            
            // Email settings
            $mail->setFrom($mail->Username, 'PAWsig City');
            $mail->addAddress($recipient_email, $recipient_name);
            $mail->addReplyTo($mail->Username, 'PAWsig City');
            
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $email_subject;
            $mail->Body    = $email_html;
            $mail->AltBody = strip_tags($email_content);
            
            // Send email
            $mail->send();
            $success_count++;
            error_log("✓ Email sent successfully to: $recipient_email");
            
            // Clear recipients for next iteration
            $mail->clearAddresses();
            $mail->clearAllRecipients();
            
            // Small delay to avoid rate limiting (Gmail allows ~100-500 per day for free accounts)
            usleep(100000); // 0.1 second delay
            
        } catch (Exception $e) {
            $failed_count++;
            $failed_emails[] = $recipient_email;
            error_log("✗ Failed to send to $recipient_email: " . $mail->ErrorInfo);
            error_log("✗ Exception: " . $e->getMessage());
        }
    }
    
    error_log("=== EMAIL SENDING COMPLETE ===");
    error_log("Success: $success_count, Failed: $failed_count");
    
    if ($failed_count > 0) {
        error_log("Failed emails: " . implode(', ', $failed_emails));
    }
    
    // Log email sending activity to database (optional)
    try {
        $log_query = "INSERT INTO email_logs 
                      (admin_id, template_type, recipients_count, success_count, failed_count, sent_at) 
                      VALUES ($1, $2, $3, $4, $5, CURRENT_TIMESTAMP)";
        pg_query_params($conn, $log_query, [
            $_SESSION['user_id'],
            $template_type,
            $recipient_count,
            $success_count,
            $failed_count
        ]);
    } catch (Exception $e) {
        // Log but don't fail the request
        error_log("Failed to log email activity: " . $e->getMessage());
    }
    
    // Response
    if ($success_count > 0) {
        $message = $failed_count === 0 
            ? "Successfully sent emails to all $success_count recipient(s)"
            : "Sent to $success_count recipient(s), failed to send to $failed_count recipient(s)";
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'details' => [
                'success' => $success_count,
                'failed' => $failed_count,
                'total' => $recipient_count
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send any emails. Please check your SMTP configuration.',
            'details' => [
                'success' => 0,
                'failed' => $failed_count,
                'total' => $recipient_count
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Bulk Email Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request: ' . $e->getMessage()
    ]);
}

// Close database connection
if (isset($conn)) {
    pg_close($conn);
}
?>