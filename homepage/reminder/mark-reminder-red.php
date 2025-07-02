<?php
require '../db.php'; // Adjust path if needed

if (isset($_GET['id'])) {
    $reminder_id = (int) $_GET['id']; // Cast to int for safety

    // Update the reminder to mark it as read
    $mysqli->query("UPDATE reminders SET is_read = 1 WHERE reminder_id = $reminder_id");

    // Redirect back to the page where reminders are shown
    header("Location: homepage.php");
    exit;
} else {
    echo "Invalid reminder ID.";
}
