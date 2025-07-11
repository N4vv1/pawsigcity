<?php
require '../db.php';
session_start();

$id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$feedback = trim($_POST['feedback'] ?? '');

// Validate inputs
if (!$id || !$rating) {
    $_SESSION['error'] = "Missing appointment or rating.";
    header("Location: rate.php");
    exit;
}

// Optional: check minimum word count for feedback
if (!empty($feedback)) {
    $wordCount = str_word_count($feedback);
    if ($wordCount < 10) {
        $_SESSION['error'] = "Please provide at least 10 words in your feedback.";
        header("Location: leave-feedback.php?id=" . urlencode($id));
        exit;
    }
}

// Use prepared statement to prevent SQL injection
$stmt = $mysqli->prepare("UPDATE appointments SET rating = ?, feedback = ? WHERE appointment_id = ?");
$stmt->bind_param("isi", $rating, $feedback, $id);
$stmt->execute();
$stmt->close();

// Optional: add sentiment analyzer trigger here (future enhancement)

$_SESSION['success'] = "Thank you for your feedback!";
header("Location: rate.php");
exit;
?>
