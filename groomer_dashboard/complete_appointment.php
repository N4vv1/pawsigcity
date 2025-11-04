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
$appointment_id = isset($input['appointment_id']) ? intval($input['appointment_id']) : 0;

if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

// Verify the appointment belongs to this groomer and is confirmed
$verify_query = "
    SELECT appointment_id, status 
    FROM appointments 
    WHERE appointment_id = $1 AND groomer_id = $2
";

$verify_result = pg_query_params($conn, $verify_query, [$appointment_id, $groomer_id]);

if (!$verify_result || pg_num_rows($verify_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or access denied']);
    exit;
}

$appointment = pg_fetch_assoc($verify_result);

if ($appointment['status'] !== 'confirmed') {
    echo json_encode(['success' => false, 'message' => 'Only confirmed appointments can be completed']);
    exit;
}

// Update appointment status to completed
$update_query = "
    UPDATE appointments 
    SET status = 'completed', 
        updated_at = CURRENT_TIMESTAMP
    WHERE appointment_id = $1 AND groomer_id = $2
";

$result = pg_query_params($conn, $update_query, [$appointment_id, $groomer_id]);

if ($result) {
    echo json_encode([
        'success' => true, 
        'message' => 'Appointment marked as completed'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to complete appointment: ' . pg_last_error($conn)
    ]);
}

pg_close($conn);
?>