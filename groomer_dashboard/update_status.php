<?php
session_start();
header('Content-Type: application/json');
require '../db.php';

// Check if groomer is logged in
if (!isset($_SESSION['groomer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$groomer_id = $_SESSION['groomer_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$is_active = isset($input['is_active']) ? (bool)$input['is_active'] : false;

// Update groomer status
$query = "
    UPDATE groomers 
    SET is_active = $1, 
        last_active = CURRENT_TIMESTAMP
    WHERE groomer_id = $2
";

$result = pg_query_params($conn, $query, [$is_active ? 'true' : 'false', $groomer_id]);

if ($result) {
    echo json_encode([
        'success' => true, 
        'is_active' => $is_active,
        'message' => $is_active ? 'You are now online' : 'You are now offline'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update status: ' . pg_last_error($conn)
    ]);
}

pg_close($conn);
?>