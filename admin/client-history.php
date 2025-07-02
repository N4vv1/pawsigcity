<?php
require '../db.php';

$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
  $history = $mysqli->query("
    SELECT a.*, p.name AS pet_name, s.name AS service_name 
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.user_id = $user_id
    ORDER BY a.appointment_date DESC
  ");
}
?>

<h2>Client History</h2>
<?php if ($user_id && $history->num_rows): ?>
  <ul>
    <?php while ($row = $history->fetch_assoc()): ?>
      <li>
        <?= $row['appointment_date'] ?> - <?= $row['pet_name'] ?> (<?= $row['service_name'] ?>) - Status: <?= $row['status'] ?>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?>
  <p>No history found.</p>
<?php endif; ?>
