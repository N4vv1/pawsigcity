<?php
session_start();
include '../db.php';

// Check if groomer is logged in
if (!isset($_SESSION['groomer_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$groomer_id = $_SESSION['groomer_id'];

// Get groomer's current status - FIXED: Changed 'groomer' to 'groomers' table
$status_query = pg_query_params($conn, "
    SELECT is_active, last_active s
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

// Fetch ONLY confirmed appointments for THIS groomer - FIXED: Added proper groomer_id filter
$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        p.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed,
        (u.first_name || ' ' || u.last_name) AS username,
        u.first_name,
        u.last_name
    FROM appointments a
    JOIN packages p ON a.package_id = p.package_id
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
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .status-toggle h4 {
      margin-bottom: 12px;
      font-size: 0.95rem;
      color: var(--dark-color);
      text-align: center;
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
      border-radius: var(--border-radius-s);
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

    h2 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 25px;
    }

    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: var(--white-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    th, td {
      padding: 14px 10px;
      border: 1px solid var(--medium-gray-color);
      text-align: center;
    }

    th {
      background: var(--primary-color);
      font-weight: var(--font-weight-bold);
      color: var(--dark-color);
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #ffe29d33;
    }

    .action-btn {
      background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    .action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }

    .action-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 80px;
      color: #ddd;
      margin-bottom: 20px;
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
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      background-color: white;
      margin: 15% auto;
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
      animation: slideDown 0.3s;
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

      table {
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 8px;
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

  <!-- Status Toggle - FIXED: Proper checked state from database -->
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
  <h2>My Confirmed Appointments</h2>

  <div id="alertContainer"></div>

  <?php if (pg_num_rows($result) == 0): ?>
    <div class="empty-state">
      <i class='bx bx-calendar-x'></i>
      <h3>No Confirmed Appointments</h3>
      <p>You don't have any confirmed appointments yet</p>
    </div>
  <?php else: ?>
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

// Status Toggle Handler - FIXED: Proper state management with password verification
const statusToggle = document.getElementById('statusToggle');
const statusLabel = document.getElementById('statusLabel');
const passwordModal = document.getElementById('passwordModal');
const adminPasswordInput = document.getElementById('adminPassword');
const passwordError = document.getElementById('passwordError');

statusToggle.addEventListener('change', function() {
  const isActive = this.checked;
  
  // If going offline, require password
  if (!isActive) {
    // Show password modal
    passwordModal.style.display = 'block';
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
      // Password correct - proceed to go offline
      passwordModal.style.display = 'none';
      updateStatus(false);
    } else {
      // Password incorrect
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
      showAlert('Status updated successfully!', 'success');
    } else {
      showAlert('Failed to update status', 'error');
      // Revert toggle if failed
      statusToggle.checked = !isActive;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showAlert('Error updating status', 'error');
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
      showAlert('Appointment completed successfully!', 'success');
      document.getElementById('row-' + appointmentId).remove();
      
      // Check if table is empty
      const tbody = document.querySelector('tbody');
      if (tbody.children.length === 0) {
        location.reload();
      }
    } else {
      showAlert(data.message || 'Failed to complete appointment', 'error');
      button.disabled = false;
      button.innerHTML = '<i class="bx bx-check"></i> Complete';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showAlert('Error completing appointment', 'error');
    button.disabled = false;
    button.innerHTML = '<i class="bx bx-check"></i> Complete';
  });
}

function showAlert(message, type) {
  const alertContainer = document.getElementById('alertContainer');
  const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
  const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';
  
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert ${alertClass}`;
  alertDiv.innerHTML = `<i class='bx ${icon}'></i>${message}`;
  
  alertContainer.appendChild(alertDiv);
  
  setTimeout(() => {
    alertDiv.remove();
  }, 3000);
}
</script>

</body>
</html>