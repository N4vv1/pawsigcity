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
$check = pg_query_params($conn, "SELECT name FROM packages WHERE package_id = $1", [$service_id]);

if (pg_num_rows($check) == 0) {
    $_SESSION['error'] = "Service not found.";
    header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
    exit;
}

$service = pg_fetch_assoc($check);

// Delete associated prices first (foreign key constraint)
pg_query_params($conn, "DELETE FROM package_prices WHERE package_id = $1", [$service_id]);

// Delete the service
$result = pg_query_params($conn, "DELETE FROM packages WHERE package_id = $1", [$service_id]);

if ($result) {
    $_SESSION['success'] = "Service '" . htmlspecialchars($service['name']) . "' deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete service. Please try again.";
}

header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
exit;
?>
