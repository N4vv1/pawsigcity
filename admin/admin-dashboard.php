<?php
session_start();
require '../db.php';

// Optional: Restrict access to admin users
// if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
//     header("Location: ../login/loginform.php");
//     exit;
// }

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
  </style>
</head>
<body>

<header>🐾 Admin Dashboard - Purrfect Paws</header>

<div class="dashboard">
  <div class="card">
    <h3>Total Users</h3>
    <p><?= $total_users ?></p>
    <a href="../users/manage-users.php">View Users</a>
  </div>

  <div class="card">
    <h3>Total Pets</h3>
    <p><?= $total_pets ?></p>
    <a href="../pets/all-pets.php">View Pets</a>
  </div>

  <div class="card">
    <h3>Total Appointments</h3>
    <p><?= $total_appointments ?></p>
    <a href="../appointment/all-appointments.php">Manage Appointments</a>
  </div>

  <div class="card">
    <h3>Pending Appointments</h3>
    <p><?= $pending_appointments ?></p>
    <a href="../appointment/all-appointments.php">View Pending</a>
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

</body>
</html>
