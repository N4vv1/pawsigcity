<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? null;
$requested_date = $_POST['requested_date'] ?? null;
$reschedule_reason = trim($_POST['reschedule_reason'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$appointment_id || !$requested_date || empty($reschedule_reason)) {
    $_SESSION['error'] = "Missing required fields.";
    header("Location: ../homepage/appointments.php");
    exit;
}

// ✅ CHECK IF ALREADY RESCHEDULED BEFORE
$rescheduleCheckQuery = "
    SELECT reschedule_count 
    FROM appointments 
    WHERE appointment_id = $1 AND user_id = $2
";
$rescheduleCheckResult = pg_query_params($conn, $rescheduleCheckQuery, [$appointment_id, $user_id]);
$appointment = pg_fetch_assoc($rescheduleCheckResult);

if (!$appointment) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../homepage/appointments.php");
    exit;
}

// ❌ ALREADY RESCHEDULED ONCE - Block it
$rescheduleCount = $appointment['reschedule_count'] ?? 0;
if ($rescheduleCount >= 1) {
    $_SESSION['error'] = "❌ You can only reschedule once per appointment. Please contact admin for further changes.";
    header("Location: ../homepage/appointments.php");
    exit;
}

// ✅ CHECK IF REQUESTED SLOT IS AVAILABLE
$conflictQuery = "
    SELECT COUNT(*) as conflict_count
    FROM appointments
    WHERE appointment_date = $1 
    AND status IN ('confirmed', 'completed')
    AND appointment_id != $2
";
$conflictResult = pg_query_params($conn, $conflictQuery, [$requested_date, $appointment_id]);
$conflictData = pg_fetch_assoc($conflictResult);

if ($conflictData['conflict_count'] > 0) {
    // ❌ SLOT TAKEN - Reject automatically
    $query = "
        UPDATE appointments 
        SET requested_date = $1,
            reschedule_reason = $2,
            reschedule_requested = TRUE,
            reschedule_approved = FALSE
        WHERE appointment_id = $3 AND user_id = $4
    ";
    pg_query_params($conn, $query, [$requested_date, $reschedule_reason, $appointment_id, $user_id]);
    $_SESSION['error'] = "❌ Reschedule request denied - time slot is already booked. Please choose another date.";
    
} else {
    // ✅ SLOT AVAILABLE - Auto-approve immediately and increment reschedule count
    $query = "
        UPDATE appointments 
        SET appointment_date = $1,
            reschedule_reason = $2,
            reschedule_requested = FALSE,
            reschedule_approved = TRUE,
            reschedule_count = COALESCE(reschedule_count, 0) + 1
        WHERE appointment_id = $3 AND user_id = $4
    ";
    pg_query_params($conn, $query, [$requested_date, $reschedule_reason, $appointment_id, $user_id]);
    $_SESSION['success'] = "✅ Reschedule approved automatically! Your new appointment date is confirmed. (Note: You cannot reschedule this appointment again)";
}

header("Location: ../homepage/appointments.php");
exit;
?>