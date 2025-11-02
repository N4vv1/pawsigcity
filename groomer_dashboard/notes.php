<?php
require_once '../db.php';

// if ($_SESSION['role'] !== 'admin') {
//   header("Location: ../homepage/main.php");
//   exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $notes = pg_escape_string($conn, $_POST['notes']);
    $query = "UPDATE appointments SET notes = '$notes' WHERE appointment_id = $appointment_id";
    pg_query($conn, $query);
    echo "Notes saved.";
}

// Updated query with joins to get pet and user info
$query = "
  SELECT a.appointment_id, 
         DATE(a.appointment_date) AS appointment_date, 
         p.name AS pet_name, 
         p.breed, 
         u.first_name || ' ' || COALESCE(u.middle_name || ' ', '') || u.last_name AS full_name
  FROM appointments a
  JOIN pets p ON a.pet_id = p.pet_id
  JOIN users u ON p.user_id = u.user_id
  ORDER BY a.appointment_date DESC
";

$appointments = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Groomer | Notes</title>
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
      --site-max-width: 1300px;
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

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    .form-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 85vh;
    }

    .form-card {
      background-color: var(--white-color);
      padding: 40px 50px;
      border-radius: var(--border-radius-s);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
      max-width: 750px;
      width: 100%;
    }

    .form-card form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .form-card label {
      font-weight: var(--font-weight-semi-bold);
      color: var(--dark-color);
    }

    .form-card select,
    .form-card textarea {
      padding: 14px;
      font-size: 1rem;
      border: 1px solid var(--medium-gray-color);
      border-radius: var(--border-radius-s);
      background-color: #fefefe;
      resize: vertical;
    }

    .form-card button {
      background-color: var(--primary-color);
      color: var(--dark-color);
      border: none;
      padding: 14px;
      font-weight: var(--font-weight-semi-bold);
      font-size: 1rem;
      border-radius: var(--border-radius-s);
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .form-card button:hover {
      background-color: var(--secondary-color);
    }

    /* RESPONSIVE DESIGN */
    @media screen and (max-width: 1024px) {
      .form-card {
        padding: 35px 40px;
        max-width: 650px;
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

      .form-wrapper {
        min-height: calc(100vh - 120px);
      }

      .form-card {
        padding: 30px 25px;
        max-width: 100%;
      }

      .form-card select,
      .form-card textarea {
        padding: 12px;
        font-size: 0.95rem;
      }

      .form-card button {
        padding: 12px;
        font-size: 0.95rem;
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

      .form-card {
        padding: 25px 20px;
      }

      .form-card label {
        font-size: 0.9rem;
      }

      .form-card select,
      .form-card textarea {
        padding: 10px;
        font-size: 0.9rem;
      }

      .form-card button {
        padding: 10px;
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
    <img src="../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="home_groomer.php"><i class='bx bx-calendar-check'></i>Appointments</a>
    <hr>
    <a href="history_log.php"><i class='bx bx-history'></i>History Logs</a>
    <hr>
    <a href="session_notes.php" class="active"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href=" https://pawsigcity.onrender.com/homepage/login/loginform.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<!-- Main Content -->
<main class="content">
  <div class="form-wrapper">
    <div class="form-card">
      <form method="POST">
        <label for="appointment_id">Select Appointment:</label>
        <select name="appointment_id" required>
          <?php while ($row = pg_fetch_assoc($appointments)): ?>
            <option value="<?= $row['appointment_id'] ?>">
              <?= $row['appointment_date'] ?> - <?= htmlspecialchars($row['full_name']) ?> - <?= htmlspecialchars($row['pet_name']) ?> (<?= htmlspecialchars($row['breed']) ?>)
            </option>
          <?php endwhile; ?>
        </select>

        <label for="notes">Notes:</label>
        <textarea name="notes" rows="6" placeholder="Enter session notes here..." required></textarea>

        <button type="submit">💾 Save Notes</button>
      </form>
    </div>
  </div>
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