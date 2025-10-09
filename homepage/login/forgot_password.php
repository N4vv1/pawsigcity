<?php
require_once '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

    $check = pg_query_params($conn, "SELECT id FROM users WHERE email = $1", [$email]);
    if (pg_num_rows($check) > 0) {
        // Save token
        pg_query_params($conn, "INSERT INTO password_resets (email, token, expires_at) VALUES ($1, $2, $3)", [$email, $token, $expires]);

        // Prepare reset link
        $reset_link = "https://yourdomain.onrender.com/auth/reset_password.php?token=$token";

        // Send email
        $subject = "Password Reset Request";
        $message = "Click the link to reset your password: $reset_link";
        $headers = "From: noreply@pawsigcity.com";

        mail($email, $subject, $message, $headers);

        $success = "A reset link has been sent to your email address.";
    } else {
        $error = "Email not found in our system.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Forgot Password | PAWsig City</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../pawsigcity/icons/pawsig.png">
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
      margin-bottom: 10px;
    }
    p {
      color: #666;
      font-size: 14px;
      margin-bottom: 25px;
    }
    .input-box {
      margin-bottom: 20px;
      position: relative;
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
      box-shadow: 0 4px 15px rgba(168, 230, 207, 0.4);
      transition: 0.3s ease;
    }
    button:hover {
      transform: translateY(-2px);
    }
    .alert {
      padding: 12px;
      border-radius: 10px;
      margin-bottom: 15px;
      font-size: 14px;
    }
    .alert-success {
      background: #d4edda;
      color: #155724;
    }
    .alert-error {
      background: #f8d7da;
      color: #721c24;
    }
    .back {
      display: block;
      margin-top: 20px;
      color: #5fb894;
      text-decoration: none;
      font-weight: 500;
    }
    .back:hover {
      color: #7FD4B3;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Forgot Password</h2>
    <p>Enter your registered email to reset your password</p>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif (!empty($error)): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="input-box">
        <input type="email" name="email" placeholder="Enter your email" required>
      </div>
      <button type="submit">Send Reset Link</button>
    </form>

    <a href="login.php" class="back"><i class='bx bx-arrow-back'></i> Back to Login</a>
  </div>
</body>
</html>
