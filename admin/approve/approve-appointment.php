<?php
require '../../db.php';
$result = $mysqli->query("SELECT a.*, p.name AS pet_name, u.name AS user_name
                          FROM appointments a
                          JOIN pets p ON a.pet_id = p.pet_id
                          JOIN users u ON a.user_id = u.id
                          WHERE is_approved = 0");

while ($row = $result->fetch_assoc()):
?>
  <div>
    <p><strong><?= $row['user_name'] ?>'s pet:</strong> <?= $row['pet_name'] ?> | Date: <?= $row['appointment_date'] ?></p>
    <a href="approve-handler.php?id=<?= $row['appointment_id'] ?>">Approve</a>
  </div>
<?php endwhile; ?>
