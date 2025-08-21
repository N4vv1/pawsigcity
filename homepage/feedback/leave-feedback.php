<?php
require '../../db.php';
session_start();

$id = $_GET['id'] ?? null;

if (!$id) {
  echo "Invalid appointment.";
  exit;
}
?>

<?php if (isset($_SESSION['error'])): ?>
  <p style="color: red;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
  <p style="color: green;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>


<h3>Rate Your Appointment</h3>
<p>Please rate your experience. <strong>Tell us what you liked or what we can improve!</strong></p>

<form action="rate-handler.php" method="POST" onsubmit="return validateFeedback();">
  <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($id) ?>">
  
  <label>Rating:</label>
  <select name="rating" required>
    <option value="">Choose</option>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
    <?php endfor; ?>
  </select><br><br>

  <label>Comments (optional but encouraged):</label><br>
  <textarea name="feedback" id="feedback" required rows="4" cols="50" placeholder="E.g. I loved how gentle the groomer was with my dog."></textarea><br><br>

  <button type="submit">Submit</button>
</form>

<script>
function validateFeedback() {
  const feedback = document.getElementById('feedback').value.trim();

  // If not empty, check if at least 10 words
  if (feedback !== '') {
    const wordCount = feedback.split(/\s+/).length;
    if (wordCount < 5) {
      alert("Please enter at least 5 words so we can better understand your experience.");
      return false;
    }
  }

  return true;
}
</script>
