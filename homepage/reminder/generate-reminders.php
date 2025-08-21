// Check for upcoming appointments in the next 2 days
$today = date('Y-m-d H:i:s');
$two_days = date('Y-m-d H:i:s', strtotime('+2 days'));

$appointments = $mysqli->query("
  SELECT a.*, p.name AS pet_name 
  FROM appointments a 
  JOIN pets p ON a.pet_id = p.pet_id 
  WHERE a.appointment_date BETWEEN '$today' AND '$two_days' 
    AND a.status = 'confirmed'
");

while ($appt = $appointments->fetch_assoc()) {
  $msg = "Reminder: Grooming appointment for " . $appt['pet_name'] . 
         " on " . date("M d, Y h:i A", strtotime($appt['appointment_date']));

  // Check if reminder already exists
  $check = $mysqli->query("
    SELECT * FROM reminders 
    WHERE pet_id = {$appt['pet_id']} 
    AND DATE(reminder_date) = DATE('{$appt['appointment_date']}')
  ");

  if ($check->num_rows === 0) {
    $mysqli->query("
      INSERT INTO reminders (user_id, pet_id, message, reminder_date) 
      VALUES (
        {$appt['user_id']}, 
        {$appt['pet_id']}, 
        '{$mysqli->real_escape_string($msg)}', 
        '{$appt['appointment_date']}'
      )
    ");
  }
}
