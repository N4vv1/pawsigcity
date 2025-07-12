<?php
require_once '../../db.php';
session_start();

// Optional: restrict access to admins only
// if ($_SESSION['role'] !== 'admin') {
//   header('Location: ../../homepage/main.php');
//   exit;
// }

$users = $mysqli->query("SELECT * FROM users ORDER BY full_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Management</title>
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background: #f4f4f4;
      padding: 30px;
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
    }

    .container {
      background: #fff;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      max-width: 1000px;
      margin: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #A8E6CF;
    }

    .actions a {
      margin: 0 5px;
      padding: 5px 10px;
      text-decoration: none;
      border-radius: 4px;
      font-size: 14px;
    }

    .edit-btn {
      background-color: #FFE29D;
      color: #000;
    }

    .delete-btn {
      background-color: #FF6B6B;
      color: #fff;
    }

    .add-user {
      display: inline-block;
      margin-bottom: 10px;
      background-color: #A8E6CF;
      padding: 10px 20px;
      color: #000;
      text-decoration: none;
      border-radius: 6px;
    }

    .add-user:hover {
      background-color: #80d5b0;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>User Management</h1>
    <a href="add.php" class="add-user">➕ Add New User</a>
    <table>
      <thead>
        <tr>
          <th>User ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($user = $users->fetch_assoc()): ?>
          <tr>
            <td><?= $user['user_id'] ?></td>
            <td><?= htmlspecialchars($user['full_name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['phone']) ?></td>
            <td><?= $user['role'] ?></td>
            <td class="actions">
              <a href="edit.php?id=<?= $user['user_id'] ?>" class="edit-btn">Edit</a>
              <a href="delete.php?id=<?= $user['user_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
