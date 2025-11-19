<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Check if service ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No service selected.";
    header("Location: services.php");
    exit;
}

$package_id = trim($_GET['id']);

// Check if deleted_at column exists
$column_check = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name='packages' AND column_name='deleted_at'");
$has_deleted_at = $column_check && pg_num_rows($column_check) > 0;

// Get service details (only active services if deleted_at exists)
if ($has_deleted_at) {
    $service_query = "SELECT * FROM packages WHERE package_id = $1 AND deleted_at IS NULL";
} else {
    $service_query = "SELECT * FROM packages WHERE package_id = $1";
}

$service_result = pg_query_params($conn, $service_query, [$package_id]);

if (!$service_result || pg_num_rows($service_result) == 0) {
    $_SESSION['error'] = "Service not found.";
    header("Location: services.php");
    exit;
}

$service = pg_fetch_assoc($service_result);

// Handle add price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_price'])) {
    $species = trim($_POST['species']);
    $size = trim($_POST['size']);
    $min_weight = trim($_POST['min_weight']);
    $max_weight = trim($_POST['max_weight']);
    $price = floatval($_POST['price']);

    $insert_query = "INSERT INTO package_prices (package_id, species, size, min_weight, max_weight, price) 
     VALUES ($1, $2, $3, $4, $5, $6)";
    $result = pg_query_params($conn, $insert_query, [
        $package_id, $species, $size, 
        $min_weight ?: null, $max_weight ?: null, $price
    ]);

    if ($result) {
        $_SESSION['success'] = "✓ Price tier added successfully!";
    } else {
        $_SESSION['error'] = "✗ Failed to add price tier. Please try again.";
    }
    header("Location: ?id=" . urlencode($package_id));
    exit;
}

// Handle update price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $price_id = intval($_POST['price_id']);
    $species = trim($_POST['species']);
    $size = trim($_POST['size']);
    $min_weight = trim($_POST['min_weight']);
    $max_weight = trim($_POST['max_weight']);
    $price = floatval($_POST['price']);

    $update_query = "UPDATE package_prices 
                    SET species=$1, size=$2, min_weight=$3, max_weight=$4, price=$5 
                    WHERE price_id=$6";
    $result = pg_query_params($conn, $update_query, [
        $species, $size, 
        $min_weight ?: null, $max_weight ?: null, 
        $price, $price_id
    ]);

    if ($result) {
        $_SESSION['success'] = "✓ Price tier updated successfully!";
    } else {
        $_SESSION['error'] = "✗ Failed to update price tier. Please try again.";
    }
    header("Location: ?id=" . urlencode($package_id));
    exit;
}

// Handle delete price (archive it)
if (isset($_GET['delete_price'])) {
    $price_id = intval($_GET['delete_price']);
    
    // Check if deleted_at column exists in package_prices
    $price_column_check = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name='package_prices' AND column_name='deleted_at'");
    
    if ($price_column_check && pg_num_rows($price_column_check) > 0) {
        // Archive the price tier by setting deleted_at timestamp
        $delete_query = "UPDATE package_prices SET deleted_at = NOW() WHERE price_id = $1";
        $message = "✓ Price tier archived successfully!";
    } else {
        // Fallback to regular delete
        $delete_query = "DELETE FROM package_prices WHERE price_id = $1";
        $message = "✓ Price tier deleted successfully!";
    }
    
    $result = pg_query_params($conn, $delete_query, [$price_id]);

    if ($result) {
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = "✗ Failed to process price tier. Please try again.";
    }
    header("Location: ?id=" . urlencode($package_id));
    exit;
}

// Get all active prices for this service
$price_column_check = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name='package_prices' AND column_name='deleted_at'");
$has_price_deleted_at = $price_column_check && pg_num_rows($price_column_check) > 0;

if ($has_price_deleted_at) {
    $prices_query = "SELECT * FROM package_prices 
                     WHERE package_id = $1 
                     AND deleted_at IS NULL
                     ORDER BY species, size, price";
} else {
    $prices_query = "SELECT * FROM package_prices 
                     WHERE package_id = $1 
                     ORDER BY species, size, price";
}

$prices = pg_query_params($conn, $prices_query, [$package_id]);

if (!$prices) {
    echo "<div style='background: #f44336; color: white; padding: 20px; margin: 20px;'>";
    echo "<strong>Database Error on Prices Query:</strong> " . htmlspecialchars(pg_last_error($conn));
    echo "<br><strong>Query:</strong> " . htmlspecialchars($prices_query);
    echo "<br><strong>Package ID:</strong> " . htmlspecialchars($package_id);
    echo "</div>";
    die();
}

// If editing specific price
$edit_price = null;
if (isset($_GET['edit'])) {
   $edit_id = intval($_GET['edit']);
   
   if ($has_price_deleted_at) {
       $get_price_query = "SELECT * FROM package_prices WHERE price_id = $1 AND deleted_at IS NULL";
   } else {
       $get_price_query = "SELECT * FROM package_prices WHERE price_id = $1";
   }
   
  $result = pg_query_params($conn, $get_price_query, [$edit_id]);
  if ($result && pg_num_rows($result) > 0) {
      $edit_price = pg_fetch_assoc($result);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Pricing - <?= htmlspecialchars($service['name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">
  
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #3ABB87;
      --secondary-color: #FFE29D;
      --light-pink-color: #faf4f5;
      --medium-gray-color: #ccc;
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

    /* SERVICE INFO */
    .service-info {
      background: var(--white-color);
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      border-left: 4px solid var(--primary-color);
    }

    .service-info h3 {
      color: var(--dark-color);
      margin-bottom: 10px;
      font-size: 1.3rem;
      font-weight: 600;
    }

    .service-info p {
      color: #666;
      line-height: 1.6;
    }

    /* BUTTONS */
    .button-group {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }

    .back-btn {
      background: #6c757d;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--white-color);
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
      font-size: 0.95rem;
    }

    .back-btn:hover {
      background: #5a6268;
      transform: translateY(-1px);
    }

    .add-btn {
      background: var(--dark-color);
      color: var(--white-color);
      padding: 12px 30px;
      border: none;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .add-btn:hover {
      background: #1a1a1a;
      transform: translateY(-1px);
    }

    .add-btn i,
    .back-btn i {
      font-size: 18px;
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

    .actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .actions a {
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
    }

    .edit-btn {
      background: rgba(76, 175, 80, 0.1);
      color: #4CAF50;
    }

    .edit-btn:hover {
      background: #4CAF50;
      color: var(--white-color);
    }

    .delete-btn {
      background: rgba(244, 67, 54, 0.1);
      color: #F44336;
    }

    .delete-btn:hover {
      background: #F44336;
      color: var(--white-color);
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state i {
      font-size: 4rem;
      color: #ccc;
      margin-bottom: 20px;
    }

    .empty-state h3 {
      color: var(--dark-color);
      margin-bottom: 10px;
      font-weight: 600;
    }

    .empty-state p {
      color: #666;
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
      background-color: rgba(0, 0, 0, 0.5);
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
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .modal-content h2 {
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
      font-size: 1.5rem;
    }

    .close {
      position: absolute;
      right: 20px;
      top: 20px;
      font-size: 1.8rem;
      color: var(--dark-color);
      cursor: pointer;
      line-height: 1;
      transition: color 0.2s;
    }

    .close:hover {
      color: #F44336;
    }

    .input_box {
      position: relative;
      margin-bottom: 25px;
    }

    .input-field,
    select.input-field {
      width: 100%;
      padding: 12px 12px 12px 45px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: var(--white-color);
      font-size: 0.95rem;
      color: var(--dark-color);
      font-family: "Montserrat", sans-serif;
      transition: border-color 0.2s;
    }

    .input-field:focus,
    select.input-field:focus {
      outline: none;
      border-color: var(--primary-color);
    }

    .label {
      position: absolute;
      left: 45px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 0.9rem;
      color: #999;
      transition: 0.3s ease;
      pointer-events: none;
      background-color: transparent;
    }

    .input-field:focus + .label,
    .input-field:not(:placeholder-shown) + .label,
    select.input-field:focus + .label,
    select.input-field:valid + .label {
      top: -10px;
      left: 12px;
      background-color: var(--white-color);
      padding: 0 5px;
      font-size: 0.75rem;
      color: var(--primary-color);
    }

    .icon {
      position: absolute;
      top: 50%;
      left: 15px;
      transform: translateY(-50%);
      font-size: 1.2rem;
      color: #999;
    }

    .input-submit {
      width: 100%;
      padding: 12px;
      background-color: var(--dark-color);
      color: var(--white-color);
      font-size: 1rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .input-submit:hover {
      background-color: #1a1a1a;
    }

    /* TOAST NOTIFICATIONS */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      padding: 16px 24px;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 600;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 300px;
      animation: slideInRight 0.4s ease-out, fadeOutRight 0.4s ease-out 3.6s forwards;
    }

    .toast i {
      font-size: 1.4rem;
    }

    .toast-success {
      background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
      color: var(--white-color);
      border-left: 4px solid #2e7d32;
    }

    .toast-error {
      background: linear-gradient(135deg, #F44336 0%, #e53935 100%);
      color: var(--white-color);
      border-left: 4px solid #c62828;
    }

    @keyframes slideInRight {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes fadeOutRight {
      from {
        opacity: 1;
        transform: translateX(0);
      }
      to {
        opacity: 0;
        transform: translateX(100px);
      }
    }

    /* MOBILE */
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
    }

    .mobile-menu-btn i {
      font-size: 24px;
      color: var(--dark-color);
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
      transition: opacity 0.3s;
    }

    .sidebar-overlay.active {
      display: block;
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

      main {
        margin-left: 0;
        width: 100%;
        padding: 80px 20px 40px;
      }

      .header h1 {
        font-size: 1.5rem;
      }

      .button-group {
        flex-direction: column;
      }

      .back-btn,
      .add-btn {
        width: 100%;
        justify-content: center;
      }

      .table-section {
        padding: 20px;
        overflow-x: auto;
      }

      table {
        min-width: 650px;
        font-size: 0.85rem;
      }

      th,
      td {
        padding: 10px 8px;
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

      .service-info {
        padding: 20px;
      }

      .table-section {
        padding: 15px;
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
    <img src="../../homepage/images/pawsig.png" alt="Logo">
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>

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

    <div class="dropdown">
      <a href="javascript:void(0)" class="dropdown-toggle active" onclick="toggleDropdown(event)">
        <span><i class='bx bx-spa'></i> Services</span>
        <i class='bx bx-chevron-down'></i>
      </a>
      <div class="dropdown-menu" style="display: block;">
        <a href="services.php"><i class='bx bx-list-ul'></i> All Services</a>
        <a href="#" class="active"><i class='bx bx-dollar'></i> Manage Pricing</a>
      </div>
    </div>

    <hr>
    <a href="../session_notes/notes.php"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../Notification/email_notifications.php"><i class='bx bx-mail-send'></i>Email Notifications</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<!-- Main Content -->
<main>
  <!-- Header -->
  <div class="header">
    <h1>Manage Pricing</h1>
    <p>Configure pricing tiers for <?= htmlspecialchars($service['name']) ?></p>
  </div>
  
  <!-- Service Info -->
  <div class="service-info">
    <h3><?= htmlspecialchars($service['name']) ?></h3>
    <p><?= htmlspecialchars($service['description']) ?></p>
  </div>

  <!-- Action Buttons -->
  <div class="button-group">
    <a href="services.php" class="back-btn">
      <i class='bx bx-arrow-back'></i> Back to Services
    </a>
    <button class="add-btn" onclick="openModal()">
      <i class='bx bx-plus'></i> Add Price Tier
    </button>
  </div>
  
  <!-- Pricing Table -->
  <?php if ($prices && pg_num_rows($prices) > 0): ?>
  <div class="table-section">
    <h2>Price Tiers</h2>
    <div style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>Species</th>
            <th>Size</th>
            <th>Weight Range</th>
            <th>Price</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          pg_result_seek($prices, 0);
          while ($price = pg_fetch_assoc($prices)): 
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($price['species']) ?></strong></td>
            <td><?= htmlspecialchars($price['size'] ?: 'N/A') ?></td>
            <td>
              <?php if ($price['min_weight'] && $price['max_weight']): ?>
                <?= htmlspecialchars($price['min_weight']) ?> - <?= htmlspecialchars($price['max_weight']) ?> kg
              <?php elseif ($price['min_weight']): ?>
                <?= htmlspecialchars($price['min_weight']) ?> kg+
              <?php else: ?>
                <span style="color: #999;">No weight range</span>
              <?php endif; ?>
            </td>
            <td><strong style="color: #4CAF50;">₱<?= number_format($price['price'], 2) ?></strong></td>
            <td>
              <div class="actions">
                <a href="?id=<?= urlencode($package_id) ?>&edit=<?= $price['price_id'] ?>" class="edit-btn">
                  <i class='bx bx-edit'></i> Edit
                </a>
                <a href="?id=<?= urlencode($package_id) ?>&delete_price=<?= $price['price_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to <?= $has_price_deleted_at ? 'archive' : 'delete' ?> this price tier?')">
                  <i class='bx bx-<?= $has_price_deleted_at ? 'archive' : 'trash' ?>'></i> <?= $has_price_deleted_at ? 'Archive' : 'Delete' ?>
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
  <div class="table-section">
    <div class="empty-state">
      <i class='bx bx-dollar-circle'></i>
      <h3>No Pricing Set</h3>
      <p>Click "Add Price Tier" to create pricing for this service.</p>
    </div>
  </div>
  <?php endif; ?>
</main>

<!-- Add Price Modal -->
<div id="priceModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAddModal()">&times;</span>
    <h2>Add Price Tier</h2>

    <form method="POST">
      <input type="hidden" name="add_price" value="1">

      <div class="input_box">
        <i class='bx bx-category icon'></i>
        <select class="input-field" name="species" required>
          <option value="">Select Species</option>
          <option value="Dog">Dog</option>
          <option value="Cat">Cat</option>
        </select>
        <label class="label">Species</label>
      </div>

      <div class="input_box">
        <i class='bx bx-ruler icon'></i>
        <input type="text" class="input-field" name="size" placeholder=" " />
        <label class="label">Size (Optional)</label>
      </div>

      <div class="input_box">
        <i class='bx bx-trending-down icon'></i>
        <input type="number" step="0.01" name="min_weight" class="input-field" placeholder=" ">
        <label class="label">Min Weight (Optional)</label>
      </div>

      <div class="input_box">
        <i class='bx bx-trending-up icon'></i>
        <input type="number" step="0.01" name="max_weight" class="input-field" placeholder=" ">
        <label class="label">Max Weight (Optional)</label>
      </div>

      <div class="input_box">
        <i class='bx bx-money icon'></i>
        <input type="number" step="0.01" class="input-field" name="price" placeholder=" " required />
        <label class="label">Price (₱)</label>
      </div>
      
      <div class="input_box">
        <input type="submit" class="input-submit" value="Add Price Tier" />
      </div>
    </form>
  </div>
</div>

<!-- Edit Price Modal -->
<?php if (isset($edit_price)): ?>
<div id="editModal" class="modal" style="display:flex;">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Price Tier</h2>
    <form method="POST">
      <input type="hidden" name="update_price" value="1">
      <input type="hidden" name="price_id" value="<?= $edit_price['price_id'] ?>">
      
      <div class="input_box">
        <i class='bx bx-category icon'></i>
        <select class="input-field" name="species" required>
          <option value="Dog" <?= $edit_price['species'] == 'Dog' ? 'selected' : '' ?>>Dog</option>
          <option value="Cat" <?= $edit_price['species'] == 'Cat' ? 'selected' : '' ?>>Cat</option>
        </select>
        <label class="label">Species</label>
      </div>

      <div class="input_box">
        <i class='bx bx-ruler icon'></i>
        <input type="text" name="size" class="input-field" placeholder=" " value="<?= htmlspecialchars($edit_price['size'] ?? '') ?>">
        <label class="label">Size (Optional)</label>
      </div>

      <div class="input_box">
        <i class='bx bx-trending-down icon'></i>
        <input type="number" step="0.01" name="min_weight" class="input-field" placeholder=" " value="<?= htmlspecialchars($edit_price['min_weight'] ?? '') ?>">
        <label class="label">Min Weight (Optional)</label>
      </div>

      <div class="input_box">
        <i class='bx bx-trending-up icon'></i>
        <input type="number" step="0.01" name="max_weight" class="input-field" placeholder=" " value="<?= htmlspecialchars($edit_price['max_weight'] ?? '') ?>">
        <label class="label">Max Weight (Optional)</label>
      </div>

      <div class="input_box">
        <i class='bx bx-money icon'></i>
        <input type="number" step="0.01" name="price" class="input-field" placeholder=" " value="<?= htmlspecialchars($edit_price['price'] ?? '') ?>" required>
        <label class="label">Price (₱)</label>
      </div>

      <div class="input_box">
        <input type="submit" class="input-submit" value="Update Price Tier">
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Toast Notifications -->
<?php if (isset($_SESSION['success'])): ?>
  <div class="toast toast-success">
    <i class='bx bx-check-circle'></i>
    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
  </div>
<?php elseif (isset($_SESSION['error'])): ?>
  <div class="toast toast-error">
    <i class='bx bx-error-circle'></i>
    <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
  </div>
<?php endif; ?>

<script>
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation();
  const dropdown = event.currentTarget.nextElementSibling;
  const isVisible = dropdown.style.display === 'block';
  dropdown.style.display = isVisible ? 'none' : 'block';
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  sidebar.classList.toggle('active');
  overlay.classList.toggle('active');
}

function openModal() {
  document.getElementById('priceModal').style.display = 'flex';
}

function closeAddModal() {
  document.getElementById('priceModal').style.display = 'none';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  window.history.replaceState(null, null, '?id=<?= urlencode($package_id) ?>');
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
  const addModal = document.getElementById('priceModal');
  const editModal = document.getElementById('editModal');
  
  if (event.target === addModal) closeAddModal();
  if (event.target === editModal) closeEditModal();
});

// Close sidebar on mobile when clicking menu links
document.addEventListener('DOMContentLoaded', function() {
  const menuLinks = document.querySelectorAll('.menu a:not(.dropdown-toggle)');
  menuLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        toggleSidebar();
      }
    });
  });

  // Auto-hide toast notifications
  const toasts = document.querySelectorAll('.toast');
  toasts.forEach(toast => {
    setTimeout(() => {
      toast.style.display = 'none';
    }, 4000);
  });
});
</script>

</body>
</html>