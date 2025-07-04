<?php
require '../db.php';

if (!isset($_GET['id'])) {
  echo "Invalid appointment ID.";
  exit;
}

$appointment_id = (int)$_GET['id'];
$result = $mysqli->query("SELECT * FROM appointments WHERE appointment_id = $appointment_id");
$appointment = $result->fetch_assoc();

if (!$appointment) {
  echo "Appointment not found.";
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Reschedule Appointment</title>
</head>
<body>

<h2>Reschedule Appointment</h2>

<form method="POST" action="reschedule-handler.php">
  <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">

  <label>New Date and Time:</label><br>
  <input type="datetime-local" name="appointment_date" value="<?= date('Y-m-d\TH:i', strtotime($appointment['appointment_date'])) ?>" required><br><br>

  <button type="submit">Update Appointment</button>
</form>

</body>
</html>
