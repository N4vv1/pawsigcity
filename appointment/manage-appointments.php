<?php
require '../db.php';

// Fetch all appointments
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

// Count pending approvals and cancellation requests
$today = date('Y-m-d');
$pendingApprovals = $mysqli->query("SELECT * FROM appointments WHERE DATE(appointment_date) = '$today' AND is_approved = 0");
$pendingCancellations = $mysqli->query("SELECT * FROM appointments WHERE cancel_requested = 1");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard - Appointments</title>
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
    .cancel-request { color: red; font-weight: bold; }
    .button {
      padding: 6px 10px;
      background: #A8E6CF;
      text-decoration: none;
      border-radius: 4px;
      font-size: 0.9rem;
      margin-right: 5px;
      display: inline-block;
    }
    .button:hover { background: #FFD3B6; }
    .danger { background: #FFB6B6; }
  </style>
  <script>
    function confirmAction(msg) {
      return confirm(msg);
    }
  </script>
</head>
<body>

<h2>📋 Admin - Manage Appointments</h2>

<?php if (isset($_GET['cancel'])): ?>
  <div style="padding: 10px; background: #e0ffe0; border-left: 5px solid green; margin-bottom: 10px;">
    <?= $_GET['cancel'] === 'approve' ? '✅ Cancellation approved.' : '❌ Cancellation rejected.' ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
  <div style="padding: 10px; background: #ffe0e0; border-left: 5px solid red; margin-bottom: 10px;">
    <?= $_GET['deleted'] == 1 ? '🗑 Appointment deleted successfully.' : '❌ Failed to delete appointment.' ?>
  </div>
<?php endif; ?>

<?php if ($pendingApprovals->num_rows > 0): ?>
  <div class="reminder">
    ⏰ You have <?= $pendingApprovals->num_rows ?> unapproved appointment(s) scheduled today.
  </div>
<?php endif; ?>

<?php if ($pendingCancellations->num_rows > 0): ?>
  <div class="reminder" style="border-left: 5px solid red;">
    ❗ You have <?= $pendingCancellations->num_rows ?> cancellation request(s) to review.
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
      <th>Approval</th>
      <th>Cancel Reason</th>
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
        <td><?= htmlspecialchars($row['pet_breed']) ?></td>
        <td><?= htmlspecialchars($row['package_name']) ?></td>
        <td><?= htmlspecialchars($row['appointment_date']) ?></td>

        <td>
          <?php if ($row['status'] === 'confirmed'): ?>
            <span class="approved">✅ Confirmed</span>
          <?php elseif ($row['cancel_requested'] == 1): ?>
            <span class="cancel-request">🛑 Cancel Requested</span>
          <?php else: ?>
            <span class="pending">⌛ <?= ucfirst($row['status']) ?></span>
          <?php endif; ?>
        </td>

        <td>
          <?= $row['is_approved'] ? '<span class="approved">✅ Approved</span>' : '<span class="pending">❗ Pending</span>' ?>
        </td>

        <td>
          <?= $row['cancel_reason'] ? nl2br(htmlspecialchars($row['cancel_reason'])) : '-' ?>
        </td>

        <td><?= $row['groomer_name'] ?: 'Not assigned' ?></td>
        <td><?= nl2br(htmlspecialchars($row['notes'] ?? '')) ?></td>

        <td>
          <!-- Approve Appointment -->
          <?php if (!$row['is_approved'] && $row['cancel_requested'] != 1): ?>
            <a href="../admin/approve/approve-handler.php?id=<?= $row['appointment_id'] ?>" 
              class="button"
              onclick="return confirmAction('Approve this appointment?')">
              ✔ Approve
            </a>
          <?php endif; ?>

          <!-- Approve Cancel or Delete -->
          <?php if ($row['cancel_requested'] == 1): ?>
            <a href="cancel-approve.php?id=<?= $row['appointment_id'] ?>&action=approve"
              class="button danger"
              onclick="return confirmAction('Approve cancellation request?')">
              ✅ Approve Cancel
            </a>
          <?php endif; ?>

          <!-- Always show delete -->
          <a href="delete-appointment.php?id=<?= $row['appointment_id'] ?>"
            class="button"
            onclick="return confirmAction('Are you sure you want to delete this appointment?')">
            🗑 Delete
          </a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

</body>
</html>
