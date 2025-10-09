<?php
session_start();
require_once '../../db.php';
// Add this at the very top of your PHP file (after session_start)
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<!-- PHP is working -->"; 

if (pg_connection_status($conn) !== PGSQL_CONNECTION_OK) {
    die('Database connection failed: ' . pg_last_error());
}
 if ($_SESSION['role'] !== 'admin') {
   header("Location: ../homepage/main.php");
   exit;
 }

// Count metrics
$total_users = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) AS count FROM users"), 0, 'count');
$total_pets = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) AS count FROM pets"), 0, 'count');
$total_appointments = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) AS count FROM appointments"), 0, 'count');
$confirmed_appointments = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) AS count FROM appointments WHERE status = 'confirmed'"), 0, 'count');
$completed_appointments = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) AS count FROM appointments WHERE status = 'completed'"), 0, 'count');

date_default_timezone_set('Asia/Manila');
$now = new DateTime();

$autoCheck = pg_query($conn, "
  SELECT appointment_id, appointment_date
  FROM appointments
  WHERE status = 'confirmed'
");

$noShowCount = 0;
while ($row = pg_fetch_assoc($autoCheck)) {
    $appointmentTime = new DateTime($row['appointment_date']);
    $graceEnd = clone $appointmentTime;
    $graceEnd->modify('+15 minutes');

    if ($now > $graceEnd) {
        $id = $row['appointment_id'];

        // Only update if it's not already a no_show (just in case)
        $update = pg_query($conn, "UPDATE appointments SET status = 'no_show' WHERE appointment_id = $id");
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
  <title>Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">
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
  padding: 10px 12px;
  text-decoration: none;
  color: var(--dark-color);
  border-radius: var(--border-radius-s);
  transition: background 0.3s, color 0.3s;
  font-weight: var(--font-weight-semi-bold);
}

.menu a i {
  margin-right: 10px;
  font-size: 20px;
}

.menu a:hover,
.menu a.active {
  background-color: var(--secondary-color);
  color: var(--dark-color);
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
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php" class="active"><i class='bx bx-home'></i>Overview</a>
    <hr>

    <!-- USERS DROPDOWN MENU -->
    <div class="dropdown">
      <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">
        <i class='bx bx-user'></i> Users <i class='bx bx-chevron-down' style="float: right;"></i>
      </a>
      <div class="dropdown-menu" style="display: none; margin-left: 20px;">
        <a href="../manage_accounts/accounts.php"><i class='bx bx-user-circle'></i> All Users</a>
        <a href="../groomer_management/groomer_accounts.php"><i class='bx bx-scissors'></i> Groomers</a>
        <a href="../../receptionist_dashboard/receptionist_home.php"><i class='bx bx-id-card'></i> Receptionists</a>
      </div>
    </div>

    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
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
      <p></p>
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
        $userList = pg_query($conn, "SELECT user_id, first_name, middle_name, last_name, email FROM users");
        while ($user = pg_fetch_assoc($userList)):
        ?>
          <tr>
            <td><?= htmlspecialchars($user['user_id']) ?></td>
            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']) ?></td>
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
        $petList = pg_query($conn, "SELECT pet_id, name, breed, user_id FROM pets");
        while ($pet = pg_fetch_assoc($petList)):
        ?>
          <tr>
            <td><?= htmlspecialchars($pet['pet_id']) ?></td>
            <td><?= htmlspecialchars($pet['name']) ?></td>
            <td><?= htmlspecialchars($pet['breed']) ?></td>
            <td><?= htmlspecialchars($pet['user_id']) ?></td>
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
        $pendingQuery = "
            SELECT a.appointment_id, a.appointment_date, a.status,
                  p.name AS pet_name, p.breed,
                  u.first_name, u.middle_name, u.last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.status = 'pending'
            ORDER BY a.appointment_date DESC
        ";

        $pendingResult = pg_query($conn, $pendingQuery);
        if (!$pendingResult) {
            die("Query Failed: " . pg_last_error($conn));
        }
        ?>
        <?php while ($row = pg_fetch_assoc($pendingResult)): ?>
          <tr>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($row['owner_name']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
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
        $confirmedQuery = "
            SELECT a.appointment_id, a.appointment_date, a.status,
                  p.name AS pet_name, p.breed,
                  u.first_name, u.middle_name, u.last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.status = 'confirmed'
            ORDER BY a.appointment_date DESC
        ";
        $confirmedResult = pg_query($conn, $confirmedQuery);
        if (!$confirmedResult) {
            die("Query Failed: " . pg_last_error($conn));
        }
        ?>
        <?php while ($row = pg_fetch_assoc($confirmedResult)): ?>
          <tr>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($row['owner_name']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
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
        $completedQuery = "
            SELECT a.appointment_id, a.appointment_date, a.status,
                  p.name AS pet_name, p.breed,
                  u.first_name, u.middle_name, u.last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.status = 'completed'
            ORDER BY a.appointment_date DESC
        ";
        $completedResult = pg_query($conn, $completedQuery);
        if (!$completedResult) {
            die("Query Failed: " . pg_last_error($conn));
        }
        ?>
        <?php while ($row = pg_fetch_assoc($completedResult)): ?>
          <tr>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($row['owner_name']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
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
        $appointmentQuery = "
            SELECT a.*,
                  u.first_name,
                  u.middle_name,
                  u.last_name,
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
        $appointmentList = pg_query($conn, $appointmentQuery);

        if (!$appointmentList) {
            die("Query Failed: " . pg_last_error($conn));
        }
        ?>
        <?php while ($row = pg_fetch_assoc($appointmentList)): ?>
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
// Define modals object and functions globally (outside DOMContentLoaded)
const modals = {
  users: 'usersModal',
  pets: 'petsModal',
  pending: 'pendingModal',
  confirmed: 'confirmedModal',
  completed: 'completedModal',
  appointments: 'appointmentsModal',
  history: 'historyModal'
};

// Global functions that onclick can access
function openModal(type) {
  console.log('openModal called with:', type);
  const modalId = modals[type];
  if (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'flex';
      console.log('Modal opened successfully');
    } else {
      console.error('Modal element not found:', modalId);
    }
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = 'none';
}

function viewHistory(userId) {
  openModal('history');
  const historyContainer = document.getElementById('historyTable');
  if (historyContainer) {
    historyContainer.innerHTML = 'Loading...';
    fetch(`../../appointment/fetch-history.php?user_id=${userId}`)
      .then(response => response.text())
      .then(html => historyContainer.innerHTML = html)
      .catch(() => historyContainer.innerHTML = 'Failed to load history.');
  }
}

function showToast(message) {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.style.cssText = `position: fixed; bottom: 30px; right: 30px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 10px; z-index: 9999; font-weight: 600;`;
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.style.display = 'block';
  setTimeout(() => toast.style.display = 'none', 3000);
}

// DOMContentLoaded only for initialization
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM ready');
  
  // Close modal on outside click
  window.onclick = function(event) {
    Object.values(modals).forEach(id => {
      const modal = document.getElementById(id);
      if (event.target === modal) modal.style.display = 'none';
    });
  };

  // Handle URL parameters
  const params = new URLSearchParams(window.location.search);
  const modalToShow = params.get('show');
  
  if (modalToShow && modals[modalToShow]) {
    openModal(modalToShow);
    // ... your toast notifications
  }
});

function toggleDropdown(event) {
  event.preventDefault();
  const menu = event.currentTarget.nextElementSibling;
  menu.style.display = (menu.style.display === "block") ? "none" : "block";
  }
</script>



</body>
</html>