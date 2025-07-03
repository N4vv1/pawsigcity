<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = 1; // Replace with $_SESSION['user_id'] when login is active
    $pet_id = $_POST['pet_id'];
    $package_id = $_POST['package _id']; // still using service_id field name for simplicity
    $appointment_date = $_POST['appointment_date'];
    $groomer_name = $_POST['groomer_name'] ?? null;
    $notes = $_POST['notes'] ?? null;

    $stmt = $mysqli->prepare("INSERT INTO appointments (user_id, pet_id, service_id, appointment_date, groomer_name, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $user_id, $pet_id, $package_id, $appointment_date, $groomer_name, $notes);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment successfully booked!";
    } else {
        $_SESSION['success'] = "Something went wrong: " . $stmt->error;
    }

    header("Location: appointment_form.php");
    exit;
}
?>
