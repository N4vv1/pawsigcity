<?php
require '../db.php';

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$id || !in_array($action, ['approve', 'reject'])) {
    header("Location: manage-appointments.php");
    exit;
}

if ($action === 'approve') {
    // ✅ Approve cancellation
    $stmt = $mysqli->prepare("UPDATE appointments 
        SET cancel_approved = 1, cancel_requested = 0, status = 'cancelled' 
        WHERE appointment_id = ?");
} else {
    // ❌ Reject cancellation
    $stmt = $mysqli->prepare("UPDATE appointments 
        SET cancel_approved = 0, cancel_requested = 0 
        WHERE appointment_id = ?");
}

$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: http://localhost/purrfect-paws/appointment/manage-appointments.php?cancel=$action");
exit;
