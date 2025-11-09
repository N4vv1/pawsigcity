<?php
/**
 * Registration Handler with OTP Verification
 * Only allows registration after OTP verification
 */

session_start();
header('Content-Type: application/json');

require_once '../../db.php';

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if OTP has been verified
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please verify your email with OTP first'
    ]);
    exit;
}

// Verify OTP purpose is for registration
if (!isset($_SESSION['otp_purpose']) || $_SESSION['otp_purpose'] !== 'registration') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid OTP verification purpose'
    ]);
    exit;
}

// Get POST data
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Validate all required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    echo json_encode([
        'success' => false, 
        'message' => 'All required fields must be filled'
    ]);
    exit;
}

// Verify email matches the OTP verified email
if (!isset($_SESSION['otp_email']) || $_SESSION['otp_email'] !== $email) {
    echo json_encode([
        'success' => false, 
        'message' => 'Email does not match verified OTP email'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Validate password length
if (strlen($password) < 8) {
    echo json_encode([
        'success' => false, 
        'message' => 'Password must be at least 8 characters long'
    ]);
    exit;
}

// Validate phone number (basic validation)
if (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid phone number format'
    ]);
    exit;
}

try {
    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = $1";
    $check_result = pg_query_params($conn, $check_query, [$email]);
    
    if (!$check_result) {
        throw new Exception('Database query failed: ' . pg_last_error($conn));
    }
    
    if (pg_num_rows($check_result) > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Email already registered'
        ]);
        exit;
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user into database
    $insert_query = "INSERT INTO users (first_name, middle_name, last_name, email, password, phone, role, created_at, updated_at) 
                     VALUES ($1, $2, $3, $4, $5, $6, 'customer', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) 
                     RETURNING user_id";
    
    $insert_result = pg_query_params($conn, $insert_query, [
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $hashed_password,
        $phone
    ]);
    
    if (!$insert_result) {
        throw new Exception('Failed to create account: ' . pg_last_error($conn));
    }
    
    $user = pg_fetch_assoc($insert_result);
    
    if ($user && isset($user['user_id'])) {
        // Clear all OTP-related session data
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_purpose']);
        unset($_SESSION['otp_timestamp']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['otp_requests']);
        
        // Log the registration (optional - for analytics)
        $log_query = "INSERT INTO registration_log (user_id, registered_at, ip_address) 
                      VALUES ($1, CURRENT_TIMESTAMP, $2)";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        // Try to log, but don't fail if logging table doesn't exist
        @pg_query_params($conn, $log_query, [$user['user_id'], $ip_address]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Registration successful! Please login with your credentials.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to create account. Please try again.'
        ]);
    }
    
} catch (Exception $e) {
    // Log error for debugging (in production, log to file instead)
    error_log("Registration Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred during registration. Please try again.'
    ]);
}

// Close database connection
if (isset($conn)) {
    pg_close($conn);
}
?>