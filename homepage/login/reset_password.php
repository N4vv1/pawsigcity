<?php
require_once '../db.php';

if (!isset($_GET['token'])) {
    die("Invalid token");
}

$token = $_GET['token'];
$query = pg_query_params($conn, "SELECT email, expires_at FROM password_resets WHERE token = $1", [$token]);
$reset = pg_fetch_assoc($query);

if (!$reset || strtotime($reset['expires_at']) < time()) {
    die("This reset link has expired or is invalid.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    pg_query_params($conn, "UPDATE users SET password = $1 WHERE email = $2", [$new_password, $reset['email']]);
    pg_query_params($conn, "DELETE FROM password_resets WHERE email = $1", [$reset['email']]);
    header("Location: login.php?success=Password+reset+successful!");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Reset Password | PAWsig City</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .container {
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      width: 400px;
      text-align: center;
    }
    h2 {
      color: #2d5f4a;
      margin-bottom: 15px;
    }
    .input-box {
      margin-bottom: 20px;
    }
    input {
      width: 100%;
      padding: 14px;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      font-size: 15px;
      outline: none;
    }
    input:focus {
      border-color: #A8E6CF;
    }
    button {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
      color: #2d5f4a;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s ease;
      box-shadow: 0 4px 15px rgba(168, 230, 207, 0.4);
    }
    button:hover {
      transform: translateY(-2px);
    }
    .back {
      display: block;
      margin-top: 20px;
      color: #5fb894;
      text-decoration: none;
      font-weight: 500;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Reset Password</h2>
    <form method="POST">
      <div class="input-box">
        <input type="password" name="password" placeholder="Enter new password" required>
      </div>
      <button type="submit">Reset Password</button>
    </form>
    <a href="login.php" class="back"><i class='bx bx-arrow-back'></i> Back to Login</a>
  </div>
</body>
</html>
