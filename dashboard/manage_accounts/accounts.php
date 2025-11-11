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

    $result = pg_query_params(
        $conn,
        "UPDATE users
         SET first_name=$1, middle_name=$2, last_name=$3, email=$4, phone=$5
         WHERE user_id=$6",
        [$first_name, $middle_name, $last_name, $email, $phone, $id]
    );

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
      --positive-color: #4CAF50;
      --neutral-color: #FF9800;
      --negative-color: #F44336;
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

    .add-btn {
      background: var(--dark-color);
      color: var(--white-color);
      padding: 14px 35px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 30px;
      cursor: pointer;
      border: none;
      font-size: 1rem;
      transition: all 0.2s;
    }

    .add-btn:hover {
      background: #1a1a1a;
      transform: translateY(-1px);
    }

    .add-btn i {
      margin-right: 8px;
    }

    /* TABLE SECTION - MATCHING SENTIMENT DASHBOARD */
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
      color: var(--negative-color);
    }

    .role-badge.customer {
      background: rgba(76, 175, 80, 0.1);
      color: var(--positive-color);
    }

    .role-badge.groomer {
      background: rgba(255, 152, 0, 0.1);
      color: var(--neutral-color);
    }

    .role-badge.receptionist {
      background: rgba(168, 230, 207, 0.3);
      color: #2d8a5d;
    }

    .actions {
      display: flex;
      gap: 8px;
    }

    .actions a {
      padding: 6px 14px;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      border-radius: 6px;
      display: inline-block;
      transition: all 0.2s;
    }

    .edit-btn {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .edit-btn:hover {
      background-color: #fdd56c;
      transform: translateY(-1px);
    }

    .delete-btn {
      background-color: #ff6b6b;
      color: var(--white-color);
    }

    .delete-btn:hover {
      background-color: #ff4949;
      transform: translateY(-1px);
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
      padding: 2rem;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .modal-content h2 {
      margin-bottom: 1.5rem;
      color: var(--dark-color);
      text-align: center;
      font-size: 1.5rem;
    }

    .close {
      position: absolute;
      right: 1rem;
      top: 1rem;
      font-size: 1.5rem;
      color: var(--dark-color);
      cursor: pointer;
      transition: color 0.2s;
    }

    .close:hover {
      color: var(--negative-color);
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
      border-radius: 8px;
      background-color: var(--light-pink-color);
      font-size: 1rem;
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
      font-size: 0.9rem;
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
      background-color: var(--dark-color);
      color: var(--white-color);
      font-size: 1rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .input-submit:hover {
      background-color: #1a1a1a;
      transform: translateY(-1px);
    }

    /* TOAST */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      z-index: 10000;
      display: none;
      animation: slideIn 0.3s ease-out;
      font-weight: 600;
    }

    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .toast-success {
      background-color: var(--dark-color);
      color: white;
    }

    .toast-error {
      background-color: var(--negative-color);
      color: white;
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
        padding: 20px;
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
      <div class="dropdown-menu">
        <a href="../manage_accounts/accounts.php"><i class='bx bx-user-circle'></i> All Users</a>
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
    <p>Manage all user accounts and roles</p>
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
                <a href="?id=<?= $user['user_id'] ?>" class="edit-btn">Edit</a>
                <a href="delete.php?id=<?= $user['user_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
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

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
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

document.addEventListener('click', function(event) {
  const addModal = document.getElementById('userModal');
  const editModal = document.getElementById('editModal');
  
  if (event.target === addModal) closeAddModal();
  if (event.target === editModal) closeEditModal();
});

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
  
  // Show toast if exists
  <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
    const toast = document.getElementById('toast');
    <?php if (isset($_SESSION['success'])): ?>
      toast.className = 'toast toast-success';
      toast.textContent = '✅ <?= $_SESSION['success']; unset($_SESSION['success']); ?>';
    <?php elseif (isset($_SESSION['error'])): ?>
      toast.className = 'toast toast-error';
      toast.textContent = '❌ <?= $_SESSION['error']; unset($_SESSION['error']); ?>';
    <?php endif; ?>
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 4000);
  <?php endif; ?>
});
</script>

</body>
</html>