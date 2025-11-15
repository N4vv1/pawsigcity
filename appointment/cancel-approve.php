<?php
session_start();
require '../db.php'; // $conn = pg_connect(...);

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$id || !in_array($action, ['approve', 'reject'])) {
    header("Location: ../../../dashboard/admin/admin.php");
    exit;
}

if ($action === 'approve') {
    // ✅ Approve cancellation
    $query = "
        UPDATE appointments 
        SET cancel_approved = TRUE, cancel_requested = FALSE, status = 'cancelled'
        WHERE appointment_id = $1
    ";
    $_SESSION['cancel_flash'] = '✅ Cancellation approved.';
} else {
    // ❌ Reject cancellation
    $query = "
        UPDATE appointments 
        SET cancel_approved = FALSE, cancel_requested = FALSE
        WHERE appointment_id = $1
    ";
    $_SESSION['cancel_flash'] = '❌ Cancellation rejected.';
}

// Execute query with parameter
$result = pg_query_params($conn, $query, [$id]);

if (!$result) {
    $_SESSION['cancel_flash'] = "⚠️ Database error: " . pg_last_error($conn);
}

// Redirect without query string
header("Location: ../../../dashboard/admin/admin.php");
exit;
?>
