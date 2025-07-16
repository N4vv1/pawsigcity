<?php
require '../../db.php';
session_start();

// Get inputs safely
$id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$feedback = trim($_POST['feedback'] ?? '');

// Validate appointment and rating
if (!$id || !$rating) {
    $_SESSION['error'] = "Missing appointment or rating.";
    header("Location: leave-feedback.php?id=" . urlencode($id));
    exit;
}

// Check minimum word count if feedback is provided
if (!empty($feedback)) {
    $wordCount = str_word_count($feedback);
    if ($wordCount < 10) {
        $_SESSION['error'] = "Please provide at least 10 words in your feedback.";
        header("Location: leave-feedback.php?id=" . urlencode($id));
        exit;
    }
}

// Prepare and bind statement
$stmt = $mysqli->prepare("UPDATE appointments SET rating = ?, feedback = ? WHERE appointment_id = ?");
$stmt->bind_param("isi", $rating, $feedback, $id);

// Execute and handle result
if ($stmt->execute()) {
    $_SESSION['success'] = "✅ Thank you for your feedback!";

    // Run sentiment analysis script
    $pythonPath = "C:\\Users\\Ivan\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
    $scriptPath = "E:\\xampp\\htdocs\\Purrfect-paws\\sentiment_analysis\\sentiment_analysis.py";
    $command = "\"$pythonPath\" \"$scriptPath\" 2>&1";
    $output = shell_exec($command);

    // Log output to file
    file_put_contents(__DIR__ . '/sentiment_log.txt', htmlspecialchars($output));
} else {
    $_SESSION['error'] = "❌ Something went wrong while submitting your feedback.";
}

$stmt->close();

// Redirect back to appointments page
header("Location: http://localhost/Purrfect-paws/homepage/appointments.php");
exit;
?>
