<?php
session_start();
require '../db.php'; // adjust path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id   = intval($_POST['pet_id']); // sanitize to integer
    $name     = $_POST['name'] ?? '';
    $breed    = $_POST['breed'] ?? '';
    $age      = $_POST['age'] ?? '';
    $birthday = $_POST['birthday'] ?? null;
    $color    = $_POST['color'] ?? '';
    $gender   = $_POST['gender'] ?? '';

    // Use pg_query_params for safety
    $query = "
        UPDATE pets 
        SET name = $1, breed = $2, age = $3, birthday = $4, color = $5, gender = $6
        WHERE pet_id = $7
    ";
    $result = pg_query_params($conn, $query, [
        $name, $breed, $age, $birthday, $color, $gender, $pet_id
    ]);

    if ($result) {
        $_SESSION['success'] = "Pet profile updated successfully!";
    } else {
        $_SESSION['success'] = "Failed to update pet profile: " . pg_last_error($conn);
    }

    // Redirect back to the profile page
    header("Location: pet-profile.php");
    exit;
}
