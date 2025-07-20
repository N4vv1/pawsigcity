<?php
require_once '../../db.php';

date_default_timezone_set('Asia/Manila'); // Make sure this matches your timezone

$now = new DateTime();

$query = $mysqli->query("
  SELECT appointment_id, appointment_date
  FROM appointments
  WHERE status = 'confirmed'
");

$affected = 0;

while ($row = $query->fetch_assoc()) {
    $appointmentTime = new DateTime($row['appointment_date']);
    $graceEnd = clone $appointmentTime;
    $graceEnd->modify('+15 minutes');

    if ($now > $graceEnd) {
        $id = $row['appointment_id'];

        $mysqli->query("UPDATE appointments SET status = 'no_show' WHERE appointment_id = $id");
        $affected++;
    }
}

header("Location: ../admin_dashboard/home.php?show=appointments&noshows=$affected");
exit;
