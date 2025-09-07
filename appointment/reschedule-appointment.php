<?php
session_start();
require '../db.php'; // $conn = pg_connect(...)

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    $_SESSION['error'] = "No appointment ID specified.";
    header("Location: ../homepage/dashboard.php");
    exit;
}

// ✅ Fetch the appointment with pg_query_params
$result = pg_query_params(
    $conn,
    "SELECT a.*, p.name AS pet_name
     FROM appointments a
     JOIN pets p ON a.pet_id = p.pet_id
     WHERE a.appointment_id = $1 AND a.user_id = $2",
    [$appointment_id, $user_id]
);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../homepage/dashboard.php");
    exit;
}

$appointment = pg_fetch_assoc($result);
?>
