<?php
require '../../db.php';
session_start();

// Fetch feedback with sentiment
$query = "
    SELECT a.appointment_id, u.full_name AS client_name, p.name AS pet_name,
           a.rating, a.feedback, a.sentiment, a.appointment_date
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE a.rating IS NOT NULL
    ORDER BY a.appointment_date DESC
";

// Only run the query once and check for errors
$results = $mysqli->query($query);
if (!$results) {
    die("Query Failed: " . $mysqli->error);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Feedback Reports</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      margin: 0;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      height: 100vh;
      background-color: #A8E6CF;
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
      border-radius: 50%;
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
      color: #252525;
      border-radius: 8px;
      transition: background 0.3s, color 0.3s;
      font-weight: 600;
    }

    .menu a i {
      margin-right: 10px;
      font-size: 20px;
    }

    .menu a:hover,
    .menu a.active {
      background-color: #FFE29D;
      color: #252525;
    }

    .menu hr {
      border: none;
      border-top: 1px solid #FFE29D;
      margin: 9px 0;
    }

    /* Main Content */
    main.content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
    }

    h2 {
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    th, td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
    }

    th {
      background: #eee;
    }

    .positive { color: green; font-weight: bold; }
    .neutral  { color: #999; font-weight: bold; }
    .negative { color: red; font-weight: bold; }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/Logo.jpg" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../home_dashboard/home.php"><i class='bx bx-home'></i>Home</a>
    <hr>
    <a href="../manage_accounts/accounts.php"><i class='bx bx-user'></i>User Management</a>
    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="feedback-reports.php" class="active"><i class='bx bx-message-square-dots'></i>Feedback Reports</a>
    <hr>
    <a href="#"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<!-- Main Content -->
<main class="content">
  <h2>📊 Feedback Reports</h2>

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
      <?php while ($row = $results->fetch_assoc()): ?>
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

</body>
</html>
