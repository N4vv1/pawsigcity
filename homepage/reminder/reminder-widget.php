// Fetch unread reminders
$user_id = 1; // or $_SESSION['user_id']
$reminders = $mysqli->query("
  SELECT * FROM reminders 
  WHERE user_id = $user_id 
  AND status = 'unread' 
  ORDER BY reminder_date ASC
");
?>

<div>
  <h3>🔔 Notifications</h3>
  <ul>
    <?php while ($reminder = $reminders->fetch_assoc()): ?>
      <li>
        <?= htmlspecialchars($reminder['message']) ?> 
        <form method="post" action="mark-reminder-read.php" style="display:inline;">
          <input type="hidden" name="reminder_id" value="<?= $reminder['reminder_id'] ?>">
          <button type="submit">Mark as Read</button>
        </form>
      </li>
    <?php endwhile; ?>
  </ul>
</div>
