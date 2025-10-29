<?php
session_start();
header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Your Flask API URL on Render
$api_url = 'https://pawsigcity-1.onrender.com/api/analyze-sentiment';

// Initialize cURL
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 seconds timeout

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

// Check for cURL errors
if ($curl_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to connect to sentiment analysis API: ' . $curl_error
    ]);
    exit;
}

// Check HTTP status
if ($http_code !== 200) {
    echo json_encode([
        'success' => false,
        'message' => 'API returned error status: ' . $http_code,
        'response' => $response
    ]);
    exit;
}

// Decode and return the response
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON response from API',
        'raw_response' => $response
    ]);
    exit;
}

// Return the API response
echo json_encode($data);
?>