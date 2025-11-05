<?php
header('Content-Type: application/json');
require_once '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';
    
    if (empty($token) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Check if token is valid
    $query = pg_query_params($conn, 
        "SELECT email, expires_at FROM password_resets WHERE token = $1", 
        [$token]
    );
    
    $reset = pg_fetch_assoc($query);
    
    if (!$reset) {
        echo json_encode(['success' => false, 'message' => 'Invalid reset token']);
        exit;
    }
    
    if (strtotime($reset['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'This reset link has expired']);
        exit;
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update = pg_query_params($conn, 
        "UPDATE users SET password = $1 WHERE email = $2", 
        [$hashed_password, $reset['email']]
    );
    
    if ($update) {
        // Delete used token
        pg_query_params($conn, "DELETE FROM password_resets WHERE email = $1", [$reset['email']]);
        echo json_encode(['success' => true, 'message' => 'Password reset successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
    exit;
}

// If GET request with token, show the form
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    // Verify token exists
    $query = pg_query_params($conn, 
        "SELECT expires_at FROM password_resets WHERE token = $1", 
        [$token]
    );
    
    if (!$query || pg_num_rows($query) === 0) {
        die("Invalid or expired reset link");
    }
    
    $reset = pg_fetch_assoc($query);
    if (strtotime($reset['expires_at']) < time()) {
        die("This reset link has expired");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Reset Password | PAWsig City</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }
    .container {
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 450px;
    }
    h2 {
      color: #2d5f4a;
      margin-bottom: 10px;
      text-align: center;
    }
    p {
      color: #666;
      text-align: center;
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
      box-sizing: border-box;
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
    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    .alert {
      padding: 12px;
      border-radius: 10px;
      margin-bottom: 15px;
      font-size: 14px;
      display: none;
    }
    .alert.show {
      display: block;
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
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Reset Your Password</h2>
    <p>Enter your new password below</p>

    <div id="reset-alerts"></div>

    <form id="reset-password-form">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      
      <div class="input-box">
        <input type="password" id="new_password" name="password" placeholder="New Password" required>
      </div>
      
      <div class="input-box">
        <input type="password" id="confirm_password" placeholder="Confirm Password" required>
      </div>
      
      <button type="submit" id="reset-btn">Reset Password</button>
    </form>

    <a href="../loginform.php" class="back"><i class='bx bx-arrow-back'></i> Back to Login</a>
  </div>
<script>
    // Define these functions first so they're available immediately
    function openForgotPasswordModal() {
      document.getElementById('forgotPasswordModal').classList.add('active');
      document.getElementById('forgot_email').focus();
    }

    function closeForgotPasswordModal() {
      document.getElementById('forgotPasswordModal').classList.remove('active');
      document.getElementById('forgot-password-form').reset();
      document.getElementById('forgot-alerts').innerHTML = '';
    }

    function closeResetPasswordModal() {
      document.getElementById('resetPasswordModal').classList.remove('active');
      document.getElementById('reset-password-form').reset();
      document.getElementById('reset-alerts').innerHTML = '';
    }

    function togglePassword(inputId, icon) {
      const input = document.getElementById(inputId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
      } else {
        input.type = 'password';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
      }
    }

    // Remove error state when user starts typing
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('.input-field');
      
      inputs.forEach(input => {
        input.addEventListener('input', function() {
          const inputBox = this.closest('.input-box');
          inputBox.classList.remove('error', 'success');
        });
      });
    });

    // Function to show error on specific field
    function showFieldError(fieldId, message) {
      const inputBox = document.getElementById(fieldId);
      if (inputBox) {
        inputBox.classList.add('error');
        const errorMsg = inputBox.querySelector('.error-message');
        if (errorMsg && message) {
          errorMsg.textContent = message;
        }
      }
    }

    // Function to show success on specific field
    function showFieldSuccess(fieldId) {
      const inputBox = document.getElementById(fieldId);
      if (inputBox) {
        inputBox.classList.add('success');
        inputBox.classList.remove('error');
      }
    }

    // Password strength checker
    document.addEventListener('DOMContentLoaded', function() {
      const regPassword = document.getElementById('reg_password');
      if (regPassword) {
        regPassword.addEventListener('input', function() {
          const password = this.value;
          const strengthBar = this.parentElement.querySelector('.password-strength-bar');
          const strengthContainer = this.parentElement.querySelector('.password-strength');
          const hint = this.parentElement.querySelector('.password-hint');
          
          if (password.length === 0) {
            strengthContainer.classList.remove('active');
            hint.classList.remove('active');
            return;
          }
          
          strengthContainer.classList.add('active');
          hint.classList.add('active');
          
          let strength = 0;
          if (password.length >= 8) strength++;
          if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
          if (/[0-9]/.test(password)) strength++;
          if (/[^a-zA-Z0-9]/.test(password)) strength++;
          
          strengthBar.className = 'password-strength-bar';
          
          if (strength <= 2) {
            strengthBar.classList.add('weak');
            hint.textContent = 'Weak password - add more characters';
            hint.style.color = '#ff4d4d';
          } else if (strength === 3) {
            strengthBar.classList.add('medium');
            hint.textContent = 'Medium strength - almost there!';
            hint.style.color = '#ffa500';
          } else {
            strengthBar.classList.add('strong');
            hint.textContent = 'Strong password!';
            hint.style.color = '#4caf50';
          }
        });
      }
    });
  </script>
  <script src="../auth.js"></script>
</body>
</html>
<?php
}
?>