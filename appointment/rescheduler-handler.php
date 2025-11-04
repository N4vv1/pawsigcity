<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? null;
$new_date = $_POST['appointment_date'] ?? null;
$reschedule_reason = $_POST['reschedule_reason'] ?? 'Client requested reschedule';

if (!$appointment_id || !$new_date) {
    $_SESSION['error'] = "Missing appointment ID or date.";
    header("Location: ../homepage/appointments.php");
    exit;
}

// Verify the appointment belongs to this user
$check = pg_query_params(
    $conn,
    "SELECT appointment_id FROM appointments WHERE appointment_id = $1 AND user_id = $2",
    [$appointment_id, $user_id]
);

if (!$check || pg_num_rows($check) === 0) {
    $_SESSION['error'] = "Appointment not found or access denied.";
    header("Location: ../homepage/appointments.php");
    exit;
}

// Submit reschedule request for admin approval
$result = pg_query_params(
    $conn,
    "UPDATE appointments
     SET reschedule_requested = true,
         reschedule_reason = $1,
         requested_date = $2,
         reschedule_approved = NULL
     WHERE appointment_id = $3 AND user_id = $4",
    [$reschedule_reason, $new_date, $appointment_id, $user_id]
);

if ($result && pg_affected_rows($result) > 0) {
    $_SESSION['success'] = "Reschedule request submitted successfully. Awaiting admin approval.";
} else {
    $_SESSION['error'] = "Failed to submit reschedule request. Please try again.";
}

pg_close($conn);
header("Location: ../homepage/appointments.php");
exit;
?>