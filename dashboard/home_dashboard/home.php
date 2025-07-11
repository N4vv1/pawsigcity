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
      <a href="../admin/manage_appointments.php">Go to Appointments</a>
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
</main>

<script>
  function openModal(type) {
    const modals = {
      users: 'usersModal',
      pets: 'petsModal',
      pending: 'pendingModal',
      confirmed: 'confirmedModal',
      completed: 'completedModal',
      appointments: 'appointmentsModal' // ✅ Added this line
    };

    if (modals[type]) {
      document.getElementById(modals[type]).style.display = 'flex';
    }
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    ['usersModal', 'petsModal', 'pendingModal', 'confirmedModal', 'completedModal', 'appointmentsModal'].forEach(id => {
      const modal = document.getElementById(id);
      if (event.target === modal) modal.style.display = 'none';
    });
  }
</script>
tab
</body>
</html>