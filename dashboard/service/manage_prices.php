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

$package_id = intval($_GET['id']);

// Get service details
$service_query = "SELECT * FROM packages WHERE package_id = $1";
$service_result = pg_query_params($conn, $service_query, [$package_id]);

if (pg_num_rows($service_result) == 0) {
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
        $_SESSION['success'] = "Price added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add price.";
    }
    header("Location: ?id=" . $package_id);
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
        $_SESSION['success'] = "Price updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update price.";
    }
    header("Location: ?id=" . $package_id);
    exit;
}

// Handle delete price
if (isset($_GET['delete_price'])) {
    $price_id = intval($_GET['delete_price']);
    $delete_query = "DELETE FROM package_prices WHERE price_id = $1";
    $result = pg_query_params($conn, $delete_query, [$price_id]);

    if ($result) {
        $_SESSION['success'] = "Price deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete price.";
    }
    header("Location: ?id=" . $package_id);
    exit;
}

// Get all prices for this service
$prices_query = "SELECT * FROM package_prices WHERE package_id = $1 ORDER BY species, size, price";
$prices = pg_query_params($conn, $prices_query, [$package_id]);

// If editing specific price
$edit_price = null;
if (isset($_GET['edit'])) {
   $edit_id = intval($_GET['edit']);
  $get_price_query = "SELECT * FROM package_prices WHERE price_id = $1";
  $result = pg_query_params($conn, $get_price_query, [$edit_id]);
  $edit_price = pg_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | Manage Pricing - <?= htmlspecialchars($service['name']) ?></title>
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
      margin-bottom: 10px;
    }

 .service-info {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
  padding: 25px;
  border-radius: 12px;
  margin-bottom: 25px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.service-info h3 {
  color: var(--dark-color);
  margin-bottom: 10px;
  font-size: 1.3rem;
  font-weight: 700;
}

.service-info p {
  color: var(--dark-color);
  line-height: 1.6;
  opacity: 0.9;
}

   .back-btn {
  background: #6c757d;
  padding: 10px 20px;
  border-radius: var(--border-radius-s);
  text-decoration: none;
  color: var(--white-color);
  font-weight: var(--font-weight-semi-bold);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
  margin-right: 10px;
  transition: all 0.2s;
}

.back-btn:hover {
  background: #5a6268;
  transform: translateY(-1px);
}

.back-btn i {
  font-size: 18px;
}

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

  .table-wrapper {
  background: var(--white-color);
  padding: 35px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  margin-bottom: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  background-color: transparent;
  box-shadow: none;
  min-width: 700px;
}

th, td {
  padding: 15px 12px;
  text-align: left;
  border: none;
  border-bottom: 1px solid #f0f0f0;
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

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .empty-state i {
      font-size: 4rem;
      color: var(--primary-color);
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .empty-state h3 {
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .empty-state p {
      color: #666;
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
      max-width: 600px;
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

    .input_box {
      position: relative;
      margin-bottom: 1.5rem;
    }

    .input-field, select.input-field {
      width: 100%;
      padding: 0.9rem 2.5rem;
      border: 1px solid var(--medium-gray-color);
      border-radius: var(--border-radius-s);
      background-color: var(--light-pink-color);
      font-size: var(--font-size-n);
      color: var(--dark-color);
      font-family: "Montserrat", sans-serif;
    }

    .input-field:focus, select.input-field:focus {
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
      background-color: transparent;
    }

    .input-field:focus + .label,
    .input-field:valid + .label,
    select.input-field:focus + .label,
    select.input-field:valid + .label {
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
      
      h2 {
        font-size: 1.6rem;
      }
      
       .add-btn, .back-btn {
    padding: 12px 20px;
    font-size: 0.95rem;
  }
  
  .table-wrapper {
    padding: 20px;
  }
  
  table {
    font-size: 0.85rem;
  }

  th, td {
    padding: 10px 8px;
  }
  
  .actions {
    flex-direction: column;
    gap: 5px;
  }
  
  .actions a {
    width: 100%;
    justify-content: center;
  }
}
 .header h1 {
    font-size: 1.5rem;
  }
  
  .service-info {
    padding: 20px;
  }
  
  .service-info h3 {
    font-size: 1.1rem;
  }

    @media screen and (max-width: 480px) {
       .header h1 {
    font-size: 1.3rem;
  }
   .header p {
    font-size: 0.85rem;
  }
      .content {
        padding: 70px 15px 30px;
      }
      
      h2 {
        font-size: 1.4rem;
      }

       .service-info {
    padding: 15px;
  }
  
  .service-info h3 {
    font-size: 1rem;
  }

  .add-btn, .back-btn {
    width: 100%;
    padding: 12px;
    text-align: center;
    justify-content: center;
    margin-bottom: 10px;
  }
  
  table {
    min-width: 650px;
    font-size: 0.75rem;
  }

  th, td {
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
        <div class="dropdown-menu">
          <a href="services.php"><i class='bx bx-list-ul'></i> All Services</a>
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
<!-- Main Content -->
<main class="content">
  <!-- Header -->
  <div class="header">
    <h1>Manage Pricing</h1>
    <p>Configure pricing tiers for <?= htmlspecialchars($service['name']) ?></p>
  </div>
  
  <div class="service-info">
    <h3><?= htmlspecialchars($service['name']) ?></h3>
    <p><?= htmlspecialchars($service['description']) ?></p>
  </div>

  <a href="services.php" class="back-btn">
    <i class='bx bx-arrow-back'></i> Back to Services
  </a>
  <button class="add-btn" onclick="openModal()">
    <i class='bx bx-plus'></i> Add Price Tier
  </button>
  
  <?php if (pg_num_rows($prices) > 0): ?>
  <div class="table-wrapper">
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
          <?php while ($price = pg_fetch_assoc($prices)): ?>
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
                <a href="?id=<?= $package_id ?>&edit=<?= $price['price_id'] ?>" class="edit-btn">
                  <i class='bx bx-edit'></i> Edit
                </a>
                <a href="?id=<?= $package_id ?>&delete_price=<?= $price['price_id'] ?>" class="delete-btn" onclick="return confirm('Delete this price?')">
                  <i class='bx bx-trash'></i> Delete
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
  <div class="empty-state">
    <i class='bx bx-dollar-circle'></i>
    <h3>No Pricing Set</h3>
    <p>Click "Add Price Tier" to create pricing for this service.</p>
  </div>
  <?php endif; ?>
  <!-- Add Price Modal -->
  <div id="priceModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeAddModal()">&times;</span>
      <h2>Add Price Tier</h2>

      <form method="POST">
        <input type="hidden" name="add_price" value="1">

        <div class="input_box">
          <select class="input-field" name="species" required>
            <option value="">Select Species</option>
            <option value="Dog">Dog</option>
            <option value="Cat">Cat</option>
          </select>
          <label class="label">Species</label>
          <i class='bx bx-category icon'></i>
        </div>

        <div class="input_box">
          <input type="text" class="input-field" name="size" placeholder="e.g., Small, Medium, Large" />
          <label class="label">Size (Optional)</label>
          <i class='bx bx-ruler icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="min_weight" class="input-field" value="<?= htmlspecialchars($edit_price['min_weight'] ?? '') ?>">
          <label class="label">Min Weight (Optional)</label>
          <i class='bx bx-trending-down icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="max_weight" class="input-field" value="<?= htmlspecialchars($edit_price['max_weight'] ?? '') ?>">
          <label class="label">Max Weight (Optional)</label>
          <i class='bx bx-trending-up icon'></i>
        </div>

        <div class="input_box">
          <input type="number" step="0.01" class="input-field" name="price" required />
          <label class="label">Price (₱)</label>
          <i class='bx bx-money icon'></i>
        </div>
        
        <div class="input_box">
          <input type="submit" class="input-submit" value="Add Price" />
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
        <input type="hidden" name="price_id" value="<?= $edit_price['price_id'] ?>">
        
        <div class="input_box">
          <select class="input-field" name="species" required>
            <option value="Dog" <?= $edit_price['species'] == 'Dog' ? 'selected' : '' ?>>Dog</option>
            <option value="Cat" <?= $edit_price['species'] == 'Cat' ? 'selected' : '' ?>>Cat</option>
          </select>
          <label class="label">Species</label>
          <i class='bx bx-category icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="size" class="input-field" value="<?= htmlspecialchars($edit_price['size'] ?? '') ?>">
          <label class="label">Size (Optional)</label>
          <i class='bx bx-ruler icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="min_weight" class="input-field" value="<?= htmlspecialchars($edit_price['min_weight']) ?>">
          <label class="label">Min Weight (Optional)</label>
          <i class='bx bx-trending-down icon'></i>
        </div>

        <div class="input_box">
          <input type="text" name="max_weight" class="input-field" value="<?= htmlspecialchars($edit_price['max_weight']) ?>">
          <label class="label">Max Weight (Optional)</label>
          <i class='bx bx-trending-up icon'></i>
        </div>

        <div class="input_box">
          <input type="number" step="0.01" name="price" class="input-field" value="<?= $edit_price['price'] ?>" required>
          <label class="label">Price (₱)</label>
          <i class='bx bx-money icon'></i>
        </div>

        <div class="input_box">
          <input type="submit" name="update_price" class="input-submit" value="Update Price">
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
</main>

<script>
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation();
  const dropdown = event.currentTarget.nextElementSibling;
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

function openModal() {
  document.getElementById('priceModal').style.display = 'flex';
}

function closeAddModal() {
  document.getElementById('priceModal').style.display = 'none';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  window.history.replaceState(null, null, '?id=<?= $package_id ?>');
}

document.addEventListener('click', function(event) {
  const addModal = document.getElementById('priceModal');
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
});
</script>

<?php if (isset($_SESSION['success'])): ?>
  <div class="toast toast-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
  <div class="toast toast-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

</body>
</html>