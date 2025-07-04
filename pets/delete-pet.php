<?php
session_start();
require '../db.php';

if (isset($_POST['pet_id'])) {
    $pet_id = intval($_POST['pet_id']);

    // Delete related data first due to foreign key constraints
    $mysqli->query("DELETE FROM health_info WHERE pet_id = $pet_id");
    $mysqli->query("DELETE FROM behavior_preferences WHERE pet_id = $pet_id");
    $mysqli->query("DELETE FROM grooming_history WHERE pet_id = $pet_id");

    // Then delete the pet itself
    $delete = $mysqli->query("DELETE FROM pets WHERE pet_id = $pet_id");

    if ($delete) {
        $_SESSION['success'] = "Pet deleted successfully.";
    } else {
        $_SESSION['success'] = "Error deleting pet.";
    }
}

header("Location: pet-profile.php");
exit;
