<?php
session_start();
require '../db.php'; // $conn = pg_connect(...);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$appointment_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$appointment_id || !is_numeric($appointment_id)) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: ../homepage/appointments.php");
    exit;
}

// Fetch appointment securely with pg_query_params
$query = "SELECT * FROM appointments WHERE appointment_id = $1 AND user_id = $2";
$result = pg_query_params($conn, $query, [$appointment_id, $user_id]);

$appointment = pg_fetch_assoc($result);

if (!$appointment) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../homepage/appointments.php");
    exit;
}
?>
