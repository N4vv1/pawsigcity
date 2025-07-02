<?php
session_start();
require '../db.php';

$user_id = 1; // Replace with $_SESSION['user_id'] when using login

$pet_id = $_POST['pet_id'];
$service_id = $_POST['service_id'];
$appointment_date = $_POST['appointment_date'];
$groomer_name = $_POST['groomer_name'] ?? null;
$notes = $_POST['notes'] ?? null;

// Use prepared statement to prevent SQL injection
$stmt = $mysqli->prepare("INSERT INTO appointments (user_id, pet_id, service_id, appointment_date, groomer_name, notes) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiisss", $user_id, $pet_id, $service_id, $appointment_date, $groomer_name, $notes);

if ($stmt->execute()) {
  $_SESSION['success'] = "Appointment successfully booked!";
  header("Location: appointment-form.php");
  exit();
} else {
  echo "Error: " . $stmt->error;
}
