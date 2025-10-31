<?php
include '../db.php'; // connection file

$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        p.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed
    FROM appointments a
    JOIN packages p ON a.package_id = p.package_id
    JOIN pets pet ON a.pet_id = pet.pet_id
    WHERE a.status = 'completed'
    ORDER BY a.appointment_date DESC
";

$result = pg_query($conn, $query);

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
    }

    /* MOBILE MENU BUTTON */
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

    /* SIDEBAR OVERLAY */
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

    /* Content */
    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    h2 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 25px;
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

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #ffe29d33;
    }

    /* RESPONSIVE DESIGN */
    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.9rem;
      }

      th, td {
        padding: 12px 8px;
      }
    }

    @media screen and (max-width: 768px) {
      /* Show mobile menu button */
      .mobile-menu-btn {
        display: block;
      }

      /* Hide sidebar off-screen by default */
      .sidebar {
        transform: translateX(-100%);
      }

      /* Show sidebar when active */
      .sidebar.active {
        transform: translateX(0);
      }

      /* Adjust content area */
      .content {
        margin-left: 0;
        width: 100%;
        padding: 80px 20px 40px;
      }

      table {
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 8px;
      }
    }

    @media screen and (max-width: 480px) {
      .content {
        padding: 70px 15px 30px;
      }

      .sidebar .logo img {
        width: 60px;
        height: 60px;
      }

      .menu a {
        padding: 8px 10px;
        font-size: 0.9rem;
      }

      .menu a i {
        font-size: 18px;
      }

      h2 {
        font-size: 1.5rem;
      }

      table {
        font-size: 0.75rem;
      }

      th, td {
        padding: 8px 5px;
      }
    }
  </style>
</head>
<body>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
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
    <a href="logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<!-- Main Content -->
<main class="content">
  <table>
    <thead>
      <tr>
        <th>Appointment ID</th>
        <th>Date</th>
        <th>Package</th>
        <th>Pet Name</th>
        <th>Breed</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = pg_fetch_assoc($result)): ?>
        <tr>
          <td><?= htmlspecialchars($row['appointment_id']) ?></td>
          <td><?= htmlspecialchars($row['appointment_date']) ?></td>
          <td><?= htmlspecialchars($row['package_name']) ?></td>
          <td><?= htmlspecialchars($row['pet_name']) ?></td>
          <td><?= htmlspecialchars($row['pet_breed']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
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

// Close sidebar when clicking a link on mobile
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