// auth-complete.js - Complete Authentication System with OTP

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

// ===== OTP MODAL FUNCTIONS =====

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

// OTP Input Handling
function setupOTPInputs() {
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
}

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
          completeRegistration();
        } else if (currentPurpose === 'reset_password') {
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

// ===== REGISTRATION FUNCTIONS =====

function setupRegistrationForm() {
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
}

// Complete Registration after OTP verification
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

function setupForgotPasswordForm() {
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
}

// ===== RESET PASSWORD FUNCTIONS =====

function openResetPasswordModal() {
  document.getElementById('resetPasswordModal').classList.add('active');
  document.getElementById('new_password').focus();
}

function closeResetPasswordModal() {
  document.getElementById('resetPasswordModal').classList.remove('active');
  document.getElementById('reset-password-form').reset();
  document.getElementById('reset-alerts').innerHTML = '';
}

function setupResetPasswordForm() {
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
}

// ===== INITIALIZATION =====

document.addEventListener('DOMContentLoaded', function() {
  // Setup all forms
  setupOTPInputs();
  setupRegistrationForm();
  setupForgotPasswordForm();
  setupResetPasswordForm();
  
  // Handle URL parameters
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
  
  // Close modals when clicking outside
  window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
      e.target.classList.remove('active');
    }
  });
});