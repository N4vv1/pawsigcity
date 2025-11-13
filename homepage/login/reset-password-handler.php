<?php
/**
 * Reset Password Handler - FIXED VERSION
 */

session_start();
header('Content-Type: application/json');
require_once '../../db.php';

error_log("=== RESET PASSWORD REQUEST ===");
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if OTP has been verified
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    error_log("OTP not verified");
    echo json_encode(['success' => false, 'message' => 'Please verify your email with OTP first']);
    exit;
}

// Verify OTP purpose is for password reset (accept both naming conventions)
$valid_purposes = ['reset_password', 'forgot_password'];
if (!isset($_SESSION['otp_purpose']) || !in_array($_SESSION['otp_purpose'], $valid_purposes)) {
    error_log("Invalid OTP purpose: " . ($_SESSION['otp_purpose'] ?? 'not set'));
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid OTP verification purpose',
        'debug' => 'Expected reset_password or forgot_password, got: ' . ($_SESSION['otp_purpose'] ?? 'not set')
    ]);
    exit;
}

// Check if OTP verification is still valid (within 30 minutes)
if (!isset($_SESSION['otp_timestamp']) || (time() - $_SESSION['otp_timestamp']) > 1800) {
    error_log("OTP verification expired");
    echo json_encode(['success' => false, 'message' => 'OTP verification expired. Please verify again.']);
    exit;
}

// Get POST data
$email = trim($_POST['email'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

error_log("Processing password reset for email: $email");

// Validate all required fields
if (empty($email) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Verify email matches the OTP verified email
if (!isset($_SESSION['otp_email']) || $_SESSION['otp_email'] !== $email) {
    error_log("Email mismatch - Session: " . ($_SESSION['otp_email'] ?? 'not set') . ", POST: $email");
    echo json_encode(['success' => false, 'message' => 'Email does not match verified OTP email']);
    exit;
}

// Validate password length
if (strlen($new_password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// Check if passwords match
if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

try {
    // Check if user exists
    $check_query = "SELECT user_id, email FROM users WHERE email = $1";
    $check_result = pg_query_params($conn, $check_query, [$email]);
    
    if (!$check_result) {
        error_log("User check query failed: " . pg_last_error($conn));
        throw new Exception('Database query failed: ' . pg_last_error($conn));
    }
    
    if (pg_num_rows($check_result) === 0) {
        error_log("User not found for email: $email");
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = pg_fetch_assoc($check_result);
    error_log("User found - ID: " . $user['user_id']);
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    error_log("Password hashed");
    
    // Update password in database
    $update_query = "UPDATE users 
                     SET password = $1 
                     WHERE user_id = $2";
    
    error_log("Updating password for user ID: " . $user['user_id']);
    $update_result = pg_query_params($conn, $update_query, [$hashed_password, $user['user_id']]);
    
    if (!$update_result) {
        error_log("Password update failed: " . pg_last_error($conn));
        throw new Exception('Failed to update password: ' . pg_last_error($conn));
    }
    
    error_log("Password updated successfully");
    
    // Mark OTP as used
    if (isset($_SESSION['otp_id'])) {
        $mark_used = "UPDATE otp_verifications SET is_used = TRUE WHERE otp_id = $1";
        $mark_result = pg_query_params($conn, $mark_used, [$_SESSION['otp_id']]);
        
        if ($mark_result) {
            error_log("OTP marked as used - ID: " . $_SESSION['otp_id']);
        }
    }
    
    // Clear all OTP-related session data
    unset($_SESSION['otp']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_purpose']);
    unset($_SESSION['otp_timestamp']);
    unset($_SESSION['otp_verified']);
    unset($_SESSION['otp_id']);
    
    error_log("Password reset completed successfully for: $email");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Password reset successful!'
    ]);
    
} catch (Exception $e) {
    error_log("Password Reset Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

if (isset($conn)) {
    pg_close($conn);
}
?>