<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$result = $mysqli->prepare("SELECT a.*, p.name AS pet_name, pk.name AS package_name
                            FROM appointments a
                            JOIN pets p ON a.pet_id = p.pet_id
                            JOIN packages pk ON a.package_id = pk.id
                            WHERE a.user_id = ?
                            ORDER BY a.appointment_date DESC");
$result->bind_param("i", $user_id);
$result->execute();
$appointments = $result->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>User Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
    table { width: 100%; background: white; border-collapse: collapse; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background: #f1f1f1; }
    .badge.approved { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 5px; }
    .badge.pending { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 5px; }
    .button {
      padding: 6px 12px; background: #A8E6CF; border-radius: 5px;
      text-decoration: none; margin: 0 2px; display: inline-block;
    }
    .button:hover { background: #FFD3B6; }
  </style>
</head>
<body>

<h2>🐾 Your Appointments</h2>

<?php if (isset($_SESSION['success'])): ?>
  <p style="color: green;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Pet</th>
      <th>Service</th>
      <th>Date & Time</th>
      <th>Approval</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $appointments->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['pet_name']) ?></td>
        <td><?= htmlspecialchars($row['package_name']) ?></td>
        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
        <td>
          <?php if ($row['is_approved']): ?>
            <span class="badge approved">Approved</span>
          <?php else: ?>
            <span class="badge pending">Waiting for Approval</span>
          <?php endif; ?>
        </td>
        <td><?= ucfirst($row['status']) ?></td>
        <td>
          <a class="button" href="../appointment/reschedule-appointment.php?id=<?= $row['appointment_id'] ?>">Reschedule</a>
          <a class="button" href="../appointment/cancel-request.php?id=<?= $row['appointment_id'] ?>" onclick="return confirm('Are you sure you want to cancel this appointment?');">Cancel</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

</body>
</html>
