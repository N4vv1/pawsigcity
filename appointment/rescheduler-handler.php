<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appointment_id = (int)$_POST['appointment_id'];
  $new_date = $mysqli->real_escape_string($_POST['appointment_date']);

  $mysqli->query("UPDATE appointments SET appointment_date = '$new_date' WHERE appointment_id = $appointment_id");

  header("Location: view-appointment.php?rescheduled=1");
  exit;
}
?>
