<?php
require '../../db.php';
session_start();

// Get inputs safely
$id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$feedback = trim($_POST['feedback'] ?? '');

// Always store inputs so they can be reused if needed
$_SESSION['feedback_appointment_id'] = $id;
$_SESSION['previous_feedback'] = $feedback;
$_SESSION['previous_rating'] = $rating;

// Validate appointment and rating
if (!$id || !$rating) {
    $_SESSION['error'] = "Missing appointment or rating.";
    $_SESSION['show_feedback_modal'] = true;
    header("Location: ../appointments.php");
    exit;
}

// Validate rating range
if ($rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Rating must be between 1 and 5 stars.";
    $_SESSION['show_feedback_modal'] = true;
    header("Location: ../appointments.php");
    exit;
}

// Check minimum word count if feedback is provided
if (!empty($feedback)) {
    $wordCount = str_word_count($feedback);
    if ($wordCount < 5) {
        $_SESSION['error'] = "Please provide at least 5 words in your feedback.";
        $_SESSION['show_feedback_modal'] = true;
        header("Location: ../appointments.php");
        exit;
    }
}

// PostgreSQL Update Query
$query = "UPDATE appointments SET rating = $1, feedback = $2 WHERE appointment_id = $3";
$result = pg_query_params($conn, $query, [$rating, $feedback, $id]);

// Execute and handle result
if ($result) {
    $_SESSION['success'] = "✅ Thank you for your feedback!";

    // Clear modal-related session variables
    unset($_SESSION['show_feedback_modal']);
    unset($_SESSION['feedback_appointment_id']);
    unset($_SESSION['previous_feedback']);
    unset($_SESSION['previous_rating']);

    // Run sentiment analysis script (optional - only if you have Python setup)
    $pythonPath = "C:\\Users\\Ivan\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
    $scriptPath = "E:\\xampp\\htdocs\\Purrfect-paws\\sentiment_analysis\\sentiment_analysis.py";
    
    // Check if Python and script exist before running
    if (file_exists($pythonPath) && file_exists($scriptPath)) {
        $command = "\"$pythonPath\" \"$scriptPath\" 2>&1";
        $output = shell_exec($command);

        // Log output to file
        if ($output) {
            file_put_contents(__DIR__ . '/sentiment_log.txt', date('Y-m-d H:i:s') . "\n" . $output . "\n\n", FILE_APPEND);
        }
    }
} else {
    $_SESSION['error'] = "❌ Something went wrong: " . pg_last_error($conn);
    $_SESSION['show_feedback_modal'] = true;
}

// Always redirect back to appointments page
header("Location: ../appointments.php");
exit;
?>