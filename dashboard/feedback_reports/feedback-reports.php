<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';
// Fetch feedback with sentiment
$query = "
    SELECT a.appointment_id, 
           u.first_name || ' ' || COALESCE(u.middle_name || ' ', '') || u.last_name AS client_name,
           p.name AS pet_name,
           a.rating, 
           a.feedback, 
           a.sentiment, 
           a.appointment_date
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE a.rating IS NOT NULL
    ORDER BY a.appointment_date DESC
";

$results = pg_query($conn, $query);
if (!$results) {
    die("Query Failed: " . pg_last_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | Feedback Reports</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">

  <script>
    function toggleDropdown(event) {
      event.preventDefault();
      const dropdown = event.currentTarget.nextElementSibling;
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    // Close dropdown if clicked outside
    window.onclick = function(event) {
      if (!event.target.matches('.dropdown-toggle')) {
        const dropdowns = document.getElementsByClassName("dropdown-menu");
        for (let i = 0; i < dropdowns.length; i++) {
          const openDropdown = dropdowns[i];
          if (openDropdown.style.display === 'block') {
            openDropdown.style.display = 'none';
          }
        }
      }
    };
  </script>
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

    /* MOBILE MENU BUTTON - Base styles first */
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
      top: 0;
      left: 0;
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

    .content {
      margin-left: 260px;
      padding: 40px;
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
      font-size: 0.95rem;
      color: var(--dark-color);
      background: var(--white-color);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
    }

    th, td {
      padding: 16px 12px;
      border: 1px solid var(--medium-gray-color);
      text-align: left;
    }

    th {
      background: var(--primary-color);
      font-weight: var(--font-weight-bold);
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #ffe29d33;
    }

    .positive { color: green; font-weight: bold; }
    .neutral  { color: #999; font-weight: bold; }
    .negative { color: red; font-weight: bold; }

    /* Dropdown styles */
    .dropdown {
      position: relative;
    }

    .dropdown-toggle {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 12px;
      text-decoration: none;
      color: var(--dark-color);
      border-radius: var(--border-radius-s);
      transition: background 0.3s, color 0.3s;
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
    }

    .dropdown-toggle:hover {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .dropdown-menu {
      display: none;
      flex-direction: column;
      gap: 5px;
      margin-left: 20px;
      margin-top: 5px;
    }

    .dropdown-menu a {
      padding: 8px 12px;
      font-size: 0.9rem;
    }

    /* Reanalyze button */
    form button {
      padding: 8px 14px;
      background-color: #ffdd57;
      color: #333;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      margin-bottom: 20px;
    }

    form button:hover {
      background-color: #fdd56c;
    }

    /* RESPONSIVE DESIGN - Media queries AFTER base styles */
    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.9rem;
      }

      th, td {
        padding: 12px 10px;
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

      form button {
        padding: 6px 12px;
        font-size: 0.9rem;
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
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>

    <!-- USERS DROPDOWN -->
    <div class="dropdown">
      <a href="javascript:void(0)" class="dropdown-toggle" onclick="toggleDropdown(event)">
        <span><i class='bx bx-user'></i> Users</span>
        <i class='bx bx-chevron-down'></i>
      </a>
      <div class="dropdown-menu">
        <a href="../manage_accounts/accounts.php"><i class='bx bx-user-circle'></i> All Users</a>
        <a href="../groomer_management/groomer_accounts.php"><i class='bx bx-scissors'></i> Groomers</a>
      </div>
    </div>

    <hr>
    <a href="../session_notes/notes.php"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php" class="active"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <form action="reanalyze_sentiment.php" method="POST">
    <button type="submit">Reanalyze Sentiment</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Client</th>
        <th>Pet</th>
        <th>Date</th>
        <th>Rating</th>
        <th>Feedback</th>
        <th>Sentiment</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = pg_fetch_assoc($results)): ?>
        <tr>
          <td><?= htmlspecialchars($row['client_name']) ?></td>
          <td><?= htmlspecialchars($row['pet_name']) ?></td>
          <td><?= htmlspecialchars($row['appointment_date']) ?></td>
          <td>⭐ <?= $row['rating'] ?>/5</td>
          <td><?= nl2br(htmlspecialchars($row['feedback'])) ?></td>
          <td class="<?= htmlspecialchars($row['sentiment']) ?>">
            <?= ucfirst($row['sentiment']) ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</main>

<script>
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation(); // IMPORTANT: Stop event from bubbling up
  const dropdown = event.currentTarget.nextElementSibling;
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Close dropdown if clicked outside
document.addEventListener('click', function(event) {
  // Check if click is outside dropdown
  if (!event.target.closest('.dropdown')) {
    const dropdowns = document.getElementsByClassName("dropdown-menu");
    for (let i = 0; i < dropdowns.length; i++) {
      dropdowns[i].style.display = 'none';
    }
  }
});

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

function openModal() { 
  document.getElementById('groomerModal').style.display='flex'; 
}

function closeModal() { 
  document.getElementById('groomerModal').style.display='none'; 
}

function closeEditModal() { 
  document.getElementById('editGroomerModal').style.display='none'; 
  window.history.replaceState(null,null,window.location.pathname); 
}

// Close modal if clicked outside
document.addEventListener('click', function(event) {
  const modal = document.getElementById('groomerModal');
  if(event.target === modal) closeModal();
});

// Close sidebar when clicking a link on mobile
document.addEventListener('DOMContentLoaded', function() {
  const menuLinks = document.querySelectorAll('.menu a:not(.dropdown-toggle)'); // Exclude dropdown toggle
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