<?php
require '../../db.php';
session_start();

if ($_SESSION['role'] !== 'admin') {
     header("Location: ../homepage/main.php");
    exit;
 }

// Fetch feedback with sentiment
$query = "
    SELECT a.appointment_id, 
           u.first_name,
           u.middle_name,
           u.last_name, 
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
  <title>Feedback Reports</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
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
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>
    <a href="../manage_accounts/accounts.php"><i class='bx bx-camera'></i>User Management</a>
    <hr>
    <a href="../groomer_management/groomer_accounts.php" ><i class='bx bx-user'></i>Groomer Management</a>
    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php" class="active"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <h2>Feedback Reports</h2>
  <form action="reanalyze_sentiment.php" method="POST">
  <button type="submit" style="
      padding: 8px 14px;
      background-color: #ffdd57;
      color: #333;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    ">Reanalyze Sentiment</button>
  </form> <br>

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

</body>
</html>
