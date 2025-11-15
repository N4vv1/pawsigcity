<?php
session_start();
require_once '../../db.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../homepage/main.php");
    exit;
}

// Check database connection
if (pg_connection_status($conn) !== PGSQL_CONNECTION_OK) {
    die('Database connection failed: ' . pg_last_error());
}

// Get appointment ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: ../../dashboard/admin/admin.php");
    exit;
}

$appointment_id = intval($_GET['id']);

// Fetch the appointment details
$query = "SELECT * FROM appointments WHERE appointment_id = $1";
$result = pg_query_params($conn, $query, [$appointment_id]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../../dashboard/admin/admin.php");
    exit;
}

$appointment = pg_fetch_assoc($result);

// Check if there's a reschedule request
if (empty($appointment['reschedule_reason'])) {
    $_SESSION['error'] = "No reschedule request found for this appointment.";
    header("Location: ../../dashboard/admin/admin.php");
    exit;
}

// Check if already approved
if ($appointment['reschedule_approved'] === 't') {
    $_SESSION['error'] = "This reschedule request has already been approved.";
    header("Location: ../../dashboard/admin/admin.php");
    exit;
}

// Check if there's a requested_date to reschedule to
if (empty($appointment['requested_date'])) {
    $_SESSION['error'] = "No new date was requested for this appointment.";
    header("Location: ../../dashboard/admin/admin.php");
    exit;
}

// Get the requested new date
$new_date = $appointment['requested_date'];

// Check if the new time slot is available (not already booked by another appointment)
$check_query = "SELECT COUNT(*) as count 
                FROM appointments 
                WHERE appointment_date = $1 
                AND appointment_id != $2 
                AND status NOT IN ('cancelled', 'completed')";
$check_result = pg_query_params($conn, $check_query, [$new_date, $appointment_id]);
$slot_check = pg_fetch_assoc($check_result);

if ($slot_check['count'] > 0) {
    // Time slot is already taken - deny the reschedule
    $deny_query = "UPDATE appointments 
                   SET reschedule_requested = false,
                       reschedule_approved = false,
                       reschedule_reason = NULL,
                       requested_date = NULL
                   WHERE appointment_id = $1";
    pg_query_params($conn, $deny_query, [$appointment_id]);
    
    $_SESSION['error'] = "Reschedule denied: The requested time slot is already booked by another appointment.";
    header("Location: ../../dashboard/admin/admin.php?show=appointments");
    exit;
}

// Approve the reschedule and update the appointment date
$update_query = "UPDATE appointments 
                 SET appointment_date = $1,
                     reschedule_approved = true,
                     reschedule_requested = false,
                     reschedule_count = COALESCE(reschedule_count, 0) + 1,
                     reschedule_reason = NULL,
                     requested_date = NULL
                 WHERE appointment_id = $2";

$update_result = pg_query_params($conn, $update_query, [$new_date, $appointment_id]);

if ($update_result && pg_affected_rows($update_result) > 0) {
    $_SESSION['success'] = "Reschedule approved! Appointment has been moved to " . date('M d, Y h:i A', strtotime($new_date));
    
    // Optional: Log the approval
    error_log("Admin " . $_SESSION['user_id'] . " approved reschedule for appointment #" . $appointment_id . " to " . $new_date);
} else {
    $_SESSION['error'] = "Failed to approve reschedule request. Please try again.";
    error_log("Failed to approve reschedule for appointment #" . $appointment_id . ": " . pg_last_error($conn));
}

// Redirect back to admin panel
header("Location: ../../dashboard/admin/admin.php?show=appointments");
exit;
?>