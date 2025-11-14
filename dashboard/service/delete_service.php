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

$service_id = trim($_GET['id']);

// Check if service exists and is not already archived
$check = pg_query_params($conn, "SELECT name FROM packages WHERE package_id = $1 AND (deleted_at IS NULL OR deleted_at = '')", [$service_id]);

if (pg_num_rows($check) == 0) {
    $_SESSION['error'] = "Service not found or already archived.";
    header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
    exit;
}

$service = pg_fetch_assoc($check);

// Archive the service by setting deleted_at timestamp
$result = pg_query_params($conn, "UPDATE packages SET deleted_at = NOW() WHERE package_id = $1", [$service_id]);

// Also archive associated prices
pg_query_params($conn, "UPDATE package_prices SET deleted_at = NOW() WHERE package_id = $1 AND (deleted_at IS NULL OR deleted_at = '')", [$service_id]);

if ($result) {
    $_SESSION['success'] = "Service '" . htmlspecialchars($service['name']) . "' archived successfully.";
} else {
    $_SESSION['error'] = "Failed to archive service. Please try again.";
}

header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
exit;
?>