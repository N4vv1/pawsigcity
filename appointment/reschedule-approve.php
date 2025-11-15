<?php
session_start();
require_once '../db.php';

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
    header("Location: ../dashboard/admin/admin.php");
    exit;
}

$appointment_id = intval($_GET['id']);

// Fetch the appointment details
$query = "SELECT * FROM appointments WHERE appointment_id = $1";
$result = pg_query_params($conn, $query, [$appointment_id]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../dashboard/admin/admin.php");
    exit;
}

$appointment = pg_fetch_assoc($result);

// Check if there's a reschedule request
if (empty($appointment['reschedule_reason'])) {
    $_SESSION['error'] = "No reschedule request found for this appointment.";
    header("Location: ../dashboard/admin/admin.php");
    exit;
}

// Check if already approved
if ($appointment['reschedule_approved'] === 't') {
    $_SESSION['error'] = "This reschedule request has already been approved.";
    header("Location: ../dashboard/admin/admin.php");
    exit;
}

// Approve the reschedule request
$update_query = "UPDATE appointments 
                 SET reschedule_approved = true 
                 WHERE appointment_id = $1";

$update_result = pg_query_params($conn, $update_query, [$appointment_id]);

if ($update_result && pg_affected_rows($update_result) > 0) {
    $_SESSION['success'] = "Reschedule request approved successfully! The client can now update their appointment date.";
    
    // Optional: Log the approval
    error_log("Admin " . $_SESSION['user_id'] . " approved reschedule for appointment #" . $appointment_id);
} else {
    $_SESSION['error'] = "Failed to approve reschedule request. Please try again.";
    error_log("Failed to approve reschedule for appointment #" . $appointment_id . ": " . pg_last_error($conn));
}

// Redirect back to admin panel
header("Location: ../dashboard/admin/admin.php?show=appointments");
exit;
?>