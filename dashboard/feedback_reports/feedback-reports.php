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

$results = $mysqli->query($query);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Feedback Reports</title>
  <style>
    body { font-family: Arial; background: #f9f9f9; padding: 20px; }
    h2 { margin-bottom: 20px; }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
    }
    th { background: #eee; }

    .positive { color: green; font-weight: bold; }
    .neutral { color: #999; font-weight: bold; }
    .negative { color: red; font-weight: bold; }
  </style>
</head>
<body>

<a href="../home_dashboard/home.php" style="
  display: inline-block;
  padding: 10px 16px;
  background-color: #A8E6CF;
  color: black;
  text-decoration: none;
  border-radius: 5px;
  margin-bottom: 20px;
"> ⬅ Back to Dashboard</a>


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

</body>
</html>
