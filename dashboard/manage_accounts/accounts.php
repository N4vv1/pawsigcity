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

    // Check if email exists
    pg_prepare($conn, "check_user", "SELECT * FROM users WHERE email = $1");
    $check = pg_execute($conn, "check_user", [$email]);

    if (pg_num_rows($check) > 0) {
        $_SESSION['error'] = "Email is already registered.";
    } else {
        pg_prepare(
            $conn,
            "insert_user",
            "INSERT INTO users (first_name, middle_name, last_name, email, password, phone, role)
             VALUES ($1, $2, $3, $4, $5, $6, $7)"
        );
        $result = pg_execute($conn, "insert_user", [
            $first_name, $middle_name, $last_name,
            $email, $password, $phone, $role
        ]);

        if ($result) {
            $_SESSION['success'] = "User account created successfully.";
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

    pg_prepare(
        $conn,
        "update_user",
        "UPDATE users
         SET first_name=$1, middle_name=$2, last_name=$3, email=$4, phone=$5
         WHERE user_id=$6"
    );
    $result = pg_execute($conn, "update_user", [
        $first_name, $middle_name, $last_name,
        $email, $phone, $id
    ]);

    if ($result) {
        $_SESSION['success'] = "User updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update user.";
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

    /* MOBILE MENU BUTTON - Base styles FIRST */
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
      border-radius: var(--border-radius-s);
      transition: background 0.3s, color 0.3s;
      font-weight: var(--font-weight-semi-bold);
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

    .add-btn {
      background: var(--primary-color);
      padding: 10px 20px;
      border-radius: var(--border-radius-s);
      text-decoration: none;
      color: var(--dark-color);
      font-weight: var(--font-weight-semi-bold);
      display: inline-block;
      margin-bottom: 20px;
      cursor: pointer;
      border: none;
    }

    .add-btn:hover {
      background: var(--secondary-color);
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

    .actions a {
      padding: 6px 14px;
      font-size: var(--font-size-s);
      font-weight: var(--font-weight-semi-bold);
      text-decoration: none;
      margin: 0 5px;
      border-radius: var(--border-radius-s);
      display: inline-block;
    }

    .edit-btn {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .edit-btn:hover {
      background-color: #fdd56c;
    }

    .delete-btn {
      background-color: #ff6b6b;
      color: var(--white-color);
    }

    .delete-btn:hover {
      background-color: #ff4949;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: var(--white-color);
      padding: 2rem;
      border-radius: var(--border-radius-s);
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .modal-content h2 {
      margin-bottom: 1rem;
      color: var(--dark-color);
      text-align: center;
    }

    .close {
      position: absolute;
      right: 1rem;
      top: 1rem;
      font-size: 1.5rem;
      color: var(--dark-color);
      cursor: pointer;
    }

    /* Input Form Styles */
    .input_box {
      position: relative;
      margin-bottom: 1.5rem;
    }

    .input-field {
      width: 100%;
      padding: 0.9rem 2.5rem;
      border: 1px solid var(--medium-gray-color);
      border-radius: var(--border-radius-s);
      background-color: var(--light-pink-color);
      font-size: var(--font-size-n);
      color: var(--dark-color);
    }

    .input-field:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
    }

    .label {
      position: absolute;
      left: 2.5rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: var(--font-size-s);
      color: var(--dark-color);
      transition: 0.3s ease;
      pointer-events: none;
    }

    .input-field:focus + .label,
    .input-field:valid + .label {
      top: -0.6rem;
      left: 1rem;
      background-color: var(--white-color);
      padding: 0 0.3rem;
      font-size: 0.75rem;
      color: var(--primary-color);
    }

    .icon {
      position: absolute;
      top: 50%;
      left: 0.8rem;
      transform: translateY(-50%);
      font-size: 1.2rem;
      color: var(--dark-color);
    }

    .input-submit {
      width: 100%;
      padding: 0.9rem;
      background-color: var(--primary-color);
      color: var(--dark-color);
      font-size: var(--font-size-n);
      border: none;
      border-radius: var(--border-radius-s);
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
    }

    .input-submit:hover {
      background-color: var(--secondary-color);
    }

    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 14px 20px;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      animation: fadeOut 4s forwards;
    }

    .toast-success {
      background-color: #eaffea;
      color: #2d8a2d;
    }

    .toast-error {
      background-color: #ffeaea;
      color: #e74c3c;
    }

    @keyframes fadeOut {
      0%, 90% { opacity: 1; }
      100% { opacity: 0; transform: translateY(-20px); }
    }

    /* RESPONSIVE DESIGN - Media queries AFTER base styles */
    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.9rem;
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

      table {
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 8px;
      }

      .modal-content {
        width: 95%;
        padding: 20px;
      }
    }

    @media screen and (max-width: 480px) {
      .content {
        padding: 70px 15px 30px;
      }

      .sidebar .logo img {
        width: 60px;
        height: 60px;
      }

      .menu a {
        padding: 8px 10px;
        font-size: 0.9rem;
      }

      .menu a i {
        font-size: 18px;
      }

      table {
        font-size: 0.75rem;
      }

      th, td {
        padding: 8px 5px;
      }
    }

  /* Add these improvements to your existing CSS */

/* 1. Make table horizontally scrollable on mobile/tablet */
.table-wrapper {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
  margin-bottom: 20px;
}

/* 2. Prevent iOS zoom on input focus */
.input-field,
.input-submit,
select.input-field {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}

/* 3. Smooth scrolling */
html {
  scroll-behavior: smooth;
}

/* TABLET RESPONSIVE (768px - 1024px) */
@media screen and (min-width: 769px) and (max-width: 1024px) {
  .content {
    padding: 30px;
  }
  
  table {
    font-size: 0.9rem;
  }
  
  th, td {
    padding: 12px 8px;
  }
  
  .table-wrapper {
    overflow-x: auto;
  }
  
  table {
    min-width: 700px;
  }
}

/* MOBILE RESPONSIVE (up to 768px) */
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
  
  h2 {
    font-size: 1.8rem;
  }
  
  .add-btn {
    padding: 12px 20px;
    font-size: 0.95rem;
  }
  
  /* Make table scrollable horizontally */
  .table-wrapper {
    overflow-x: auto;
    margin: 0 -20px; /* Extend to edges */
    padding: 0 20px;
  }
  
  table {
    min-width: 700px; /* Maintains table structure */
    font-size: 0.85rem;
  }

  th, td {
    padding: 10px 8px;
    white-space: nowrap; /* Prevents text wrapping */
  }
  
  /* Make action buttons more touch-friendly */
  .actions a {
    padding: 8px 12px;
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    width: 95%;
    padding: 20px;
    max-height: 85vh;
  }
  
  .input-field {
    padding: 0.8rem 2.2rem;
    font-size: 16px; /* Prevents iOS zoom */
  }
  
  .label {
    font-size: 0.85rem;
  }
  
  .input_box {
    margin-bottom: 1.3rem;
  }
}

/* SMALL MOBILE (up to 480px) */
@media screen and (max-width: 480px) {
  .content {
    padding: 70px 15px 30px;
  }
  
  h2 {
    font-size: 1.5rem;
    margin-bottom: 15px;
  }
  
  .add-btn {
    width: 100%;
    padding: 12px;
    text-align: center;
  }

  .sidebar .logo img {
    width: 60px;
    height: 60px;
  }

  .menu a {
    padding: 8px 10px;
    font-size: 0.9rem;
  }

  .menu a i {
    font-size: 18px;
  }
  
  /* Keep table scrollable */
  .table-wrapper {
    margin: 0 -15px;
    padding: 0 15px;
  }
  
  table {
    min-width: 650px;
    font-size: 0.75rem;
  }

  th, td {
    padding: 8px 5px;
    font-size: 0.75rem;
  }
  
  .actions a {
    padding: 6px 10px;
    font-size: 0.75rem;
    margin: 2px;
  }
  
  .modal-content {
    padding: 15px;
  }
  
  .modal-content h2 {
    font-size: 1.3rem;
  }
  
  .input-field {
    padding: 0.75rem 2rem;
  }
  
  .icon {
    font-size: 1rem;
  }
}

/* EXTRA SMALL DEVICES (up to 360px) */
@media screen and (max-width: 360px) {
  .content {
    padding: 70px 10px 30px;
  }
  
  h2 {
    font-size: 1.3rem;
  }
  
  table {
    min-width: 600px;
    font-size: 0.7rem;
  }
  
  th, td {
    padding: 6px 4px;
  }
  
  .actions a {
    padding: 5px 8px;
    font-size: 0.7rem;
  }
  
  .menu a {
    font-size: 0.85rem;
    padding: 7px 8px;
  }
}

/* LANDSCAPE ORIENTATION (for phones in landscape) */
@media screen and (max-height: 600px) and (orientation: landscape) {
  .modal-content {
    max-height: 95vh;
    padding: 15px;
  }
  
  .sidebar {
    padding: 15px 10px;
  }
  
  .sidebar .logo img {
    width: 50px;
    height: 50px;
  }
  
  .menu a {
    padding: 6px 10px;
  }
}

/* TOUCH DEVICE OPTIMIZATIONS */
@media (hover: none) and (pointer: coarse) {
  /* Better touch targets */
  .menu a,
  .dropdown-toggle {
    min-height: 44px;
    display: flex;
    align-items: center;
  }
  
  .actions a {
    min-height: 44px;
    padding: 10px 14px;
  }
  
  .add-btn {
    min-height: 44px;
  }
  
  .input-submit {
    min-height: 48px;
  }
}

/* PRINT STYLES */
@media print {
  .sidebar,
  .mobile-menu-btn,
  .sidebar-overlay,
  .add-btn,
  .actions,
  .toast {
    display: none !important;
  }
  
  .content {
    margin-left: 0;
    width: 100%;
    padding: 20px;
  }
  
  table {
    page-break-inside: avoid;
    font-size: 0.85rem;
  }
}

/* DESKTOP - Keep original design (1025px and above) */
@media screen and (min-width: 1025px) {
  /* Desktop maintains all original styles */
  /* No changes needed - original design preserved */
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
      <div class="dropdown-menu">
        <a href="../manage_accounts/accounts.php"><i class='bx bx-user-circle'></i> All Users</a>
        <a href="../groomer_management/groomer_accounts.php"><i class='bx bx-scissors'></i> Groomers</a>
      </div>
    </div>

    <hr>

    <!-- SERVICES DROPDOWN -->
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
<main class="content">
  <h2>User Management</h2>
  <button class="add-btn" onclick="openModal()">➕ Add New User</button>
  
  <!-- Find your table in the HTML and wrap it like this: -->
<div class="table-wrapper">
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
        <td><?= ucfirst($user['role']) ?></td>
        <td class="actions">
          <a href="?id=<?= $user['user_id'] ?>" class="edit-btn">Edit</a>
          <a href="delete.php?id=<?= $user['user_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

  <!-- Add User Modal -->
  <div id="userModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeAddModal()">&times;</span>
      <h2>Create User Account</h2>

      <form method="POST">
        <input type="hidden" name="create_user" value="1">

        <div class="input_box">
          <input type="text" class="input-field" name="first_name" required />
          <label class="label">First Name</label>
          <i class='bx bx-user icon'></i>
        </div>

        <div class="input_box">
          <input type="text" class="input-field" name="middle_name" />
          <label class="label">Middle Name</label>
          <i class='bx bx-user icon'></i>
        </div>

        <div class="input_box">
          <input type="text" class="input-field" name="last_name" required />
          <label class="label">Last Name</label>
          <i class='bx bx-user icon'></i>
        </div>

        <div class="input_box">
          <input type="email" class="input-field" name="email" required />
          <label class="label">Email</label>
          <i class='bx bx-envelope icon'></i>
        </div>

        <div class="input_box">
          <input type="password" class="input-field" name="password" required />
          <label class="label">Password</label>
          <i class='bx bx-lock-alt icon'></i>
        </div>

        <div class="input_box">
          <input type="text" class="input-field" name="phone" required />
          <label class="label">Phone Number</label>
          <i class='bx bx-phone icon'></i>
        </div>

        <div class="input_box">
          <select class="input-field" name="role" required>
            <option value="" disabled selected>Select Role</option>
            <option value="admin">Admin</option>
            <option value="customer">Customer</option>
            <option value="groomer">Groomer</option>
            <option value="receptionist">Receptionist</option>
          </select>
          <label class="label">Role</label>
          <i class='bx bx-id-card icon'></i>
        </div>
        
        <div class="input_box">
          <input type="submit" class="input-submit" value="Create Account" />
        </div>
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
          <input type="text" name="first_name" class="input-field" value="<?= htmlspecialchars($edit_user['first_name']) ?>" required>
          <label class="label">First Name</label>
          <i class='bx bx-user icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="middle_name" class="input-field" value="<?= htmlspecialchars($edit_user['middle_name']) ?>">
          <label class="label">Middle Name</label>
          <i class='bx bx-user icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="last_name" class="input-field" value="<?= htmlspecialchars($edit_user['last_name']) ?>" required>
          <label class="label">Last Name</label>
          <i class='bx bx-user icon'></i>
        </div>

        <div class="input_box">
          <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
          <label class="label">Email</label>
          <i class='bx bx-envelope icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="phone" class="input-field" value="<?= htmlspecialchars($edit_user['phone']) ?>" required>
          <label class="label">Phone</label>
          <i class='bx bx-phone icon'></i>
        </div>

        <div class="input_box">
          <input type="submit" name="update_user" class="input-submit" value="Update User">
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
</main>

<script>
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation(); // Prevent event bubbling
  const dropdown = event.currentTarget.nextElementSibling;
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Close dropdown if clicked outside
document.addEventListener('click', function(event) {
  if (!event.target.closest('.dropdown')) {
    const dropdowns = document.getElementsByClassName("dropdown-menu");
    for (let i = 0; i < dropdowns.length; i++) {
      dropdowns[i].style.display = 'none';
    }
  }
});

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

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

// Close modal if clicked outside
document.addEventListener('click', function(event) {
  const addModal = document.getElementById('userModal');
  const editModal = document.getElementById('editModal');
  
  if (event.target === addModal) closeAddModal();
  if (event.target === editModal) closeEditModal();
});

// Close sidebar when clicking a link on mobile
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
  <div class="toast toast-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
  <div class="toast toast-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

</body>
</html>