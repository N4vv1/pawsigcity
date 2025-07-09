<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? null;
$reason = trim($_POST['cancel_reason'] ?? '');

if (!$appointment_id || empty($reason)) {
    $_SESSION['error'] = "Missing reason or appointment ID.";
    header("Location: http://localhost/purrfect-paws/homepage/appointments.php");
    exit;
}

$stmt = $mysqli->prepare("
    UPDATE appointments 
    SET cancel_reason = ?, 
        cancel_requested = 1, 
        cancel_approved = NULL,
        status = 'cancellation_requested'
    WHERE appointment_id = ? AND user_id = ?
");
$stmt->bind_param("sii", $reason, $appointment_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Cancellation request submitted.";
} else {
    $_SESSION['error'] = "Failed to submit cancellation request.";
}

header("Location: http://localhost/purrfect-paws/homepage/appointments.php");
exit;
