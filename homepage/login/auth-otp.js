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

function openResetPasswordModal() {
  document.getElementById('resetPasswordModal').classList.add('active');
  document.getElementById('new_password').focus();
}

function closeResetPasswordModal() {
  document.getElementById('resetPasswordModal').classList.remove('active');
  document.getElementById('reset-password-form').reset();
  document.getElementById('reset-alerts').innerHTML = '';
}

// Send reset password request
document.getElementById('forgot-password-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const email = document.getElementById('forgot_email').value.trim();
  const btn = document.getElementById('send-forgot-btn');
  const originalText = btn.innerHTML;
  
  btn.disabled = true;
  btn.innerHTML = 'Processing...<span class="spinner"></span>';
  
  try {
    const response = await fetch('reset-password-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `email=${encodeURIComponent(email)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Close forgot password modal and show success on login form
      closeForgotPasswordModal();
      showAlert('login-alerts', data.message, 'success');
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

// Reset password (this will be accessed via email link with token)
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
    // Get token from URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    const response = await fetch('reset-password-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `password=${encodeURIComponent(newPassword)}&token=${encodeURIComponent(token)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      showAlert('reset-alerts', 'Password reset successful! Redirecting to login...', 'success');
      setTimeout(() => {
        window.location.href = 'loginform.php?success=Password reset successful! Please login with your new password.';
      }, 2000);
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

// ===== REGISTRATION FUNCTIONS =====

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
  btn.innerHTML = 'Creating Account...<span class="spinner"></span>';
  
  try {
    const params = new URLSearchParams(formData);
    
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
    } else {
      showAlert('register-alerts', data.message || 'Registration failed', 'error');
    }
  } catch (error) {
    showAlert('register-alerts', 'An error occurred. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

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
function openForgotPasswordModal() {
  document.getElementById('forgotPasswordModal').classList.add('active');
}

function closeForgotPasswordModal() {
  document.getElementById('forgotPasswordModal').classList.remove('active');
}

function closeResetPasswordModal() {
  document.getElementById('resetPasswordModal').classList.remove('active');
}

// Close modal when clicking outside
window.onclick = function(event) {
  const forgotModal = document.getElementById('forgotPasswordModal');
  const resetModal = document.getElementById('resetPasswordModal');
  
  if (event.target === forgotModal) {
    closeForgotPasswordModal();
  }
  if (event.target === resetModal) {
    closeResetPasswordModal();
  }
}