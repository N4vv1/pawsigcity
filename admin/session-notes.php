<?php
require '../db.php'; // sets $conn with pg_connect

//if ($_SESSION['role'] !== 'admin') {
//   header("Location: http://localhost/purrfect-paws/homepage/login/loginform.php");
//   exit;
//}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appointment_id = $_POST['appointment_id'];
  $notes = $_POST['notes'];

  // Update notes safely with pg_query_params
  $update = pg_query_params(
    $conn,
    "UPDATE appointments SET notes = $1 WHERE appointment_id = $2",
    [$notes, $appointment_id]
  );

  if ($update) {
    echo "Notes saved.";
  } else {
    echo "Error: " . pg_last_error($conn);
  }
}

// Fetch all appointments
$appointments = pg_query($conn, "SELECT * FROM appointments ORDER BY appointment_date DESC");
?>

<h2>Log Session Notes</h2>
<form method="POST">
  <label for="appointment_id">Select Appointment:</label><br>
  <select name="appointment_id">
    <?php while ($row = pg_fetch_assoc($appointments)): ?>
      <option value="<?= htmlspecialchars($row['appointment_id']) ?>">
        #<?= htmlspecialchars($row['appointment_id']) ?> - <?= htmlspecialchars($row['appointment_date']) ?>
      </option>
    <?php endwhile; ?>
  </select><br><br>

  <label>Notes:</label><br>
  <textarea name="notes" rows="5" cols="40"></textarea><br><br>

  <button type="submit">Save Notes</button>
</form>
