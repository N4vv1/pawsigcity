<?php
session_start();
require '../db.php';

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
<html>
<head>
  <title>Admin Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    header {
      background: #A8E6CF;
      padding: 20px;
      color: #333;
      text-align: center;
      font-size: 24px;
    }

    .dashboard {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      padding: 40px;
    }

    .card {
      background: white;
      border-radius: 10px;
      padding: 30px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .card h3 {
      font-size: 20px;
      color: #555;
    }

    .card p {
      font-size: 32px;
      font-weight: bold;
      color: #222;
      margin: 10px 0 0;
    }

    a {
      text-decoration: none;
      color: #007BFF;
      display: block;
      margin-top: 10px;
    }

    a:hover {
      text-decoration: underline;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
      z-index: 999;
    }
    .modal-content {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      max-height: 80vh;
      overflow-y: auto;
      width: 90%;
      max-width: 600px;
    }
    .modal-content table {
      width: 100%;
      border-collapse: collapse;
    }
    .modal-content th, .modal-content td {
      padding: 8px;
      border: 1px solid #ccc;
      text-align: left;
    }
    .modal-content button {
      margin-top: 20px;
      padding: 10px 20px;
      background: #A8E6CF;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

  </style>
</head>
<body>

<header>🐾 Admin Dashboard - Purrfect Paws</header>

<div class="dashboard">
  <div class="card">
    <h3>Total Users</h3>
    <p><?= $total_users ?></p>
    <a href="javascript:void(0)" onclick="openModal('users')">View Users</a>
  </div>

  <div class="card">
    <h3>Total Pets</h3>
    <p><?= $total_pets ?></p>
    <a href="javascript:void(0)" onclick="openModal('pets')">View Pets</a>
  </div>

  <div class="card">
    <h3>Total Appointments</h3>
    <p><?= $total_appointments ?></p>
    <a href="../appointment/manage-appointments.php">Manage Appointments</a>
  </div>

  <div class="card">
    <h3>Pending Appointments</h3>
    <p><?= $pending_appointments ?></p>
    <a href="javascript:void(0)" onclick="openModal('pending')">View Pending</a>  
  </div>

  <div class="card">
    <h3>Confirmed Appointments</h3>
    <p><?= $confirmed_appointments ?></p>
  </div>

  <div class="card">
    <h3>Completed Appointments</h3>
    <p><?= $completed_appointments ?></p>
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
</div>
</div>
</body>
</html>
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

  // Close modal when clicking outside
  window.onclick = function(event) {
    ['usersModal', 'petsModal', 'pendingModal'].forEach(id => {
      const modal = document.getElementById(id);
      if (event.target === modal) modal.style.display = 'none';
    });
  }
</script>


