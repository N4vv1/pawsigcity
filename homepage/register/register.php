<?php
session_start();
require '../../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get individual name parts
  $first_name  = trim($_POST['first_name']);
  $middle_name = trim($_POST['middle_name']);
  $last_name   = trim($_POST['last_name']);

  // Combine into full_name (with optional middle initial)
  $full_name = $first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name;

  $email     = trim($_POST['email']);
  $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $phone     = trim($_POST['phone']);
  $role      = 'customer';

  // Check if email already exists
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
      $_SESSION['success'] = "Registration successful!";
      header("Location: ../login/loginform.php");
      exit;
    } else {
      $error = "Something went wrong. Please try again.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="register.css" />
</head>
<body>

  <a href="../login/loginform.php" class="back-button">
    <i class='bx bx-arrow-back'></i> Back
  </a>

  <div class="wrapper">
    <form method="POST" action="" class="login_box">
      <div class="login-header">
        <span>REGISTER</span>
      </div>

      <?php if (isset($error)): ?>
        <p style="color: red; margin-bottom: 10px; font-size: 0.9rem;"><?= $error ?></p>
      <?php endif; ?>

      <div class="input_box">
        <input type="text" class="input-field" name="first_name" required />
        <label class="label">First Name</label>
        <i class='bx bx-user icon'></i>
      </div>

      <div class="input_box">
        <input type="text" class="input-field" name="middle_name" />
        <label class="label">Middle Name (optional)</label>
        <i class='bx bx-user icon'></i>
      </div>

      <div class="input_box">
        <input type="text" class="input-field" name="last_name" required />
        <label class="label">Last Name</label>
        <i class='bx bx-user icon'></i>
      </div>

      <div class="input_box">
        <input type="email" class="input-field" name="email" required />
        <label class="label">Email</label>
        <i class='bx bx-envelope icon'></i>
      </div>

      <div class="input_box">
        <input type="password" class="input-field" name="password" required />
        <label class="label">Password</label>
        <i class='bx bx-lock icon'></i>
      </div>

      <div class="input_box">
        <input type="text" class="input-field" name="phone" />
        <label class="label">Phone Number</label>
        <i class='bx bx-phone icon'></i>
      </div>

      <div class="input_box">
        <input type="submit" class="input-submit" value="Register" />
      </div>

      <div class="register">
        <span>Already have an account? <a href="../login/loginform.php">Login</a></span>
      </div>
    </form>
  </div>

</body>
</html>
