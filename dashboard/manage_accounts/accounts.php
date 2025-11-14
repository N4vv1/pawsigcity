<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Handle new user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $first_name  = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name   = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone      = trim($_POST['phone']);
    $role       = $_POST['role'];

    $check = pg_query_params($conn, "SELECT * FROM users WHERE email = $1", [$email]);

    if (pg_num_rows($check) > 0) {
        $_SESSION['error'] = "Email is already registered.";
    } else {
        $result = pg_query_params(
            $conn,
            "INSERT INTO users (first_name, middle_name, last_name, email, password, phone, role)
             VALUES ($1, $2, $3, $4, $5, $6, $7)",
            [$first_name, $middle_name, $last_name, $email, $password, $phone, $role]
        );

        if ($result) {
            $_SESSION['success'] = "User account created successfully!";
        } else {
            $_SESSION['error'] = "Something went wrong. Please try again.";
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id         = intval($_POST['user_id']);
    $first_name  = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name   = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);

    $result = pg_query_params(
        $conn,
        "UPDATE users
         SET first_name=$1, middle_name=$2, last_name=$3, email=$4, phone=$5
         WHERE user_id=$6",
        [$first_name, $middle_name, $last_name, $email, $phone, $id]
    );

    if ($result) {
        $_SESSION['success'] = "User updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update user.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $result = pg_query_params($conn, "DELETE FROM users WHERE user_id = $1", [$delete_id]);
    
    if ($result) {
        $_SESSION['success'] = "User deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete user.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch users
$users = pg_query($conn, "SELECT * FROM users ORDER BY last_name ASC, first_name ASC");

// If editing specific user
$edit_user = null;
if (isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $result = pg_query_params($conn, "SELECT * FROM users WHERE user_id = $1", [$edit_id]);
    $edit_user = pg_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | User Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">

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

    /* SIDEBAR */
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
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      overflow-y: auto;
      z-index: 999;
      transition: transform 0.3s;
    }

    .sidebar .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .sidebar .logo img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
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
      font-weight: 600;
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

    /* Dropdown styles */
    .dropdown {
      position: relative;
    }

    .dropdown-toggle {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 12px;
      text-decoration: none;
      color: var(--dark-color);
      border-radius: 14px;
      transition: background 0.3s, color 0.3s;
      font-weight: 600;
      cursor: pointer;
    }

    .dropdown-toggle:hover,
    .dropdown-toggle.active {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .dropdown-menu {
      display: none;
      flex-direction: column;
      gap: 5px;
      margin-left: 20px;
      margin-top: 5px;
    }

    .dropdown-menu a {
      padding: 8px 12px;
      font-size: 0.9rem;
    }

    /* MAIN CONTENT */
    main {
      margin-left: 260px;
      padding: 40px;
      width: calc(100% - 260px);
    }

    .header {
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: 2rem;
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .header p {
      color: #666;
      font-size: 0.95rem;
    }

    /* ADD BUTTON */
    .add-btn {
      background: var(--dark-color);
      color: var(--white-color);
      padding: 14px 30px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 30px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .add-btn:hover {
      background: #1a1a1a;
      transform: translateY(-1px);
    }

    .add-btn i {
      font-size: 20px;
    }

    /* TABLE SECTION */
    .table-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .table-section h2 {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    table th,
    table td {
      padding: 15px 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }

    table th {
      background-color: #fafafa;
      color: var(--dark-color);
      font-weight: 600;
      font-size: 0.9rem;
      position: sticky;
      top: 0;
    }

    table tbody tr:hover {
      background-color: #fafafa;
    }

    .role-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .role-badge.admin {
      background: rgba(244, 67, 54, 0.1);
      color: var(--delete-color);
    }

    .role-badge.customer {
      background: rgba(76, 175, 80, 0.1);
      color: var(--edit-color);
    }

    .role-badge.groomer {
      background: rgba(255, 152, 0, 0.1);
      color: #FF9800;
    }

    .role-badge.receptionist {
      background: rgba(168, 230, 207, 0.3);
      color: #2d8a5d;
    }

    /* ACTION BUTTONS */
    .actions {
      display: flex;
      gap: 8px;
    }

    .actions a,
    .actions button {
      padding: 6px 14px;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.2s;
      border: none;
      cursor: pointer;
      font-family: "Montserrat", sans-serif;
    }

    .edit-btn {
      background: rgba(76, 175, 80, 0.1);
      color: var(--edit-color);
    }

    .edit-btn:hover {
      background: var(--edit-color);
      color: var(--white-color);
    }

    .delete-btn {
      background: rgba(244, 67, 54, 0.1);
      color: var(--delete-color);
    }

    .delete-btn:hover {
      background: var(--delete-color);
      color: var(--white-color);
    }

    /* MODAL */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-content h2 {
      margin-bottom: 25px;
      color: var(--dark-color);
      font-size: 1.5rem;
      font-weight: 600;
    }

    .close {
      position: absolute;
      right: 20px;
      top: 20px;
      font-size: 1.8rem;
      color: #999;
      cursor: pointer;
      transition: color 0.2s;
    }

    .close:hover {
      color: var(--dark-color);
    }

    /* Input Form Styles */
    .input_box {
      position: relative;
      margin-bottom: 20px;
    }

    .input_box label {
      display: block;
      margin-bottom: 8px;
      color: var(--dark-color);
      font-weight: 500;
      font-size: 0.9rem;
    }

    .input-field {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: var(--light-pink-color);
      font-size: 1rem;
      color: var(--dark-color);
      transition: all 0.2s;
    }

    .input-field:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    /* SUBMIT BUTTON */
    .input-submit {
      width: 100%;
      padding: 14px;
      border-radius: 8px;
      border: none;
      background-color: var(--dark-color);
      font-weight: 600;
      color: var(--white-color);
      cursor: pointer;
      transition: all 0.2s;
      font-size: 1rem;
    }

    .input-submit:hover {
      background-color: #1a1a1a;
      transform: translateY(-1px);
    }

    /* ENHANCED TOAST NOTIFICATION */
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

    /* MOBILE MENU BUTTON */
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
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      transition: 0.3s;
    }

    .mobile-menu-btn i {
      font-size: 24px;
      color: var(--dark-color);
    }

    .mobile-menu-btn:hover {
      background: var(--secondary-color);
    }

    /* SIDEBAR OVERLAY */
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
      transition: opacity 0.3s;
    }

    .sidebar-overlay.active {
      display: block;
      opacity: 1;
    }

    /* RESPONSIVE DESIGN */
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

      main {
        margin-left: 0;
        width: 100%;
        padding: 80px 20px 40px;
      }

      .header h1 {
        font-size: 1.5rem;
      }

      .table-section {
        padding: 20px;
        overflow-x: auto;
      }

      table {
        min-width: 700px;
        font-size: 0.85rem;
      }

      table th,
      table td {
        padding: 10px 8px;
      }

      .actions {
        flex-direction: column;
        gap: 5px;
      }

      .modal-content {
        width: 95%;
        padding: 25px;
      }

      .toast {
        bottom: 20px;
        right: 20px;
        left: 20px;
        min-width: auto;
      }
    }

    @media screen and (max-width: 480px) {
      main {
        padding: 70px 15px 30px;
      }

      .header h1 {
        font-size: 1.3rem;
      }

      .add-btn {
        width: 100%;
        text-align: center;
      }

      table {
        min-width: 650px;
        font-size: 0.75rem;
      }

      table th,
      table td {
        padding: 8px 5px;
      }
    }
  </style>
</head>
<body>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>

    <div class="dropdown">
      <a href="javascript:void(0)" class="dropdown-toggle active" onclick="toggleDropdown(event)">
        <span><i class='bx bx-user'></i> Users</span>
        <i class='bx bx-chevron-down'></i>
      </a>
      <div class="dropdown-menu" style="display: block;">
        <a href="../manage_accounts/accounts.php" class="active"><i class='bx bx-user-circle'></i> All Users</a>
        <a href="../groomer_management/groomer_accounts.php"><i class='bx bx-scissors'></i> Groomers</a>
      </div>
    </div>

    <hr>

    <div class="dropdown">
      <a href="javascript:void(0)" class="dropdown-toggle" onclick="toggleDropdown(event)">
        <span><i class='bx bx-spa'></i> Services</span>
        <i class='bx bx-chevron-down'></i>
      </a>
      <div class="dropdown-menu">
        <a href="../service/services.php"><i class='bx bx-list-ul'></i> All Services</a>
        <a href="../service/manage_prices.php"><i class='bx bx-dollar'></i> Manage Pricing</a>
      </div>
    </div>

    <hr>
    <a href="../session_notes/notes.php"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<!-- Main Content -->
<main>
  <!-- Header -->
  <div class="header">
    <h1>User Management</h1>
  </div>

  <button class="add-btn" onclick="openModal()">
    <i class='bx bx-plus'></i> Add New User
  </button>
  
  <!-- Table Section -->
  <div class="table-section">
    <h2>All Users</h2>
    
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>User ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($user = pg_fetch_assoc($users)): ?>
          <tr>
            <td><?= $user['user_id'] ?></td>
            <td>
              <?= htmlspecialchars($user['first_name']) ?>
              <?= htmlspecialchars($user['middle_name']) ?>
              <?= htmlspecialchars($user['last_name']) ?>
            </td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['phone']) ?></td>
            <td>
              <span class="role-badge <?= strtolower($user['role']) ?>">
                <?= ucfirst($user['role']) ?>
              </span>
            </td>
            <td>
              <div class="actions">
                <a href="?id=<?= $user['user_id'] ?>" class="edit-btn">
                  <i class='bx bx-edit'></i> Edit
                </a>
                <button onclick="confirmDelete(<?= $user['user_id'] ?>)" class="delete-btn">
                  <i class='bx bx-trash'></i> Delete
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Add User Modal -->
<div id="userModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAddModal()">&times;</span>
    <h2>Create User Account</h2>

    <form method="POST">
      <input type="hidden" name="create_user" value="1">

      <div class="input_box">
        <label>First Name</label>
        <input type="text" class="input-field" name="first_name" placeholder="Enter first name" required />
      </div>

      <div class="input_box">
        <label>Middle Name</label>
        <input type="text" class="input-field" name="middle_name" placeholder="Enter middle name (optional)" />
      </div>

      <div class="input_box">
        <label>Last Name</label>
        <input type="text" class="input-field" name="last_name" placeholder="Enter last name" required />
      </div>

      <div class="input_box">
        <label>Email Address</label>
        <input type="email" class="input-field" name="email" placeholder="Enter email address" required />
      </div>

      <div class="input_box">
        <label>Password</label>
        <input type="password" class="input-field" name="password" placeholder="Enter password" required />
      </div>

      <div class="input_box">
        <label>Phone Number</label>
        <input type="text" class="input-field" name="phone" placeholder="Enter phone number" required />
      </div>

      <div class="input_box">
        <label>Role</label>
        <select class="input-field" name="role" required>
          <option value="" disabled selected>Select Role</option>
          <option value="admin">Admin</option>
          <option value="customer">Customer</option>
          <option value="groomer">Groomer</option>
          <option value="receptionist">Receptionist</option>
        </select>
      </div>
      
      <input type="submit" class="input-submit" value="Create User Account" />
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<?php if (isset($edit_user)): ?>
<div id="editModal" class="modal" style="display:flex;">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit User</h2>
    <form method="POST">
      <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
      
      <div class="input_box">
        <label>First Name</label>
        <input type="text" name="first_name" class="input-field" value="<?= htmlspecialchars($edit_user['first_name']) ?>" required>
      </div>

      <div class="input_box">
        <label>Middle Name</label>
        <input type="text" name="middle_name" class="input-field" value="<?= htmlspecialchars($edit_user['middle_name']) ?>">
      </div>

      <div class="input_box">
        <label>Last Name</label>
        <input type="text" name="last_name" class="input-field" value="<?= htmlspecialchars($edit_user['last_name']) ?>" required>
      </div>

      <div class="input_box">
        <label>Email Address</label>
        <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
      </div>

      <div class="input_box">
        <label>Phone Number</label>
        <input type="text" name="phone" class="input-field" value="<?= htmlspecialchars($edit_user['phone']) ?>" required>
      </div>

      <input type="submit" name="update_user" class="input-submit" value="Update User">
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// Toast Notification System
function showToast(message, type = 'success') {
  // Remove any existing toasts
  const existingToasts = document.querySelectorAll('.toast');
  existingToasts.forEach(toast => toast.remove());

  // Create new toast
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';
  
  toast.innerHTML = `
    <i class='bx ${icon}'></i>
    <span class="toast-message">${message}</span>
    <i class='bx bx-x toast-close' onclick="closeToast(this)"></i>
  `;
  
  document.body.appendChild(toast);
  
  // Trigger animation
  setTimeout(() => {
    toast.classList.add('show');
  }, 10);
  
  // Auto hide after 4 seconds
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

// Dropdown functionality
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation();
  const dropdown = event.currentTarget.nextElementSibling;
  dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
}

document.addEventListener('click', function(event) {
  if (!event.target.closest('.dropdown')) {
    const dropdowns = document.getElementsByClassName("dropdown-menu");
    for (let i = 0; i < dropdowns.length; i++) {
      dropdowns[i].style.display = 'none';
    }
  }
});

// Sidebar toggle
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

// Modal functions
function openModal() {
  document.getElementById('userModal').style.display = 'flex';
}

function closeAddModal() {
  document.getElementById('userModal').style.display = 'none';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  window.history.replaceState(null, null, window.location.pathname);
}

// Delete confirmation
function confirmDelete(userId) {
  if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
    window.location.href = '?delete_id=' + userId;
  }
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
  const addModal = document.getElementById('userModal');
  const editModal = document.getElementById('editModal');
  
  if (event.target === addModal) closeAddModal();
  if (event.target === editModal) closeEditModal();
});

// Close sidebar on menu link click (mobile)
document.addEventListener('DOMContentLoaded', function() {
  const menuLinks = document.querySelectorAll('.menu a:not(.dropdown-toggle)');
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
</script>

<?php if (isset($_SESSION['success'])): ?>
  <script>
    showToast('<?= addslashes($_SESSION['success']); ?>', 'success');
  </script>
  <?php unset($_SESSION['success']); ?>
<?php elseif (isset($_SESSION['error'])): ?>
  <script>
    showToast('<?= addslashes($_SESSION['error']); ?>', 'error');
  </script>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>