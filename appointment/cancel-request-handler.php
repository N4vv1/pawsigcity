<?php
session_start();
require '../db.php'; // $conn = pg_connect(...);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? null;
$cancel_reason = trim($_POST['cancel_reason'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$appointment_id || empty($cancel_reason)) {
    $_SESSION['error'] = "Missing reason or appointment ID.";
    header("Location: ../homepage/appointments.php");
    exit;
}

$query = "
    UPDATE appointments 
    SET status = 'cancellation_requested', 
        cancel_requested = TRUE, 
        cancel_reason = $1, 
        cancel_approved = NULL 
    WHERE appointment_id = $2 AND user_id = $3
";

$result = pg_query_params($conn, $query, [$cancel_reason, $appointment_id, $user_id]);

if ($result) {
    $_SESSION['cancel_success'] = "Cancellation request submitted. Waiting for admin approval.";
    $_SESSION['reopen_modal_id'] = $appointment_id;
} else {
    $_SESSION['error'] = "Failed to submit cancellation request: " . pg_last_error($conn);
}

header("Location: ../homepage/appointments.php");
exit;
?>
