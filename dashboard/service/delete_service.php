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

// Check if deleted_at column exists
$column_check = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name='packages' AND column_name='deleted_at'");
$has_deleted_at = $column_check && pg_num_rows($column_check) > 0;

// Check if service exists
if ($has_deleted_at) {
    $check = pg_query_params($conn, "SELECT name FROM packages WHERE package_id = $1 AND deleted_at IS NULL", [$service_id]);
} else {
    $check = pg_query_params($conn, "SELECT name FROM packages WHERE package_id = $1", [$service_id]);
}

if (!$check || pg_num_rows($check) == 0) {
    $_SESSION['error'] = $has_deleted_at ? "Service not found or already archived." : "Service not found.";
    header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
    exit;
}

$service = pg_fetch_assoc($check);

if ($has_deleted_at) {
    // Archive the service by setting deleted_at timestamp
    $result = pg_query_params($conn, "UPDATE packages SET deleted_at = NOW() WHERE package_id = $1", [$service_id]);

    // Also archive associated prices if they have deleted_at column
    $price_column_check = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name='package_prices' AND column_name='deleted_at'");
    if ($price_column_check && pg_num_rows($price_column_check) > 0) {
        pg_query_params($conn, "UPDATE package_prices SET deleted_at = NOW() WHERE package_id = $1 AND deleted_at IS NULL", [$service_id]);
    }
    
    $success_message = "Service '" . htmlspecialchars($service['name']) . "' archived successfully.";
} else {
    // Delete associated prices first (foreign key constraint)
    pg_query_params($conn, "DELETE FROM package_prices WHERE package_id = $1", [$service_id]);

    // Delete the service
    $result = pg_query_params($conn, "DELETE FROM packages WHERE package_id = $1", [$service_id]);
    
    $success_message = "Service '" . htmlspecialchars($service['name']) . "' deleted successfully.";
}

if ($result) {
    $_SESSION['success'] = $success_message;
} else {
    $_SESSION['error'] = "Failed to process service. Please try again.";
}

header("Location: https://pawsigcity.onrender.com/dashboard/service/services.php");
exit;
?>