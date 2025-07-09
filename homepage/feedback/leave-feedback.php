<?php
require '../../db.php';
session_start();

$id = $_GET['id'] ?? null;

if (!$id) {
  echo "Invalid appointment.";
  exit;
}
?>

<h3>Rate Your Appointment</h3>
<form action="submit-feedback.php" method="POST">
  <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($id) ?>">
  
  <label>Rating:</label>
  <select name="rating" required>
    <option value="">Choose</option>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
    <?php endfor; ?>
  </select><br><br>

  <label>Comments (optional):</label><br>
  <textarea name="feedback" rows="4" cols="50"></textarea><br><br>

  <button type="submit">Submit</button>
</form>
