<?php
require '../db.php'; // $conn = pg_connect(...)
session_start();

$id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$feedback = trim($_POST['feedback'] ?? '');

// ✅ Validate inputs
if (!$id || !$rating) {
    $_SESSION['error'] = "Missing appointment or rating.";
    header("Location: rate.php");
    exit;
}

// ✅ Optional: check minimum word count for feedback
if (!empty($feedback)) {
    $wordCount = str_word_count($feedback);
    if ($wordCount < 3) {
        $_SESSION['error'] = "Please provide at least 3 words in your feedback.";
        header("Location: leave-feedback.php?id=" . urlencode($id));
        exit;
    }
}

// ✅ Use pg_query_params to prevent SQL injection
$result = pg_query_params(
    $conn,
    "UPDATE appointments SET rating = $1, feedback = $2 WHERE appointment_id = $3",
    [$rating, $feedback, $id]
);

if ($result) {
    $_SESSION['success'] = "Thank you for your feedback!";
} else {
    $_SESSION['error'] = "❌ Failed to save feedback.";
}

header("Location: rate.php");
exit;
?>
