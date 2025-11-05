<?php
session_start();
require '../db.php'; // $conn = pg_connect(...);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? null;
$reason = trim($_POST['cancel_reason'] ?? '');

if (!$appointment_id || empty($reason)) {
    $_SESSION['error'] = "Missing reason or appointment ID.";
    header("Location: ../homepage/appointments.php");
    exit;
}

$query = "
    UPDATE appointments 
    SET cancel_reason = $1, 
        cancel_requested = TRUE, 
        cancel_approved = NULL
    WHERE appointment_id = $2 AND user_id = $3
";

$result = pg_query_params($conn, $query, [$reason, $appointment_id, $user_id]);

if ($result) {
    $_SESSION['success'] = "Cancellation request submitted.";
} else {
    $_SESSION['error'] = "Failed to submit cancellation request: " . pg_last_error($conn);
}

header("Location: ../homepage/appointments.php");
exit;
?>