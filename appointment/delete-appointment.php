<?php
session_start();
require '../db.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    $_SESSION['deleted'] = '❌ Invalid appointment ID.';
    header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php?deleted=1&show=appointments");
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM appointments WHERE appointment_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['deleted'] = '🗑 Appointment deleted successfully.';
} else {
    $_SESSION['deleted'] = '❌ Failed to delete appointment.';
}
header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php?deleted=1&show=appointments");
exit;
