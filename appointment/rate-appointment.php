<form action="rate-handler.php" method="POST">
  <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
  <label>Rate our service:</label>
  <select name="rating" required>
    <option value="5">⭐⭐⭐⭐⭐</option>
    <option value="4">⭐⭐⭐⭐</option>
    <option value="3">⭐⭐⭐</option>
    <option value="2">⭐⭐</option>
    <option value="1">⭐</option>
  </select><br>
  <label>Feedback:</label><br>
  <textarea name="feedback" rows="4" cols="30"></textarea><br>
  <button type="submit">Submit Rating</button>
</form>
