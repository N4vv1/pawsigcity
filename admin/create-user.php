<?php
session_start();
require '../db.php';

//if ($_SESSION['role'] !== 'admin') {
  //header("Location: ../homepage/main.php");
  //exit;
//}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $firstname  = trim($_POST['firstname']);
  $middlename = trim($_POST['middlename']);
  $lastname   = trim($_POST['lastname']);
  $email     = trim($_POST['email']);
  $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $phone     = trim($_POST['phone']);
  $role      = 'admin'; // Fixed role

  // Check if email is already registered
  $check = pg_query_params(
    $conn,
    "SELECT 1 FROM users WHERE email = $1 LIMIT 1",
    [$email]
  );

  if (!$check) {
    $error = "Database error: " . pg_last_error($conn);
  } elseif (pg_num_rows($check) > 0) {
    $error = "Email is already registered.";
  } else {
    // Insert new admin user
    $insert = pg_query_params(
      $conn,
      "INSERT INTO users (full_name, email, password, phone, role) VALUES ($1, $2, $3, $4, $5)",
      [$full_name, $email, $password, $phone, $role]
    );

    if ($insert) {
      $success = "Admin account created successfully.";
    } else {
      $error = "Something went wrong. Please try again.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Admin Account</title>
  <link rel="stylesheet" href="register.css">
</head>
<body>

  <a href="admin-dashboard.php" class="back-button">
    ← Back to Dashboard
  </a>

  <div class="wrapper">
    <form method="POST" class="login_box">
      <div class="login-header">
        <span>Create Admin Account</span>
      </div>

      <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
      <?php elseif (isset($success)): ?>
        <p style="color: green;"><?= $success ?></p>
      <?php endif; ?>

      <div class="input_box">
        <label class="label">Full Name</label>
        <input type="text" class="input-field" name="full_name" required />
      </div>

      <div class="input_box">
        <label class="label">Email</label>
        <input type="email" class="input-field" name="email" required />
      </div>

      <div class="input_box">
        <label class="label">Password</label>
        <input type="password" class="input-field" name="password" required />
      </div>

      <div class="input_box">
        <label class="label">Phone Number</label>
        <input type="text" class="input-field" name="phone" />
      </div>

      <div class="input_box">
        <input type="submit" class="input-submit" value="Create Admin" />
      </div>
    </form>
  </div>

</body>
</html>
