<?php
session_start();
require_once '../../db.php';
require_once '../admin/check_admin.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../homepage/main.php");
    exit;
}

$appointment_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null; // 'approve' or 'deny'

if (!$appointment_id || !in_array($action, ['approve', 'deny'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../admin/admin.php");
    exit;
}

// Fetch the reschedule request details
$result = pg_query_params(
    $conn,
    "SELECT * FROM appointments WHERE appointment_id = $1 AND reschedule_requested = true",
    [$appointment_id]
);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error'] = "Reschedule request not found.";
    header("Location: ../admin/admin.php");
    exit;
}

$appointment = pg_fetch_assoc($result);

if ($action === 'approve') {
    // Approve the reschedule: update the appointment date and reset flags
    $update = pg_query_params(
        $conn,
        "UPDATE appointments
         SET appointment_date = $1,
             reschedule_requested = false,
             reschedule_approved = true,
             reschedule_reason = NULL,
             requested_date = NULL,
             is_approved = false,
             status = 'confirmed'
         WHERE appointment_id = $2",
        [$appointment['requested_date'], $appointment_id]
    );

    if ($update && pg_affected_rows($update) > 0) {
        $_SESSION['success'] = "Reschedule request approved successfully.";
    } else {
        $_SESSION['error'] = "Failed to approve reschedule.";
    }

} elseif ($action === 'deny') {
    // Deny the reschedule: mark as denied but keep original date
    $update = pg_query_params(
        $conn,
        "UPDATE appointments
         SET reschedule_approved = false,
             reschedule_requested = false,
             requested_date = NULL
         WHERE appointment_id = $1",
        [$appointment_id]
    );

    if ($update && pg_affected_rows($update) > 0) {
        $_SESSION['success'] = "Reschedule request denied.";
    } else {
        $_SESSION['error'] = "Failed to deny reschedule.";
    }
}

pg_close($conn);
header("Location: ../admin/admin.php?show=appointments");
exit;
?>