// Global variables for storing temp data
let tempRegistrationData = {};
let tempForgotEmail = '';
let forgotOtpTimer;
let registerOtpTimer;

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
  alertDiv.textContent = message;
  container.appendChild(alertDiv);

  setTimeout(() => {
    alertDiv.style.opacity = '0';
    setTimeout(() => alertDiv.remove(), 300);
  }, 5000);
}

// OTP Input Handling
function setupOtpInputs(container) {
  const inputs = container.querySelectorAll('.otp-input');
  
  inputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
      const value = e.target.value;
      
      // Only allow numbers
      if (!/^\d*$/.test(value)) {
        e.target.value = '';
        return;
      }
      
      // Move to next input
      if (value && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });
    
    input.addEventListener('keydown', (e) => {
      // Move to previous input on backspace
      if (e.key === 'Backspace' && !e.target.value && index > 0) {
        inputs[index - 1].focus();
      }
    });
    
    // Auto-select on focus
    input.addEventListener('focus', (e) => {
      e.target.select();
    });
  });
}

// Get OTP value from inputs
function getOtpValue(container) {
  const inputs = container.querySelectorAll('.otp-input');
  return Array.from(inputs).map(input => input.value).join('');
}

// Clear OTP inputs
function clearOtpInputs(container) {
  const inputs = container.querySelectorAll('.otp-input');
  inputs.forEach(input => input.value = '');
  inputs[0].focus();
}

// Timer for resend OTP
function startTimer(duration, displayId, linkId) {
  let timer = duration;
  const display = document.getElementById(displayId);
  const link = document.getElementById(linkId);
  
  link.classList.add('disabled');
  
  const interval = setInterval(() => {
    const minutes = parseInt(timer / 60, 10);
    const seconds = parseInt(timer % 60, 10);
    
    display.textContent = `(${minutes}:${seconds < 10 ? '0' : ''}${seconds})`;
    
    if (--timer < 0) {
      clearInterval(interval);
      display.textContent = '';
      link.classList.remove('disabled');
    }
  }, 1000);
  
  return interval;
}

// ===== FORGOT PASSWORD FUNCTIONS =====

function openForgotPasswordModal() {
  document.getElementById('forgotPasswordModal').classList.add('active');
  document.getElementById('forgot_email').focus();
}

function closeForgotPasswordModal() {
  document.getElementById('forgotPasswordModal').classList.remove('active');
  document.getElementById('forgot-password-form').reset();
  document.getElementById('forgot-alerts').innerHTML = '';
}

function openForgotOtpModal() {
  document.getElementById('forgotOtpModal').classList.add('active');
  const otpContainer = document.getElementById('forgotOtpModal');
  setupOtpInputs(otpContainer);
  const firstInput = otpContainer.querySelector('.otp-input');
  if (firstInput) firstInput.focus();
  
  // Start 2-minute timer
  forgotOtpTimer = startTimer(120, 'forgot-timer', 'resend-forgot-link');
}

function closeForgotOtpModal() {
  document.getElementById('forgotOtpModal').classList.remove('active');
  clearOtpInputs(document.getElementById('forgotOtpModal'));
  document.getElementById('forgot-otp-alerts').innerHTML = '';
  if (forgotOtpTimer) clearInterval(forgotOtpTimer);
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

// Send OTP for forgot password
document.getElementById('forgot-password-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const email = document.getElementById('forgot_email').value.trim();
  const btn = document.getElementById('send-forgot-otp-btn');
  const originalText = btn.innerHTML;
  
  btn.disabled = true;
  btn.innerHTML = 'Sending OTP...<span class="spinner"></span>';
  
  try {
    const response = await fetch('otp-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=send_forgot_otp&email=${encodeURIComponent(email)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      tempForgotEmail = email;
      document.getElementById('forgot-email-display').textContent = email;
      closeForgotPasswordModal();
      openForgotOtpModal();
      showAlert('forgot-otp-alerts', data.message, 'success');
    } else {
      showAlert('forgot-alerts', data.message, 'error');
    }
  } catch (error) {
    showAlert('forgot-alerts', 'An error occurred. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Verify OTP for forgot password
document.getElementById('forgot-otp-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const otpContainer = document.getElementById('forgotOtpModal');
  const otp = getOtpValue(otpContainer);
  
  if (otp.length !== 6) {
    showAlert('forgot-otp-alerts', 'Please enter all 6 digits', 'error');
    return;
  }
  
  const btn = e.target.querySelector('.submit-btn');
  const originalText = btn.innerHTML;
  
  btn.disabled = true;
  btn.innerHTML = 'Verifying...<span class="spinner"></span>';
  
  try {
    const response = await fetch('otp-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=verify_forgot_otp&email=${encodeURIComponent(tempForgotEmail)}&otp=${otp}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      closeForgotOtpModal();
      openResetPasswordModal();
      showAlert('reset-alerts', 'OTP verified! Enter your new password.', 'success');
    } else {
      showAlert('forgot-otp-alerts', data.message, 'error');
      clearOtpInputs(otpContainer);
    }
  } catch (error) {
    showAlert('forgot-otp-alerts', 'An error occurred. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Reset password
document.getElementById('reset-password-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const newPassword = document.getElementById('new_password').value;
  const confirmPassword = document.getElementById('confirm_password').value;
  
  if (newPassword.length < 6) {
    showAlert('reset-alerts', 'Password must be at least 6 characters long', 'error');
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
    const response = await fetch('otp-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=reset_password&email=${encodeURIComponent(tempForgotEmail)}&password=${encodeURIComponent(newPassword)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      closeResetPasswordModal();
      tempForgotEmail = '';
      showAlert('login-alerts', 'Password reset successful! Please login with your new password.', 'success');
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

// Resend forgot password OTP
async function resendForgotOTP() {
  const link = document.getElementById('resend-forgot-link');
  if (link.classList.contains('disabled')) return;
  
  try {
    const response = await fetch('otp-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=send_forgot_otp&email=${encodeURIComponent(tempForgotEmail)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      showAlert('forgot-otp-alerts', 'OTP resent successfully!', 'success');
      forgotOtpTimer = startTimer(120, 'forgot-timer', 'resend-forgot-link');
    } else {
      showAlert('forgot-otp-alerts', data.message, 'error');
    }
  } catch (error) {
    showAlert('forgot-otp-alerts', 'Failed to resend OTP', 'error');
  }
}

// ===== REGISTRATION FUNCTIONS =====

function openRegisterOtpModal() {
  document.getElementById('registerOtpModal').classList.add('active');
  const otpContainer = document.getElementById('registerOtpModal');
  setupOtpInputs(otpContainer);
  const firstInput = otpContainer.querySelector('.otp-input');
  if (firstInput) firstInput.focus();
  
  // Start 2-minute timer
  registerOtpTimer = startTimer(120, 'register-timer', 'resend-register-link');
}

function closeRegisterOtpModal() {
  document.getElementById('registerOtpModal').classList.remove('active');
  clearOtpInputs(document.getElementById('registerOtpModal'));
  document.getElementById('register-otp-alerts').innerHTML = '';
  if (registerOtpTimer) clearInterval(registerOtpTimer);
}

// Handle registration form submission
document.getElementById('registration-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  // Get form data
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
  
  if (formData.password.length < 6) {
    showAlert('register-alerts', 'Password must be at least 6 characters long', 'error');
    return;
  }
  
  const btn = e.target.querySelector('.submit-btn');
  const originalText = btn.innerHTML;
  
  btn.disabled = true;
  btn.innerHTML = 'Sending OTP...<span class="spinner"></span>';
  
  try {
    const response = await fetch('otp-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=send_register_otp&email=${encodeURIComponent(formData.email)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      tempRegistrationData = formData;
      document.getElementById('register-email-display').textContent = formData.email;
      openRegisterOtpModal();
      showAlert('register-otp-alerts', data.message, 'success');
    } else {
      showAlert('register-alerts', data.message, 'error');
    }
  } catch (error) {
    showAlert('register-alerts', 'An error occurred. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Verify OTP and complete registration
document.getElementById('register-otp-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const otpContainer = document.getElementById('registerOtpModal');
  const otp = getOtpValue(otpContainer);
  
  if (otp.length !== 6) {
    showAlert('register-otp-alerts', 'Please enter all 6 digits', 'error');
    return;
  }
  
  const btn = e.target.querySelector('.submit-btn');
  const originalText = btn.innerHTML;
  
  btn.disabled = true;
  btn.innerHTML = 'Verifying...<span class="spinner"></span>';
  
  try {
    // First verify OTP
    const verifyResponse = await fetch('otp-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=verify_register_otp&email=${encodeURIComponent(tempRegistrationData.email)}&otp=${otp}`
    });
    
    const verifyData = await verifyResponse.json();
    
    if (verifyData.success) {
      // OTP verified, now complete registration
      const params = new URLSearchParams(tempRegistrationData);
      
      const registerResponse = await fetch('register-handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      });
      
      const text = await registerResponse.text();
      
      // Check if response is redirect or contains success
      if (text.includes('Location:') || registerResponse.redirected) {
        closeRegisterOtpModal();
        switchTab('login');
        showAlert('login-alerts', 'Registration successful! Please login with your credentials.', 'success');
        document.getElementById('registration-form').reset();
        tempRegistrationData = {};
      } else {
        // Try to parse as JSON for error messages
        try {
          const data = JSON.parse(text);
          showAlert('register-otp-alerts', data.message || 'Registration failed', 'error');
        } catch {
          // If registration was successful, PHP redirects
          closeRegisterOtpModal();
          switchTab('login');
          showAlert('login-alerts', 'Registration successful! Please login.', 'success');
          document.getElementById('registration-form').reset();
          tempRegistrationData = {};
        }
      }
    } else {
      showAlert('register-otp-alerts', verifyData.message, 'error');
      clearOtpInputs(otpContainer);
    }
  } catch (error) {
    showAlert('register-otp-alerts', 'An error occurred. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Resend registration OTP
async function resendRegisterOTP() {
  const link = document.getElementById('resend-register-link');
  if (link.classList.contains('disabled')) return;
  
  try {
    const response = await fetch('otp-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=send_register_otp&email=${encodeURIComponent(tempRegistrationData.email)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      showAlert('register-otp-alerts', 'OTP resent successfully!', 'success');
      registerOtpTimer = startTimer(120, 'register-timer', 'resend-register-link');
    } else {
      showAlert('register-otp-alerts', data.message, 'error');
    }
  } catch (error) {
    showAlert('register-otp-alerts', 'Failed to resend OTP', 'error');
  }
}

// Handle URL parameters for success/error messages
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
  
  // Clean URL
  if (success || error) {
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});

// Close modals when clicking outside
window.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal')) {
    e.target.classList.remove('active');
  }
});