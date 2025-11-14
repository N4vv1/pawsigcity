<?php
session_start();
require '../db.php';

if (isset($_POST['pet_id'])) {
    $pet_id = intval($_POST['pet_id']); // sanitize

    // Delete related data first due to foreign key constraints
    pg_query_params($conn, "DELETE FROM health_info WHERE pet_id = $1", [$pet_id]);
    pg_query_params($conn, "DELETE FROM behavior_preferences WHERE pet_id = $1", [$pet_id]);
    pg_query_params($conn, "DELETE FROM grooming_history WHERE pet_id = $1", [$pet_id]);

    // Then delete the pet itself
    $delete = pg_query_params($conn, "DELETE FROM pets WHERE pet_id = $1", [$pet_id]);

    if ($delete) {
        $_SESSION['notification'] = "Pet deleted successfully!";
        $_SESSION['notification_type'] = 'success';
    } else {
        $_SESSION['notification'] = "Error deleting pet: " . pg_last_error($conn);
        $_SESSION['notification_type'] = 'error';
    }
}

header("Location: pet-profile.php");
exit;