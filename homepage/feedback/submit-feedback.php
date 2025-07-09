<?php
require '../../db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $feedback = trim($_POST['feedback'] ?? '');

    if (!$appointment_id || !$rating) {
        $_SESSION['success'] = "Missing required fields.";
        header("Location: http://localhost/purrfect-paws/homepage/appointments.php");
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE appointments SET rating = ?, feedback = ? WHERE appointment_id = ?");
    $stmt->bind_param("isi", $rating, $feedback, $appointment_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Thank you for your feedback!";
    } else {
        $_SESSION['success'] = "❌ Failed to submit feedback.";
    }

    header("Location: http://localhost/purrfect-paws/homepage/appointments.php");
    exit;
}
?>
