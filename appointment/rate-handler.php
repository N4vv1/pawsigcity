<?php
require '../db.php';  // Changed from ../../db.php
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
    header("Location: ../homepage/appointments.php");  // Updated path
    exit;
}

// Validate rating range
if ($rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Rating must be between 1 and 5 stars.";
    $_SESSION['show_feedback_modal'] = true;
    header("Location: ../homepage/appointments.php");  // Updated path
    exit;
}

// Check minimum word count if feedback is provided
if (!empty($feedback)) {
    $wordCount = str_word_count($feedback);
    if ($wordCount < 5) {
        $_SESSION['error'] = "Please provide at least 5 words in your feedback.";
        $_SESSION['show_feedback_modal'] = true;
        header("Location: ../homepage/appointments.php");  // Updated path
        exit;
    }
}

// PostgreSQL Update Query - Set sentiment to 'pending' initially
$query = "UPDATE appointments SET rating = $1, feedback = $2, sentiment = 'pending' WHERE appointment_id = $3";
$result = pg_query_params($conn, $query, [$rating, $feedback, $id]);

// Execute and handle result
if ($result) {
    // ✅ Automatically analyze sentiment using Flask API
    $sentiment = analyzeSentiment($feedback, $rating, $id);
    
    // Update sentiment in database
    if ($sentiment) {
        $sentiment_query = "UPDATE appointments SET sentiment = $1 WHERE appointment_id = $2";
        pg_query_params($conn, $sentiment_query, [$sentiment, $id]);
        error_log("✅ Sentiment analyzed: $sentiment for appointment #$id");
    } else {
        error_log("⚠️ Sentiment analysis failed for appointment #$id. Marked as 'pending'.");
    }
    
    $_SESSION['success'] = "✅ Thank you for your feedback!";

    // Clear modal-related session variables
    unset($_SESSION['show_feedback_modal']);
    unset($_SESSION['feedback_appointment_id']);
    unset($_SESSION['previous_feedback']);
    unset($_SESSION['previous_rating']);
} else {
    $_SESSION['error'] = "❌ Something went wrong: " . pg_last_error($conn);
    $_SESSION['show_feedback_modal'] = true;
}

// Always redirect back to appointments page
header("Location: ../homepage/appointments.php");  // Updated path
exit;

/**
 * Analyze sentiment using Flask API (VADER)
 * @param string $feedback_text The feedback to analyze
 * @param int $rating Star rating (1-5)
 * @param int $appointment_id For logging purposes
 * @return string|null Returns sentiment (positive/neutral/negative) or null if failed
 */
function analyzeSentiment($feedback_text, $rating, $appointment_id) {
    // Skip analysis if feedback is empty
    if (empty(trim($feedback_text))) {
        return 'neutral';
    }
    
    // Use Render deployed API URL
    $api_url = 'https://pawsigcity-1.onrender.com/sentiment';  // Updated to your Render URL
    
    // Prepare data
    $data = json_encode([
        'feedback' => $feedback_text
    ]);
    
    // Initialize cURL
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 second timeout for Render
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($curl_error) {
        error_log("❌ cURL Error for Appointment #$appointment_id: $curl_error");
        return null;
    }
    
    // Check HTTP status
    if ($http_code != 200) {
        error_log("❌ API returned HTTP $http_code for Appointment #$appointment_id");
        return null;
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if (!isset($result['sentiment'])) {
        error_log("❌ Invalid API response for Appointment #$appointment_id");
        return null;
    }
    
    $sentiment = $result['sentiment'];
    $compound_score = $result['compound_score'] ?? 0;
    
    // Optional: Override sentiment based on rating if there's strong disagreement
    if ($rating <= 2 && $sentiment === 'positive') {
        error_log("⚠️ Overriding sentiment from 'positive' to 'negative' due to low rating ($rating stars) for appointment #$appointment_id");
        $sentiment = 'negative';
    } elseif ($rating >= 4 && $sentiment === 'negative') {
        error_log("⚠️ Overriding sentiment from 'negative' to 'positive' due to high rating ($rating stars) for appointment #$appointment_id");
        $sentiment = 'positive';
    }
    
    // Log successful analysis
    error_log("✅ VADER Analysis for Appointment #$appointment_id: sentiment=$sentiment, compound=$compound_score, rating=$rating");
    
    return $sentiment;
}
?>