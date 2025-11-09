<?php
/**
 * Verify OTP - Fixed Version with Better Error Handling
 */

session_start();
header('Content-Type: application/json');
require_once '../../db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$entered_otp = trim($_POST['otp'] ?? '');

if (empty($entered_otp)) {
    echo json_encode(['success' => false, 'message' => 'Please enter OTP']);
    exit;
}

if (strlen($entered_otp) !== 6 || !ctype_digit($entered_otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP format']);
    exit;
}

try {
    // Find the most recent valid OTP
    $query = "SELECT otp_id, email, otp, purpose, type, expires_at, attempts, is_verified, is_used 
              FROM otp_verifications 
              WHERE otp = $1 
              AND is_verified = FALSE 
              AND is_used = FALSE 
              AND expires_at > NOW()
              ORDER BY created_at DESC 
              LIMIT 1";
    
    $result = pg_query_params($conn, $query, [$entered_otp]);
    
    if (!$result) {
        error_log("OTP Query Error: " . pg_last_error($conn));
        throw new Exception('Database query failed');
    }
    
    if (pg_num_rows($result) === 0) {
        // Check if OTP exists but is expired or already used
        $check_query = "SELECT otp_id, is_verified, is_used, expires_at 
                       FROM otp_verifications 
                       WHERE otp = $1 
                       ORDER BY created_at DESC LIMIT 1";
        $check_result = pg_query_params($conn, $check_query, [$entered_otp]);
        
        if ($check_result && pg_num_rows($check_result) > 0) {
            $check_data = pg_fetch_assoc($check_result);
            
            if ($check_data['is_used'] === 't' || $check_data['is_used'] === true) {
                echo json_encode(['success' => false, 'message' => 'OTP has already been used. Please request a new one.']);
            } else if ($check_data['is_verified'] === 't' || $check_data['is_verified'] === true) {
                echo json_encode(['success' => false, 'message' => 'OTP already verified. Please complete your registration.']);
            } else if (strtotime($check_data['expires_at']) <= time()) {
                echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
        }
        exit;
    }
    
    $otp_data = pg_fetch_assoc($result);
    
    // Check attempts limit (max 5 attempts)
    if ($otp_data['attempts'] >= 5) {
        $mark_used = "UPDATE otp_verifications SET is_used = TRUE WHERE otp_id = $1";
        pg_query_params($conn, $mark_used, [$otp_data['otp_id']]);
        
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.']);
        exit;
    }
    
    // OTP is valid - Mark as verified
    $update_query = "UPDATE otp_verifications 
                     SET is_verified = TRUE,
                         verified_at = CURRENT_TIMESTAMP
                     WHERE otp_id = $1";
    
    $update_result = pg_query_params($conn, $update_query, [$otp_data['otp_id']]);
    
    if (!$update_result) {
        error_log("OTP Update Error: " . pg_last_error($conn));
        throw new Exception('Failed to update OTP status');
    }
    
    // Store verification info in session
    $_SESSION['otp_verified'] = true;
    $_SESSION['otp_email'] = $otp_data['email'];
    $_SESSION['otp_purpose'] = $otp_data['purpose'] ?: $otp_data['type'];
    $_SESSION['otp_id'] = $otp_data['otp_id'];
    $_SESSION['otp_timestamp'] = time();
    
    error_log("OTP Verified Successfully - Email: " . $otp_data['email'] . ", Purpose: " . ($otp_data['purpose'] ?: $otp_data['type']));
    
    echo json_encode([
        'success' => true, 
        'message' => 'OTP verified successfully'
    ]);
    
} catch (Exception $e) {
    error_log("OTP Verification Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred during verification. Please try again.'
    ]);
}

if (isset($conn)) {
    pg_close($conn);
}
?>