<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Check if ID is provided
if (isset($_GET['id'])) {
    $groomer_id = intval($_GET['id']);

    // Delete the groomer
    $result = pg_query_params($conn, "DELETE FROM groomer WHERE groomer_id = $1", [$groomer_id]);

    if ($result) {
        $_SESSION['success'] = "Groomer deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete groomer: " . pg_last_error($conn);
    }
} else {
    $_SESSION['error'] = "No groomer ID provided.";
}

// Redirect back to the groomer accounts page
header("Location: groomer_accounts.php");
exit;
?>
