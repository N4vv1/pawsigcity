<?php
session_start();
require '../../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $full_name = trim($_POST['full_name']);
  $email     = trim($_POST['email']);
  $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $phone     = trim($_POST['phone']);
  $role      = 'customer'; // default role for registration

  // Check for duplicate email
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
      header("Location: register.php");
      exit;
    } else {
      $error = "Something went wrong. Please try again.";
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>User Registration</title>
</head>
<body>

<h2>Register</h2>

<?php if (isset($_SESSION['success'])): ?>
  <p style="color: green; font-weight: bold;"><?= $_SESSION['success'] ?></p>
  <?php unset($_SESSION['success']); ?>
<?php elseif (isset($error)): ?>
  <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="">
  <label>Full Name:</label><br>
  <input type="text" name="full_name" required><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" required><br><br>

  <label>Password:</label><br>
  <input type="password" name="password" required><br><br>

  <label>Phone Number:</label><br>
  <input type="text" name="phone"><br><br>

  <button type="submit">Register</button>
</form>

</body>
</html>
