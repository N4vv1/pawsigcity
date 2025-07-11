<?php
session_start();
require '../../db.php';

if (!isset($mysqli) || !$mysqli) {
  die("Database connection error.");
}

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
  <title>Gallery Dashboard Template</title>
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
      --border-radius-s: 8px;
      --border-radius-circle: 50%;
      --site-max-width: 1300px;
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
    }

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

    .submenu {
      margin-left: 30px;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .submenu a {
      font-size: var(--font-size-s);
    }

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
    }

    .add-btn {
      background: var(--primary-color);
      padding: 10px 20px;
      border-radius: var(--border-radius-s);
      text-decoration: none;
      color: var(--dark-color);
      font-weight: var(--font-weight-semi-bold);
      display: inline-block;
      margin-bottom: 20px;
    }

    .add-btn:hover {
      background: var(--secondary-color);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: var(--white-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    th, td {
      padding: 14px 10px;
      border: 1px solid var(--medium-gray-color);
      text-align: center;
    }

    th {
      background: var(--primary-color);
      font-weight: var(--font-weight-bold);
      color: var(--dark-color);
    }

    img {
      width: 100px;
      height: auto;
      border-radius: var(--border-radius-s);
    }

    .actions a {
      padding: 6px 14px;
      font-size: var(--font-size-s);
      font-weight: var(--font-weight-semi-bold);
      text-decoration: none;
      margin: 0 5px;
      border-radius: var(--border-radius-s);
      display: inline-block;
    }

    .edit-btn {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .edit-btn:hover {
      background-color: #fdd56c;
    }

    .delete-btn {
      background-color: #ff6b6b;
      color: var(--white-color);
    }

    .delete-btn:hover {
      background-color: #ff4949;
    }

    .card {
      background: var(--white-color);
      padding: 20px;
      border-radius: var(--border-radius-s);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }

    .dashboard {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 30px;
    }


    .modal {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  justify-content: center;
  align-items: center;
  z-index: 1000;
  transition: opacity 0.3s ease;
}

.modal-content {
  background: var(--white-color);
  padding: 30px;
  border-radius: var(--border-radius-s);
  width: 90%;
  max-width: 900px;
  max-height: 80vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-content h2 {
  margin-bottom: 20px;
  font-size: 1.6rem;
  color: var(--dark-color);
}

.modal-content table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.modal-content th,
.modal-content td {
  padding: 12px;
  border: 1px solid var(--medium-gray-color);
  text-align: center;
}

.modal-content th {
  background-color: var(--primary-color);
  color: var(--dark-color);
  font-weight: var(--font-weight-bold);
}

.modal-content td {
  background-color: var(--white-color);
}

.modal-close {
  position: absolute;
  top: 12px;
  right: 16px;
  background: none;
  border: none;
  font-size: 1.5rem;
  font-weight: bold;
  color: var(--dark-color);
  cursor: pointer;
  transition: color 0.3s ease;
}

.modal-close:hover {
  color: var(--secondary-color);
}
    .dashboard-card {
  position: relative;
  transition: transform 0.2s ease, box-shadow 0.3s ease;
  text-align: center;
}

.dashboard-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 18px rgba(0, 0, 0, 0.15);
}

.dashboard-icon {
  font-size: 2.2rem;
  margin-bottom: 10px;
  color: var(--primary-color);
}

.dashboard-card h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--dark-color);
}

.dashboard-card p {
  font-size: 1.6rem;
  font-weight: 700;
  margin: 10px 0;
  color: var(--dark-color);
}

.dashboard-card a {
  display: inline-block;
  margin-top: 10px;
  font-size: 0.95rem;
  color: black;
  text-decoration: underline;
  transition: color 0.3s ease;
}

.dashboard-card a:hover {
  color: var(--secondary-color);
}

  </style>
</head>
<body>

<!-- Sidebar -->
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
    <a href="../feedback_reports/feedback-reports.php" class="button">📝 View Feedback Reports</a>
    <hr>
    <a href="#"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>


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
    <button class="modal-close" onclick="closeModal('usersModal')">&times;</button>
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
    <button class="modal-close" onclick="closeModal('petsModal')">&times;</button>
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
          <th>Recommended</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $pendingList = $mysqli->query("
          SELECT a.appointment_id, a.user_id, a.pet_id, pk.name AS service, a.recommended_package, a.appointment_date, a.status
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
          <td><?= !empty($row['recommended_package']) ? htmlspecialchars($row['recommended_package']) : '<em>Not available</em>' ?></td>
          <td><?= htmlspecialchars($row['appointment_date']) ?></td>
          <td><?= ucfirst($row['status']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button class="modal-close" onclick="closeModal('pendingModal')">&times;</button>
  </div>
</div>

<main class="content">

  <div class="dashboard">
    <div class="card dashboard-card">
      <i class='bx bx-user dashboard-icon'></i>
      <h3>Total Users</h3>
      <p><?= $total_users ?></p>
      <a href="javascript:void(0)" onclick="openModal('users')">View Users</a>
    </div>

    <div class="card dashboard-card">
      <i class="fas fa-dog dashboard-icon"></i>
      <h3>Total Pets</h3>
      <p><?= $total_pets ?></p>
      <a href="javascript:void(0)" onclick="openModal('pets')">View Pets</a>
    </div>

    <div class="card dashboard-card">
      <i class='bx bx-calendar-check dashboard-icon'></i>
      <h3>Total Appointments</h3>
      <p><?= $total_appointments ?></p>
      <a href="../../appointment/manage-appointments.php">Manage Appointments</a>
    </div>

    <div class="card dashboard-card">
      <i class='bx bx-time-five dashboard-icon'></i>
      <h3>Pending Appointments</h3>
      <p><?= $pending_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('pending')">View Pending</a>
    </div>

    <div class="card dashboard-card">
      <i class='bx bx-calendar-star dashboard-icon'></i>
      <h3>Confirmed Appointments</h3>
      <p><?= $confirmed_appointments ?></p>
    </div>

    <div class="card dashboard-card">
      <i class='bx bx-check-circle dashboard-icon'></i>
      <h3>Completed Appointments</h3>
      <p><?= $completed_appointments ?></p>
    </div>
  </div>
</main>


<script>
  function openModal(type) {
    if (type === 'users') {
      document.getElementById('usersModal').style.display = 'flex';
    } else if (type === 'pets') {
      document.getElementById('petsModal').style.display = 'flex';
    } else if (type === 'pending') {
      document.getElementById('pendingModal').style.display = 'flex';
    }
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  window.onclick = function(event) {
    ['usersModal', 'petsModal', 'pendingModal'].forEach(id => {
      const modal = document.getElementById(id);
      if (event.target === modal) modal.style.display = 'none';
    });
  }
</script>
</body>
</html>
