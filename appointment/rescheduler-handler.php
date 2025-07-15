<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? null;
$new_date = $_POST['appointment_date'] ?? null;

if (!$appointment_id || !$new_date) {
    $_SESSION['error'] = "Missing appointment ID or date.";
    header("Location: ../homepage/appointments.php");
    exit;
}

// Update appointment
$stmt = $mysqli->prepare("UPDATE appointments 
                          SET appointment_date = ?, status = 'pending', is_approved = 0 
                          WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("sii", $new_date, $appointment_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['reschedule_success'] = "Appointment successfully rescheduled. Awaiting admin approval.";
    $_SESSION['reopen_modal_id'] = $appointment_id;
} else {
    $_SESSION['error'] = "Failed to reschedule.";
}

header("Location: ../homepage/appointments.php");
exit;
?>
