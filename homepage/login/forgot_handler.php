<?php
header('Content-Type: application/json');
require_once '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }

    // Check if email exists
    $check = pg_query_params($conn, "SELECT user_id FROM users WHERE email = $1", [$email]);
    
    if (pg_num_rows($check) > 0) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Delete any existing tokens for this email
        pg_query_params($conn, "DELETE FROM password_resets WHERE email = $1", [$email]);
        
        // Save new token
        pg_query_params($conn, 
            "INSERT INTO password_resets (email, token, expires_at) VALUES ($1, $2, $3)", 
            [$email, $token, $expires]
        );

        // Prepare reset link
        $reset_link = "http://localhost/pawsigcity/homepage/login/reset_password.php?token=$token";

        // Send email
        $subject = "Password Reset Request - PAWsig City";
        $message = "Hello,\n\nClick the link below to reset your password:\n\n$reset_link\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.";
        $headers = "From: noreply@pawsigcity.com";

        if (mail($email, $subject, $message, $headers)) {
            echo json_encode(['success' => true, 'message' => 'A reset link has been sent to your email address.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
        }
    } else {
        // Don't reveal if email exists or not for security
        echo json_encode(['success' => true, 'message' => 'If this email exists, a reset link has been sent.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>