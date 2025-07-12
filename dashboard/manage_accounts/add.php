<?php
require_once '../../db.php';
session_start();

// Optional: restrict access to admins only
// if ($_SESSION['role'] !== 'admin') {
//   header('Location: ../../homepage/main.php');
//   exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $mysqli->real_escape_string($_POST['first_name']);
    $middle_name = $mysqli->real_escape_string($_POST['middle_name']);
    $last_name = $mysqli->real_escape_string($_POST['last_name']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $full_name = trim("$first_name $middle_name $last_name");

    $insert = $mysqli->query("INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$password', '$role')");

    if ($insert) {
        $message = "User added successfully.";
    } else {
        $message = "Error: " . $mysqli->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add User</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .form-container {
      padding: 2rem;
    }

    .card {
      background-color: var(--white-color);
      padding: 3rem;
      border-radius: var(--border-radius-s);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      max-width: 650px;
      width: 100%;
    }

    .input_box {
      position: relative;
      margin-bottom: 1.5rem;
    }

    .input-field {
      width: 100%;
      padding: 1.1rem 2.7rem 1.1rem 2.7rem;
      border: 1px solid var(--medium-gray-color);
      border-radius: var(--border-radius-s);
      background-color: var(--light-pink-color);
      font-size: var(--font-size-n);
      color: var(--dark-color);
    }

    .input-field:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
    }

    .label {
      position: absolute;
      left: 2.7rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: var(--font-size-s);
      color: var(--dark-color);
      transition: 0.3s ease;
      pointer-events: none;
    }

    .input-field:focus + .label,
    .input-field:valid + .label {
      top: -0.6rem;
      left: 1rem;
      background-color: var(--white-color);
      padding: 0 0.3rem;
      font-size: 0.75rem;
      color: var(--primary-color);
    }

    .input-submit {
      width: 100%;
      padding: 0.9rem;
      background-color: var(--primary-color);
      color: var(--dark-color);
      font-size: var(--font-size-n);
      border: none;
      border-radius: var(--border-radius-s);
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
    }

    .input-submit:hover {
      background-color: var(--secondary-color);
    }

    .message-success {
      color: green;
      background: #eaffea;
      padding: 0.8rem;
      border-radius: var(--border-radius-s);
      margin-bottom: 1rem;
      font-size: var(--font-size-s);
      text-align: center;
    }

    a.back-btn {
      display: inline-block;
      margin-bottom: 20px;
      background-color: var(--secondary-color);
      color: var(--dark-color);
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <div class="card">
      <a href="accounts.php" class="back-btn">← Back to User Management</a>

      <?php if (!empty($message)): ?>
        <div class="message-success"><?= $message ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="input_box">
          <input type="text" name="first_name" class="input-field" required>
          <label class="label">First Name</label>
        </div>
        <div class="input_box">
          <input type="text" name="middle_name" class="input-field">
          <label class="label">Middle Name (Optional)</label>
        </div>
        <div class="input_box">
          <input type="text" name="last_name" class="input-field" required>
          <label class="label">Last Name</label>
        </div>
        <div class="input_box">
          <input type="email" name="email" class="input-field" required>
          <label class="label">Email</label>
        </div>
        <div class="input_box">
          <input type="password" name="password" class="input-field" required>
          <label class="label">Password</label>
        </div>
        <div class="input_box">
          <select name="role" class="input-field" required>
            <option value="" disabled selected>Select Role</option>
            <option value="admin">Admin</option>
            <option value="customer">Customer</option>
          </select>
          <label class="label">Role</label>
        </div>

        <button type="submit" class="input-submit">➕ Add User</button>
      </form>
    </div>
  </div>
</body>
</html>
