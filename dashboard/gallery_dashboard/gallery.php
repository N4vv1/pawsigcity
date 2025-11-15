<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Pagination settings
$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $records_per_page;

// Date filter
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_count = 1;

if ($date_filter === 'today') {
    $where_conditions[] = "DATE(uploaded_at) = CURRENT_DATE";
} elseif ($date_filter === 'week') {
    $where_conditions[] = "uploaded_at >= CURRENT_DATE - INTERVAL '7 days'";
} elseif ($date_filter === 'month') {
    $where_conditions[] = "uploaded_at >= CURRENT_DATE - INTERVAL '30 days'";
} elseif ($date_filter === 'custom' && $start_date && $end_date) {
    $where_conditions[] = "DATE(uploaded_at) BETWEEN $" . $param_count++ . " AND $" . $param_count++;
    $params[] = $start_date;
    $params[] = $end_date;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total records
$count_query = "SELECT COUNT(*) as total FROM gallery $where_clause";
$count_result = empty($params) ? pg_query($conn, $count_query) : pg_query_params($conn, $count_query, $params);
$total_records = pg_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch gallery images with pagination
$query = "SELECT * FROM gallery $where_clause ORDER BY id ASC LIMIT $records_per_page OFFSET $offset";
$result = empty($params) ? pg_query($conn, $query) : pg_query_params($conn, $query, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | Pet Gallery</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig2.png">

  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #016B61;
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

    /* FILTER SECTION */
    .filter-section {
      background: var(--white-color);
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 25px;
    }

    .filter-section h3 {
      font-size: 1rem;
      color: var(--dark-color);
      margin-bottom: 15px;
      font-weight: 600;
    }

    .filter-controls {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: flex-end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 150px;
    }

    .filter-group label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--dark-color);
    }

    .filter-group select,
    .filter-group input[type="date"] {
      padding: 10px 12px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      font-size: 0.9rem;
      background: var(--white-color);
      color: var(--dark-color);
      transition: border-color 0.2s;
    }

    .filter-group select:focus,
    .filter-group input[type="date"]:focus {
      outline: none;
      border-color: var(--primary-color);
    }

    .custom-date-range {
      display: none;
      gap: 15px;
    }

    .custom-date-range.active {
      display: flex;
    }

    .filter-buttons {
      display: flex;
      gap: 10px;
      align-items: flex-end;
    }

    .filter-btn, .clear-btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .filter-btn {
      background: var(--dark-color);
      color: var(--white-color);
    }

    .filter-btn:hover {
      background: #1a1a1a;
    }

    .clear-btn {
      background: rgba(244, 67, 54, 0.1);
      color: #F44336;
    }

    .clear-btn:hover {
      background: #F44336;
      color: var(--white-color);
    }

    /* ADD BUTTON */
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
      margin-bottom: 25px;
    }

    .add-btn:hover {
      background: #1a1a1a;
      transform: translateY(-1px);
    }

    .add-btn i {
      font-size: 18px;
    }

    /* TABLE SECTION */
    .table-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .results-info {
      margin-bottom: 20px;
      color: #666;
      font-size: 0.9rem;
    }

    .results-info strong {
      color: var(--dark-color);
    }

    .table-wrapper {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
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

    .image-preview {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.3s;
      background: #f0f0f0;
    }

    .image-preview:hover {
      transform: scale(1.05);
    }

    .image-error {
      width: 80px;
      height: 80px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #ffebee;
      border-radius: 8px;
      color: #c62828;
      font-size: 0.75rem;
      text-align: center;
      padding: 5px;
    }

    .actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .actions button,
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
      border: none;
      cursor: pointer;
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

    /* PAGINATION */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-top: 30px;
      flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
      padding: 10px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .pagination a {
      background: var(--white-color);
      color: var(--dark-color);
      border: 1px solid #e0e0e0;
    }

    .pagination a:hover {
      background: var(--primary-color);
      border-color: var(--primary-color);
    }

    .pagination span.current {
      background: var(--dark-color);
      color: var(--white-color);
    }

    .pagination a.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
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

    .file-input-wrapper {
      position: relative;
      width: 100%;
      margin-bottom: 25px;
    }

    .file-input-wrapper input[type="file"] {
      display: none;
    }

    .file-input-label {
      display: block;
      width: 100%;
      padding: 20px;
      background-color: var(--light-pink-color);
      border: 2px dashed var(--medium-gray-color);
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }

    .file-input-label:hover {
      border-color: var(--primary-color);
      background-color: var(--white-color);
    }

    .file-input-label i {
      font-size: 2rem;
      display: block;
      margin-bottom: 10px;
      color: var(--dark-color);
    }

    .file-input-label strong {
      display: block;
      color: var(--dark-color);
      margin-bottom: 5px;
    }

    .file-input-label p {
      font-size: 0.85rem;
      color: #666;
      margin-top: 5px;
    }

    .file-preview {
      margin-top: 15px;
      text-align: center;
    }

    .file-preview img {
      max-width: 100%;
      max-height: 300px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
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

    /* IMAGE VIEW MODAL */
    .image-modal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.9);
      justify-content: center;
      align-items: center;
    }

    .image-modal-content {
      max-width: 90%;
      max-height: 90vh;
      position: relative;
    }

    .image-modal-content img {
      width: 100%;
      height: auto;
      border-radius: 8px;
    }

    .image-modal .close {
      position: absolute;
      top: -40px;
      right: 0;
      font-size: 2rem;
      color: var(--white-color);
      cursor: pointer;
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

      .filter-controls {
        flex-direction: column;
      }

      .filter-group {
        width: 100%;
      }

      .filter-buttons {
        width: 100%;
        flex-direction: column;
      }

      .filter-btn, .clear-btn {
        width: 100%;
        justify-content: center;
      }

      .custom-date-range {
        flex-direction: column;
      }

      .add-btn {
        width: 100%;
        justify-content: center;
      }

      .table-section {
        padding: 20px;
      }

      table {
        min-width: 600px;
        font-size: 0.85rem;
      }

      th,
      td {
        padding: 10px 8px;
      }

      .image-preview {
        width: 60px;
        height: 60px;
      }

      .actions {
        flex-direction: column;
        gap: 6px;
      }

      .actions button,
      .actions a {
        width: 100%;
        justify-content: center;
      }

      .pagination {
        gap: 5px;
      }

      .pagination a,
      .pagination span {
        padding: 8px 12px;
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
      main {
        padding: 70px 15px 30px;
      }

      .header h1 {
        font-size: 1.3rem;
      }

      .table-section {
        padding: 15px;
      }

      table {
        min-width: 500px;
      }

      .modal-content {
        padding: 25px;
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
    <img src="../../homepage/images/pawsig2.png" alt="Logo" />
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
    <a href="../gallery_dashboard/gallery.php" class="active"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/sentiment_dashboard.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<!-- Main Content -->
<main>
  <!-- Header -->
  <div class="header">
    <h1>Pet Gallery</h1>
  </div>
  
  <!-- Filter Section -->
  <div class="filter-section">
    <h3><i class='bx bx-filter'></i></h3>
    <form method="GET" action="" id="filterForm">
      <div class="filter-controls">
        <div class="filter-group">
          <label for="date_filter">Date Range</label>
          <select name="date_filter" id="date_filter" onchange="toggleCustomDateRange()">
            <option value="">All Time</option>
            <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
            <option value="custom" <?= $date_filter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
          </select>
        </div>

        <div class="custom-date-range <?= $date_filter === 'custom' ? 'active' : '' ?>" id="customDateRange">
          <div class="filter-group">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
          </div>
          <div class="filter-group">
            <label for="end_date">End Date</label>
            <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
          </div>
        </div>

        <div class="filter-buttons">
          <button type="submit" class="filter-btn">
            <i class='bx bx-search'></i>
          </button>
          <a href="gallery.php" class="clear-btn">
            <i class='bx bx-x'></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Add Button -->
  <button class="add-btn" onclick="openAddModal()">
    <i class='bx bx-plus'></i> Add New Image
  </button>

  <!-- Gallery Table -->
  <div class="table-section">
    <?php if ($total_records > 0): ?>
      <div class="results-info">
        Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($offset + $records_per_page, $total_records) ?></strong> of <strong><?= $total_records ?></strong> images
      </div>
    <?php endif; ?>

    <div class="table-wrapper">
      <?php if (pg_num_rows($result) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Image ID</th>
            <th>Preview</th>
            <th>Uploaded Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($image = pg_fetch_assoc($result)): 
            $image_url = htmlspecialchars($image['image_path']);
          ?>
          <tr>
            <td><?= htmlspecialchars($image['id']) ?></td>
            <td>
              <img src="<?= $image_url ?>" 
                   alt="Pet Gallery Image"
                   class="image-preview"
                   onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'image-error\'>Image not found</div>';"
                   onclick="viewImage('<?= $image_url ?>')">
            </td>
            <td><?= date('F j, Y', strtotime($image['uploaded_at'])) ?></td>
            <td>
              <div class="actions">
                <button class="edit-btn" 
                        onclick="openEditModal(<?= htmlspecialchars($image['id']) ?>, '<?= htmlspecialchars($image['image_path'], ENT_QUOTES) ?>', '<?= $image_url ?>')">
                  <i class='bx bx-edit'></i> Edit
                </button>
                <form method="POST" action="delete_image.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?')">
                  <input type="hidden" name="image_id" value="<?= htmlspecialchars($image['id']) ?>">
                  <input type="hidden" name="image_path" value="<?= htmlspecialchars($image['image_path']) ?>">
                  <button type="submit" class="delete-btn">
                    <i class='bx bx-trash'></i> Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($total_pages > 1): 
        $query_params = [];
        if ($date_filter) $query_params['date_filter'] = $date_filter;
        if ($start_date) $query_params['start_date'] = $start_date;
        if ($end_date) $query_params['end_date'] = $end_date;
        
        function buildUrl($page, $params) {
          $params['page'] = $page;
          return '?' . http_build_query($params);
        }
      ?>
      <div class="pagination">
        <!-- Previous Button -->
        <?php if ($current_page > 1): ?>
          <a href="<?= buildUrl($current_page - 1, $query_params) ?>">
            <i class='bx bx-chevron-left'></i> Previous
          </a>
        <?php else: ?>
          <a href="#" class="disabled">
            <i class='bx bx-chevron-left'></i> Previous
          </a>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1): ?>
          <a href="<?= buildUrl(1, $query_params) ?>">1</a>
          <?php if ($start_page > 2): ?>
            <span>...</span>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
          <?php if ($i == $current_page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= buildUrl($i, $query_params) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end_page < $total_pages): ?>
          <?php if ($end_page < $total_pages - 1): ?>
            <span>...</span>
          <?php endif; ?>
          <a href="<?= buildUrl($total_pages, $query_params) ?>"><?= $total_pages ?></a>
        <?php endif; ?>

        <!-- Next Button -->
        <?php if ($current_page < $total_pages): ?>
          <a href="<?= buildUrl($current_page + 1, $query_params) ?>">
            Next <i class='bx bx-chevron-right'></i>
          </a>
        <?php else: ?>
          <a href="#" class="disabled">
            Next <i class='bx bx-chevron-right'></i>
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="empty-state">
        <i class='bx bx-image'></i>
        <h3>No images found</h3>
        <p><?= !empty($where_conditions) ? 'Try adjusting your filters or add new images' : 'Click "Add New Image" to upload your first image' ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Add Image Modal -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAddModal()">&times;</span>
    <h2>Add New Image</h2>
    <form method="POST" action="add_image.php" enctype="multipart/form-data">
      <div class="file-input-wrapper">
        <input type="file" id="imageFile" name="image" accept="image/*" required onchange="previewImage(this, 'addPreview')">
        <label for="imageFile" class="file-input-label">
          <i class='bx bx-upload'></i>
          <strong>Choose Image File</strong>
          <p>JPG, PNG, GIF, WEBP (Max 5MB)</p>
        </label>
        <div id="addPreview" class="file-preview"></div>
      </div>

      <div>
        <input type="submit" class="input-submit" value="Upload Image" />
      </div>
    </form>
  </div>
</div>

<!-- Edit Image Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Replace Image</h2>
    <form method="POST" action="edit_image.php" enctype="multipart/form-data">
      <input type="hidden" id="edit_image_id" name="image_id">
      <input type="hidden" id="edit_current_path" name="current_image_path">

      <div class="file-preview" id="editCurrentImage" style="margin-bottom: 20px;">
        <p style="margin-bottom: 10px; font-weight: 600; color: var(--dark-color);">Current Image:</p>
      </div>

      <div class="file-input-wrapper">
        <input type="file" id="editImageFile" name="image" accept="image/*" required onchange="previewImage(this, 'editPreview')">
        <label for="editImageFile" class="file-input-label">
          <i class='bx bx-upload'></i>
          <strong>Choose New Image</strong>
          <p>JPG, PNG, GIF, WEBP (Max 5MB)</p>
        </label>
        <div id="editPreview" class="file-preview"></div>
      </div>

      <div>
        <input type="submit" class="input-submit" value="Replace Image" />
      </div>
    </form>
  </div>
</div>

<!-- Image View Modal -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
  <div class="image-modal-content">
    <span class="close">&times;</span>
    <img id="modalImage" src="" alt="">
  </div>
</div>

<!-- Toast Notifications -->
<?php if (isset($_SESSION['success'])): ?>
  <div class="toast toast-success">
    <i class='bx bx-check-circle'></i>
    <span><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
  </div>
<?php elseif (isset($_SESSION['error'])): ?>
  <div class="toast toast-error">
    <i class='bx bx-error-circle'></i>
    <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
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

function toggleCustomDateRange() {
  const select = document.getElementById('date_filter');
  const customRange = document.getElementById('customDateRange');
  
  if (select.value === 'custom') {
    customRange.classList.add('active');
  } else {
    customRange.classList.remove('active');
  }
}

function openAddModal() {
  document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
  document.getElementById('addModal').style.display = 'none';
  document.getElementById('addPreview').innerHTML = '';
  document.getElementById('imageFile').value = '';
}

function openEditModal(id, dbImagePath, displayUrl) {
  document.getElementById('edit_image_id').value = id;
  document.getElementById('edit_current_path').value = dbImagePath;
  
  document.getElementById('editCurrentImage').innerHTML = 
    '<p style="margin-bottom: 10px; font-weight: 600; color: var(--dark-color);">Current Image:</p>' +
    '<img src="' + displayUrl + '" style="max-width: 100%; max-height: 200px; border-radius: 8px;" onerror="this.parentElement.innerHTML=\'<p>Current image unavailable</p>\';">';
  
  document.getElementById('editPreview').innerHTML = '';
  document.getElementById('editImageFile').value = '';
  
  document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  document.getElementById('editPreview').innerHTML = '';
  document.getElementById('editImageFile').value = '';
}

function viewImage(imageUrl) {
  document.getElementById('modalImage').src = imageUrl;
  document.getElementById('imageModal').style.display = 'flex';
}

function closeImageModal() {
  document.getElementById('imageModal').style.display = 'none';
}

function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  preview.innerHTML = '';
  
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
    }
    reader.readAsDataURL(input.files[0]);
  }
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
  const addModal = document.getElementById('addModal');
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