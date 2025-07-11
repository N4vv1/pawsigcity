<?php
require '../../db.php';
session_start();

$id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$feedback = trim($_POST['feedback'] ?? '');

// Validate required fields
if (!$id || !$rating) {
    $_SESSION['error'] = "Missing appointment or rating.";
    header("Location: leave-feedback.php?id=" . urlencode($id));
    exit;
}

// Check minimum word count for feedback (at least 10 words)
if (!empty($feedback)) {
    $wordCount = str_word_count($feedback);
    if ($wordCount < 10) {
        $_SESSION['error'] = "Please provide at least 10 words in your feedback.";
        header("Location: leave-feedback.php?id=" . urlencode($id));
        exit;
    }
}

// Save to database
$stmt = $mysqli->prepare("UPDATE appointments SET rating = ?, feedback = ? WHERE appointment_id = ?");
$stmt->bind_param("isi", $rating, $feedback, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "✅ Thank you for your feedback!";

    // ✅ Trigger sentiment analysis script
    $output = shell_exec("\"C:\\Users\\Ivan\\AppData\\Local\\Programs\\Python\\Python313\\python.exe\" \"E:\\xampp\\htdocs\\Purrfect-paws\\sentiment_analysis\\sentiment_analysis.py\" 2>&1");
file_put_contents(__DIR__ . '/sentiment_log.txt', $output);

} else {
    $_SESSION['error'] = "❌ Something went wrong while submitting your feedback.";
}

$stmt->close();

// Redirect to confirmation or appointment page
header("Location: http://localhost/Purrfect-paws/homepage/appointments.php");
exit;
?>
