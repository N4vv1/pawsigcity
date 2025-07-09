<?php
session_start();
require '../db.php';

//if ($_SESSION['role'] !== 'admin') {
  //header("Location: ../homepage/main.php");
  //exit;
//}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $full_name = trim($_POST['full_name']);
  $email     = trim($_POST['email']);
  $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $phone     = trim($_POST['phone']);
  $role      = 'admin'; // Fixed role

  // Check if email is already registered
  $check = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
  $check->bind_param("s", $email);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    $error = "Email is already registered.";
  } else {
    $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $password, $phone, $role);

    if ($stmt->execute()) {
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
