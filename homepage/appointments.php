<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!$conn) {
    die("Database connection failed: " . pg_last_error());
}

error_log("User ID: " . $user_id);

$items_per_page = 5;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = ["a.user_id = $1"];
$params = [$user_id];
$param_count = 1;

if (!empty($search)) {
    $param_count++;
    $where_conditions[] = "(p.name ILIKE $" . $param_count . " OR pk.name ILIKE $" . $param_count . ")";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    if ($status_filter === 'approved') {
        $where_conditions[] = "a.is_approved = true AND a.status != 'completed' AND a.status != 'cancelled'";
    } elseif ($status_filter === 'pending') {
        $where_conditions[] = "a.is_approved = false AND a.status != 'cancelled'";
    } else {
        $param_count++;
        $where_conditions[] = "a.status = $" . $param_count;
        $params[] = $status_filter;
    }
}

$where_clause = implode(' AND ', $where_conditions);

$count_query = "
    SELECT COUNT(*) as total
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN packages pk ON a.package_id = pk.package_id
    WHERE $where_clause
";
$count_result = pg_query_params($conn, $count_query, $params);
$total_records = pg_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $items_per_page);

$query = "
    SELECT a.*, 
           p.name AS pet_name, 
           pk.name AS package_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN packages pk ON a.package_id = pk.package_id
    WHERE $where_clause
    ORDER BY a.appointment_date ASC
    LIMIT $items_per_page OFFSET $offset
";

$appointments = pg_query_params($conn, $query, $params);

if (!$appointments) {
    die("Query failed: " . pg_last_error($conn));
}

$row_count = pg_num_rows($appointments);
error_log("Number of appointments found: " . $row_count);

$total = $row_count;
$approved = 0;
$pending = 0;
$completed = 0;
$cancelled = 0;

if ($row_count > 0) {
    pg_result_seek($appointments, 0);
    while ($row = pg_fetch_assoc($appointments)) {
        if ($row['status'] === 'cancelled') $cancelled++;
        elseif ($row['status'] === 'completed') $completed++;
        elseif ($row['is_approved']) $approved++;
        else $pending++;
    }
    pg_result_seek($appointments, 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PAWsig City | Appointments</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="./images/pawsig2.png">
  
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #016B61;
      --light-pink-color: #faf4f5;
      --approved-color: #4CAF50;
      --pending-color: #FF9800;
      --cancelled-color: #F44336;
      --completed-color: #2196F3;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Montserrat", sans-serif;
    }

    body {
      background: var(--light-pink-color);
      min-height: 100vh;
    }

    /* KEEP ORIGINAL NAVBAR STYLES */
    .section-content {
      max-width: 1200px;
      margin: auto;
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 30px;
      position: relative;
      z-index: 100;
    }

    .nav-menu {
      display: flex;
      align-items: center;
      list-style: none;
      margin: 0;
      padding: 0;
      gap: 5px;
    }

    .nav-item {
      position: relative;
      list-style: none;
    }

    .nav-link {
      text-decoration: none;
      padding: 8px 16px;
      display: flex;
      align-items: center;
      color: #2c3e50;
      font-weight: 500;
      transition: all 0.3s ease;
      border-radius: 8px;
    }

    .nav-link:hover {
      background-color: rgba(168, 230, 207, 0.1);
      color: #16a085;
    }

    .nav-link.active {
      background-color: rgba(168, 230, 207, 0.15);
      color: #16a085;
    }

    .hamburger {
      display: none;
    }
@media (min-width: 1025px) {
  /* Dropdown container */
  .dropdown {
    position: relative;
  }

  /* Dropdown menu - hidden by default */
  .dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 220px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
    transition-delay: 0s, 0s, 0s;
    margin-top: 8px;
    padding: 8px 0;
    z-index: 1000;
    list-style: none;
    pointer-events: none;
  }

  /* Show dropdown on hover - appears immediately */
  .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto;
    transition-delay: 0s, 0s, 0s;
  }

  /* Keep dropdown visible when hovering over menu items */
  .dropdown-menu:hover {
    opacity: 1;
    visibility: visible;
  }
  
  /* Add delay when mouse leaves dropdown - waits 300ms before hiding */
  .dropdown:not(:hover) .dropdown-menu {
    transition-delay: 0.3s, 0.3s, 0.3s;
  }

  /* Dropdown menu items */
  .dropdown-menu li {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .dropdown-menu a {
    display: block;
    padding: 12px 20px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    white-space: nowrap;
    text-align: left;
  }

  .dropdown-menu a:hover {
    background: linear-gradient(90deg, rgba(168, 230, 207, 0.1) 0%, transparent 100%);
    border-left-color: #A8E6CF;
    padding-left: 24px;
    color: #16a085;
  }

  /* Profile icon styling */
  .profile-icon {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    position: relative;
  }

  /* Arrow indicator */
  .profile-icon::after {
    content: '\f078';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 0.7rem;
    margin-left: 4px;
    transition: transform 0.3s ease;
  }

  .dropdown:hover .profile-icon::after {
    transform: rotate(180deg);
  }
}

    @media (max-width: 1024px) {
      .hamburger {
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        cursor: pointer;
        background: none;
        border: none;
        padding: 8px;
        z-index: 1001;
        width: 40px;
        height: 40px;
        position: relative;
      }

      .hamburger span {
        width: 28px;
        height: 3px;
        background-color: #2c3e50;
        transition: all 0.3s ease;
        border-radius: 3px;
        display: block;
        position: relative;
      }

      .hamburger.active span:nth-child(1) {
        transform: rotate(45deg);
        position: absolute;
        top: 50%;
        margin-top: -1.5px;
      }

      .hamburger.active span:nth-child(2) {
        opacity: 0;
        transform: scale(0);
      }

      .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg);
        position: absolute;
        top: 50%;
        margin-top: -1.5px;
      }

      .nav-menu {
        position: fixed;
        right: -100%;
        top: 0;
        flex-direction: column;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        width: 320px;
        text-align: left;
        transition: right 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
        padding: 100px 0 30px 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 999;
        align-items: stretch;
        gap: 0;
      }

      .nav-menu.active {
        right: 0;
      }

      .nav-item {
        margin: 0;
        padding: 0;
        border-bottom: 1px solid #e9ecef;
        position: relative;
      }

      .nav-link {
        font-size: 1.1rem;
        padding: 18px 30px;
        display: block;
        color: #2c3e50;
        transition: all 0.3s ease;
        font-weight: 500;
        width: 100%;
      }

      .nav-link:hover {
        background: linear-gradient(90deg, #A8E6CF 0%, transparent 100%);
        padding-left: 40px;
        color: #16a085;
      }

      .nav-link i {
        margin-right: 12px;
        font-size: 1.2rem;
        color: #A8E6CF;
      }

      .profile-icon {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
      }

      .profile-icon::after {
        content: 'Profile Menu';
        font-family: 'Segoe UI', sans-serif;
        font-size: 1rem;
      }

      .dropdown-menu {
        position: relative;
        display: none;
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        box-shadow: none;
        background-color: #f1f3f5;
        margin: 0;
        border-radius: 0;
        padding: 8px 0;
        list-style: none;
      }

      .dropdown.active .dropdown-menu {
        display: block;
      }

      .dropdown-menu li {
        margin: 0;
        border-bottom: none;
        list-style: none;
      }

      .dropdown-menu a {
        padding: 14px 30px 14px 50px;
        font-size: 0.95rem;
        color: #495057;
        display: block;
        transition: all 0.3s ease;
        position: relative;
      }

      .dropdown-menu a::before {
        content: '•';
        position: absolute;
        left: 35px;
        color: #A8E6CF;
        font-size: 1.2rem;
      }

      .dropdown-menu a:hover {
        background-color: #e9ecef;
        padding-left: 55px;
        color: #16a085;
      }

      .nav-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 998;
        opacity: 0;
        transition: opacity 0.3s ease;
      }

      .nav-overlay.active {
        display: block;
        opacity: 1;
      }
    }

    @media (min-width: 1025px) {
      .hamburger {
        display: none;
      }
      
      .nav-overlay {
        display: none;
      }
    }

    /* MAIN CONTENT - NEW DESIGN */
    main {
      padding: 120px 40px 60px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .header {
      margin-bottom: 40px;
    }

    .header h1 {
      font-size: 2rem;
      color: var(--dark-color);
      margin-bottom: 10px;
      font-weight: 600;
    }

    .header p {
      color: #666;
      font-size: 0.95rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
      margin-top: 40px;
    }

    .stat-card {
      background: var(--white-color);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      transition: transform 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-3px);
    }

    .stat-card h3 {
      font-size: 0.85rem;
      color: #999;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 500;
    }

    .stat-card .count {
      font-size: 2.5rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .stat-card.approved .count { color: var(--approved-color); }
    .stat-card.pending .count { color: var(--pending-color); }
    .stat-card.completed .count { color: var(--completed-color); }
    .stat-card.cancelled .count { color: var(--cancelled-color); }

    .search-filter-container {
      background: var(--white-color);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 30px;
    }

    .search-filter-container form {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: flex-end;
    }

    .form-group {
      flex: 1;
      min-width: 250px;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .form-group label i {
      color: var(--primary-color);
      margin-right: 6px;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      font-family: inherit;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    .form-actions {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-primary {
      background: var(--dark-color);
      color: var(--white-color);
    }

    .btn-primary:hover {
      background: #1a1a1a;
      transform: translateY(-1px);
    }

    .btn-secondary {
      background: #f0f0f0;
      color: var(--dark-color);
    }

    .btn-secondary:hover {
      background: #e0e0e0;
    }

    .results-info {
      margin-top: 16px;
      padding: 12px 16px;
      background: rgba(168, 230, 207, 0.1);
      border-left: 4px solid var(--primary-color);
      border-radius: 8px;
      color: var(--dark-color);
      font-size: 0.9rem;
    }

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
      font-size: 0.9rem;
    }

    table th {
      background-color: #fafafa;
      color: var(--dark-color);
      font-weight: 600;
      position: sticky;
      top: 0;
    }

    table tbody tr:hover {
      background-color: #fafafa;
    }

    .badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .badge.approved {
      background: rgba(76, 175, 80, 0.1);
      color: var(--approved-color);
    }

    .badge.pending {
      background: rgba(255, 152, 0, 0.1);
      color: var(--pending-color);
    }

    .badge.cancelled {
      background: rgba(244, 67, 54, 0.1);
      color: var(--cancelled-color);
    }

    .badge.completed {
      background: rgba(33, 150, 243, 0.1);
      color: var(--completed-color);
    }

    .btn-sm {
      padding: 8px 16px;
      font-size: 0.85rem;
      margin: 3px;
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
    }

    .empty-state i {
      font-size: 80px;
      color: var(--primary-color);
      margin-bottom: 25px;
      opacity: 0.7;
    }

    .empty-state h3 {
      color: var(--dark-color);
      margin-bottom: 12px;
      font-size: 1.8rem;
      font-weight: 600;
    }

    .empty-state p {
      color: #666;
      font-size: 1rem;
      margin-bottom: 30px;
    }

    .pagination {
      margin-top: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }

    .pagination-info {
      color: #666;
      font-size: 0.9rem;
    }

    .pagination-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .page-btn {
      padding: 10px 16px;
      text-decoration: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      transition: all 0.2s;
      background: #f0f0f0;
      color: var(--dark-color);
    }

    .page-btn:hover {
      background: var(--dark-color);
      color: var(--white-color);
    }

    .page-btn.active {
      background: var(--dark-color);
      color: var(--white-color);
      font-weight: 600;
    }

    .alert-message {
      position: fixed;
      top: 90px;
      right: 30px;
      z-index: 9999;
      max-width: 400px;
      padding: 16px 24px;
      border-radius: 12px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
      animation: slideInRight 0.4s ease, fadeOut 0.5s ease 4.5s forwards;
    }

    .alert-message.success {
      background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
      color: #155724;
      border-left: 5px solid #28a745;
    }

    .alert-message.error {
      background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
      color: #721c24;
      border-left: 5px solid #dc3545;
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(100px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes fadeOut {
      to {
        opacity: 0;
        transform: translateX(50px);
      }
    }

    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 2000;
    }

    .modal.show {
      display: flex;
    }

    .modal-content {
      background: var(--white-color);
      padding: 40px;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header h3 {
      color: var(--dark-color);
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .modal-header p {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 25px;
    }

    .modal-body {
      margin-bottom: 25px;
    }

    .modal-body label {
      display: block;
      font-size: 0.85rem;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .modal-body input,
    .modal-body textarea,
    .modal-body select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      font-family: inherit;
      transition: all 0.2s;
    }

    .modal-body input:focus,
    .modal-body textarea:focus,
    .modal-body select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    .modal-body textarea {
      resize: vertical;
      min-height: 100px;
    }

    .modal-footer {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    @media (max-width: 768px) {
      main {
        padding: 100px 20px 40px;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .search-filter-container form {
        flex-direction: column;
      }

      .form-group {
        min-width: 100%;
      }

      .form-actions {
        width: 100%;
      }

      .btn {
        flex: 1;
      }

      .table-section {
        padding: 20px;
        overflow-x: auto;
      }

      table {
        min-width: 900px;
      }

      .alert-message {
        right: 15px;
        left: 15px;
        max-width: none;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .header h1 {
        font-size: 1.5rem;
      }

      .modal-content {
        padding: 25px;
      }
    }
  </style>
</head>
<body>

<header>
  <nav class="navbar section-content">
    <a href="#" class="navbar-logo">
      <img src="../homepage/images/pawsig2.png" alt="Logo" class="icon" />
    </a>
    
    <button class="hamburger" id="hamburger">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="nav-overlay" id="nav-overlay"></div>

    <ul class="nav-menu" id="nav-menu">
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-info-circle"></i> About</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-concierge-bell"></i> Services</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-images"></i> Gallery</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a></li>
      <li class="nav-item dropdown" id="profile-dropdown">
        <a href="#" class="nav-link profile-icon active">
          <i class="fas fa-user"></i>
        </a>
        <ul class="dropdown-menu">
          <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
          <li><a href="../pets/add-pet.php">Add Pet</a></li>
          <li><a href="../appointment/book-appointment.php">Book</a></li>
          <li><a href="../homepage/appointments.php">Appointments</a></li>
          <li><a href="../ai/templates/index.html">Help Center</a></li>
          <li><a href="../homepage/logout/logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </nav>
</header>

<main>
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert-message success" id="successMessage">
      <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
      <span><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert-message error" id="errorMessage">
      <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
      <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
    </div>
  <?php endif; ?>

  <?php if ($row_count > 0): ?>
  <div class="stats-grid">
    <div class="stat-card">
      <h3>Total</h3>
      <div class="count" style="color: var(--dark-color);"><?= $total ?></div>
    </div>
    
    <div class="stat-card approved">
      <h3>Approved</h3>
      <div class="count"><?= $approved ?></div>
    </div>
    
    <div class="stat-card pending">
      <h3>Pending</h3>
      <div class="count"><?= $pending ?></div>
    </div>
    
    <div class="stat-card completed">
      <h3>Completed</h3>
      <div class="count"><?= $completed ?></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="search-filter-container">
    <form method="GET" action="">
      <div class="form-group">
        <label><i class="fas fa-search"></i>Search</label>
        <input 
          type="text" 
          name="search" 
          placeholder="Search by pet or service name..." 
          value="<?= htmlspecialchars($search) ?>">
      </div>

      <div class="form-group" style="flex: 0 0 200px; min-width: 180px;">
        <label><i class="fas fa-filter"></i>Status</label>
        <select name="status">
          <option value="">All Status</option>
          <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-search"></i> Filter
        </button>
        
        <?php if (!empty($search) || !empty($status_filter)): ?>
          <a href="appointments.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
          </a>
        <?php endif; ?>
      </div>
    </form>

    <?php if (!empty($search) || !empty($status_filter)): ?>
      <div class="results-info">
        <i class="fas fa-info-circle"></i>
        Showing <strong><?= $total_records ?></strong> result<?= $total_records != 1 ? 's' : '' ?>
        <?php if (!empty($search)): ?>
          for "<strong><?= htmlspecialchars($search) ?></strong>"
        <?php endif; ?>
        <?php if (!empty($status_filter)): ?>
          with status: <strong><?= ucfirst($status_filter) ?></strong>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="table-section">
    <h2>All Appointments</h2>

    <?php if ($row_count > 0): ?>
      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Pet</th>
              <th>Service</th>
              <th>Date & Time</th>
              <th>Recommended</th>
              <th>Approval</th>
              <th>Status</th>
              <th>Session Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = pg_fetch_assoc($appointments)): ?>
              <tr>
                <td><?= htmlspecialchars($row['appointment_id']) ?></td>
                <td><?= htmlspecialchars($row['pet_name']) ?></td>
                <td><?= htmlspecialchars($row['package_name']) ?></td>
                <td>
                  <?= htmlspecialchars(date("M d, Y h:i A", strtotime($row['appointment_date']))) ?>
                  
                  <?php if (!empty($row['reschedule_requested']) && $row['reschedule_approved'] === false): ?>
                    <div style="margin-top:8px; padding:8px; background:#f8d7da; border-left:3px solid #dc3545; border-radius:4px; font-size:0.85rem;">
                      <strong style="color:#721c24;">⚠️ Last Reschedule Denied</strong><br>
                      <em style="color:#721c24; font-size:0.8rem;">Time slot was already booked</em>
                    </div>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['recommended_package'] ?? 'N/A') ?></td>
                <td>
                  <?php if ($row['status'] === 'cancelled'): ?>
                    <span class="badge cancelled">Cancelled</span>
                  <?php elseif ($row['is_approved']): ?>
                    <span class="badge approved">Approved</span>
                  <?php else: ?>
                    <span class="badge pending">Waiting</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php 
                    $status = ucfirst($row['status']);
                    $badge_class = strtolower($row['status']);
                  ?>
                  <span class="badge <?= $badge_class ?>"><?= $status ?></span>
                </td>
                <td style="max-width: 250px;"><?= !empty($row['notes']) ? nl2br(htmlspecialchars($row['notes'])) : '<em style="color:#999;">No notes yet.</em>' ?></td>
                <td>
                  <?php if ($row['status'] !== 'completed' && $row['status'] !== 'cancelled'): ?>
                    
                    <?php if (($row['reschedule_count'] ?? 0) < 1): ?>
                      <button class="btn btn-primary btn-sm" onclick="openRescheduleModal(<?= $row['appointment_id'] ?>)">
                        <i class="fas fa-calendar-alt"></i> Reschedule
                      </button>
                    <?php else: ?>
                      <span style="color: #999; font-size: 0.85rem;">(Already rescheduled)</span>
                    <?php endif; ?>
                    
                    <button class="btn btn-secondary btn-sm" onclick="openCancelModal(<?= $row['appointment_id'] ?>)">
                      <i class="fas fa-times"></i> Cancel
                    </button>
                    
                  <?php endif; ?>

                  <?php if ($row['status'] === 'completed' && is_null($row['rating'])): ?>
                    <button class="btn btn-primary btn-sm" onclick="openFeedbackModal(<?= $row['appointment_id'] ?>)">
                      <i class="fas fa-star"></i> Feedback
                    </button>
                  <?php elseif ($row['status'] === 'completed' && $row['rating'] !== null): ?>
                    <div style="margin-top: 8px; padding: 10px; background: #e3f2fd; border-radius: 8px; font-size: 0.85rem; color: #0d47a1;">
                      <strong>⭐ <?= $row['rating'] ?>/5</strong><br>
                      <?= !empty($row['feedback']) ? nl2br(htmlspecialchars($row['feedback'])) : '<em style="color:#777;">No comment.</em>' ?>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <div class="pagination-info">
            Showing <strong><?= min($offset + 1, $total_records) ?></strong> to 
            <strong><?= min($offset + $items_per_page, $total_records) ?></strong> of 
            <strong><?= $total_records ?></strong> appointments
          </div>

          <div class="pagination-buttons">
            <?php if ($current_page > 1): ?>
              <a href="?page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>" 
                 class="page-btn">
                <i class="fas fa-chevron-left"></i> Previous
              </a>
            <?php endif; ?>

            <?php
            $range = 2;
            for ($i = 1; $i <= $total_pages; $i++):
              if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)):
            ?>
              <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>" 
                 class="page-btn <?= $i == $current_page ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php
              elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1):
                echo '<span style="padding: 10px 8px; color: #999;">...</span>';
              endif;
            endfor;
            ?>

            <?php if ($current_page < $total_pages): ?>
              <a href="?page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>" 
                 class="page-btn">
                Next <i class="fas fa-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3>No Appointments Found</h3>
        <p>You don't have any appointments yet. Book your first appointment!</p>
        <a href="../appointment/book-appointment.php" class="btn btn-primary" style="margin-top: 20px;">
          <i class="fas fa-plus"></i> Book Now
        </a>
      </div>
    <?php endif; ?>
  </div>
</main>

<div id="cancelModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Cancel Appointment</h3>
      <p>Please let us know why you need to cancel this appointment.</p>
    </div>

    <form action="../appointment/cancel-appointment.php" method="POST">
      <div class="modal-body">
        <input type="hidden" name="appointment_id" id="cancel_appointment_id">
        
        <label>Reason for cancellation</label>
        <textarea 
          name="cancel_reason" 
          required 
          placeholder="e.g., Schedule conflict, feeling better, need to reschedule..."
          rows="5"
        ></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeCancelModal()" class="btn btn-secondary">
          Close
        </button>
        <button type="submit" class="btn btn-primary">
          Submit
        </button>
      </div>
    </form>
  </div>
</div>

<div id="rescheduleModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Reschedule Appointment</h3>
      <p>Request a new date and time for your appointment</p>
    </div>
    
    <form action="../appointment/rescheduler-handler.php" method="POST">
      <div class="modal-body">
        <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
        
        <div style="margin-bottom: 20px;">
          <label>New Date & Time</label>
          <input type="datetime-local" name="appointment_date" id="appointment_date" required 
                 min="<?= date('Y-m-d\TH:i') ?>">
        </div>

        <div>
          <label>Reason for Rescheduling</label>
          <textarea name="reschedule_reason" id="reschedule_reason" rows="3" 
                    placeholder="Please explain why you need to reschedule..."></textarea>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" onclick="closeRescheduleModal()" class="btn btn-secondary">
          Cancel
        </button>
        <button type="submit" class="btn btn-primary">
          Request Reschedule
        </button>
      </div>
    </form>
  </div>
</div>

<div id="feedbackModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Rate Your Appointment</h3>
      <p>Please rate your experience and tell us what you liked or what we can improve!</p>
    </div>

    <form action="./feedback/rate-handler.php" method="POST" onsubmit="return validateFeedback();">
      <div class="modal-body">
        <input type="hidden" name="appointment_id" id="feedback_appointment_id">

        <div style="margin-bottom: 20px;">
          <label>Rating</label>
          <select name="rating" required>
            <option value="">Choose a rating</option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div>
          <label>Comments <small>(minimum 5 words)</small></label>
          <textarea name="feedback" id="feedback_text" required 
                    placeholder="E.g. I loved how gentle the groomer was with my dog."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeFeedbackModal()" class="btn btn-secondary">
          Close
        </button>
        <button type="submit" class="btn btn-primary">
          Submit Feedback
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('nav-menu');
const navOverlay = document.getElementById('nav-overlay');
const profileDropdown = document.getElementById('profile-dropdown');

hamburger.addEventListener('click', function() {
  hamburger.classList.toggle('active');
  navMenu.classList.toggle('active');
  navOverlay.classList.toggle('active');
  document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
});

navOverlay.addEventListener('click', function() {
  hamburger.classList.remove('active');
  navMenu.classList.remove('active');
  navOverlay.classList.remove('active');
  profileDropdown.classList.remove('active');
  document.body.style.overflow = '';
});

document.querySelectorAll('.nav-link:not(.profile-icon)').forEach(link => {
  link.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    document.body.style.overflow = '';
  });
});

profileDropdown.addEventListener('click', function(e) {
  if (window.innerWidth <= 1024) {
    if (e.target.closest('.profile-icon')) {
      e.preventDefault();
      this.classList.toggle('active');
    }
  }
});

document.querySelectorAll('.dropdown-menu a').forEach(link => {
  link.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  });
});

window.addEventListener('resize', function() {
  if (window.innerWidth > 1024) {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  }
});

document.addEventListener('DOMContentLoaded', function() {
  const successMessage = document.getElementById('successMessage');
  const errorMessage = document.getElementById('errorMessage');
  
  if (successMessage) {
    setTimeout(() => successMessage.remove(), 5000);
  }
  
  if (errorMessage) {
    setTimeout(() => errorMessage.remove(), 5000);
  }
});

function openCancelModal(id) {
  document.getElementById('cancel_appointment_id').value = id;
  document.getElementById('cancelModal').classList.add('show');
}

function closeCancelModal() {
  document.getElementById('cancelModal').classList.remove('show');
}

function openRescheduleModal(id) {
  document.getElementById('reschedule_appointment_id').value = id;
  document.getElementById('rescheduleModal').classList.add('show');
}

function closeRescheduleModal() {
  document.getElementById('rescheduleModal').classList.remove('show');
}

function openFeedbackModal(id) {
  document.getElementById('feedback_appointment_id').value = id;
  document.getElementById('feedbackModal').classList.add('show');
}

function closeFeedbackModal() {
  document.getElementById('feedbackModal').classList.remove('show');
}

window.onclick = function(event) {
  const modals = document.querySelectorAll('.modal');
  modals.forEach(modal => {
    if (event.target === modal) {
      modal.classList.remove('show');
    }
  });
}

function validateFeedback() {
  const feedback = document.getElementById('feedback_text').value.trim();
  if (feedback !== '') {
    const wordCount = feedback.split(/\s+/).length;
    if (wordCount < 5) {
      alert("Please enter at least 5 words so we can better understand your experience.");
      return false;
    }
  }
  return true;
}
</script>

<?php if (isset($_SESSION['show_feedback_modal']) && $_SESSION['show_feedback_modal']): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const feedbackId = <?= json_encode($_SESSION['feedback_appointment_id'] ?? null) ?>;
    if (feedbackId) {
      openFeedbackModal(feedbackId);
    }
  });
</script>
<?php unset($_SESSION['show_feedback_modal'], $_SESSION['feedback_appointment_id']); ?>
<?php endif; ?>

</body>
</html>