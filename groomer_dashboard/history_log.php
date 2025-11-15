<?php
session_start();
include '../db.php';

// Check if groomer is logged in
if (!isset($_SESSION['groomer_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$groomer_id = $_SESSION['groomer_id'];

// FIXED: Fetch ONLY completed appointments for THIS groomer with type casting
$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        p.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed,
        (u.first_name || ' ' || u.last_name) AS customer_name,
        u.first_name,
        u.last_name,
        COALESCE(TO_CHAR(a.updated_at, 'YYYY-MM-DD HH24:MI:SS'), 'Not yet completed') AS completed_date
    FROM appointments a
    JOIN packages p ON a.package_id::text = p.package_id
    JOIN pets pet ON a.pet_id = pet.pet_id
    JOIN users u ON pet.user_id::text = u.user_id
    WHERE a.status = 'completed'
    AND a.groomer_id = $1
    ORDER BY a.updated_at DESC
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
  <title>Groomer | History Logs</title>
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

    .stats-card {
      background: linear-gradient(135deg, var(--primary-color) 0%, #8fd4b8 100%);
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      text-align: center;
      color: var(--dark-color);
    }

    .stats-card h3 {
      font-size: 3rem;
      margin-bottom: 8px;
      font-weight: 700;
    }

    .stats-card p {
      font-size: 1.1rem;
      font-weight: 500;
      opacity: 0.9;
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
      min-width: 180px;
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
      min-width: 1000px;
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

    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.85rem;
        min-width: 900px;
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

      .stats-card {
        padding: 25px;
      }

      .stats-card h3 {
        font-size: 2.5rem;
      }

      .stats-card p {
        font-size: 1rem;
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
        min-width: 800px;
      }

      th, td {
        padding: 10px 8px;
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

      .stats-card {
        padding: 20px;
      }

      .stats-card h3 {
        font-size: 2rem;
      }

      .stats-card p {
        font-size: 0.9rem;
      }

      .filter-section {
        padding: 15px;
      }

      table {
        font-size: 0.75rem;
        min-width: 700px;
      }

      th, td {
        padding: 8px 5px;
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
    <a href="home_groomer.php"><i class='bx bx-calendar-check'></i>Appointments</a>
    <hr>
    <a href="history_log.php" class="active"><i class='bx bx-history'></i>History Logs</a>
    <hr>
    <a href="notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="https://pawsigcity.onrender.com/homepage/login/loginform.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>Completed Appointments History</h1>
    <p>View your completed grooming sessions and track your performance</p>
  </div>

  <?php if (pg_num_rows($result) == 0): ?>
    <div class="table-section">
      <div class="empty-state">
        <i class='bx bx-history'></i>
        <h3>No Completed Appointments Yet</h3>
        <p>Completed appointments will appear here</p>
      </div>
    </div>
  <?php else: ?>
    <div class="filter-section">
      <div class="filter-controls">
        <div class="search-box">
          <i class='bx bx-search'></i>
          <input type="text" id="searchInput" placeholder="Search by ID, pet name, breed, customer..." />
        </div>
        <div class="filter-group">
          <select id="dateRangeFilter">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
          </select>
        </div>
        <div class="filter-group">
          <select id="packageFilter">
            <option value="all">All Packages</option>
            <?php
            pg_result_seek($result, 0);
            $packages = [];
            while ($row = pg_fetch_assoc($result)) {
              if (!in_array($row['package_name'], $packages)) {
                $packages[] = $row['package_name'];
              }
            }
            foreach ($packages as $package) {
              echo '<option value="' . htmlspecialchars(strtolower($package)) . '">' . htmlspecialchars($package) . '</option>';
            }
            pg_result_seek($result, 0);
            ?>
          </select>
        </div>
        <button class="clear-btn" onclick="clearFilters()">
          <i class='bx bx-x-circle'></i> Clear
        </button>
      </div>
    </div>

    <div class="table-section">
      <div class="table-header">
        <h2>Appointment History</h2>
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
            <th>Appointment ID</th>
            <th>Appointment Date</th>
            <th>Completed Date</th>
            <th>Package</th>
            <th>Pet Name</th>
            <th>Breed</th>
            <th>Customer</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr class="history-row"
                data-id="<?= strtolower(htmlspecialchars($row['appointment_id'])) ?>"
                data-appointment-date="<?= htmlspecialchars($row['appointment_date']) ?>"
                data-completed-date="<?= htmlspecialchars($row['completed_date']) ?>"
                data-package="<?= strtolower(htmlspecialchars($row['package_name'])) ?>"
                data-pet="<?= strtolower(htmlspecialchars($row['pet_name'])) ?>"
                data-breed="<?= strtolower(htmlspecialchars($row['pet_breed'])) ?>"
                data-customer="<?= strtolower(htmlspecialchars($row['customer_name'])) ?>">
              <td><?= htmlspecialchars($row['appointment_id']) ?></td>
              <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['appointment_date']))) ?></td>
              <td>
                <?php 
                if ($row['completed_date'] !== 'Not yet completed') {
                  echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['completed_date'])));
                } else {
                  echo htmlspecialchars($row['completed_date']);
                }
                ?>
              </td>
              <td><?= htmlspecialchars($row['package_name']) ?></td>
              <td><?= htmlspecialchars($row['pet_name']) ?></td>
              <td><?= htmlspecialchars($row['pet_breed']) ?></td>
              <td><?= htmlspecialchars($row['customer_name']) ?></td>
            </tr>
          <?php endwhile; ?>
          <tr id="noResults">
            <td colspan="7">
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
  <?php endif; ?>
</main>

<script>
let currentPage = 1;
let itemsPerPage = 10;
let filteredRows = [];

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

  initPagination();
});

function filterHistory() {
  const searchValue = document.getElementById('searchInput').value.toLowerCase();
  const dateRangeFilter = document.getElementById('dateRangeFilter').value;
  const packageFilter = document.getElementById('packageFilter').value;
  const rows = document.querySelectorAll('.history-row');
  filteredRows = [];
  
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  
  rows.forEach(row => {
    const id = row.getAttribute('data-id');
    const completedDateStr = row.getAttribute('data-completed-date');
    const packageName = row.getAttribute('data-package');
    const petName = row.getAttribute('data-pet');
    const breed = row.getAttribute('data-breed');
    const customer = row.getAttribute('data-customer');
    
    const matchesSearch = searchValue === '' || 
                         id.includes(searchValue) || 
                         packageName.includes(searchValue) ||
                         petName.includes(searchValue) ||
                         breed.includes(searchValue) ||
                         customer.includes(searchValue);
    
    let matchesDateRange = true;
    if (completedDateStr !== 'Not yet completed' && dateRangeFilter !== 'all') {
      const completedDate = new Date(completedDateStr);
      
      if (dateRangeFilter === 'today') {
        const compDate = new Date(completedDate.getFullYear(), completedDate.getMonth(), completedDate.getDate());
        matchesDateRange = compDate.getTime() === today.getTime();
      } else if (dateRangeFilter === 'week') {
        const weekAgo = new Date(today);
        weekAgo.setDate(weekAgo.getDate() - 7);
        matchesDateRange = completedDate >= weekAgo && completedDate <= now;
      } else if (dateRangeFilter === 'month') {
        const monthAgo = new Date(today);
        monthAgo.setMonth(monthAgo.getMonth() - 1);
        matchesDateRange = completedDate >= monthAgo && completedDate <= now;
      } else if (dateRangeFilter === 'year') {
        const yearAgo = new Date(today);
        yearAgo.setFullYear(yearAgo.getFullYear() - 1);
        matchesDateRange = completedDate >= yearAgo && completedDate <= now;
      }
    }
    
    const matchesPackage = packageFilter === 'all' || packageName === packageFilter;
    
    if (matchesSearch && matchesDateRange && matchesPackage) {
      filteredRows.push(row);
    }
  });
  
  currentPage = 1;
  displayPage();
}

function displayPage() {
  const rows = document.querySelectorAll('.history-row');
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
  pageRows.forEach(row => {
    row.style.display = '';
  });
  
  if (filteredRows.length === 0) {
    noResults.style.display = '';
  } else {
    noResults.style.display = 'none';
  }
  
  updatePaginationControls(totalPages);
  updateResultsCount(filteredRows.length, document.querySelectorAll('.history-row').length, startIndex, endIndex);
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
  const rows = document.querySelectorAll('.history-row');
  filteredRows = Array.from(rows);
  displayPage();
}

function updateResultsCount(visible = null, total = null, startIndex = 0, endIndex = 0) {
  const resultsCount = document.getElementById('resultsCount');
  const rows = document.querySelectorAll('.history-row');
  
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
  document.getElementById('dateRangeFilter').value = 'all';
  document.getElementById('packageFilter').value = 'all';
  filterHistory();
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchInput');
  const dateRangeFilter = document.getElementById('dateRangeFilter');
  const packageFilter = document.getElementById('packageFilter');
  const itemsPerPageSelect = document.getElementById('itemsPerPage');
  
  if (searchInput) {
    searchInput.addEventListener('keyup', filterHistory);
  }
  
  if (dateRangeFilter) {
    dateRangeFilter.addEventListener('change', filterHistory);
  }
  
  if (packageFilter) {
    packageFilter.addEventListener('change', filterHistory);
  }

  if (itemsPerPageSelect) {
    itemsPerPageSelect.addEventListener('change', function() {
      currentPage = 1;
      displayPage();
    });
  }
});
</script>

</body>
</html>