<?php
/**
 * Registration Handler - Fixed (Now checks both GET and POST for debugging)
 */

session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, only log them

// Log everything for debugging
error_log("=== REGISTRATION ATTEMPT ===");
error_log("Request Method: " . $_SERVER["REQUEST_METHOD"]);
error_log("Session Data: " . print_r($_SESSION, true));
error_log("POST Data: " . print_r($_POST, true));
error_log("GET Data: " . print_r($_GET, true));

require_once '../../db.php';

// Check if request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    error_log("Registration failed: Invalid request method - " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method',
        'debug' => [
            'method' => $_SERVER["REQUEST_METHOD"],
            'expected' => 'POST'
        ]
    ]);
    exit;
}

// Check if OTP has been verified
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    error_log("Registration failed: OTP not verified");
    echo json_encode([
        'success' => false, 
        'message' => 'Please verify your email with OTP first',
        'debug' => [
            'otp_verified' => $_SESSION['otp_verified'] ?? 'not set',
            'session_keys' => array_keys($_SESSION)
        ]
    ]);
    exit;
}

// Verify OTP purpose is for registration
if (!isset($_SESSION['otp_purpose']) || $_SESSION['otp_purpose'] !== 'registration') {
    error_log("Registration failed: Wrong purpose - " . ($_SESSION['otp_purpose'] ?? 'not set'));
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid OTP verification purpose',
        'debug' => [
            'otp_purpose' => $_SESSION['otp_purpose'] ?? 'not set',
            'expected' => 'registration'
        ]
    ]);
    exit;
}

// Check if OTP verification is still valid (within 30 minutes)
if (!isset($_SESSION['otp_timestamp']) || (time() - $_SESSION['otp_timestamp']) > 1800) {
    error_log("Registration failed: OTP verification expired");
    echo json_encode([
        'success' => false, 
        'message' => 'OTP verification expired. Please verify again.',
        'debug' => [
            'timestamp' => $_SESSION['otp_timestamp'] ?? 'not set',
            'current_time' => time(),
            'age_seconds' => isset($_SESSION['otp_timestamp']) ? (time() - $_SESSION['otp_timestamp']) : 'N/A'
        ]
    ]);
    exit;
}

// Get POST data
$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$phone = trim($_POST['phone'] ?? '');

error_log("Parsed POST data - Email: $email, Name: $first_name $last_name");

// Validate all required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    error_log("Registration failed: Missing required fields");
    echo json_encode([
        'success' => false, 
        'message' => 'All required fields must be filled',
        'debug' => [
            'first_name' => !empty($first_name),
            'last_name' => !empty($last_name),
            'email' => !empty($email),
            'password' => !empty($password)
        ]
    ]);
    exit;
}

// Verify email matches the OTP verified email
if (!isset($_SESSION['otp_email']) || $_SESSION['otp_email'] !== $email) {
    error_log("Registration failed: Email mismatch - Session: " . ($_SESSION['otp_email'] ?? 'not set') . ", POST: $email");
    echo json_encode([
        'success' => false, 
        'message' => 'Email does not match verified OTP email',
        'debug' => [
            'session_email' => $_SESSION['otp_email'] ?? 'not set',
            'post_email' => $email
        ]
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Registration failed: Invalid email format - $email");
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate password length
if (strlen($password) < 8) {
    error_log("Registration failed: Password too short");
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// Validate phone number
if (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
    error_log("Registration failed: Invalid phone format");
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

try {
    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = $1";
    $check_result = pg_query_params($conn, $check_query, [$email]);
    
    if (!$check_result) {
        error_log("DB Error (check): " . pg_last_error($conn));
        throw new Exception('Database query failed');
    }
    
    if (pg_num_rows($check_result) > 0) {
        error_log("Registration failed: Email already exists - $email");
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully");
    
    // Insert new user (without created_at and updated_at if they don't exist)
    $insert_query = "INSERT INTO users (first_name, middle_name, last_name, email, password, phone, role) 
                     VALUES ($1, $2, $3, $4, $5, $6, 'customer') 
                     RETURNING user_id";
    
    error_log("Attempting to insert user...");
    
    $insert_result = pg_query_params($conn, $insert_query, [
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $hashed_password,
        $phone
    ]);
    
    if (!$insert_result) {
        error_log("DB Error (insert): " . pg_last_error($conn));
        throw new Exception('Failed to create account: ' . pg_last_error($conn));
    }
    
    $user = pg_fetch_assoc($insert_result);
    error_log("User created with ID: " . ($user['user_id'] ?? 'unknown'));
    
    if ($user && isset($user['user_id'])) {
        // Mark OTP as used
        if (isset($_SESSION['otp_id'])) {
            $mark_used = "UPDATE otp_verifications SET is_used = TRUE WHERE otp_id = $1";
            pg_query_params($conn, $mark_used, [$_SESSION['otp_id']]);
            error_log("OTP marked as used");
        }
        
        // Clear OTP session data
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_purpose']);
        unset($_SESSION['otp_timestamp']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['otp_id']);
        
        error_log("Registration completed successfully for: $email");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Registration successful! Please login with your credentials.',
            'user_id' => $user['user_id']
        ]);
    } else {
        error_log("Registration failed: User record not returned");
        echo json_encode(['success' => false, 'message' => 'Failed to create account. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log("Registration Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred during registration. Please try again.',
        'error' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    pg_close($conn);
}
?>