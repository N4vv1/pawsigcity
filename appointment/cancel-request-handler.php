<?php
session_start();
require '../db.php';

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

$stmt = $mysqli->prepare("UPDATE appointments 
    SET status = 'cancellation_requested', cancel_requested = 1, cancel_reason = ?, cancel_approved = NULL 
    WHERE appointment_id = ? AND user_id = ?");
    
$stmt->bind_param("sii", $cancel_reason, $appointment_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['cancel_success'] = "Cancellation request submitted. Waiting for admin approval.";
    $_SESSION['reopen_modal_id'] = $appointment_id;
} else {
    $_SESSION['error'] = "Failed to submit cancellation request.";
}

header("Location: ../homepage/appointments.php");
exit;
?>
