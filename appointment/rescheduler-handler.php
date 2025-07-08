<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? null;
$new_date = $_POST['appointment_date'] ?? null;

if (!$appointment_id || !$new_date) {
    $_SESSION['error'] = "Missing appointment ID or date.";
    header("Location: reschedule-appointment.php?id=" . urlencode($appointment_id));
    exit;
}

// Update appointment date, reset approval and status
$stmt = $mysqli->prepare("UPDATE appointments 
                          SET appointment_date = ?, status = 'pending', is_approved = 0 
                          WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("sii", $new_date, $appointment_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Appointment successfully rescheduled. Awaiting admin approval.";
    header("Location: ../homepage/dashboard.php?rescheduled=1");
    exit;
} else {
    $_SESSION['error'] = "Failed to reschedule.";
    // ✅ Fix this line:
    header("Location: ../homepage/dashboard.php?id=" . urlencode($appointment_id));
    exit;
}
?>
