<?php
require '../db.php';

if ($_SESSION['role'] !== 'admin') {
  header("Location: ../homepage/main.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appointment_id = $_POST['appointment_id'];
  $notes = $mysqli->real_escape_string($_POST['notes']);
  $mysqli->query("UPDATE appointments SET notes = '$notes' WHERE appointment_id = $appointment_id");
  echo "Notes saved.";
}

$appointments = $mysqli->query("SELECT * FROM appointments ORDER BY appointment_date DESC");
?>

<h2>Log Session Notes</h2>
<form method="POST">
  <label for="appointment_id">Select Appointment:</label><br>
  <select name="appointment_id">
    <?php while ($row = $appointments->fetch_assoc()): ?>
      <option value="<?= $row['appointment_id'] ?>">#<?= $row['appointment_id'] ?> - <?= $row['appointment_date'] ?></option>
    <?php endwhile; ?>
  </select><br><br>

  <label>Notes:</label><br>
  <textarea name="notes" rows="5" cols="40"></textarea><br><br>

  <button type="submit">Save Notes</button>
</form>
