<?php
session_start();
require '../db.php'; // $conn = pg_connect(...)

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

// ✅ Update appointment with valid enum value
$result = pg_query_params(
    $conn,
    "UPDATE appointments
     SET appointment_date = $1,
         status = 'confirmed',
         is_approved = false
     WHERE appointment_id = $2 AND user_id = $3",
    [$new_date, $appointment_id, $user_id]
);

if ($result && pg_affected_rows($result) > 0) {
    $_SESSION['reschedule_success'] = "Appointment successfully rescheduled. Awaiting admin approval.";
    $_SESSION['reopen_modal_id'] = $appointment_id;
} else {
    $_SESSION['error'] = "Failed to reschedule.";
}

pg_close($conn);
header("Location: ../homepage/appointments.php");
exit;
?><?php
session_start();
require '../db.php'; // $conn = pg_connect(...)

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

// ✅ Update appointment with valid enum value
$result = pg_query_params(
    $conn,
    "UPDATE appointments
     SET appointment_date = $1,
         status = 'confirmed',
         is_approved = false
     WHERE appointment_id = $2 AND user_id = $3",
    [$new_date, $appointment_id, $user_id]
);

if ($result && pg_affected_rows($result) > 0) {
    $_SESSION['reschedule_success'] = "Appointment successfully rescheduled. Awaiting admin approval.";
    $_SESSION['reopen_modal_id'] = $appointment_id;
} else {
    $_SESSION['error'] = "Failed to reschedule.";
}

pg_close($conn);
header("Location: ../homepage/appointments.php");
exit;
?>