<?php
session_start();
require '../db.php';

// ✅ Include PHPMailer and email function
require_once './vendor/autoload.php';
require_once 'send-booking-confirmation.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ DEBUG: Log all incoming data
error_log("=== APPOINTMENT HANDLER STARTED ===");
error_log("POST data: " . print_r($_POST, true));
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login/loginform.php");
        exit;
    }

    // ✅ Collect and sanitize inputs - DON'T use intval() on UUIDs!
    $user_id = $_SESSION['user_id'];
    $pet_id = trim($_POST['pet_id'] ?? '');
    $package_id = trim($_POST['package_id'] ?? '');
    $groomer_id = trim($_POST['groomer_id'] ?? '');
    $recommended_package = trim($_POST['recommended_package'] ?? '');
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    error_log("Sanitized data:");
    error_log("  pet_id: '$pet_id'");
    error_log("  package_id: '$package_id'");
    error_log("  groomer_id: '$groomer_id'");
    error_log("  appointment_date: '$appointment_date'");

    // Validate required fields
    if (!$pet_id || !$package_id || !$appointment_date || !$groomer_id) {
        error_log("ERROR: Missing required fields");
        $_SESSION['error'] = "Please complete all required fields including groomer selection.";
        header("Location: book-appointment.php?pet_id=" . urlencode($pet_id));
        exit;
    }

    // ✅ Check if the pet belongs to the logged-in user
    $check_pet = pg_query_params(
        $conn,
        "SELECT 1 FROM pets WHERE pet_id = $1 AND user_id = $2",
        [$pet_id, $user_id]
    );

    if (!$check_pet || pg_num_rows($check_pet) === 0) {
        error_log("ERROR: Pet validation failed");
        $_SESSION['error'] = "Invalid pet or unauthorized access.";
        header("Location: book-appointment.php");
        exit;
    }

    error_log("✓ Pet validation passed");

    // ✅ Fetch groomer name from groomer table
    $groomer_query = pg_query_params(
        $conn,
        "SELECT groomer_name FROM groomer WHERE groomer_id = $1",
        [$groomer_id]
    );

    if (!$groomer_query || pg_num_rows($groomer_query) === 0) {
        error_log("ERROR: Groomer not found");
        $_SESSION['error'] = "Invalid groomer selection.";
        header("Location: book-appointment.php?pet_id=" . urlencode($pet_id));
        exit;
    }

    $groomer_row = pg_fetch_assoc($groomer_query);
    $groomer_name = $groomer_row['groomer_name'];
    
    error_log("Groomer found: $groomer_name");

    // ✅ Insert appointment into database
    $insert_query = "
        INSERT INTO appointments 
        (user_id, pet_id, package_id, appointment_date, groomer_id, groomer_name, notes, recommended_package) 
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
        RETURNING appointment_id
    ";

    error_log("Executing INSERT query...");
    
    $result = pg_query_params($conn, $insert_query, [
        $user_id, 
        $pet_id, 
        $package_id, 
        $appointment_date, 
        $groomer_id, 
        $groomer_name, 
        $notes, 
        $recommended_package
    ]);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $appointment_id = $row['appointment_id'];
        
        error_log("✓ Appointment created successfully! ID: $appointment_id");

        // ✅ NEW: Send confirmation email
        error_log("Attempting to send confirmation email...");
        $email_result = sendBookingConfirmation($conn, $appointment_id);
        
        if ($email_result['success']) {
            error_log("✓ Confirmation email sent successfully to: " . $email_result['recipient']);
            $_SESSION['success'] = "🎉 Appointment booked successfully! A confirmation email has been sent to your email address.";
        } else {
            error_log("✗ Failed to send confirmation email: " . $email_result['message']);
            // Still show success since appointment was created
            $_SESSION['success'] = "✓ Appointment booked successfully! However, we couldn't send the confirmation email. Please check your appointments page.";
        }

        // Run Python script for recommendation (optional)
        $pythonPath = "C:\\Users\\Ivan\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
        $scriptPath = "E:\\xampp\\htdocs\\Purrfect-paws\\recommendation\\recommend.py";
        $command = "\"$pythonPath\" \"$scriptPath\" 2>&1";
        $output = shell_exec($command);

        // Redirect to appointment confirmation page
        header("Location: ../homepage/appointments.php?appointment_id=$appointment_id");
        exit;
        
    } else {
        $error = pg_last_error($conn);
        error_log("ERROR: Database insert failed - $error");
        $_SESSION['error'] = "Database error: " . $error;
        header("Location: book-appointment.php?pet_id=" . urlencode($pet_id));
        exit;
    }

} else {
    error_log("ERROR: Not a POST request");
    echo "Invalid request.";
}
?>