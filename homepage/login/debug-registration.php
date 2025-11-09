<?php
/**
 * Debug Registration - Test what's happening
 * Temporarily use this to see what's going on
 */

session_start();
header('Content-Type: application/json');

// First, let's see what we're receiving
$debug_info = [
    'request_method' => $_SERVER["REQUEST_METHOD"],
    'session_exists' => isset($_SESSION),
    'session_data' => $_SESSION ?? [],
    'post_data' => $_POST ?? [],
    'otp_verified' => $_SESSION['otp_verified'] ?? 'NOT SET',
    'otp_purpose' => $_SESSION['otp_purpose'] ?? 'NOT SET',
    'otp_email' => $_SESSION['otp_email'] ?? 'NOT SET',
    'otp_timestamp' => $_SESSION['otp_timestamp'] ?? 'NOT SET',
    'time_diff' => isset($_SESSION['otp_timestamp']) ? (time() - $_SESSION['otp_timestamp']) : 'N/A'
];

echo json_encode([
    'debug' => true,
    'info' => $debug_info
], JSON_PRETTY_PRINT);
?>