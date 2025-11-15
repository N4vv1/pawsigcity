<?php
session_start();
include '../db.php';

$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.status,
        a.groomer_name,
        p.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed,
        u.first_name,
        u.last_name,
        CONCAT(u.first_name, ' ', u.last_name) AS customer_name
    FROM appointments a
    LEFT JOIN packages p ON a.package_id::text = p.package_id
    LEFT JOIN pets pet ON a.pet_id = pet.pet_id
    LEFT JOIN users u ON LPAD(a.user_id::text, 5, '0') = u.user_id
    ORDER BY a.appointment_date DESC
";

$result = pg_query($conn, $query);

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

$groomers_query = "SELECT DISTINCT groomer_name FROM groomer ORDER BY groomer_name";
$groomers_result = pg_query($conn, $groomers_query);

$packages_query = "SELECT package_id, name FROM packages ORDER BY name";
$packages_result = pg_query($conn, $packages_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receptionist Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../pawsigcity/icons/pawsig.png">
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #3ABB87;
      --light-pink-color: #faf4f5;
      --medium-gray-color: #ccc;
      --disabled-color: #e0e0e0;
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

    .filter-section {
      background: var(--white-color);
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 20px;
    }

    .filter-controls {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: center;
    }

    .search-box {
      flex: 1;
      min-width: 250px;
      position: relative;
    }

    .search-box i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 20px;
      color: #999;
    }

    .search-box input {
      width: 100%;
      padding: 12px 12px 12px 45px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      font-family: 'Montserrat', sans-serif;
      transition: all 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    .filter-group {
      min-width: 160px;
    }

    .filter-group select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      font-family: 'Montserrat', sans-serif;
      background-color: white;
      cursor: pointer;
      transition: all 0.3s;
    }

    .filter-group select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    .clear-btn {
      padding: 12px 20px;
      background: #6c757d;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      font-family: 'Montserrat', sans-serif;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }

    .clear-btn:hover {
      background: #5a6268;
      transform: translateY(-1px);
    }

    .table-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      overflow-x: auto;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .table-section h2 {
      font-size: 1.3rem;
      color: var(--dark-color);
      font-weight: 600;
      margin: 0;
    }

    .table-info {
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }

    .results-count {
      color: #666;
      font-size: 0.9rem;
    }

    .items-per-page {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 0.9rem;
      font-family: 'Montserrat', sans-serif;
      background-color: white;
      cursor: pointer;
    }

    table {
      width: 100%;
      min-width: 1200px;
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

    .status-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-badge.confirmed {
      background: rgba(76, 175, 80, 0.1);
      color: #4CAF50;
    }

    .status-badge.completed {
      background: rgba(33, 150, 243, 0.1);
      color: #2196F3;
    }

    .status-badge.cancelled {
      background: rgba(244, 67, 54, 0.1);
      color: #F44336;
    }

    .status-badge.no_show {
      background: rgba(255, 193, 7, 0.1);
      color: #FFC107;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .action-buttons button {
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
    }

    .action-buttons button:disabled {
      background-color: var(--disabled-color) !important;
      color: #999 !important;
      cursor: not-allowed;
      opacity: 0.6;
    }

    .edit-btn {
      background: rgba(76, 175, 80, 0.1);
      color: var(--edit-color);
    }

    .edit-btn:hover:not(:disabled) {
      background: var(--edit-color);
      color: var(--white-color);
    }

    .cancel-btn-table {
      background: rgba(244, 67, 54, 0.1);
      color: var(--delete-color);
    }

    .cancel-btn-table:hover:not(:disabled) {
      background: var(--delete-color);
      color: var(--white-color);
    }

    #noResults {
      display: none;
    }

    #noResults td {
      text-align: center;
      padding: 40px;
      color: #999;
    }

    #noResults i {
      font-size: 3rem;
      display: block;
      margin-bottom: 10px;
      color: #ddd;
    }

    #paginationControls {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-top: 25px;
      flex-wrap: wrap;
    }

    .pagination-btn {
      padding: 8px 12px;
      border: 1px solid #ddd;
      background: white;
      border-radius: 6px;
      cursor: pointer;
      font-family: 'Montserrat', sans-serif;
      font-weight: 600;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .pagination-btn:hover:not(:disabled) {
      background: var(--primary-color);
      border-color: var(--primary-color);
      transform: translateY(-1px);
    }

    .pagination-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .page-number {
      padding: 8px 12px;
      border: 1px solid #ddd;
      background: white;
      border-radius: 6px;
      cursor: pointer;
      font-family: 'Montserrat', sans-serif;
      font-weight: 600;
      transition: all 0.2s;
      min-width: 40px;
      text-align: center;
    }

    .page-number:hover {
      background: var(--primary-color);
      border-color: var(--primary-color);
      transform: translateY(-1px);
    }

    .page-number.active {
      background: var(--dark-color);
      color: white;
      border-color: var(--dark-color);
    }

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

    .modal-content form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .modal-content label {
      display: block;
      margin-bottom: 8px;
      color: var(--dark-color);
      font-weight: 500;
      font-size: 0.9rem;
    }

    .modal-content input,
    .modal-content select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: var(--light-pink-color);
      font-size: 1rem;
      color: var(--dark-color);
      transition: all 0.2s;
    }

    .modal-content input:focus,
    .modal-content select:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 10px;
      flex-wrap: wrap;
    }

    .modal-content button {
      padding: 14px 20px;
      border: none;
      border-radius: var(--border-radius-s);
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.9rem;
    }

    .modal-content button[type="submit"] {
      background-color: var(--dark-color);
      color: var(--white-color);
    }

    .modal-content button[type="submit"]:hover {
      background-color: #1a1a1a;
      transform: translateY(-1px);
    }

    .modal-content .cancel-btn {
      background-color: #FF6B6B;
      color: var(--white-color);
    }

    .modal-content .cancel-btn:hover {
      background-color: #FF4B4B;
    }

    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.85rem;
        min-width: 1100px;
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

      .filter-section {
        padding: 20px;
      }

      .filter-controls {
        flex-direction: column;
      }

      .search-box,
      .filter-group {
        width: 100%;
      }

      .clear-btn {
        width: 100%;
        justify-content: center;
      }

      .table-section {
        padding: 20px;
      }

      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .table-info {
        width: 100%;
        justify-content: space-between;
      }

      table {
        font-size: 0.8rem;
        min-width: 1000px;
      }

      th, td {
        padding: 10px 8px;
      }

      .action-buttons {
        flex-direction: column;
        gap: 5px;
      }

      .action-buttons button {
        width: 100%;
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

      .filter-section {
        padding: 15px;
      }

      table {
        font-size: 0.75rem;
        min-width: 900px;
      }

      th, td {
        padding: 8px 5px;
      }

      .modal-content {
        padding: 20px 15px;
      }

      .modal-content h2 {
        font-size: 1.2rem;
      }

      .modal-buttons {
        flex-direction: column;
      }

      .modal-buttons button {
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
  <nav class="menu">
    <a href="#" class="active"><i class='bx bx-home'></i>All Appointments</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>All Appointments</h1>
    <p>Manage and track all grooming appointments</p>
  </div>

  <div class="filter-section">
    <div class="filter-controls">
      <div class="search-box">
        <i class='bx bx-search'></i>
        <input type="text" id="searchInput" placeholder="Search by ID, customer, pet, breed..." />
      </div>
      <div class="filter-group">
        <select id="statusFilter">
          <option value="all">All Status</option>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="no_show">No Show</option>
        </select>
      </div>
      <div class="filter-group">
        <select id="packageFilter">
          <option value="all">All Packages</option>
          <?php
          pg_result_seek($packages_result, 0);
          while ($pkg = pg_fetch_assoc($packages_result)):
          ?>
          <option value="<?= strtolower(htmlspecialchars($pkg['name'])) ?>"><?= htmlspecialchars($pkg['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="filter-group">
        <select id="groomerFilter">
          <option value="all">All Groomers</option>
          <?php
          pg_result_seek($groomers_result, 0);
          while ($g = pg_fetch_assoc($groomers_result)):
          ?>
          <option value="<?= strtolower(htmlspecialchars($g['groomer_name'])) ?>"><?= htmlspecialchars($g['groomer_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="filter-group">
        <select id="dateRangeFilter">
          <option value="all">All Dates</option>
          <option value="today">Today</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="upcoming">Upcoming</option>
          <option value="past">Past</option>
        </select>
      </div>
      <button class="clear-btn" onclick="clearFilters()">
        <i class='bx bx-x-circle'></i> Clear
      </button>
    </div>
  </div>

  <div class="table-section">
    <div class="table-header">
      <h2>Appointment List</h2>
      <div class="table-info">
        <div class="results-count" id="resultsCount"></div>
        <select class="items-per-page" id="itemsPerPage">
          <option value="10">10 per page</option>
          <option value="25">25 per page</option>
          <option value="50">50 per page</option>
          <option value="100">100 per page</option>
          <option value="all">Show all</option>
        </select>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Appointment ID</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Package</th>
          <th>Pet Name</th>
          <th>Breed</th>
          <th>Status</th>
          <th>Groomer</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $counter = 1; ?>
        <?php while ($row = pg_fetch_assoc($result)): ?>
          <?php
            $status = strtolower($row['status']);
            $is_disabled = ($status === 'completed' || $status === 'cancelled');
          ?>
          <tr class="appointment-row"
              data-id="<?= strtolower(htmlspecialchars($row['appointment_id'] ?? '')) ?>"
              data-date="<?= htmlspecialchars($row['appointment_date'] ?? '') ?>"
              data-customer="<?= strtolower(htmlspecialchars($row['customer_name'] ?? '')) ?>"
              data-package="<?= strtolower(htmlspecialchars($row['package_name'] ?? '')) ?>"
              data-pet="<?= strtolower(htmlspecialchars($row['pet_name'] ?? '')) ?>"
              data-breed="<?= strtolower(htmlspecialchars($row['pet_breed'] ?? '')) ?>"
              data-status="<?= $status ?>"
              data-groomer="<?= strtolower(htmlspecialchars($row['groomer_name'])) ?>"
              data-counter="<?= $counter ?>">
            <td class="row-counter"><?= $counter++ ?></td>
            <td><?= htmlspecialchars($row['appointment_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['appointment_date'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['package_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['pet_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['pet_breed'] ?? 'N/A') ?></td>
            <td>
              <span class="status-badge <?= $status ?>">
                <?= ucfirst(str_replace('_', ' ', $status)) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($row['groomer_name']) ?></td>
            <td>
              <div class="action-buttons">
                <button class="edit-btn"
                        data-id="<?= $row['appointment_id'] ?>"
                        data-date="<?= $row['appointment_date'] ?>"
                        data-package="<?= $row['package_id'] ?>"
                        data-status="<?= $row['status'] ?>"
                        data-groomer="<?= htmlspecialchars($row['groomer_name']) ?>"
                        <?= $is_disabled ? 'disabled title="Cannot edit completed/cancelled appointments"' : '' ?>>
                  <i class='bx bx-edit'></i> Edit
                </button>
                <button class="cancel-btn-table"
                        onclick="if(confirm('Cancel this appointment? The customer will be notified via email.')) { window.location.href='cancel_appointment.php?id=<?= $row['appointment_id'] ?>'; }"
                        <?= $is_disabled ? 'disabled title="Already completed/cancelled"' : '' ?>>
                  <i class='bx bx-trash'></i> Cancel
                </button>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        <tr id="noResults">
          <td colspan="10">
            <i class='bx bx-search-alt'></i>
            <strong>No appointments found</strong>
            <p style="margin-top: 5px; font-size: 0.9rem;">Try adjusting your search or filters</p>
          </td>
        </tr>
      </tbody>
    </table>

    <div id="paginationControls">
      <button onclick="changePage('first')" id="firstBtn" class="pagination-btn">
        <i class='bx bx-chevrons-left'></i>
      </button>
      <button onclick="changePage('prev')" id="prevBtn" class="pagination-btn">
        <i class='bx bx-chevron-left'></i> Prev
      </button>
      
      <div id="pageNumbers" style="display: flex; gap: 5px; flex-wrap: wrap;"></div>
      
      <button onclick="changePage('next')" id="nextBtn" class="pagination-btn">
        Next <i class='bx bx-chevron-right'></i>
      </button>
      <button onclick="changePage('last')" id="lastBtn" class="pagination-btn">
        <i class='bx bx-chevrons-right'></i>
      </button>
    </div>
  </div>
</main>

<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Edit Appointment</h2>
    <form id="editForm" method="POST" action="edit_appointment.php">
      <input type="hidden" name="appointment_id" id="modalAppointmentId">

      <div>
        <label>Date:</label>
        <input type="datetime-local" name="appointment_date" id="modalDate" required>
      </div>

      <div>
        <label>Package:</label>
        <select name="package_id" id="modalPackage" required>
          <?php
          pg_result_seek($packages_result, 0);
          while ($pkg = pg_fetch_assoc($packages_result)):
          ?>
          <option value="<?= $pkg['package_id'] ?>"><?= htmlspecialchars($pkg['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Groomer:</label>
        <select name="groomer_name" id="modalGroomer" required>
          <?php
          pg_result_seek($groomers_result, 0);
          while ($g = pg_fetch_assoc($groomers_result)):
          ?>
          <option value="<?= htmlspecialchars($g['groomer_name']) ?>"><?= htmlspecialchars($g['groomer_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Status:</label>
        <select name="status" id="modalStatus" required>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="no_show">No Show</option>
        </select>
      </div>

      <div class="modal-buttons">
        <button type="submit">Update Appointment</button>
        <button type="button" class="cancel-btn" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
let currentPage = 1;
let itemsPerPage = 10;
let filteredRows = [];

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

  const modal = document.getElementById("editModal");
  const closeBtn = modal.querySelector(".close");
  const editButtons = document.querySelectorAll(".edit-btn");

  editButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      if (btn.disabled) return;
      
      document.getElementById("modalAppointmentId").value = btn.dataset.id;
      
      let dateValue = btn.dataset.date;
      if (dateValue) {
        dateValue = dateValue.substring(0, 16).replace(' ', 'T');
      }
      document.getElementById("modalDate").value = dateValue;
      
      document.getElementById("modalPackage").value = btn.dataset.package;
      document.getElementById("modalStatus").value = btn.dataset.status;
      document.getElementById("modalGroomer").value = btn.dataset.groomer;

      modal.style.display = "flex";
    });
  });

  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (event) => {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });

  initPagination();
});

function filterAppointments() {
  const searchValue = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const packageFilter = document.getElementById('packageFilter').value;
  const groomerFilter = document.getElementById('groomerFilter').value;
  const dateRangeFilter = document.getElementById('dateRangeFilter').value;
  const rows = document.querySelectorAll('.appointment-row');
  filteredRows = [];
  
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  
  rows.forEach(row => {
    const id = row.getAttribute('data-id');
    const appointmentDateStr = row.getAttribute('data-date');
    const customer = row.getAttribute('data-customer');
    const packageName = row.getAttribute('data-package');
    const petName = row.getAttribute('data-pet');
    const breed = row.getAttribute('data-breed');
    const status = row.getAttribute('data-status');
    const groomer = row.getAttribute('data-groomer');
    
    const matchesSearch = searchValue === '' || 
                         id.includes(searchValue) || 
                         customer.includes(searchValue) ||
                         packageName.includes(searchValue) ||
                         petName.includes(searchValue) ||
                         breed.includes(searchValue) ||
                         groomer.includes(searchValue);
    
    const matchesStatus = statusFilter === 'all' || status === statusFilter;
    const matchesPackage = packageFilter === 'all' || packageName === packageFilter;
    const matchesGroomer = groomerFilter === 'all' || groomer === groomerFilter;
    
    let matchesDateRange = true;
    if (appointmentDateStr && dateRangeFilter !== 'all') {
      const appointmentDate = new Date(appointmentDateStr);
      
      if (dateRangeFilter === 'today') {
        const apptDate = new Date(appointmentDate.getFullYear(), appointmentDate.getMonth(), appointmentDate.getDate());
        matchesDateRange = apptDate.getTime() === today.getTime();
      } else if (dateRangeFilter === 'week') {
        const weekFromNow = new Date(today);
        weekFromNow.setDate(weekFromNow.getDate() + 7);
        matchesDateRange = appointmentDate >= today && appointmentDate <= weekFromNow;
      } else if (dateRangeFilter === 'month') {
        const monthFromNow = new Date(today);
        monthFromNow.setMonth(monthFromNow.getMonth() + 1);
        matchesDateRange = appointmentDate >= today && appointmentDate <= monthFromNow;
      } else if (dateRangeFilter === 'upcoming') {
        matchesDateRange = appointmentDate >= now;
      } else if (dateRangeFilter === 'past') {
        matchesDateRange = appointmentDate < now;
      }
    }
    
    if (matchesSearch && matchesStatus && matchesPackage && matchesGroomer && matchesDateRange) {
      filteredRows.push(row);
    }
  });
  
  currentPage = 1;
  displayPage();
}

function displayPage() {
  const rows = document.querySelectorAll('.appointment-row');
  const noResults = document.getElementById('noResults');
  const itemsPerPageSelect = document.getElementById('itemsPerPage').value;
  
  itemsPerPage = itemsPerPageSelect === 'all' ? filteredRows.length : parseInt(itemsPerPageSelect);
  
  rows.forEach(row => {
    row.style.display = 'none';
  });
  
  const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  
  const pageRows = filteredRows.slice(startIndex, endIndex);
  pageRows.forEach((row, index) => {
    row.style.display = '';
    const counterCell = row.querySelector('.row-counter');
    if (counterCell) {
      counterCell.textContent = startIndex + index + 1;
    }
  });
  
  if (filteredRows.length === 0) {
    noResults.style.display = '';
  } else {
    noResults.style.display = 'none';
  }
  
  updatePaginationControls(totalPages);
  updateResultsCount(filteredRows.length, document.querySelectorAll('.appointment-row').length, startIndex, endIndex);
}

function updatePaginationControls(totalPages) {
  const paginationControls = document.getElementById('paginationControls');
  const pageNumbers = document.getElementById('pageNumbers');
  const firstBtn = document.getElementById('firstBtn');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const lastBtn = document.getElementById('lastBtn');
  const itemsPerPageSelect = document.getElementById('itemsPerPage').value;
  
  if (itemsPerPageSelect === 'all' || totalPages <= 1) {
    paginationControls.style.display = 'none';
    return;
  }
  
  paginationControls.style.display = 'flex';
  
  firstBtn.disabled = currentPage === 1;
  prevBtn.disabled = currentPage === 1;
  nextBtn.disabled = currentPage === totalPages;
  lastBtn.disabled = currentPage === totalPages;
  
  pageNumbers.innerHTML = '';
  
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, startPage + 4);
  
  if (endPage - startPage < 4) {
    startPage = Math.max(1, endPage - 4);
  }
  
  if (startPage > 1) {
    const firstPage = createPageButton(1);
    pageNumbers.appendChild(firstPage);
    
    if (startPage > 2) {
      const ellipsis = document.createElement('span');
      ellipsis.textContent = '...';
      ellipsis.style.padding = '8px 4px';
      ellipsis.style.color = '#999';
      pageNumbers.appendChild(ellipsis);
    }
  }
  
  for (let i = startPage; i <= endPage; i++) {
    const pageBtn = createPageButton(i);
    pageNumbers.appendChild(pageBtn);
  }
  
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      const ellipsis = document.createElement('span');
      ellipsis.textContent = '...';
      ellipsis.style.padding = '8px 4px';
      ellipsis.style.color = '#999';
      pageNumbers.appendChild(ellipsis);
    }
    
    const lastPage = createPageButton(totalPages);
    pageNumbers.appendChild(lastPage);
  }
}

function createPageButton(pageNum) {
  const btn = document.createElement('button');
  btn.textContent = pageNum;
  btn.className = 'page-number' + (pageNum === currentPage ? ' active' : '');
  btn.onclick = () => goToPage(pageNum);
  return btn;
}

function goToPage(pageNum) {
  currentPage = pageNum;
  displayPage();
  document.querySelector('.table-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function changePage(direction) {
  const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
  
  switch(direction) {
    case 'first':
      currentPage = 1;
      break;
    case 'prev':
      if (currentPage > 1) currentPage--;
      break;
    case 'next':
      if (currentPage < totalPages) currentPage++;
      break;
    case 'last':
      currentPage = totalPages;
      break;
  }
  
  displayPage();
  document.querySelector('.table-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function initPagination() {
  const rows = document.querySelectorAll('.appointment-row');
  filteredRows = Array.from(rows);
  displayPage();
}

function updateResultsCount(visible = null, total = null, startIndex = 0, endIndex = 0) {
  const resultsCount = document.getElementById('resultsCount');
  const rows = document.querySelectorAll('.appointment-row');
  
  if (visible === null) {
    visible = rows.length;
    total = rows.length;
  }
  
  const itemsPerPageSelect = document.getElementById('itemsPerPage').value;
  
  if (itemsPerPageSelect === 'all' || visible <= itemsPerPage) {
    if (visible === total) {
      resultsCount.textContent = `Showing all ${total} appointment${total !== 1 ? 's' : ''}`;
    } else {
      resultsCount.textContent = `Showing ${visible} of ${total} appointment${total !== 1 ? 's' : ''}`;
    }
  } else {
    const showing = Math.min(endIndex, visible);
    resultsCount.textContent = `Showing ${startIndex + 1}-${showing} of ${visible} appointment${visible !== 1 ? 's' : ''}`;
  }
}

function clearFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = 'all';
  document.getElementById('packageFilter').value = 'all';
  document.getElementById('groomerFilter').value = 'all';
  document.getElementById('dateRangeFilter').value = 'all';
  filterAppointments();
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const packageFilter = document.getElementById('packageFilter');
  const groomerFilter = document.getElementById('groomerFilter');
  const dateRangeFilter = document.getElementById('dateRangeFilter');
  const itemsPerPageSelect = document.getElementById('itemsPerPage');
  
  if (searchInput) {
    searchInput.addEventListener('keyup', filterAppointments);
  }
  
  if (statusFilter) {
    statusFilter.addEventListener('change', filterAppointments);
  }
  
  if (packageFilter) {
    packageFilter.addEventListener('change', filterAppointments);
  }
  
  if (groomerFilter) {
    groomerFilter.addEventListener('change', filterAppointments);
  }
  
  if (dateRangeFilter) {
    dateRangeFilter.addEventListener('change', filterAppointments);
  }

  if (itemsPerPageSelect) {
    itemsPerPageSelect.addEventListener('change', function() {
      currentPage = 1;
      displayPage();
    });
  }
});
</script>

<?php if (isset($_SESSION['success_message'])): ?>
  <script>
    showToast('<?= addslashes($_SESSION['success_message']); ?>', 'success');
  </script>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <script>
    showToast('<?= addslashes($_SESSION['error_message']); ?>', 'error');
  </script>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

</body>
</html>