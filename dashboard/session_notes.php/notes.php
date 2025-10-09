<?php
session_start();
require '../../db.php';
require_once '../check_admin.php';

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
         u.first_name,
         u.middle_name,
         u.last_name 
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
  <title>Session Notes</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
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

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
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
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="admin.php" class="active"><i class='bx bx-home'></i>Overview</a>
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
        <a href="../../receptionist_dashboard/receptionist_home.php"><i class='bx bx-id-card'></i> Receptionists</a>
      </div>
    </div>

    <hr>
    <a href="../session_notes/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
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

</body>
</html>
