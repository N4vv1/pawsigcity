<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login/loginform.php");
        exit;
    }

    // Collect and sanitize inputs
    $user_id = $_SESSION['user_id'];
    $pet_id = $_POST['pet_id'] ?? null;
    $package_id = $_POST['package_id'] ?? null;
    $appointment_date = $_POST['appointment_date'] ?? null;
    $groomer_name = $_POST['groomer_name'] ?? null;
    $notes = $_POST['notes'] ?? null;

    // Validate required fields
    if (!$pet_id || !$package_id || !$appointment_date) {
        $_SESSION['error'] = "Please complete all required fields.";
        header("Location: book-appointment.php?pet_id=" . urlencode($pet_id));
        exit;
    }

    // Insert appointment
    $stmt = $mysqli->prepare("INSERT INTO appointments (user_id, pet_id, package_id, appointment_date, groomer_name, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $user_id, $pet_id, $package_id, $appointment_date, $groomer_name, $notes);

    if ($stmt->execute()) {
        $appointment_id = $stmt->insert_id;

        // Redirect to payment step
        $_SESSION['success'] = "Appointment booked successfully!";
        header("Location: ../pets/pet-profile.php?appointment_id=$appointment_id");
        exit;
    } else {
        $_SESSION['error'] = "Database error: " . $stmt->error;
        header("Location: book-appointment.php?pet_id=" . urlencode($pet_id));
        exit;
    }
} else {
    echo "Invalid request.";
}
