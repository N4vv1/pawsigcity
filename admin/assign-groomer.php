<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appointment_id = $_POST['appointment_id'];
  $groomer = $mysqli->real_escape_string($_POST['groomer_name']);
  $mysqli->query("UPDATE appointments SET groomer_name = '$groomer' WHERE appointment_id = $appointment_id");
  echo "Groomer assigned.";
}

$appointments = $mysqli->query("SELECT * FROM appointments WHERE groomer_name IS NULL OR groomer_name = ''");
?>

<h2>Assign Groomer</h2>
<form method="POST">
  <label for="appointment_id">Select Appointment:</label><br>
  <select name="appointment_id">
    <?php while ($row = $appointments->fetch_assoc()): ?>
      <option value="<?= $row['appointment_id'] ?>">#<?= $row['appointment_id'] ?> - <?= $row['appointment_date'] ?></option>
    <?php endwhile; ?>
  </select><br><br>

  <label for="groomer_name">Groomer Name:</label><br>
  <input type="text" name="groomer_name" required><br><br>

  <button type="submit">Assign</button>
</form>
