<?php
session_start();
include '../db.php';

// Check if groomer is logged in
if (!isset($_SESSION['groomer_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$groomer_id = $_SESSION['groomer_id'];

// Get groomer's current status
$status_query = pg_query_params($conn, "
    SELECT is_active, last_active
    FROM groomer
    WHERE groomer_id = $1
", [$groomer_id]);

$groomer_status = pg_fetch_assoc($status_query);
$is_active = $groomer_status['is_active'] ?? false;

// Convert PostgreSQL boolean to PHP boolean properly
if ($is_active === 't' || $is_active === 'true' || $is_active === true) {
    $is_active = true;
} else {
    $is_active = false;
}

// Fetch ONLY confirmed appointments for THIS groomer - FIXED: Added explicit type casting for all joins
$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed,
        (u.first_name || ' ' || u.last_name) AS username,
        u.first_name,
        u.last_name,
        a.groomer_id as assigned_groomer
    FROM appointments a
    JOIN packages p ON a.package_id = p.price_id
    JOIN pets pet ON a.pet_id = pet.pet_id
    JOIN users u ON pet.user_id = u.user_id
    WHERE a.status = 'confirmed'
      AND a.groomer_id = $1
    ORDER BY a.appointment_date ASC
";

$result = pg_query_params($conn, $query, [$groomer_id]);

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Groomer | Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../homepage/images/pawsig.png">
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
      --light-pink-color: #faf4f5;
      --medium-gray-color: #ccc;
      --edit-color: #4CAF50;
      --delete-color: #F44336;
      --font-size-s: 0.9rem;
      --font-size-n: 1rem;
      --font-size-l: 1.5rem;
      --font-size-xl: 2rem;
      --font-weight-semi-bold: 600;
      --font-weight-bold: 700;
      --border-radius-s: 8px;
      --border-radius-circle: 50%;
      --sidebar-width: 260px;
      --transition-speed: 0.3s;
      --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Montserrat", sans-serif;
    }

    body {
      background: var(--light-pink-color);
      display: flex;
      min-height: 100vh;
    }

    /* TOAST NOTIFICATION */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      padding: 16px 24px;
      border-radius: 10px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 300px;
      max-width: 400px;
      font-weight: 500;
      font-size: 0.95rem;
      animation: slideInToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      opacity: 0;
    }

    @keyframes slideInToast {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOutToast {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }

    .toast.show {
      opacity: 1;
    }

    .toast.hide {
      animation: slideOutToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
    }

    .toast-success {
      background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
      color: white;
    }

    .toast-error {
      background: linear-gradient(135deg, #F44336 0%, #e53935 100%);
      color: white;
    }

    .toast i {
      font-size: 24px;
      flex-shrink: 0;
    }

    .toast-message {
      flex: 1;
    }

    .toast-close {
      cursor: pointer;
      font-size: 20px;
      opacity: 0.8;
      transition: opacity 0.2s;
      flex-shrink: 0;
    }

    .toast-close:hover {
      opacity: 1;
    }

    .mobile-menu-btn {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1001;
      background: var(--primary-color);
      border: none;
      border-radius: 8px;
      padding: 12px;
      cursor: pointer;
      box-shadow: var(--shadow-light);
      transition: var(--transition-speed);
    }

    .mobile-menu-btn i {
      font-size: 24px;
      color: var(--dark-color);
    }

    .mobile-menu-btn:hover {
      background: var(--secondary-color);
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 998;
      opacity: 0;
      transition: opacity var(--transition-speed);
    }

    .sidebar-overlay.active {
      display: block;
      opacity: 1;
    }

    .sidebar {
      width: 260px;
      height: 100vh;
      background-color: var(--primary-color);
      padding: 30px 20px;
      position: fixed;
      left: 0;
      top: 0;
      display: flex;
      flex-direction: column;
      gap: 20px;
      overflow-y: auto;
      box-shadow: var(--shadow-light);
      transition: transform var(--transition-speed);
      z-index: 999;
    }

    .sidebar .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .sidebar .logo img {
      width: 80px;
      height: 80px;
      border-radius: var(--border-radius-circle);
    }

    /* Status Toggle */
    .status-toggle {
      background: rgba(255, 255, 255, 0.9);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .status-toggle h4 {
      margin-bottom: 12px;
      font-size: 0.95rem;
      color: var(--dark-color);
      text-align: center;
      font-weight: 600;
    }

    .toggle-container {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    .toggle-switch {
      position: relative;
      width: 60px;
      height: 30px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .toggle-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: 0.4s;
      border-radius: 30px;
    }

    .toggle-slider:before {
      position: absolute;
      content: "";
      height: 22px;
      width: 22px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: 0.4s;
      border-radius: 50%;
    }

    input:checked + .toggle-slider {
      background-color: #4CAF50;
    }

    input:checked + .toggle-slider:before {
      transform: translateX(30px);
    }

    .status-label {
      font-weight: 600;
      font-size: 0.9rem;
    }

    .status-label.active {
      color: #4CAF50;
    }

    .status-label.inactive {
      color: #f44336;
    }

    .menu {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .menu a {
      display: flex;
      align-items: center;
      padding: 10px 12px;
      text-decoration: none;
      color: var(--dark-color);
      border-radius: 14px;
      transition: background 0.3s, color 0.3s;
      font-weight: var(--font-weight-semi-bold);
    }

    .menu a i {
      margin-right: 10px;
      font-size: 20px;
    }

    .menu a:hover,
    .menu a.active {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .menu hr {
      border: none;
      border-top: 1px solid var(--secondary-color);
      margin: 9px 0;
    }

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    .header {
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 10px;
      font-weight: 600;
    }

    .header p {
      color: #666;
      font-size: 0.95rem;
    }

    /* TABLE SECTION */
    .table-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      overflow-x: auto;
    }

    .table-section h2 {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
    }

    table {
      width: 100%;
      min-width: 900px;
      border-collapse: collapse;
    }

    th, td {
      padding: 15px 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }

    th {
      background-color: #fafafa;
      color: var(--dark-color);
      font-weight: 600;
      font-size: 0.9rem;
      position: sticky;
      top: 0;
    }

    tbody tr:hover {
      background-color: #fafafa;
    }

    .action-btn {
      padding: 6px 14px;
      font-size: 0.85rem;
      font-weight: 600;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(76, 175, 80, 0.1);
      color: var(--edit-color);
    }

    .action-btn:hover:not(:disabled) {
      background: var(--edit-color);
      color: var(--white-color);
    }

    .action-btn:disabled {
      background: #e0e0e0;
      color: #999;
      cursor: not-allowed;
      opacity: 0.6;
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 80px;
      color: #ddd;
      margin-bottom: 20px;
      display: block;
    }

    .empty-state h3 {
      font-size: 1.5rem;
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .empty-state p {
      font-size: 1rem;
      color: #999;
    }

    /* Password Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1002;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      animation: fadeIn 0.3s;
      align-items: center;
      justify-content: center;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      background-color: white;
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
      animation: slideDown 0.3s;
      position: relative;
    }

    @keyframes slideDown {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      color: var(--dark-color);
    }

    .modal-header i {
      font-size: 24px;
      color: #f44336;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 1.3rem;
      font-weight: 600;
    }

    .modal-body {
      margin-bottom: 20px;
    }

    .modal-body p {
      color: #666;
      margin-bottom: 15px;
      line-height: 1.5;
    }

    .password-input-group {
      position: relative;
    }

    .password-input-group input {
      width: 100%;
      padding: 12px 40px 12px 12px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s;
    }

    .password-input-group input:focus {
      outline: none;
      border-color: var(--primary-color);
    }

    .password-toggle-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #666;
      font-size: 20px;
      padding: 5px;
    }

    .modal-footer {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .modal-btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 0.95rem;
    }

    .modal-btn-cancel {
      background-color: #f5f5f5;
      color: var(--dark-color);
    }

    .modal-btn-cancel:hover {
      background-color: #e0e0e0;
    }

    .modal-btn-confirm {
      background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
      color: white;
    }

    .modal-btn-confirm:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
    }

    .modal-btn-confirm:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .error-message {
      color: #f44336;
      font-size: 0.85rem;
      margin-top: 8px;
      display: none;
    }

    .error-message.show {
      display: block;
    }

    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.85rem;
        min-width: 800px;
      }

      th, td {
        padding: 12px 10px;
      }
    }

    @media screen and (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .content {
        margin-left: 0;
        width: 100%;
        padding: 80px 20px 40px;
      }

      .header h1 {
        font-size: 1.6rem;
      }

      .table-section {
        padding: 20px;
      }

      table {
        font-size: 0.8rem;
        min-width: 700px;
      }

      th, td {
        padding: 10px 8px;
      }

      .toast {
        bottom: 20px;
        right: 20px;
        left: 20px;
        min-width: auto;
      }

      .modal-content {
        width: 95%;
        padding: 25px;
      }
    }

    @media screen and (max-width: 480px) {
      .content {
        padding: 70px 10px 30px;
      }

      .sidebar .logo img {
        width: 60px;
        height: 60px;
      }

      .menu a {
        padding: 8px 10px;
        font-size: 0.9rem;
      }

      .header h1 {
        font-size: 1.4rem;
      }

      table {
        font-size: 0.75rem;
        min-width: 650px;
      }

      th, td {
        padding: 8px 5px;
      }

      .modal-content {
        padding: 20px 15px;
      }

      .modal-header h3 {
        font-size: 1.1rem;
      }

      .modal-footer {
        flex-direction: column;
      }

      .modal-footer button {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar">
  <div class="logo">
    <img src="../homepage/images/pawsig.png" alt="Logo" />
  </div>

  <!-- Status Toggle -->
  <div class="status-toggle">
    <h4>Availability Status</h4>
    <div class="toggle-container">
      <label class="toggle-switch">
        <input type="checkbox" id="statusToggle" <?= $is_active ? 'checked' : '' ?>>
        <span class="toggle-slider"></span>
      </label>
      <span class="status-label <?= $is_active ? 'active' : 'inactive' ?>" id="statusLabel">
        <?= $is_active ? 'Online' : 'Offline' ?>
      </span>
    </div>
  </div>

  <nav class="menu">
    <a href="home_groomer.php" class="active"><i class='bx bx-calendar-check'></i>Appointments</a>
    <hr>
    <a href="history_log.php"><i class='bx bx-history'></i>History Logs</a>
    <hr>
    <a href="notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="https://pawsigcity.onrender.com/homepage/login/loginform.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>My Confirmed Appointments</h1>
    <p>View and manage your upcoming grooming sessions</p>
  </div>

  <?php if (pg_num_rows($result) == 0): ?>
    <div class="table-section">
      <div class="empty-state">
        <i class='bx bx-calendar-x'></i>
        <h3>No Confirmed Appointments</h3>
        <p>You don't have any confirmed appointments yet</p>
      </div>
    </div>
  <?php else: ?>
    <div class="table-section">
      <h2>Appointment List</h2>
      <table>
        <thead>
          <tr>
            <th>Appointment ID</th>
            <th>Date</th>
            <th>Package</th>
            <th>Pet Name</th>
            <th>Breed</th>
            <th>Customer</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr id="row-<?= $row['appointment_id'] ?>">
              <td><?= htmlspecialchars($row['appointment_id']) ?></td>
              <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['appointment_date']))) ?></td>
              <td><?= htmlspecialchars($row['package_name']) ?></td>
              <td><?= htmlspecialchars($row['pet_name']) ?></td>
              <td><?= htmlspecialchars($row['pet_breed']) ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td>
                <button class="action-btn" onclick="completeAppointment(<?= $row['appointment_id'] ?>)">
                  <i class='bx bx-check'></i> Complete
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>

<!-- Password Verification Modal -->
<div id="passwordModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <i class='bx bx-lock-alt'></i>
      <h3>Admin Verification Required</h3>
    </div>
    <div class="modal-body">
      <p>Please enter the admin password to go offline:</p>
      <div class="password-input-group">
        <input type="password" id="adminPassword" placeholder="Enter admin password" />
        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()">
          <i class='bx bx-hide' id="passwordIcon"></i>
        </button>
      </div>
      <div class="error-message" id="passwordError">Incorrect password. Please try again.</div>
    </div>
    <div class="modal-footer">
      <button type="button" class="modal-btn modal-btn-cancel" onclick="cancelOffline()">Cancel</button>
      <button type="button" class="modal-btn modal-btn-confirm" onclick="verifyPassword()">Confirm</button>
    </div>
  </div>
</div>

<script>
function showToast(message, type = 'success') {
  const existingToasts = document.querySelectorAll('.toast');
  existingToasts.forEach(toast => toast.remove());

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';
  
  toast.innerHTML = `
    <i class='bx ${icon}'></i>
    <span class="toast-message">${message}</span>
    <i class='bx bx-x toast-close' onclick="closeToast(this)"></i>
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.classList.add('show');
  }, 10);
  
  setTimeout(() => {
    hideToast(toast);
  }, 4000);
}

function hideToast(toast) {
  toast.classList.add('hide');
  setTimeout(() => {
    toast.remove();
  }, 400);
}

function closeToast(closeBtn) {
  const toast = closeBtn.closest('.toast');
  hideToast(toast);
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const menuLinks = document.querySelectorAll('.menu a');
  menuLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      }
    });
  });
});

// Status Toggle Handler
const statusToggle = document.getElementById('statusToggle');
const statusLabel = document.getElementById('statusLabel');
const passwordModal = document.getElementById('passwordModal');
const adminPasswordInput = document.getElementById('adminPassword');
const passwordError = document.getElementById('passwordError');

statusToggle.addEventListener('change', function() {
  const isActive = this.checked;
  
  // If going offline, require password
  if (!isActive) {
    passwordModal.style.display = 'flex';
    adminPasswordInput.value = '';
    passwordError.classList.remove('show');
    adminPasswordInput.focus();
  } else {
    // Going online - no password needed
    updateStatus(true);
  }
});

function togglePasswordVisibility() {
  const passwordInput = document.getElementById('adminPassword');
  const passwordIcon = document.getElementById('passwordIcon');
  
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    passwordIcon.className = 'bx bx-show';
  } else {
    passwordInput.type = 'password';
    passwordIcon.className = 'bx bx-hide';
  }
}

function cancelOffline() {
  passwordModal.style.display = 'none';
  statusToggle.checked = true; // Revert to online
}

function verifyPassword() {
  const password = adminPasswordInput.value;
  
  if (!password) {
    passwordError.textContent = 'Please enter a password';
    passwordError.classList.add('show');
    return;
  }
  
  // Send password to server for verification
  fetch('verify_admin_password.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ password: password })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      passwordModal.style.display = 'none';
      updateStatus(false);
    } else {
      passwordError.textContent = data.message || 'Incorrect password. Please try again.';
      passwordError.classList.add('show');
      adminPasswordInput.value = '';
      adminPasswordInput.focus();
    }
  })
  .catch(error => {
    console.error('Error:', error);
    passwordError.textContent = 'Error verifying password. Please try again.';
    passwordError.classList.add('show');
  });
}

// Allow Enter key to submit password
adminPasswordInput.addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    verifyPassword();
  }
});

// Close modal when clicking outside
window.addEventListener('click', function(e) {
  if (e.target === passwordModal) {
    cancelOffline();
  }
});

function updateStatus(isActive) {
  fetch('update_status.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ is_active: isActive })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      statusLabel.textContent = isActive ? 'Online' : 'Offline';
      statusLabel.className = 'status-label ' + (isActive ? 'active' : 'inactive');
      showToast('Status updated successfully!', 'success');
    } else {
      showToast('Failed to update status', 'error');
      // Revert toggle if failed
      statusToggle.checked = !isActive;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error updating status', 'error');
    // Revert toggle if error
    statusToggle.checked = !isActive;
  });
}

// Complete Appointment Handler
function completeAppointment(appointmentId) {
  if (!confirm('Mark this appointment as completed?')) {
    return;
  }

  const button = event.target.closest('button');
  button.disabled = true;
  button.innerHTML = '<i class="bx bx-loader bx-spin"></i> Processing...';

  fetch('complete_appointment.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ appointment_id: appointmentId })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('Appointment completed successfully!', 'success');
      document.getElementById('row-' + appointmentId).remove();
      
      // Check if table is empty
      const tbody = document.querySelector('tbody');
      if (tbody.children.length === 0) {
        location.reload();
      }
    } else {
      showToast(data.message || 'Failed to complete appointment', 'error');
      button.disabled = false;
      button.innerHTML = '<i class="bx bx-check"></i> Complete';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error completing appointment', 'error');
    button.disabled = false;
    button.innerHTML = '<i class="bx bx-check"></i> Complete';
  });
}
</script>

</body>
</html>