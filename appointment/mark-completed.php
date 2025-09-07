<?php
session_start();
require '../db.php'; // $conn = pg_connect(...);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['completed'] = "❌ Invalid appointment ID.";
    header("Location: http://localhost/pawsigcity/dashboard/home_dashboard/home.php?show=appointments");
    exit;
}

$appointment_id = (int) $_GET['id'];

// ✅ Use parameterized query to prevent SQL injection
$result = pg_query_params(
    $conn,
    "UPDATE appointments SET status = 'completed' WHERE appointment_id = $1",
    [$appointment_id]
);

if ($result) {
    $_SESSION['completed'] = "🎉 Appointment marked as completed.";
} else {
    $_SESSION['completed'] = "❌ Failed to update status.";
}

header("Location: http://localhost/pawsigcity/dashboard/home_dashboard/home.php?show=appointments");
exit;
?>
