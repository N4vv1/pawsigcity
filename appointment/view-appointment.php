<?php
require '../db.php';

// Join multiple tables to display full appointment info
$query = "
  SELECT a.*, 
       u.full_name AS client_name,
       p.name AS pet_name,
       p.breed AS pet_breed,
       pk.name AS package_name
  FROM appointments a
  JOIN users u ON a.user_id = u.user_id
  JOIN pets p ON a.pet_id = p.pet_id
  JOIN packages pk ON a.package_id = pk.id
  ORDER BY a.appointment_date DESC
";

$appointments = $mysqli->query($query);
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
  </style>
</head>
<body>

<h2>📋 All Appointments</h2>

<?php if (isset($_GET['rescheduled'])): ?>
  <p style="color: green; font-weight: bold;">Appointment successfully rescheduled!</p>
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
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $appointments->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['client_name']) ?></td>
        <td><?= htmlspecialchars($row['pet_name']) ?></td>
        <td>
          <?php
            $breed = $mysqli->query("SELECT breed FROM pets WHERE pet_id = " . $row['pet_id'])->fetch_assoc();
            echo htmlspecialchars($breed['breed']);
          ?>
        </td>
        <td><?= htmlspecialchars($row['package_name']) ?></td>
        <td><?= htmlspecialchars($row['pet_breed']) ?></td>
        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
        <td><?= ucfirst($row['status']) ?></td>
        <td><?= htmlspecialchars($row['groomer_name']) ?: 'Not assigned' ?></td>
        <td><?= nl2br(htmlspecialchars($row['notes'] ?? '')) ?></td>
        <td><a href="reschedule-appointment.php?id=<?= $row['appointment_id'] ?>">🗓 Reschedule</a></td>

      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

</body>
</html>
