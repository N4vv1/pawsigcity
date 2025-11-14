<?php
session_start();
include '../db.php';

// Check if groomer is logged in
if (!isset($_SESSION['groomer_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$groomer_id = $_SESSION['groomer_id'];

// FIXED: Fetch ONLY completed appointments for THIS groomer with type casting
$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        p.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed,
        (u.first_name || ' ' || u.last_name) AS customer_name,
        u.first_name,
        u.last_name,
        COALESCE(TO_CHAR(a.updated_at, 'YYYY-MM-DD HH24:MI:SS'), 'Not yet completed') AS completed_date
    FROM appointments a
    JOIN packages p ON a.package_id::text = p.package_id
    JOIN pets pet ON a.pet_id = pet.pet_id
    JOIN users u ON pet.user_id::text = u.user_id
    WHERE a.status = 'completed'
    AND a.groomer_id = $1
    ORDER BY a.updated_at DESC
";

$result = pg_query_params($conn, $query, [$groomer_id]);

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Groomer | History Logs</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../homepage/images/pawsig.png">
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
      --light-pink-color: #faf4f5;
      --medium-gray-color: #ccc;
      --edit-color: #4CAF50;
      --font-size-s: 0.9rem;
      --font-size-n: 1rem;
      --font-size-l: 1.5rem;
      --font-size-xl: 2rem;
      --font-weight-semi-bold: 600;
      --font-weight-bold: 700;
      --border-radius-s: 8px;
      --border-radius-circle: 50%;
      --sidebar-width: 260px;
      --transition-speed: 0.3s;
      --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
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

    .mobile-menu-btn {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1001;
      background: var(--primary-color);
      border: none;
      border-radius: 8px;
      padding: 12px;
      cursor: pointer;
      box-shadow: var(--shadow-light);
      transition: var(--transition-speed);
    }

    .mobile-menu-btn i {
      font-size: 24px;
      color: var(--dark-color);
    }

    .mobile-menu-btn:hover {
      background: var(--secondary-color);
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 998;
      opacity: 0;
      transition: opacity var(--transition-speed);
    }

    .sidebar-overlay.active {
      display: block;
      opacity: 1;
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
      overflow-y: auto;
      box-shadow: var(--shadow-light);
      transition: transform var(--transition-speed);
      z-index: 999;
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
      border-radius: 14px;
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

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    .header {
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 10px;
      font-weight: 600;
    }

    .header p {
      color: #666;
      font-size: 0.95rem;
    }

    .stats-card {
      background: linear-gradient(135deg, var(--primary-color) 0%, #8fd4b8 100%);
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      text-align: center;
      color: var(--dark-color);
    }

    .stats-card h3 {
      font-size: 3rem;
      margin-bottom: 8px;
      font-weight: 700;
    }

    .stats-card p {
      font-size: 1.1rem;
      font-weight: 500;
      opacity: 0.9;
    }

    /* TABLE SECTION */
    .table-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      overflow-x: auto;
    }

    .table-section h2 {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
    }

    table {
      width: 100%;
      min-width: 1000px;
      border-collapse: collapse;
    }

    th, td {
      padding: 15px 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }

    th {
      background-color: #fafafa;
      color: var(--dark-color);
      font-weight: 600;
      font-size: 0.9rem;
      position: sticky;
      top: 0;
    }

    tbody tr:hover {
      background-color: #fafafa;
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 80px;
      color: #ddd;
      margin-bottom: 20px;
      display: block;
    }

    .empty-state h3 {
      font-size: 1.5rem;
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .empty-state p {
      font-size: 1rem;
      color: #999;
    }

    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.85rem;
        min-width: 900px;
      }

      th, td {
        padding: 12px 10px;
      }
    }

    @media screen and (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .content {
        margin-left: 0;
        width: 100%;
        padding: 80px 20px 40px;
      }

      .header h1 {
        font-size: 1.6rem;
      }

      .stats-card {
        padding: 25px;
      }

      .stats-card h3 {
        font-size: 2.5rem;
      }

      .stats-card p {
        font-size: 1rem;
      }

      .table-section {
        padding: 20px;
      }

      table {
        font-size: 0.8rem;
        min-width: 800px;
      }

      th, td {
        padding: 10px 8px;
      }
    }

    @media screen and (max-width: 480px) {
      .content {
        padding: 70px 10px 30px;
      }

      .sidebar .logo img {
        width: 60px;
        height: 60px;
      }

      .menu a {
        padding: 8px 10px;
        font-size: 0.9rem;
      }

      .header h1 {
        font-size: 1.4rem;
      }

      .stats-card {
        padding: 20px;
      }

      .stats-card h3 {
        font-size: 2rem;
      }

      .stats-card p {
        font-size: 0.9rem;
      }

      table {
        font-size: 0.75rem;
        min-width: 700px;
      }

      th, td {
        padding: 8px 5px;
      }
    }
  </style>
</head>
<body>

<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar">
  <div class="logo">
    <img src="../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="home_groomer.php"><i class='bx bx-calendar-check'></i>Appointments</a>
    <hr>
    <a href="history_log.php" class="active"><i class='bx bx-history'></i>History Logs</a>
    <hr>
    <a href="notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="https://pawsigcity.onrender.com/homepage/login/loginform.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>Completed Appointments History</h1>
    <p>View your completed grooming sessions and track your performance</p>
  </div>

  <div class="stats-card">
    <h3><?= pg_num_rows($result) ?></h3>
    <p>Total Completed Appointments</p>
  </div>

  <?php if (pg_num_rows($result) == 0): ?>
    <div class="table-section">
      <div class="empty-state">
        <i class='bx bx-history'></i>
        <h3>No Completed Appointments Yet</h3>
        <p>Completed appointments will appear here</p>
      </div>
    </div>
  <?php else: ?>
    <div class="table-section">
      <h2>Appointment History</h2>
      <table>
        <thead>
          <tr>
            <th>Appointment ID</th>
            <th>Appointment Date</th>
            <th>Completed Date</th>
            <th>Package</th>
            <th>Pet Name</th>
            <th>Breed</th>
            <th>Customer</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['appointment_id']) ?></td>
              <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['appointment_date']))) ?></td>
              <td>
                <?php 
                if ($row['completed_date'] !== 'Not yet completed') {
                  echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['completed_date'])));
                } else {
                  echo htmlspecialchars($row['completed_date']);
                }
                ?>
              </td>
              <td><?= htmlspecialchars($row['package_name']) ?></td>
              <td><?= htmlspecialchars($row['pet_name']) ?></td>
              <td><?= htmlspecialchars($row['pet_breed']) ?></td>
              <td><?= htmlspecialchars($row['customer_name']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>

<script>
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const menuLinks = document.querySelectorAll('.menu a');
  menuLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      }
    });
  });
});
</script>

</body>
</html> 