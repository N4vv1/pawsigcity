<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);
    
    if (empty($entered_otp)) {
        echo json_encode(['success' => false, 'message' => 'Please enter OTP']);
        exit;
    }
    
    // Check if OTP exists in session
    if (!isset($_SESSION['otp'])) {
        echo json_encode(['success' => false, 'message' => 'No OTP found. Please request a new one.']);
        exit;
    }
    
    // Check if OTP has expired (10 minutes)
    $otp_age = time() - $_SESSION['otp_timestamp'];
    if ($otp_age > 600) { // 600 seconds = 10 minutes
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_purpose']);
        unset($_SESSION['otp_timestamp']);
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
        exit;
    }
    
    // Verify OTP
    if ($entered_otp === $_SESSION['otp']) {
        // OTP is correct
        $_SESSION['otp_verified'] = true;
        echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>