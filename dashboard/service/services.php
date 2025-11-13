<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Handle new service creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_service'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 'true' : 'false';

    $result = pg_query_params($conn, 
        "INSERT INTO packages (name, description, is_active) VALUES ($1, $2, $3)",
        [$name, $description, $is_active]);

    if ($result) {
        $_SESSION['success'] = "Service created successfully!";
    } else {
        $_SESSION['error'] = "Failed to create service.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle service update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $id = intval($_POST['package_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 'true' : 'false';

    $result = pg_query_params($conn,
        "UPDATE packages SET name=$1, description=$2, is_active=$3 WHERE package_id=$4",
        [$name, $description, $is_active, $id]);

    if ($result) {
        $_SESSION['success'] = "Service updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update service.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle service deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $result = pg_query_params($conn, "DELETE FROM packages WHERE package_id = $1", [$delete_id]);
    
    if ($result) {
        $_SESSION['success'] = "Service deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete service.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all services with pricing info
$services_query = "
    SELECT 
        p.package_id,
        p.name,
        p.description,
        p.is_active,
        MIN(pp.price) as min_price,
        MAX(pp.price) as max_price,
        COUNT(pp.price_id) as price_count
    FROM packages p
    LEFT JOIN package_prices pp ON p.package_id = pp.package_id
    GROUP BY p.package_id, p.name, p.description, p.is_active
    ORDER BY p.package_id ASC
";
$services = pg_query($conn, $services_query);

// If editing specific service
$edit_service = null;
if (isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $result = pg_query_params($conn, "SELECT * FROM packages WHERE package_id = $1", [$edit_id]);
    $edit_service = pg_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | Service Management</title>
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
      overflow-y: auto;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s;
      z-index: 999;
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
      border-radius: 8px;
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

    /* DROPDOWN */
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
      border-radius: 8px;
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
    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
    }

    /* HEADER */
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
    .table-wrapper {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 20px;
    }

    .table-wrapper h3 {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: transparent;
      box-shadow: none;
      min-width: 800px;
    }

    th, td {
      padding: 15px 12px;
      text-align: left;
      border: none;
      border-bottom: 1px solid #f0f0f0;
      font-size: 0.95rem;
    }

    th {
      background-color: #fafafa;
      font-weight: 600;
      color: var(--dark-color);
      font-size: 0.9rem;
      position: sticky;
      top: 0;
    }

    tbody tr:hover {
      background-color: #fafafa;
    }

    /* STATUS BADGE */
    .status-badge {
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-block;
    }

    .status-active {
      background: rgba(76, 175, 80, 0.1);
      color: var(--edit-color);
    }

    .status-inactive {
      background: rgba(244, 67, 54, 0.1);
      color: var(--delete-color);
    }

    /* ACTION BUTTONS */
    .actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
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
      white-space: nowrap;
      border: none;
      cursor: pointer;
      font-family: "Montserrat", sans-serif;
    }

    .view-btn {
      background: rgba(23, 162, 184, 0.1);
      color: #17a2b8;
    }

    .view-btn:hover {
      background: #17a2b8;
      color: var(--white-color);
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
      max-width: 600px;
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

    /* INPUT FIELDS */
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
      font-family: "Montserrat", sans-serif;
      transition: all 0.2s;
    }

    textarea.input-field {
      min-height: 120px;
      resize: vertical;
      font-family: "Montserrat", sans-serif;
    }

    .input-field:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    /* CHECKBOX */
    .checkbox-wrapper {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      padding: 12px 15px;
      background-color: var(--light-pink-color);
      border-radius: 8px;
    }

    .checkbox-wrapper input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }

    .checkbox-wrapper label {
      font-size: 1rem;
      color: var(--dark-color);
      cursor: pointer;
      font-weight: 500;
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

    /* RESPONSIVE */
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
        font-size: 1.5rem;
      }

      .header p {
        font-size: 0.85rem;
      }
      
      .add-btn {
        padding: 12px 20px;
        font-size: 0.95rem;
      }
      
      .table-wrapper {
        padding: 20px;
        overflow-x: auto;
      }
      
      table {
        min-width: 700px;
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 8px;
      }
      
      .actions a,
      .actions button {
        padding: 8px 12px;
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 2px;
      }

      .modal-content {
        width: 95%;
        padding: 25px;
        max-height: 85vh;
      }
      
      .input-field {
        padding: 12px;
        font-size: 16px;
      }
      
      .input_box label {
        font-size: 0.85rem;
      }

      .toast {
        bottom: 20px;
        right: 20px;
        left: 20px;
        min-width: auto;
      }
    }

    @media screen and (max-width: 480px) {
      .content {
        padding: 70px 15px 30px;
      }
      
      .header h1 {
        font-size: 1.3rem;
        margin-bottom: 8px;
      }
  
      .header p {
        font-size: 0.85rem;
      }
  
      .add-btn {
        width: 100%;
        padding: 12px;
        text-align: center;
        justify-content: center;
      }

      .table-wrapper {
        padding: 15px;
      }
      
      table {
        min-width: 700px;
        font-size: 0.75rem;
      }

      th, td {
        padding: 8px 5px;
        font-size: 0.75rem;
      }
      
      .actions a,
      .actions button {
        padding: 6px 10px;
        font-size: 0.75rem;
      }
      
      .modal-content {
        padding: 15px;
      }
      
      .modal-content h2 {
        font-size: 1.3rem;
      }
      
      .input-field {
        padding: 10px;
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

    <!-- USERS DROPDOWN -->
    <div class="dropdown">
      <a href="javascript:void(0)" class="dropdown-toggle" onclick="toggleDropdown(event)">
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
      <a href="javascript:void(0)" class="dropdown-toggle active" onclick="toggleDropdown(event)">
        <span><i class='bx bx-spa'></i> Services</span>
        <i class='bx bx-chevron-down'></i>
      </a>
      <div class="dropdown-menu" style="display: block;">
        <a href="services.php" class="active"><i class='bx bx-list-ul'></i> All Services</a>
        <a href="manage_prices.php"><i class='bx bx-dollar'></i> Manage Pricing</a>
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
  <!-- Header -->
  <div class="header">
    <h1>Service Management</h1>
    <p>Manage all services and packages</p>
  </div>

  <button class="add-btn" onclick="openModal()">
    <i class='bx bx-plus'></i> Add New Service
  </button>
  
  <div class="table-wrapper">
    <h3>All Services</h3>
    
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Service ID</th>
            <th>Service Name</th>
            <th>Description</th>
            <th>Price Range</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($service = pg_fetch_assoc($services)): ?>
          <tr>
            <td><?= $service['package_id'] ?></td>
            <td><strong><?= htmlspecialchars($service['name']) ?></strong></td>
            <td style="max-width: 300px;">
              <?= htmlspecialchars(substr($service['description'], 0, 80)) ?>...
            </td>
            <td>
              <?php if ($service['min_price']): ?>
                <?php if ($service['min_price'] == $service['max_price']): ?>
                  ₱<?= number_format($service['min_price'], 2) ?>
                <?php else: ?>
                  ₱<?= number_format($service['min_price'], 2) ?> - ₱<?= number_format($service['max_price'], 2) ?>
                <?php endif; ?>
              <?php else: ?>
                <span style="color: #999;">No pricing set</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($service['is_active'] == 't'): ?>
                <span class="status-badge status-active">Active</span>
              <?php else: ?>
                <span class="status-badge status-inactive">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="actions">
                <a href="manage_prices.php?id=<?= $service['package_id'] ?>" class="view-btn">
                  <i class='bx bx-dollar'></i> Pricing
                </a>
                <a href="?id=<?= $service['package_id'] ?>" class="edit-btn">
                  <i class='bx bx-edit'></i> Edit
                </a>
                <button onclick="confirmDelete(<?= $service['package_id'] ?>)" class="delete-btn">
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

<!-- Add Service Modal -->
<div id="serviceModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAddModal()">&times;</span>
    <h2>Create New Service</h2>

    <form method="POST">
      <input type="hidden" name="create_service" value="1">

      <div class="input_box">
        <label>Service Name</label>
        <input type="text" class="input-field" name="name" placeholder="Enter service name" required />
      </div>

      <div class="input_box">
        <label>Description</label>
        <textarea class="input-field" name="description" placeholder="Enter service description" required></textarea>
      </div>

      <div class="checkbox-wrapper">
        <input type="checkbox" id="is_active" name="is_active" checked>
        <label for="is_active">Active Service (Visible to customers)</label>
      </div>
      
      <input type="submit" class="input-submit" value="Create Service" />
    </form>
  </div>
</div>

<!-- Edit Service Modal -->
<?php if (isset($edit_service)): ?>
<div id="editModal" class="modal" style="display:flex;">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Service</h2>
    <form method="POST">
      <input type="hidden" name="package_id" value="<?= $edit_service['package_id'] ?>">
      
      <div class="input_box">
        <label>Service Name</label>
        <input type="text" name="name" class="input-field" value="<?= htmlspecialchars($edit_service['name']) ?>" required>
      </div>

      <div class="input_box">
        <label>Description</label>
        <textarea name="description" class="input-field" required><?= htmlspecialchars($edit_service['description']) ?></textarea>
      </div>

      <div class="checkbox-wrapper">
        <input type="checkbox" id="edit_is_active" name="is_active" <?= $edit_service['is_active'] == 't' ? 'checked' : '' ?>>
        <label for="edit_is_active">Active Service (Visible to customers)</label>
      </div>

      <input type="submit" name="update_service" class="input-submit" value="Update Service">
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
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
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
  document.getElementById('serviceModal').style.display = 'flex';
}

function closeAddModal() {
  document.getElementById('serviceModal').style.display = 'none';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  window.history.replaceState(null, null, window.location.pathname);
}

// Delete confirmation
function confirmDelete(serviceId) {
  if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
    window.location.href = '?delete_id=' + serviceId;
  }
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
  const addModal = document.getElementById('serviceModal');
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