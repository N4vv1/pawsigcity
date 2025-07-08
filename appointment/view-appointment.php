<?php
require '../db.php';

// Join multiple tables to display full appointment info
$query = "
  SELECT a.*, 
         u.full_name AS client_name,
         p.name AS pet_name,
         p.breed AS pet_breed,
         pk.name AS package_name,
         a.is_approved
  FROM appointments a
  JOIN users u ON a.user_id = u.user_id
  JOIN pets p ON a.pet_id = p.pet_id
  JOIN packages pk ON a.package_id = pk.id
  ORDER BY a.appointment_date DESC
";

$appointments = $mysqli->query($query);

// Reminder logic: show how many unapproved appointments are today
$today = date('Y-m-d');
$upcoming = $mysqli->query("SELECT * FROM appointments WHERE DATE(appointment_date) = '$today' AND is_approved = 0");
?>

<!DOCTYPE html>
<html>
<head>
  <title>View All Appointments</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f2f2f2; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
    th { background-color: #eaeaea; }
    .reminder {
      background: #fff3cd;
      padding: 15px;
      margin-bottom: 20px;
      border-left: 5px solid #ffa000;
    }
    .approved { color: green; font-weight: bold; }
    .pending { color: #ff6600; font-weight: bold; }
    a.button {
      padding: 6px 12px;
      background: #A8E6CF;
      text-decoration: none;
      border-radius: 4px;
      font-size: 0.9rem;
    }
    a.button:hover {
      background: #FFD3B6;
    }
  </style>
</head>
<body>

<h2>📋 All Appointments</h2>

<?php if (isset($_GET['rescheduled'])): ?>
  <p style="color: green; font-weight: bold;">Appointment successfully rescheduled!</p>
<?php endif; ?>

<?php if ($upcoming->num_rows > 0): ?>
  <div class="reminder">
    <strong>⏰ Reminder:</strong> You have <?= $upcoming->num_rows ?> unapproved appointment(s) today.
  </div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Client</th>
      <th>Pet</th>
      <th>Breed</th>
      <th>Service</th>
      <th>Date & Time</th>
      <th>Status</th>
      <th>Groomer</th>
      <th>Notes</th>
      <th>Approval</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $appointments->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['client_name']) ?></td>
        <td><?= htmlspecialchars($row['pet_name']) ?></td>
        <td><?= htmlspecialchars($row['pet_breed']) ?></td>
        <td><?= htmlspecialchars($row['package_name']) ?></td>
        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
        <td><?= ucfirst($row['status']) ?></td>
        <td><?= htmlspecialchars($row['groomer_name']) ?: 'Not assigned' ?></td>
        <td><?= nl2br(htmlspecialchars($row['notes'] ?? '')) ?></td>
        <td>
          <?php if ($row['is_approved']): ?>
            <span class="approved">✅ Approved</span>
          <?php else: ?>
            <span class="pending">❗ Pending</span><br>
            <a href="approve-handler.php?id=<?= $row['appointment_id'] ?>" class="button">✔ Approve</a>
          <?php endif; ?>
        </td>
        <td>
          <a href="reschedule-appointment.php?id=<?= $row['appointment_id'] ?>" class="button">🗓 Reschedule</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

</body>
</html>
