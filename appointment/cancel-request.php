<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$appointment_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$appointment_id || !is_numeric($appointment_id)) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: ../homepage/dashboard.php");
    exit;
}

// Fetch appointment
$stmt = $mysqli->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../homepage/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Request Cancellation</title>
</head>
<body>
  <h2>Cancel Appointment</h2>
  <form action="../appointment/cancel-appointment.php" method="POST">
    <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">
    <textarea name="cancel_reason" required placeholder="Reason for cancellation..."></textarea>
    <br><br>
    <button type="submit">Submit Cancellation Request</button>
  </form>
</body>
</html>
