<?php
session_start();
require '../db.php'; // adjust path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = $_POST['pet_id'];
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $birthday = $_POST['birthday'];
    $color = $_POST['color'];
    $gender = $_POST['gender'];

    // Simple SQL update (you can improve security with prepared statements)
    $stmt = $mysqli->prepare("UPDATE pets SET name = ?, breed = ?, age = ?, birthday = ?, color = ?, gender = ? WHERE pet_id = ?");
    $stmt->bind_param("ssssssi", $name, $breed, $age, $birthday, $color, $gender, $pet_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Pet profile updated successfully!";
    } else {
        $_SESSION['success'] = "Failed to update pet profile.";
    }

    $stmt->close();
    $mysqli->close();

    // Redirect back to the profile page
    header("Location: pet-profile.php");
    exit;
}
?>
