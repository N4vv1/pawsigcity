<?php
session_start();
require '../db.php'; // $conn = pg_connect(...);

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    $_SESSION['deleted'] = '❌ Invalid appointment ID.';
    header("Location: http://localhost/pawsigcity/dashboard/home_dashboard/home.php?deleted=1&show=appointments");
    exit;
}

// Delete appointment using pg_query_params
$query = "DELETE FROM appointments WHERE appointment_id = $1";
$result = pg_query_params($conn, $query, [$id]);

if ($result) {
    $_SESSION['deleted'] = '🗑 Appointment deleted successfully.';
} else {
    $_SESSION['deleted'] = '❌ Failed to delete appointment.';
}

header("Location: http://localhost/pawsigcity/dashboard/home_dashboard/home.php?deleted=1&show=appointments");
exit;
?>
