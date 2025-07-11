<?php
session_start();
require '../db.php';

// Check if user is logged in
//if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  // Redirect non-admins to login or another appropriate page
  //header('Location: ../login/loginform.php');
  //exit;
//}

// Fetch all appointments
$query = "
  SELECT a.*, 
         u.full_name AS client_name,
         u.user_id,
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

    /* Modal styles */
    #deleteModal {
      display: none; position: fixed; top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
    }
    #deleteModal .modal-content {
      background: #fff; padding: 20px;
      border-radius: 5px; max-width: 400px;
      text-align: center;
    }
    .modal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 999;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
      text-align: left;
    }

  </style>
</head>
<body>

<a href="../dashboard/home_dashboard/home.php" style="display: inline-block; padding: 8px 16px; background: #A8E6CF; color: black; text-decoration: none; border-radius: 5px; margin-bottom: 20px;">
  ⬅ Back to Dashboard
</a>

<h2>📋 Admin - Manage Appointments</h2>

<?php if (isset($_SESSION['completed'])): ?>
  <div style="padding: 10px; background: #d4edda; border-left: 5px solid green; margin-bottom: 10px;">
    <?= $_SESSION['completed'] ?>
  </div>
  <?php unset($_SESSION['completed']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['cancel_flash'])): ?>
  <div style="padding: 10px; background: #e0ffe0; border-left: 5px solid green; margin-bottom: 10px;">
    <?= $_SESSION['cancel_flash']; unset($_SESSION['cancel_flash']); ?>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['deleted'])): ?>
  <div style="padding: 10px; background: #ffe0e0; border-left: 5px solid red; margin-bottom: 10px;">
    <?= $_SESSION['deleted'] ?>
  </div>
  <?php unset($_SESSION['deleted']); ?>
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
      <th>Feedback</th>
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
          <?php if ($row['status'] === 'completed'): ?>
            <span class="approved">🎉 Completed</span>
          <?php elseif ($row['status'] === 'confirmed'): ?>
            <span class="approved">✅ Confirmed</span>
          <?php elseif ($row['status'] === 'cancelled'): ?>
            <span class="cancel-request">❌ Cancelled</span>
          <?php elseif ($row['cancel_requested'] == 1): ?>
            <span class="cancel-request">🛑 Cancel Requested</span>
          <?php else: ?>
            <span class="pending">⌛ <?= ucfirst($row['status']) ?></span>
          <?php endif; ?>
        </td>


        <td>
          <?php if ($row['status'] === 'cancelled'): ?>
            <span class="cancel-request" style="color: #721c24;">❌ Cancelled</span>
          <?php elseif ($row['is_approved']): ?>
            <span class="approved">✅ Approved</span>
          <?php else: ?>
            <span class="pending">❗ Pending</span>
          <?php endif; ?>
        </td>


        <td><?= $row['cancel_reason'] ? nl2br(htmlspecialchars($row['cancel_reason'])) : '-' ?></td>
        <td><?= $row['groomer_name'] ?: 'Not assigned' ?></td>
        <td><?= nl2br(htmlspecialchars($row['notes'] ?? '')) ?></td>
        <td>
          <?php if (isset($row['rating']) && $row['rating'] !== null): ?>
            ⭐ <?= $row['rating'] ?>/5<br>
            <?= !empty($row['feedback']) ? nl2br(htmlspecialchars($row['feedback'])) : '<em>No comment.</em>' ?>
          <?php else: ?>
            <em>No feedback</em>
          <?php endif; ?>
        </td>


        <td>
          <?php if (!$row['is_approved'] && $row['cancel_requested'] != 1 && $row['status'] !== 'cancelled'): ?>
            <a href="../admin/approve/approve-handler.php?id=<?= $row['appointment_id'] ?>" 
              class="button">
              ✔ Approve
            </a>
          <?php endif; ?>

          <?php if ($row['cancel_requested'] == 1): ?>
            <a href="cancel-approve.php?id=<?= $row['appointment_id'] ?>&action=approve"
              class="button danger">
              ✅ Approve Cancel
            </a>
          <?php endif; ?>

          <?php if ($row['status'] === 'confirmed'): ?>
            <a href="mark-completed.php?id=<?= $row['appointment_id'] ?>" class="button" onclick="return confirm('Mark this appointment as completed?');">
              🎯 Complete
            </a>
          <?php endif; ?>

          <!-- Delete button triggers modal -->
          <button class="button delete-btn" data-id="<?= $row['appointment_id'] ?>">
            🗑 Delete
          </button>

          <!-- 📖 View History -->
          <a href="javascript:void(0)" class="button" onclick="viewHistory(<?= $row['user_id'] ?>)">📖 History</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- Delete Modal -->
<div id="deleteModal">
  <div class="modal-content">
    <h3>Confirm Delete</h3>
    <p>Are you sure you want to delete this appointment?</p>
    <form id="deleteForm" method="GET" action="delete-appointment.php">
      <input type="hidden" name="id" id="deleteAppointmentId">
      <button type="submit" class="button danger">Yes, Delete</button>
      <button type="button" class="button" onclick="closeModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="modal">
  <div class="modal-content" id="historyContent">
    <h3>📖 Appointment History</h3>
    <div id="historyTable">Loading...</div>
    <button onclick="closeModal('historyModal')">Close</button>
  </div>
</div>


<script>
  const deleteBtns = document.querySelectorAll('.delete-btn');
  const deleteModal = document.getElementById('deleteModal');
  const deleteInput = document.getElementById('deleteAppointmentId');

  deleteBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      deleteInput.value = id;
      deleteModal.style.display = 'flex';
    });
  });

  function closeModal() {
    deleteModal.style.display = 'none';
  }

  window.addEventListener('click', (e) => {
    if (e.target === deleteModal) closeModal();
  });
</script>

<script>
function viewHistory(userId) {
  document.getElementById('historyTable').innerHTML = 'Loading...';
  document.getElementById('historyModal').style.display = 'flex';

  fetch(`fetch-history.php?user_id=${userId}`)
    .then(response => response.text())
    .then(html => {
      document.getElementById('historyTable').innerHTML = html;
    })
    .catch(() => {
      document.getElementById('historyTable').innerHTML = 'Failed to load history.';
    });
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}

// Optional: Click outside to close
window.onclick = function(event) {
  const modal = document.getElementById('historyModal');
  if (event.target === modal) modal.style.display = 'none';
}
</script>



</body>
</html>
