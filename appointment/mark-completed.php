<?php
session_start();
require '../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['completed'] = "❌ Invalid appointment ID.";
    header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php?show=appointments");

    exit;
}

$appointment_id = $_GET['id'];

$stmt = $mysqli->prepare("UPDATE appointments SET status = 'completed' WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);

if ($stmt->execute()) {
    $_SESSION['completed'] = "🎉 Appointment marked as completed.";
} else {
    $_SESSION['completed'] = "❌ Failed to update status.";
}

header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php?show=appointments");

exit;
