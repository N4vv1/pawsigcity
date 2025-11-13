<?php session_start(); 
// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PAWsig City | Authentication</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      margin: 0;
      overflow-x: hidden;
      position: relative;
    }

    body::before {
      content: '';
      position: absolute;
      width: 500px;
      height: 500px;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 50%;
      top: -250px;
      right: -250px;
      animation: float 6s ease-in-out infinite;
      z-index: 0;
    }

    body::after {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 50%;
      bottom: -200px;
      left: -200px;
      animation: float 8s ease-in-out infinite reverse;
      z-index: 0;
    }

    /* Left Side - Branding */
    .brand-side {
      flex: 1;
      background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 60px 40px;
      position: relative;
      overflow: hidden;
      min-height: 100vh;
    }

    .brand-side::before {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      top: -200px;
      right: -100px;
      animation: float 8s ease-in-out infinite;
    }

    .brand-side::after {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      bottom: -150px;
      left: -100px;
      animation: float 6s ease-in-out infinite reverse;
    }

    .brand-content {
      position: relative;
      z-index: 1;
      color: #2d5f4a;
      text-align: center;
      max-width: 500px;
    }

    .brand-content h1 {
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 20px;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .brand-content p {
      font-size: 18px;
      line-height: 1.6;
      opacity: 0.9;
      max-width: 400px;
      margin: 0 auto;
    }

    .brand-features {
      margin-top: 40px;
      display: flex;
      flex-direction: column;
      gap: 20px;
      width: 100%;
      max-width: 350px;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 15px;
      background: rgba(255, 255, 255, 0.2);
      padding: 15px 25px;
      border-radius: 12px;
      backdrop-filter: blur(10px);
    }

    .feature-item i {
      font-size: 24px;
      color: #2d5f4a;
      flex-shrink: 0;
    }

    .feature-item span {
      font-size: 16px;
      font-weight: 500;
    }

    /* Right Side - Form */
    .form-side {
      flex: 1;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      position: relative;
      min-height: 100vh;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) translateX(0px); }
      50% { transform: translateY(-20px) translateX(20px); }
    }

    .back-button {
      position: absolute;
      top: 30px;
      left: 30px;
      display: flex;
      align-items: center;
      gap: 8px;
      color: #2d5f4a;
      text-decoration: none;
      font-weight: 500;
      font-size: 15px;
      padding: 10px 20px;
      border-radius: 50px;
      background: rgba(168, 230, 207, 0.3);
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
      z-index: 10;
    }

    .back-button:hover {
      background: rgba(168, 230, 207, 0.5);
      transform: translateX(-5px);
    }

    .container {
      width: 100%;
      max-width: 500px;
      position: relative;
      z-index: 1;
    }

    .logo-section {
      text-align: center;
      margin-bottom: 30px;
    }

    .logo-section h2 {
      font-size: 28px;
      font-weight: 700;
      color: #2d5f4a;
      margin-bottom: 8px;
    }

    .logo-section p {
      color: #666;
      font-size: 14px;
    }

    .tab-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
      background: #f0f0f0;
      padding: 5px;
      border-radius: 12px;
    }

    .tab-btn {
      flex: 1;
      padding: 12px;
      border: none;
      background: transparent;
      font-size: 15px;
      font-weight: 600;
      color: #666;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .tab-btn.active {
      background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
      color: #2d5f4a;
      box-shadow: 0 4px 12px rgba(168, 230, 207, 0.4);
    }

    .form-content {
      position: relative;
    }

    .form-section {
      display: none;
      animation: fadeIn 0.5s ease;
    }

    .form-section.active {
      display: block;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-message {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      animation: slideDown 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-message i {
      font-size: 20px;
      flex-shrink: 0;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .input-box {
      position: relative;
      margin-bottom: 25px;
    }

    .input-field {
      width: 100%;
      padding: 14px 45px 14px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #fafafa;
      outline: none;
    }

    .input-field:focus {
      border-color: #A8E6CF;
      background: white;
      box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.15);
    }

    .input-field:focus + .label,
    .input-field:valid + .label {
      transform: translateY(-32px) scale(0.85);
      color: #5fb894;
      background: white;
      padding: 0 8px;
    }

    .label {
      position: absolute;
      left: 15px;
      top: 14px;
      color: #999;
      font-size: 15px;
      pointer-events: none;
      transition: all 0.3s ease;
    }

    .icon {
      position: absolute;
      right: 15px;
      top: 14px;
      font-size: 20px;
      color: #999;
      transition: color 0.3s ease;
      pointer-events: none;
    }

    .input-field:focus ~ .icon {
      color: #A8E6CF;
    }

    .row-inputs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .remember-forgot {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      font-size: 14px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .remember-me input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
      accent-color: #A8E6CF;
    }

    .forgot a {
      color: #5fb894;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
      cursor: pointer;
    }

    .forgot a:hover {
      color: #7FD4B3;
    }

    .submit-btn {
      width: 100%;
      padding: 15px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
      color: #2d5f4a;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(168, 230, 207, 0.4);
    }

    .submit-btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(168, 230, 207, 0.5);
    }

    .submit-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .password-toggle {
      position: absolute;
      right: 15px;
      top: 14px;
      font-size: 20px;
      color: #999;
      cursor: pointer;
      transition: color 0.3s ease;
      z-index: 2;
    }

    .password-toggle:hover {
      color: #5fb894;
    }

    .input-box.has-toggle .icon {
      right: 45px;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(8px);
      animation: fadeIn 0.3s ease;
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: white;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      width: 90%;
      max-width: 480px;
      animation: slideUp 0.4s ease;
      position: relative;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(50px) scale(0.9);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .modal-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .modal-header h3 {
      font-size: 26px;
      color: #2d5f4a;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .modal-header p {
      color: #666;
      font-size: 14px;
      line-height: 1.5;
    }

    .modal-header .email-display {
      color: #5fb894;
      font-weight: 600;
      margin-top: 5px;
    }

    .close-modal {
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 28px;
      color: #999;
      cursor: pointer;
      transition: all 0.3s ease;
      line-height: 1;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    .close-modal:hover {
      color: #2d5f4a;
      background: #f0f0f0;
      transform: rotate(90deg);
    }

    /* OTP Input Styles */
    .otp-container {
      display: flex;
      gap: 12px;
      justify-content: center;
      margin: 30px 0;
    }

    .otp-input {
      width: 55px;
      height: 60px;
      text-align: center;
      font-size: 24px;
      font-weight: 600;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      background: #fafafa;
      transition: all 0.3s ease;
      outline: none;
      color: #2d5f4a;
    }

    .otp-input:focus {
      border-color: #A8E6CF;
      background: white;
      box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.15);
      transform: scale(1.05);
    }

    .otp-input.filled {
      background: linear-gradient(135deg, #A8E6CF 0%, #7FD4B3 100%);
      border-color: #A8E6CF;
      color: #2d5f4a;
    }

    .otp-input.error {
      border-color: #ff4d4d;
      background: #fff5f5;
      animation: shake 0.5s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    .resend-section {
      text-align: center;
      margin-top: 25px;
      font-size: 14px;
      color: #666;
    }

    .resend-link {
      color: #5fb894;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .resend-link:hover:not(.disabled) {
      color: #7FD4B3;
      text-decoration: underline;
    }

    .resend-link.disabled {
      color: #ccc;
      cursor: not-allowed;
    }

    .timer {
      color: #5fb894;
      font-weight: 600;
    }

    /* Loading Spinner */
    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(45, 95, 74, 0.2);
      border-top: 2px solid #2d5f4a;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-left: 8px;
      vertical-align: middle;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 968px) {
      body {
        flex-direction: column;
      }

      .brand-side {
        min-height: auto;
        padding: 50px 30px 40px;
      }

      .brand-content h1 {
        font-size: 36px;
      }

      .form-side {
        min-height: auto;
        padding: 40px 30px 50px;
      }

      .otp-input {
        width: 48px;
        height: 55px;
        font-size: 20px;
      }
    }

    @media (max-width: 640px) {
      .modal-content {
        padding: 30px 25px;
        max-width: 95%;
      }

      .row-inputs {
        grid-template-columns: 1fr;
        gap: 0;
      }

      .otp-input {
        width: 45px;
        height: 50px;
        font-size: 18px;
      }

      .otp-container {
        gap: 8px;
      }
    }
  </style>
</head>
<body>

  <!-- Left Side - Branding -->
  <div class="brand-side">
    <a href="../../index.php" class="back-button">
      <i class='bx bx-arrow-back'></i> Back
    </a>
    
    <div class="brand-content">
      <h1>This is PAWsig City!</h1>
      <p>We care for your pets when they need it most.</p>
      
      <div class="brand-features">
        <div class="feature-item">
          <i class='bx bxs-hotel'></i>
          <span>Pet Hotel</span>
        </div>
        <div class="feature-item">
          <i class='bx bxs-home-heart'></i>
          <span>Home Service</span>
        </div>
        <div class="feature-item">
          <i class='bx bx-cut'></i>
          <span>Pet Grooming</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Side - Form -->
  <div class="form-side">
    <div class="container">
      <div class="form-container">
        <div class="logo-section">
          <h2>Welcome Back!</h2>
          <p>Please enter your details to continue</p>
        </div>

        <div class="tab-buttons">
          <button class="tab-btn active" onclick="switchTab('login')">Login</button>
          <button class="tab-btn" onclick="switchTab('register')">Register</button>
        </div>

        <div class="form-content">
          <!-- Login Form -->
          <div id="login-form" class="form-section active">
            <div id="login-alerts">
              <?php
              if (isset($_SESSION['login_error'])) {
                  echo '<div class="alert-message alert-error">';
                  echo '<i class="bx bx-error-circle"></i>';
                  echo '<span>' . htmlspecialchars($_SESSION['login_error']) . '</span>';
                  echo '</div>';
                  unset($_SESSION['login_error']);
              }
              if (isset($_SESSION['login_success'])) {
                  echo '<div class="alert-message alert-success">';
                  echo '<i class="bx bx-check-circle"></i>';
                  echo '<span>' . htmlspecialchars($_SESSION['login_success']) . '</span>';
                  echo '</div>';
                  unset($_SESSION['login_success']);
              }
              ?>
            </div>
            
            <form action="login-handler.php" method="post" autocomplete="off">
              <div class="input-box">
                <input type="email" class="input-field" name="email" id="login_email" required />
                <label class="label">Email</label>
                <i class='bx bx-user icon'></i>
              </div>

              <div class="input-box has-toggle">
                <input type="password" class="input-field" name="password" id="login_password" required autocomplete="new-password" />
                <label class="label">Password</label>
                <i class='bx bx-lock-alt icon'></i>
                <i class='bx bx-hide password-toggle' onclick="togglePassword('login_password', this)"></i>
              </div>

              <div class="remember-forgot">
                <div class="remember-me">
                  <input type="checkbox" id="remember" />
                  <label for="remember">Remember me</label>
                </div>
                <div class="forgot">
                  <a onclick="openForgotPasswordModal()">Forgot password?</a>
                </div>
              </div>

              <button type="submit" class="submit-btn">Login</button>
            </form>
          </div>

          <!-- Register Form -->
          <div id="register-form" class="form-section">
            <div id="register-alerts"></div>
            
            <form id="registration-form" autocomplete="off">
              <div class="row-inputs">
                <div class="input-box">
                  <input type="text" class="input-field" name="first_name" id="first_name" required autocomplete="off" />
                  <label class="label">First Name</label>
                  <i class='bx bx-user icon'></i>
                </div>

                <div class="input-box">
                  <input type="text" class="input-field" name="last_name" id="last_name" required autocomplete="off" />
                  <label class="label">Last Name</label>
                  <i class='bx bx-user icon'></i>
                </div>
              </div>

              <div class="input-box">
                <input type="text" class="input-field" name="middle_name" id="middle_name" autocomplete="off" />
                <label class="label">Middle Name (Optional)</label>
                <i class='bx bx-user icon'></i>
              </div>

              <div class="input-box">
                <input type="email" class="input-field" name="email" id="reg_email" required autocomplete="off" />
                <label class="label">Email</label>
                <i class='bx bx-envelope icon'></i>
              </div>

              <div class="input-box has-toggle">
                <input type="password" class="input-field" name="password" id="reg_password" required autocomplete="new-password" />
                <label class="label">Password</label>
                <i class='bx bx-lock icon'></i>
                <i class='bx bx-hide password-toggle' onclick="togglePassword('reg_password', this)"></i>
              </div>

              <div class="input-box">
                <input type="text" class="input-field" name="phone" id="phone" required autocomplete="off" />
                <label class="label">Phone Number</label>
                <i class='bx bx-phone icon'></i>
              </div>

              <button type="submit" class="submit-btn">Create Account</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- OTP Verification Modal -->
  <div id="otpModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeOTPModal()">&times;</span>
      <div class="modal-header">
        <h3>Verify Your Email</h3>
        <p>Enter the 6-digit code sent to</p>
        <p class="email-display" id="otp-email-display"></p>
      </div>
      
      <div id="otp-alerts"></div>
      
      <div class="otp-container">
        <input type="text" class="otp-input" maxlength="1" data-index="0" />
        <input type="text" class="otp-input" maxlength="1" data-index="1" />
        <input type="text" class="otp-input" maxlength="1" data-index="2" />
        <input type="text" class="otp-input" maxlength="1" data-index="3" />
        <input type="text" class="otp-input" maxlength="1" data-index="4" />
        <input type="text" class="otp-input" maxlength="1" data-index="5" />
      </div>
      
      <button type="button" class="submit-btn" id="verify-otp-btn" onclick="verifyOTP()">
        Verify & Continue
      </button>
      
      <div class="resend-section">
        <p>Didn't receive code? <a class="resend-link" id="resend-link" onclick="resendOTP()">Resend OTP</a></p>
        <p class="timer" id="timer"></p>
      </div>
    </div>
  </div>

  <!-- Forgot Password Modal -->
  <div id="forgotPasswordModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeForgotPasswordModal()">&times;</span>
      <div class="modal-header">
        <h3>Reset Password</h3>
        <p>Enter your email to receive a verification code</p>
      </div>
      <div id="forgot-alerts"></div>
      <form id="forgot-password-form">
        <div class="input-box">
          <input type="email" class="input-field" name="email" id="forgot_email" required />
          <label class="label">Email Address</label>
          <i class='bx bx-envelope icon'></i>
        </div>
        <button type="submit" class="submit-btn">Send Verification Code</button>
      </form>
    </div>
  </div>

  <!-- Reset Password Modal -->
  <div id="resetPasswordModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeResetPasswordModal()">&times;</span>
      <div class="modal-header">
        <h3>Create New Password</h3>
        <p>Enter your new password</p>
      </div>
      <div id="reset-alerts"></div>
      <form id="reset-password-form">
        <div class="input-box has-toggle">
          <input type="password" class="input-field" name="new_password" id="new_password" required />
          <label class="label">New Password</label>
          <i class='bx bx-lock icon'></i>
          <i class='bx bx-hide password-toggle' onclick="togglePassword('new_password', this)"></i>
        </div>
        
        <div class="input-box has-toggle">
          <input type="password" class="input-field" name="confirm_password" id="confirm_password" required />
          <label class="label">Confirm Password</label>
          <i class='bx bx-lock-alt icon'></i>
          <i class='bx bx-hide password-toggle' onclick="togglePassword('confirm_password', this)"></i>
        </div>
        
        <button type="submit" class="submit-btn">Reset Password</button>
      </form>
    </div>
  </div>

  <script>
    // Global variables
    let currentEmail = '';
    let currentPurpose = '';
    let resendTimer = null;
    let resendCountdown = 60;
    let pendingFormData = null;

    // Tab switching
    function switchTab(tab) {
      const loginForm = document.getElementById('login-form');
      const registerForm = document.getElementById('register-form');
      const tabButtons = document.querySelectorAll('.tab-btn');

      tabButtons.forEach(btn => btn.classList.remove('active'));

      if (tab === 'login') {
        loginForm.classList.add('active');
        registerForm.classList.remove('active');
        tabButtons[0].classList.add('active');
      } else {
        registerForm.classList.add('active');
        loginForm.classList.remove('active');
        tabButtons[1].classList.add('active');
      }
    }

    // Alert functions
    function showAlert(containerId, message, type) {
      const container = document.getElementById(containerId);
      container.innerHTML = '';
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert-message alert-${type}`;
      alertDiv.innerHTML = `<i class='bx ${type === 'success' ? 'bx-check-circle' : 'bx-error-circle'}'></i><span>${message}</span>`;
      container.appendChild(alertDiv);

      setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
      }, 5000);
    }

    // Password toggle
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

    // OTP Modal Functions
    function openOTPModal(email, purpose) {
      currentEmail = email;
      currentPurpose = purpose;
      document.getElementById('otp-email-display').textContent = email;
      document.getElementById('otpModal').classList.add('active');
      document.getElementById('otp-alerts').innerHTML = '';
      
      // Clear and focus first input
      const otpInputs = document.querySelectorAll('.otp-input');
      otpInputs.forEach(input => {
        input.value = '';
        input.classList.remove('filled', 'error');
      });
      otpInputs[0].focus();
      
      // Start resend timer
      startResendTimer();
    }

    function closeOTPModal() {
      document.getElementById('otpModal').classList.remove('active');
      clearInterval(resendTimer);
    }

    function openForgotPasswordModal() {
      document.getElementById('forgotPasswordModal').classList.add('active');
      document.getElementById('forgot_email').focus();
    }

    function closeForgotPasswordModal() {
      document.getElementById('forgotPasswordModal').classList.remove('active');
      document.getElementById('forgot-password-form').reset();
      document.getElementById('forgot-alerts').innerHTML = '';
    }

    function openResetPasswordModal() {
      document.getElementById('resetPasswordModal').classList.add('active');
      document.getElementById('new_password').focus();
    }

    function closeResetPasswordModal() {
      document.getElementById('resetPasswordModal').classList.remove('active');
      document.getElementById('reset-password-form').reset();
      document.getElementById('reset-alerts').innerHTML = '';
    }

    // OTP Input Handling
    document.addEventListener('DOMContentLoaded', function() {
      const otpInputs = document.querySelectorAll('.otp-input');
      
      otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
          const value = e.target.value;
          
          // Only allow numbers
          if (!/^\d*$/.test(value)) {
            e.target.value = '';
            return;
          }
          
          // Add filled class
          if (value) {
            e.target.classList.add('filled');
            e.target.classList.remove('error');
          } else {
            e.target.classList.remove('filled');
          }
          
          // Auto-focus next input
          if (value && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
          }
        });
        
        input.addEventListener('keydown', function(e) {
          // Handle backspace
          if (e.key === 'Backspace' && !e.target.value && index > 0) {
            otpInputs[index - 1].focus();
          }
          
          // Handle paste
          if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            navigator.clipboard.readText().then(text => {
              const digits = text.replace(/\D/g, '').slice(0, 6);
              digits.split('').forEach((digit, i) => {
                if (otpInputs[i]) {
                  otpInputs[i].value = digit;
                  otpInputs[i].classList.add('filled');
                }
              });
              if (digits.length === 6) {
                verifyOTP();
              }
            });
          }
        });
      });
    });

    // Send OTP
    async function sendOTP(email, purpose) {
      try {
        const response = await fetch('send-otp.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `email=${encodeURIComponent(email)}&purpose=${encodeURIComponent(purpose)}`
        });
        
        const data = await response.json();
        return data;
      } catch (error) {
        console.error('Send OTP Error:', error);
        return { success: false, message: 'Failed to send OTP. Please try again.' };
      }
    }

    // Verify OTP
    async function verifyOTP() {
      const otpInputs = document.querySelectorAll('.otp-input');
      const otp = Array.from(otpInputs).map(input => input.value).join('');
      
      if (otp.length !== 6) {
        showAlert('otp-alerts', 'Please enter all 6 digits', 'error');
        return;
      }
      
      const btn = document.getElementById('verify-otp-btn');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Verifying...<span class="spinner"></span>';
      
      try {
        const response = await fetch('verify-otp.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `otp=${encodeURIComponent(otp)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
          showAlert('otp-alerts', 'Verification successful!', 'success');
          
          setTimeout(() => {
            closeOTPModal();
            
            // Handle based on purpose
            if (currentPurpose === 'registration') {
              // Complete registration
              completeRegistration();
            } else if (currentPurpose === 'reset_password') {
              // Show reset password form
              closeForgotPasswordModal();
              openResetPasswordModal();
            }
          }, 1000);
        } else {
          // Show error on OTP inputs
          otpInputs.forEach(input => {
            input.classList.add('error');
            input.classList.remove('filled');
          });
          showAlert('otp-alerts', data.message, 'error');
          
          // Clear inputs after error
          setTimeout(() => {
            otpInputs.forEach(input => {
              input.value = '';
              input.classList.remove('error');
            });
            otpInputs[0].focus();
          }, 1500);
        }
      } catch (error) {
        showAlert('otp-alerts', 'Verification failed. Please try again.', 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    }

    // Resend OTP
    async function resendOTP() {
      const resendLink = document.getElementById('resend-link');
      if (resendLink.classList.contains('disabled')) return;
      
      resendLink.classList.add('disabled');
      resendLink.textContent = 'Sending...';
      
      const result = await sendOTP(currentEmail, currentPurpose);
      
      if (result.success) {
        showAlert('otp-alerts', 'New OTP sent successfully!', 'success');
        startResendTimer();
      } else {
        showAlert('otp-alerts', result.message, 'error');
        resendLink.classList.remove('disabled');
        resendLink.textContent = 'Resend OTP';
      }
    }

    // Resend Timer
    function startResendTimer() {
      const resendLink = document.getElementById('resend-link');
      const timer = document.getElementById('timer');
      resendCountdown = 60;
      
      resendLink.classList.add('disabled');
      
      resendTimer = setInterval(() => {
        resendCountdown--;
        timer.textContent = `Resend available in ${resendCountdown}s`;
        
        if (resendCountdown <= 0) {
          clearInterval(resendTimer);
          timer.textContent = '';
          resendLink.classList.remove('disabled');
          resendLink.textContent = 'Resend OTP';
        }
      }, 1000);
    }

    // Registration Form Handler
    document.getElementById('registration-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = {
        first_name: document.getElementById('first_name').value.trim(),
        middle_name: document.getElementById('middle_name').value.trim(),
        last_name: document.getElementById('last_name').value.trim(),
        email: document.getElementById('reg_email').value.trim(),
        password: document.getElementById('reg_password').value,
        phone: document.getElementById('phone').value.trim()
      };
      
      // Validate
      if (!formData.first_name || !formData.last_name || !formData.email || !formData.password) {
        showAlert('register-alerts', 'All required fields must be filled', 'error');
        return;
      }
      
      if (formData.password.length < 8) {
        showAlert('register-alerts', 'Password must be at least 8 characters long', 'error');
        return;
      }
      
      // Store form data and send OTP
      pendingFormData = formData;
      
      const btn = e.target.querySelector('.submit-btn');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Sending OTP...<span class="spinner"></span>';
      
      const result = await sendOTP(formData.email, 'registration');
      
      btn.disabled = false;
      btn.innerHTML = originalText;
      
      if (result.success) {
        openOTPModal(formData.email, 'registration');
      } else {
        showAlert('register-alerts', result.message, 'error');
      }
    });

    // Complete Registration
    async function completeRegistration() {
      if (!pendingFormData) return;
      
      try {
        const params = new URLSearchParams(pendingFormData);
        
        const response = await fetch('register-password-handler.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params.toString()
        });
        
        const data = await response.json();
        
        if (data.success) {
          switchTab('login');
          showAlert('login-alerts', 'Registration successful! Please login with your credentials.', 'success');
          document.getElementById('registration-form').reset();
          pendingFormData = null;
        } else {
          showAlert('register-alerts', data.message || 'Registration failed', 'error');
        }
      } catch (error) {
        showAlert('register-alerts', 'An error occurred. Please try again.', 'error');
      }
    }

    // Forgot Password Form Handler
    document.getElementById('forgot-password-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const email = document.getElementById('forgot_email').value.trim();
      
      const btn = e.target.querySelector('.submit-btn');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Sending OTP...<span class="spinner"></span>';
      
      const result = await sendOTP(email, 'reset_password');
      
      btn.disabled = false;
      btn.innerHTML = originalText;
      
      if (result.success) {
        openOTPModal(email, 'reset_password');
      } else {
        showAlert('forgot-alerts', result.message, 'error');
      }
    });

    // Reset Password Form Handler
    document.getElementById('reset-password-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (newPassword.length < 8) {
        showAlert('reset-alerts', 'Password must be at least 8 characters long', 'error');
        return;
      }
      
      if (newPassword !== confirmPassword) {
        showAlert('reset-alerts', 'Passwords do not match', 'error');
        return;
      }
      
      const btn = e.target.querySelector('.submit-btn');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Resetting...<span class="spinner"></span>';
      
      try {
        const response = await fetch('reset-password-handler.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `email=${encodeURIComponent(currentEmail)}&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
          showAlert('reset-alerts', 'Password reset successful!', 'success');
          setTimeout(() => {
            closeResetPasswordModal();
            switchTab('login');
            showAlert('login-alerts', 'Password reset successful! Please login with your new password.', 'success');
          }, 1500);
        } else {
          showAlert('reset-alerts', data.message, 'error');
        }
      } catch (error) {
        showAlert('reset-alerts', 'An error occurred. Please try again.', 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
      }
    });

    // Handle URL parameters
    window.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const success = urlParams.get('success');
      const error = urlParams.get('error');

      if (success) {
        showAlert('login-alerts', success, 'success');
      }
      if (error) {
        showAlert('login-alerts', error, 'error');
      }
      
      if (success || error) {
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    });
  </script>
</body>
</html>