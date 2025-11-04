<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No service ID provided.";
    header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
    exit;
}

$service_id = intval($_GET['id']);

// Check if service exists
pg_prepare($conn, "check_service", "SELECT name FROM packages WHERE package_id = $1");
$check = pg_execute($conn, "check_service", [$service_id]);

if (pg_num_rows($check) == 0) {
    $_SESSION['error'] = "Service not found.";
    header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
    exit;
}

$service = pg_fetch_assoc($check);

// Delete associated prices first (foreign key constraint)
pg_prepare($conn, "delete_prices", "DELETE FROM package_prices WHERE package_id = $1");
pg_execute($conn, "delete_prices", [$service_id]);

// Delete the service
pg_prepare($conn, "delete_service", "DELETE FROM packages WHERE package_id = $1");
$result = pg_execute($conn, "delete_service", [$service_id]);

if ($result) {
    $_SESSION['success'] = "Service '" . htmlspecialchars($service['name']) . "' deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete service. Please try again.";
}

header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
exit;
?>