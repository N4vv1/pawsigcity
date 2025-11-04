<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: book-appointment.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id = intval($_POST['pet_id']);
$package_id = intval($_POST['package_id']);
$groomer_id = intval($_POST['groomer_id']);
$appointment_date = $_POST['appointment_date'];
$notes = trim($_POST['notes'] ?? '');

// ✅ Validate pet ownership
$pet_check = pg_query_params($conn, "
    SELECT pet_id FROM pets WHERE pet_id = $1 AND user_id = $2
", [$pet_id, $user_id]);

if (pg_num_rows($pet_check) === 0) {
    $_SESSION['error'] = "⚠️ Invalid pet selection.";
    header("Location: book-appointment.php?pet_id=$pet_id");
    exit;
}

// ✅ Validate package exists
$package_check = pg_query_params($conn, "
    SELECT price_id FROM package_prices WHERE price_id = $1
", [$package_id]);

if (pg_num_rows($package_check) === 0) {
    $_SESSION['error'] = "⚠️ Invalid package selection.";
    header("Location: book-appointment.php?pet_id=$pet_id");
    exit;
}

// ✅ FIXED: Validate groomer exists and is active (removed DATE check)
// Changed table name from 'groomers' to match your database
$groomer_check = pg_query_params($conn, "
    SELECT groomer_id FROM groomer
    WHERE groomer_id = $1 AND is_active = true
", [$groomer_id]);

if (pg_num_rows($groomer_check) === 0) {
    $_SESSION['error'] = "⚠️ Selected groomer is not available. Please choose another groomer.";
    header("Location: book-appointment.php?pet_id=$pet_id");
    exit;
}

// ✅ Validate appointment date
$date_obj = new DateTime($appointment_date);
$hour = (int)$date_obj->format('H');

if ($hour < 9 || $hour > 18) {
    $_SESSION['error'] = "⚠️ Appointments are only available between 9 AM and 6 PM.";
    header("Location: book-appointment.php?pet_id=$pet_id");
    exit;
}

// ✅ Check slot availability (max 5 appointments per hour)
$slot_check = pg_query_params($conn, "
    SELECT COUNT(*) as count 
    FROM appointments 
    WHERE DATE(appointment_date) = DATE($1)
    AND EXTRACT(HOUR FROM appointment_date) = EXTRACT(HOUR FROM $1::timestamp)
    AND status != 'cancelled'
", [$appointment_date]);

$slot_data = pg_fetch_assoc($slot_check);
if ($slot_data['count'] >= 5) {
    $_SESSION['error'] = "⚠️ This time slot is fully booked. Please choose another time.";
    header("Location: book-appointment.php?pet_id=$pet_id");
    exit;
}

// ✅ Insert appointment with groomer_id
$insert_query = "
    INSERT INTO appointments (pet_id, package_id, groomer_id, appointment_date, notes, status, created_at)
    VALUES ($1, $2, $3, $4, $5, 'confirmed', CURRENT_TIMESTAMP)
    RETURNING appointment_id
";

$result = pg_query_params($conn, $insert_query, [
    $pet_id, 
    $package_id, 
    $groomer_id, 
    $appointment_date, 
    $notes
]);

if ($result) {
    $appointment = pg_fetch_assoc($result);
    $_SESSION['success'] = "✅ Appointment #{$appointment['appointment_id']} confirmed successfully!";
    header("Location: ../homepage/appointments.php");
} else {
    $_SESSION['error'] = "❌ Failed to book appointment: " . pg_last_error($conn);
    header("Location: book-appointment.php?pet_id=$pet_id");
}
exit;
?>