<?php session_start(); ?>
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

    .back-button:active {
      transform: translateX(-3px);
    }

    .container {
      width: 100%;
      max-width: 500px;
      position: relative;
      z-index: 1;
    }

    .form-container {
      padding: 0;
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
      touch-action: manipulation;
      -webkit-tap-highlight-color: transparent;
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
      word-wrap: break-word;
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
      -webkit-appearance: none;
      appearance: none;
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

    .remember-me label {
      cursor: pointer;
      -webkit-tap-highlight-color: transparent;
    }

    .forgot a {
      color: #5fb894;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
      -webkit-tap-highlight-color: transparent;
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
      touch-action: manipulation;
      -webkit-tap-highlight-color: transparent;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(168, 230, 207, 0.5);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    /* Password Toggle */
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

    /* Error States */
    .input-box.error .input-field {
      border-color: #ff4d4d;
      background: #fff5f5;
    }

    .input-box.error .icon,
    .input-box.error .password-toggle {
      color: #ff4d4d;
    }

    .input-box.success .input-field {
      border-color: #4caf50;
    }

    .input-box.success .icon {
      color: #4caf50;
    }

    .error-message {
      display: none;
      color: #ff4d4d;
      font-size: 12px;
      margin-top: 5px;
      margin-left: 5px;
      animation: slideDown 0.3s ease;
    }

    .input-box.error .error-message {
      display: block;
    }

    /* Password Strength */
    .password-strength {
      margin-top: 8px;
      height: 4px;
      background: #e0e0e0;
      border-radius: 2px;
      overflow: hidden;
      display: none;
    }

    .password-strength.active {
      display: block;
    }

    .password-strength-bar {
      height: 100%;
      width: 0%;
      transition: all 0.3s ease;
      border-radius: 2px;
    }

    .password-strength-bar.weak {
      width: 33%;
      background: #ff4d4d;
    }

    .password-strength-bar.medium {
      width: 66%;
      background: #ffa500;
    }

    .password-strength-bar.strong {
      width: 100%;
      background: #4caf50;
    }

    .password-hint {
      font-size: 11px;
      color: #999;
      margin-top: 5px;
      display: none;
    }

    .password-hint.active {
      display: block;
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
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
      animation: fadeIn 0.3s ease;
    }

    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      padding: 35px;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      width: 90%;
      max-width: 450px;
      animation: slideUp 0.3s ease;
      position: relative;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .modal-header {
      text-align: center;
      margin-bottom: 25px;
    }

    .modal-header h3 {
      font-size: 24px;
      color: #2d5f4a;
      margin-bottom: 8px;
    }

    .modal-header p {
      color: #666;
      font-size: 14px;
    }

    .close-modal {
      position: absolute;
      top: 15px;
      right: 15px;
      font-size: 28px;
      color: #999;
      cursor: pointer;
      transition: color 0.3s ease;
      line-height: 1;
    }

    .close-modal:hover {
      color: #2d5f4a;
    }

    .otp-inputs {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin: 25px 0;
    }

    .otp-input {
      width: 50px;
      height: 55px;
      text-align: center;
      font-size: 24px;
      font-weight: 600;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      outline: none;
      transition: all 0.3s ease;
    }

    .otp-input:focus {
      border-color: #A8E6CF;
      box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.15);
    }

    .resend-otp {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
      color: #666;
    }

    .resend-otp a {
      color: #5fb894;
      text-decoration: none;
      font-weight: 500;
      cursor: pointer;
    }

    .resend-otp a:hover {
      color: #7FD4B3;
    }

    .resend-otp a.disabled {
      color: #ccc;
      cursor: not-allowed;
      pointer-events: none;
    }

    /* Loading Spinner */
    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid #2d5f4a;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-left: 8px;
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

      .brand-content p {
        font-size: 16px;
      }

      .brand-features {
        margin-top: 30px;
        gap: 15px;
      }

      .feature-item {
        padding: 12px 20px;
      }

      .feature-item i {
        font-size: 20px;
      }

      .feature-item span {
        font-size: 14px;
      }

      .form-side {
        min-height: auto;
        padding: 40px 30px 50px;
      }

      .back-button {
        top: 20px;
        left: 20px;
      }

      .container {
        max-width: 600px;
      }
    }

    @media (max-width: 640px) {
      .modal-content {
        padding: 30px 25px;
        max-width: 95%;
      }

      .otp-input {
        width: 45px;
        height: 50px;
        font-size: 20px;
      }

      .brand-side {
        min-height: auto;
        padding: 45px 25px 35px;
      }

      .brand-content h1 {
        font-size: 32px;
        margin-bottom: 15px;
        line-height: 1.2;
      }

      .brand-content p {
        font-size: 15px;
        line-height: 1.5;
      }

      .form-side {
        padding: 35px 25px 45px;
      }

      .back-button {
        top: 18px;
        left: 18px;
        padding: 10px 16px;
        font-size: 14px;
        gap: 6px;
      }

      .row-inputs {
        grid-template-columns: 1fr;
        gap: 0;
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
                  echo '<span>' . $_SESSION['login_error'] . '</span>';
                  echo '</div>';
                  
                  // Add inline script to highlight the error field
                  if (isset($_SESSION['error_field'])) {
                      $field = $_SESSION['error_field'];
                      echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                          showFieldError('login-{$field}-box', '');
                        });
                      </script>";
                      unset($_SESSION['error_field']);
                  }
                  
                  unset($_SESSION['login_error']);
              }
              if (isset($_SESSION['login_success'])) {
                  echo '<div class="alert-message alert-success">';
                  echo '<i class="bx bx-check-circle"></i>';
                  echo '<span>' . $_SESSION['login_success'] . '</span>';
                  echo '</div>';
                  unset($_SESSION['login_success']);
              }
              ?>
            </div>
            
            <form action="login-handler.php" method="post">
              <div class="input-box" id="login-email-box">
                <input type="email" class="input-field" name="email" id="login_email" required />
                <label class="label">Email</label>
                <i class='bx bx-user icon'></i>
                <span class="error-message">Email not found</span>
              </div>

              <div class="input-box has-toggle" id="login-password-box">
                <input type="password" class="input-field" name="password" id="login_password" required />
                <label class="label">Password</label>
                <i class='bx bx-lock-alt icon'></i>
                <i class='bx bx-hide password-toggle' onclick="togglePassword('login_password', this)"></i>
                <span class="error-message">Incorrect password</span>
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
            
            <form id="registration-form">
              <div class="row-inputs">
                <div class="input-box">
                  <input type="text" class="input-field" name="first_name" id="first_name" required />
                  <label class="label">First Name</label>
                  <i class='bx bx-user icon'></i>
                </div>

                <div class="input-box">
                  <input type="text" class="input-field" name="last_name" id="last_name" required />
                  <label class="label">Last Name</label>
                  <i class='bx bx-user icon'></i>
                </div>
              </div>

              <div class="input-box">
                <input type="text" class="input-field" name="middle_name" id="middle_name" required/>
                <label class="label">Middle Name</label>
                <i class='bx bx-user icon'></i>
              </div>

              <div class="input-box">
                <input type="email" class="input-field" name="email" id="reg_email" required />
                <label class="label">Email</label>
                <i class='bx bx-envelope icon'></i>
              </div>

              <div class="input-box has-toggle">
                <input type="password" class="input-field" name="password" id="reg_password" required />
                <label class="label">Password</label>
                <i class='bx bx-lock icon'></i>
                <i class='bx bx-hide password-toggle' onclick="togglePassword('reg_password', this)"></i>
                <div class="password-strength">
                  <div class="password-strength-bar"></div>
                </div>
                <div class="password-hint">Use 8+ characters with letters, numbers & symbols</div>
              </div>

              <div class="input-box">
                <input type="text" class="input-field" name="phone" id="phone" required/>
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

  <!-- Forgot Password Modal -->
  <div id="forgotPasswordModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeForgotPasswordModal()">&times;</span>
      <div class="modal-header">
        <h3>Forgot Password</h3>
        <p>Enter your email to receive an OTP</p>
      </div>
      <div id="forgot-alerts"></div>
      <form id="forgot-password-form">
        <div class="input-box">
          <input type="email" class="input-field" id="forgot_email" required />
          <label class="label">Email Address</label>
          <i class='bx bx-envelope icon'></i>
        </div>
        <button type="submit" class="submit-btn" id="send-forgot-otp-btn">Send OTP</button>
      </form>
    </div>
  </div>

  <!-- Forgot Password OTP Verification Modal -->
  <div id="forgotOtpModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeForgotOtpModal()">&times;</span>
      <div class="modal-header">
        <h3>Verify OTP</h3>
        <p>Enter the 6-digit code sent to <span id="forgot-email-display"></span></p>
      </div>
      <div id="forgot-otp-alerts"></div>
      <form id="forgot-otp-form">
        <div class="otp-inputs">
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
        </div>
        <button type="submit" class="submit-btn">Verify OTP</button>
        <div class="resend-otp">
          Didn't receive code? <a onclick="resendForgotOTP()" id="resend-forgot-link">Resend OTP</a>
          <span id="forgot-timer"></span>
        </div>
      </form>
    </div>
  </div>

  <!-- Reset Password Modal -->
  <div id="resetPasswordModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeResetPasswordModal()">&times;</span>
      <div class="modal-header">
        <h3>Reset Password</h3>
        <p>Enter your new password</p>
      </div>
      <div id="reset-alerts"></div>
      <form id="reset-password-form">
        <div class="input-box">
          <input type="password" class="input-field" id="new_password" required />
          <label class="label">New Password</label>
          <i class='bx bx-lock icon'></i>
        </div>
        <div class="input-box">
          <input type="password" class="input-field" id="confirm_password" required />
          <label class="label">Confirm Password</label>
          <i class='bx bx-lock-alt icon'></i>
        </div>
        <button type="submit" class="submit-btn">Reset Password</button>
      </form>
    </div>
  </div>

  <!-- Registration OTP Modal -->
  <div id="registerOtpModal" class="modal">
    <div class="modal-content">
      <span class="close-modal" onclick="closeRegisterOtpModal()">&times;</span>
      <div class="modal-header">
        <h3>Verify Your Email</h3>
        <p>Enter the 6-digit code sent to <span id="register-email-display"></span></p>
      </div>
      <div id="register-otp-alerts"></div>
      <form id="register-otp-form">
        <div class="otp-inputs">
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
          <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" />
        </div>
        <button type="submit" class="submit-btn">Verify & Register</button>
        <div class="resend-otp">
          Didn't receive code? <a onclick="resendRegisterOTP()" id="resend-register-link">Resend OTP</a>
          <span id="register-timer"></span>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
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

  <script src="auth-otp.js"></script>

</body>
</html>