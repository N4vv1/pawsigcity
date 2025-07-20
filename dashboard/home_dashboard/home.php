<?php
session_start();
require_once '../../db.php';
//if ($_SESSION['role'] !== 'admin') {
  //header("Location: ../homepage/main.php");
  //exit;
//}
// Count metrics
$total_users = $mysqli->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$total_pets = $mysqli->query("SELECT COUNT(*) AS count FROM pets")->fetch_assoc()['count'];
$total_appointments = $mysqli->query("SELECT COUNT(*) AS count FROM appointments")->fetch_assoc()['count'];
$pending_appointments = $mysqli->query("SELECT COUNT(*) AS count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];
$confirmed_appointments = $mysqli->query("SELECT COUNT(*) AS count FROM appointments WHERE status = 'confirmed'")->fetch_assoc()['count'];
$completed_appointments = $mysqli->query("SELECT COUNT(*) AS count FROM appointments WHERE status = 'completed'")->fetch_assoc()['count'];

date_default_timezone_set('Asia/Manila');
$now = new DateTime();

$autoCheck = $mysqli->query("
  SELECT appointment_id, appointment_date
  FROM appointments
  WHERE status = 'confirmed'
");

$noShowCount = 0;
while ($row = $autoCheck->fetch_assoc()) {
    $appointmentTime = new DateTime($row['appointment_date']);
    $graceEnd = clone $appointmentTime;
    $graceEnd->modify('+15 minutes');

    if ($now > $graceEnd) {
        $id = $row['appointment_id'];

        // Only update if it's not already a no_show (just in case)
        $update = $mysqli->query("UPDATE appointments SET status = 'no_show' WHERE appointment_id = $id");
        if ($update) {
            $noShowCount++;
        }
    }
}

if ($noShowCount > 0) {
    // Redirect to show toast
    header("Location: home.php?noshows=$noShowCount&show=appointments");
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
   <style>
    :root {
  --white-color: #fff;
  --dark-color: #252525;
  --primary-color: #A8E6CF;
  --secondary-color: #FFE29D;
  --light-pink-color: #faf4f5;
  --medium-gray-color: #ccc;
  --font-size-s: 0.9rem;
  --font-size-n: 1rem;
  --font-size-l: 1.5rem;
  --font-size-xl: 2rem;
  --font-weight-semi-bold: 600;
  --font-weight-bold: 700;
  --border-radius-s: 14px;
  --border-radius-circle: 50%;
  --site-max-width: 1300px;
  --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
  --transition-speed: 0.3s;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Montserrat", sans-serif;
}

body {
  background: var(--light-pink-color);
  display: flex;
  min-height: 100vh;
}

/* --- SIDEBAR --- */
.sidebar {
  width: 260px;
  height: 100vh;
  background-color: var(--primary-color);
  padding: 30px 20px;
  position: fixed;
  left: 0;
  top: 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
  z-index: 100;
  box-shadow: var(--shadow-light);
}

.sidebar .logo {
  text-align: center;
  margin-bottom: 20px;
}

.sidebar .logo img {
  width: 80px;
  height: 80px;
  border-radius: var(--border-radius-circle);
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.menu a {
  display: flex;
  align-items: center;
  padding: 12px 14px;
  text-decoration: none;
  color: var(--dark-color);
  border-radius: var(--border-radius-s);
  font-weight: var(--font-weight-semi-bold);
  transition: var(--transition-speed);
}

.menu a i {
  margin-right: 12px;
  font-size: 20px;
}

.menu a:hover,
.menu a.active {
  background-color: var(--secondary-color);
  color: var(--dark-color);
  box-shadow: var(--shadow-light);
}

.menu hr {
  border: none;
  border-top: 1px solid var(--secondary-color);
  margin: 9px 0;
}

/* --- MAIN CONTENT --- */
main {
  margin-left: 260px;
  padding: 40px;
  width: calc(100% - 260px);
}

.dashboard {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 50px;
  max-width: 1200px;
  margin: 0 auto;
  padding-top: 150px;
}

/* --- CARDS --- */
.card {
  background: var(--white-color);
  padding: 30px;
  border-radius: var(--border-radius-s);
  box-shadow: var(--shadow-light);
  transition: var(--transition-speed);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  text-align: center;
  min-height: 230px;
  position: relative;
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.card-icon {
  font-size: 2.5rem;
  color: var(--secondary-color);
  margin-bottom: 10px;
}

.card h3 {
  font-size: 1.1rem;
  font-weight: var(--font-weight-semi-bold);
  color: var(--dark-color);
  margin-bottom: 8px;
}

.card p {
  font-size: 2rem;
  font-weight: var(--font-weight-bold);
  color: var(--primary-color);
}

.card a {
  margin-top: 20px;
  font-size: var(--font-size-n);
  text-decoration: none;
  color: var(--dark-color);
  background-color: var(--secondary-color);
  padding: 10px 18px;
  border-radius: var(--border-radius-s);
  border: none;
  transition: var(--transition-speed);
}

.card a:hover {
  background-color: var(--primary-color);
  color: var(--white-color);
}

/* --- MODALS --- */
.modal {
  display: none;
  position: fixed;
  z-index: 200;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
  align-items: center;
  justify-content: center;
}

.modal-content {
  background-color: var(--white-color);
  padding: 25px 30px;
  border-radius: var(--border-radius-s);
  width: 90%;
  max-width: 800px;
  box-shadow: 0 0 20px rgba(0,0,0,0.2);
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.modal-content h2 {
  margin-bottom: 18px;
  color: var(--dark-color);
  font-size: var(--font-size-l);
}

.modal-content table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.modal-content table th,
.modal-content table td {
  padding: 12px 10px;
  text-align: left;
  border-bottom: 1px solid var(--medium-gray-color);
  font-size: var(--font-size-s);
}

.modal-content table th {
  background-color: var(--primary-color);
  color: var(--dark-color);
}

.modal-content button {
  margin-top: 20px;
  padding: 10px 20px;
  background-color: var(--primary-color);
  color: var(--dark-color);
  border: none;
  border-radius: var(--border-radius-s);
  cursor: pointer;
  transition: var(--transition-speed);
  font-weight: var(--font-weight-semi-bold);
}

.modal-content button:hover {
  background-color: var(--secondary-color);
}

.feedback-box {
  padding: 0;
  margin: 0;
  background: none;
  border: none;
  box-shadow: none;
  font-size: inherit;
  display: block;
}


.feedback-box:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.08);
}

.feedback-stars {
  color: #FFD700;
  font-size: 1rem;
  display: flex;
  gap: 2px;
}


.feedback-comment {
  color: #333;
  line-height: 1.3;
  font-style: italic;
  word-wrap: break-word;
}

.feedback-user {
  font-weight: 600;
  font-size: 0.9rem;
  color: #888;
}

.feedback-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}


/* Action buttons group */
.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

/* Button style refinements */
.button {
  padding: 7px 12px;
  border-radius: 6px;
  font-size: 0.85rem;
  text-decoration: none;
  color: #252525;
  background: #A8E6CF;
  font-weight: 600;
  transition: 0.2s;
}

.button:hover {
  background: #80d1b8;
  color: #000;
}

.button.danger {
  background-color: #FFB6B6;
}

.button.danger:hover {
  background-color: #e67d7d;
}

.button.secondary {
  background-color: #FFE29D;
}

.button.secondary:hover {
  background-color: #f8d775;
}

.button.view-history {
  background-color: #dcdcdc;
}

.button.view-history:hover {
  background-color: #c0c0c0;
}
   </style>
</head>
<body>

<!-- Sidebar Only -->
<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/Logo.jpg" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="home.php" class="active"><i class='bx bx-home'></i>Home</a>
    <hr>
    <a href="../manage_accounts/accounts.php"><i class='bx bx-camera'></i>User Management</a>
    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="#"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main>

  <div class="dashboard">
    <div class="card">
      <div class="card-icon">
        <i class='bx bx-user'></i>
      </div>
      <h3>Total Users</h3>
      <p><?= $total_users ?></p>
      <a href="javascript:void(0)" onclick="openModal('users')">View Users</a>
    </div>

    <div class="card">
      <div class="card-icon">
        <i class='bx bx-heart'></i>
      </div>
      <h3>Total Pets</h3>
      <p><?= $total_pets ?></p>
      <a href="javascript:void(0)" onclick="openModal('pets')">View Pets</a>
    </div>

    <div class="card">
      <div class="card-icon">
        <i class='bx bx-calendar'></i>
      </div>
      <h3>Total Appointments</h3>
      <p><?= $total_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('appointments')">Manage Appointments</a>
    </div>

    <div class="card">
      <div class="card-icon">
        <i class='bx bx-time'></i>
      </div>
      <h3>Pending Appointments</h3>
      <p><?= $pending_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('pending')">View Pending</a>  
    </div>

    <div class="card">
      <div class="card-icon">
        <i class='bx bx-check-circle'></i>
      </div>
      <h3>Confirmed Appointments</h3>
      <p><?= $confirmed_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('confirmed')">View Confirmed</a>
    </div>

    <div class="card">
      <div class="card-icon">
        <i class='bx bx-badge-check'></i>
      </div>
      <h3>Completed Appointments</h3>
      <p><?= $completed_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('completed')">View Completed</a>
    </div>
  </div>

<!-- USERS MODAL -->
<div id="usersModal" class="modal">
  <div class="modal-content">
    <h2>👥 User List</h2>
    <table>
      <thead>
        <tr>
          <th>User ID</th>
          <th>Full Name</th>
          <th>Email</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $userList = $mysqli->query("SELECT user_id, full_name, email FROM users");
        while ($user = $userList->fetch_assoc()):
        ?>
          <tr>
            <td><?= $user['user_id'] ?></td>
            <td><?= htmlspecialchars($user['full_name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button onclick="closeModal('usersModal')">Close</button>
  </div>
</div>

<!-- PETS MODAL -->
<div id="petsModal" class="modal">
  <div class="modal-content">
    <h2>🐶 Pet List</h2>
    <table>
      <thead>
        <tr>
          <th>Pet ID</th>
          <th>Name</th>
          <th>Breed</th>
          <th>Owner ID</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $petList = $mysqli->query("SELECT pet_id, name, breed, user_id FROM pets");
        while ($pet = $petList->fetch_assoc()):
        ?>
          <tr>
            <td><?= $pet['pet_id'] ?></td>
            <td><?= htmlspecialchars($pet['name']) ?></td>
            <td><?= htmlspecialchars($pet['breed']) ?></td>
            <td><?= $pet['user_id'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button onclick="closeModal('petsModal')">Close</button>
  </div>
</div>

<!-- PENDING APPOINTMENTS MODAL -->
<div id="pendingModal" class="modal">
  <div class="modal-content">
    <h2>🕒 Pending Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Appointment ID</th>
          <th>User ID</th>
          <th>Pet ID</th>
          <th>Service</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $pendingList = $mysqli->query("
          SELECT a.appointment_id, a.user_id, a.pet_id, pk.name AS service, a.appointment_date, a.status
          FROM appointments a
          JOIN packages pk ON a.package_id = pk.id
          WHERE a.status = 'pending'
        ");
        while ($row = $pendingList->fetch_assoc()):
        ?>
          <tr>
            <td><?= $row['appointment_id'] ?></td>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['pet_id'] ?></td>
            <td><?= htmlspecialchars($row['service']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= ucfirst($row['status']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button onclick="closeModal('pendingModal')">Close</button>
  </div>
</div>

<!-- CONFIRMED APPOINTMENTS MODAL -->
<div id="confirmedModal" class="modal">
  <div class="modal-content">
    <h2>✅ Confirmed Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Appointment ID</th>
          <th>User ID</th>
          <th>Pet ID</th>
          <th>Service</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $confirmedList = $mysqli->query("
          SELECT a.appointment_id, a.user_id, a.pet_id, pk.name AS service, a.appointment_date, a.status
          FROM appointments a
          JOIN packages pk ON a.package_id = pk.id
          WHERE a.status = 'confirmed'
        ");
        while ($row = $confirmedList->fetch_assoc()):
        ?>
          <tr>
            <td><?= $row['appointment_id'] ?></td>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['pet_id'] ?></td>
            <td><?= htmlspecialchars($row['service']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= ucfirst($row['status']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button onclick="closeModal('confirmedModal')">Close</button>
  </div>
</div>



<!-- COMPLETED APPOINTMENTS MODAL -->
<div id="completedModal" class="modal">
  <div class="modal-content">
    <h2>🎉 Completed Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Appointment ID</th>
          <th>User ID</th>
          <th>Pet ID</th>
          <th>Service</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $completedList = $mysqli->query("
          SELECT a.appointment_id, a.user_id, a.pet_id, pk.name AS service, a.appointment_date, a.status
          FROM appointments a
          JOIN packages pk ON a.package_id = pk.id
          WHERE a.status = 'completed'
        ");
        while ($row = $completedList->fetch_assoc()):
        ?>
          <tr>
            <td><?= $row['appointment_id'] ?></td>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['pet_id'] ?></td>
            <td><?= htmlspecialchars($row['service']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= ucfirst($row['status']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button onclick="closeModal('completedModal')">Close</button>
  </div>
</div>

<!-- ALL APPOINTMENTS MODAL -->
<div id="appointmentsModal" class="modal">
  <div class="modal-content" style="max-width: 95%; max-height: 90vh; overflow-y: auto;">
    <h2>📋 All Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Client</th>
          <th>Pet</th>
          <th>Breed</th>
          <th>Service</th>
          <th>Date</th>
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
        <?php
        $appointmentList = $mysqli->query("
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
        ");
        while ($row = $appointmentList->fetch_assoc()):
        ?>
          <tr>
            <td><?= htmlspecialchars($row['client_name']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($row['pet_breed']) ?></td>
            <td><?= htmlspecialchars($row['package_name']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td>
              <?php if ($row['status'] === 'no_show'): ?>
                <span style="color: red; font-weight: bold;">No Show</span>
              <?php elseif ($row['status'] === 'completed'): ?>
                <span style="color: green;">Completed</span>
              <?php elseif ($row['status'] === 'confirmed'): ?>
                <span style="color: green;">Confirmed</span>
              <?php elseif ($row['status'] === 'cancelled'): ?>
                <span style="color: red;">Cancelled</span>
              <?php elseif (!empty($row['cancel_requested'])): ?>
                <span style="color: red;">Cancel Requested</span>
              <?php else: ?>
                <span style="color: orange;">Pending</span>
              <?php endif; ?>
            </td>
            <td>
              <?= $row['status'] === 'cancelled' ? '<span style="color:red;">Cancelled</span>' :
                  (!empty($row['is_approved']) ? '<span style="color:green;">Approved</span>' : '<span style="color:orange;">Pending</span>') ?>
            </td>
            <td><?= !empty($row['cancel_reason']) ? nl2br(htmlspecialchars($row['cancel_reason'])) : '-' ?></td>
            <td><?= !empty($row['groomer_name']) ? htmlspecialchars($row['groomer_name']) : 'Not assigned' ?></td>
            <td><?= nl2br(htmlspecialchars($row['notes'] ?? '')) ?></td>
            <td>
            <?php if (isset($row['rating'])): ?>
              <div class="feedback-box">
                <div class="feedback-header">
                  <div class="feedback-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fa<?= $i <= $row['rating'] ? 's' : 'r' ?> fa-star"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                <div class="feedback-comment">
                  <?= !empty($row['feedback']) ? nl2br(htmlspecialchars($row['feedback'])) : '<em>No comment.</em>' ?>
                </div>
              </div>
            <?php else: ?>
              <em>No feedback</em>
            <?php endif; ?>
          </td>
            <td>
            <div class="action-buttons">
            <?php
              $status = strtolower($row['status']);
              $appointmentId = $row['appointment_id'];
              $isApproved = !empty($row['is_approved']);
            ?>

            <?php if ($status === 'pending' && !$isApproved): ?>
              <a href="../../admin/approve/approve-handler.php?id=<?= $appointmentId ?>" class="button">Approve</a>
              <a href="../../appointment/delete-appointment.php?id=<?= $appointmentId ?>" class="button danger" onclick="return confirm('Delete this appointment?')">Delete</a>

            <?php elseif ($status === 'confirmed'): ?>
              <a href="../../appointment/mark-completed.php?id=<?= $appointmentId ?>" class="button" onclick="return confirm('Mark this appointment as completed?');">Complete</a>
              <a href="../../appointment/delete-appointment.php?id=<?= $appointmentId ?>" class="button danger" onclick="return confirm('Delete this appointment?')">Delete</a>

            <?php elseif ($status === 'cancelled' || $status === 'no_show' || $status === 'completed'): ?>
              <a href="../../appointment/delete-appointment.php?id=<?= $appointmentId ?>" class="button danger" onclick="return confirm('Delete this appointment?')">Delete</a>
            <?php endif; ?>

            <?php if (!empty($row['cancel_requested']) && $status !== 'cancelled'): ?>
              <a href="../../appointment/cancel-approve.php?id=<?= $appointmentId ?>&action=approve" class="button danger">Cancel</a>
            <?php endif; ?>

            <a href="javascript:void(0)" class="button view-history" onclick="viewHistory(<?= $row['user_id'] ?>)">History</a>
          </div>

          </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button onclick="closeModal('appointmentsModal')">Close</button>
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



</main>

<script>
  const modals = {
    users: 'usersModal',
    pets: 'petsModal',
    pending: 'pendingModal',
    confirmed: 'confirmedModal',
    completed: 'completedModal',
    appointments: 'appointmentsModal',
    history: 'historyModal'
  };

  function openModal(type) {
    const modalId = modals[type];
    if (modalId) {
      const modal = document.getElementById(modalId);
      if (modal) modal.style.display = 'flex';
    }
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
  }

  window.onclick = function(event) {
    Object.values(modals).forEach(id => {
      const modal = document.getElementById(id);
      if (event.target === modal) modal.style.display = 'none';
    });
  }

  function viewHistory(userId) {
    openModal('history');
    const historyContainer = document.getElementById('historyTable');
    historyContainer.innerHTML = 'Loading...';

    fetch(`../../appointment/fetch-history.php?user_id=${userId}`)
      .then(response => response.text())
      .then(html => {
        historyContainer.innerHTML = html;
      })
      .catch(() => {
        historyContainer.innerHTML = 'Failed to load history.';
      });
  }

  // --- Toast Notification ---
  function showToast(message) {
    let toast = document.getElementById('toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast';
      toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #4CAF50;
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        z-index: 9999;
        font-weight: 600;
        font-size: 0.95rem;
        display: none;
      `;
      document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.style.display = 'block';

    setTimeout(() => {
      toast.style.display = 'none';
    }, 3000);
  }

  // Auto-show modal and toast based on query params
  window.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const modalToShow = params.get('show');

  if (modalToShow && modals[modalToShow]) {
    openModal(modalToShow);

    if (params.get('approved') === '1') {
      showToast('✅ Appointment approved successfully.');
    }
    if (params.get('deleted') === '1') {
      showToast('🗑️ Appointment deleted successfully.');
    }
    if (params.get('completed') === '1') {
      showToast('🎉 Appointment marked as completed.');
    }
    if (params.get('cancelled') === '1') {
      showToast('❌ Appointment cancelled successfully.');
    }
  }

  // ✅ This should be here
  if (params.get('noshows')) {
    const count = params.get('noshows');
    showToast(`🚫 ${count} appointment(s) marked as NO SHOW`);
  }

  // Clean the URL
  window.history.replaceState({}, document.title, window.location.pathname);
});


</script>


</body>
</html>