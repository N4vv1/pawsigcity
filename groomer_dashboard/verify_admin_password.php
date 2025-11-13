<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

// Check if groomer is logged in
if (!isset($_SESSION['groomer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

// Query to get all admin users' passwords
$query = "SELECT user_id, first_name, password FROM users WHERE role = 'admin'";
$result = pg_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Check if the password matches any admin user
$passwordMatch = false;
$adminName = '';

while ($admin = pg_fetch_assoc($result)) {
    // Verify the password against the hashed password in database
    if (password_verify($password, $admin['password'])) {
        $passwordMatch = true;
        $adminName = $admin['first_name'];
        break;
    }
}

if ($passwordMatch) {
    // Log the admin who authorized going offline (optional)
    error_log("Groomer ID {$_SESSION['groomer_id']} went offline - authorized by admin: {$adminName}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Authorized by ' . $adminName
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Incorrect admin password'
    ]);
}
?>