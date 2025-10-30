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

    /* Tablet and below - Stack vertically */
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

    /* Mobile devices */
    @media (max-width: 640px) {
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

      .brand-features {
        margin-top: 25px;
        gap: 12px;
        max-width: 100%;
      }

      .feature-item {
        padding: 12px 18px;
      }

      .feature-item i {
        font-size: 20px;
      }

      .feature-item span {
        font-size: 14px;
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

      .back-button i {
        font-size: 18px;
      }

      .logo-section {
        margin-bottom: 25px;
      }

      .logo-section h2 {
        font-size: 26px;
      }

      .logo-section p {
        font-size: 13px;
      }

      .tab-buttons {
        margin-bottom: 25px;
        padding: 4px;
      }

      .tab-btn {
        padding: 12px 8px;
        font-size: 14px;
      }

      .input-field {
        padding: 14px 42px 14px 14px;
        font-size: 15px;
      }

      .label {
        font-size: 15px;
        top: 14px;
        left: 14px;
      }

      .icon {
        right: 14px;
        top: 14px;
        font-size: 19px;
      }

      .row-inputs {
        grid-template-columns: 1fr;
        gap: 0;
      }

      .input-box {
        margin-bottom: 22px;
      }

      .remember-forgot {
        font-size: 13px;
        margin-bottom: 22px;
      }

      .remember-me input[type="checkbox"] {
        width: 20px;
        height: 20px;
      }

      .submit-btn {
        padding: 14px;
        font-size: 15px;
      }

      .alert-message {
        font-size: 13px;
        padding: 11px 14px;
      }
    }

    /* Very small mobile devices */
    @media (max-width: 380px) {
      .brand-side {
        padding: 40px 20px 30px;
      }

      .brand-content h1 {
        font-size: 28px;
        margin-bottom: 12px;
      }

      .brand-content p {
        font-size: 14px;
      }

      .brand-features {
        gap: 10px;
        margin-top: 20px;
      }

      .feature-item {
        padding: 10px 15px;
        gap: 12px;
      }

      .feature-item i {
        font-size: 18px;
      }

      .feature-item span {
        font-size: 13px;
      }

      .form-side {
        padding: 30px 20px 40px;
      }

      .back-button {
        padding: 8px 14px;
        font-size: 13px;
        top: 15px;
        left: 15px;
      }

      .back-button i {
        font-size: 16px;
      }

      .logo-section h2 {
        font-size: 24px;
      }

      .logo-section p {
        font-size: 12px;
      }

      .tab-btn {
        padding: 11px 6px;
        font-size: 13px;
      }

      .input-field {
        padding: 13px 40px 13px 13px;
        font-size: 14px;
      }

      .label {
        font-size: 14px;
        top: 13px;
        left: 13px;
      }

      .icon {
        right: 13px;
        top: 13px;
        font-size: 18px;
      }

      .input-box {
        margin-bottom: 20px;
      }

      .submit-btn {
        padding: 13px;
        font-size: 14px;
      }

      .container {
        padding: 0 5px;
      }
    }

    /* Large tablets in landscape */
    @media (min-width: 969px) and (max-width: 1200px) {
      .brand-content h1 {
        font-size: 42px;
      }

      .brand-features {
        max-width: 320px;
      }
    }

    /* Extra large screens */
    @media (min-width: 1400px) {
      .brand-content h1 {
        font-size: 56px;
      }

      .brand-content p {
        font-size: 20px;
      }

      .brand-features {
        max-width: 400px;
      }

      .container {
        max-width: 550px;
      }
    }

    /* Landscape mobile orientation fix */
    @media (max-height: 500px) and (orientation: landscape) {
      .brand-side {
        min-height: auto;
        padding: 30px 25px;
      }

      .brand-features {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 20px;
      }

      .feature-item {
        flex: 0 1 auto;
      }

      .form-side {
        padding: 30px 25px;
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
          <div id="login-alerts"></div>
          
          <form action="login-handler.php" method="post">
            <div class="input-box">
              <input type="email" class="input-field" name="email" required />
              <label class="label">Email</label>
              <i class='bx bx-user icon'></i>
            </div>

            <div class="input-box">
              <input type="password" class="input-field" name="password" required />
              <label class="label">Password</label>
              <i class='bx bx-lock-alt icon'></i>
            </div>

            <div class="remember-forgot">
              <div class="remember-me">
                <input type="checkbox" id="remember" />
                <label for="remember">Remember me</label>
              </div>
              <div class="forgot">
                <a href="forgot_password.php">Forgot password?</a>
              </div>
            </div>

            <button type="submit" class="submit-btn">Login</button>
          </form>
        </div>

        <!-- Register Form -->
        <div id="register-form" class="form-section">
          <div id="register-alerts"></div>
          
          <form action="../login/register-handler.php" method="post">
            <div class="row-inputs">
              <div class="input-box">
                <input type="text" class="input-field" name="first_name" required />
                <label class="label">First Name</label>
                <i class='bx bx-user icon'></i>
              </div>

              <div class="input-box">
                <input type="text" class="input-field" name="last_name" required />
                <label class="label">Last Name</label>
                <i class='bx bx-user icon'></i>
              </div>
            </div>

            <div class="input-box">
              <input type="text" class="input-field" name="middle_name" required/>
              <label class="label">Middle Name</label>
              <i class='bx bx-user icon'></i>
            </div>

            <div class="input-box">
              <input type="email" class="input-field" name="email" required />
              <label class="label">Email</label>
              <i class='bx bx-envelope icon'></i>
            </div>

            <div class="input-box">
              <input type="password" class="input-field" name="password" required />
              <label class="label">Password</label>
              <i class='bx bx-lock icon'></i>
            </div>

            <div class="input-box">
              <input type="text" class="input-field" name="phone"  required/>
              <label class="label">Phone Number</label>
              <i class='bx bx-phone icon'></i>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
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

    // Handle PHP session messages
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
    });

    function showAlert(containerId, message, type) {
      const container = document.getElementById(containerId);
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert-message alert-${type}`;
      alertDiv.textContent = message;
      container.appendChild(alertDiv);

      setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
      }, 5000);
    }
  </script>

</body>
</html>