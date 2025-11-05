<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Debug: Check connection
if (!$conn) {
    die("Database connection failed: " . pg_last_error());
}

// ✅ Debug: Check user_id
error_log("User ID: " . $user_id);

// ✅ PostgreSQL query using pg_query_params
$query = "
    SELECT a.*, 
           p.name AS pet_name, 
           pk.name AS package_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN packages pk ON a.package_id = pk.package_id
    WHERE a.user_id = $1
    ORDER BY a.appointment_date DESC
";

$appointments = pg_query_params($conn, $query, [$user_id]);

// ✅ Debug: Check query execution
if (!$appointments) {
    die("Query failed: " . pg_last_error($conn));
}

// ✅ Debug: Check row count
$row_count = pg_num_rows($appointments);
error_log("Number of appointments found: " . $row_count);
?>

<!DOCTYPE html>
<html>
<head>
  <title>PAWsig City | Appointments</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="./images/pawsig.png">

  <style>
  .section-content {
    max-width: 1200px;
    margin: auto;
  }

  h2 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
    font-size: 28px;
  }

  .container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 140px 40px 60px;
    box-sizing: border-box;
    min-height: calc(100vh - 80px);
  }

  .page-header {
    text-align: center;
    margin-bottom: 40px;
  }

  .page-header h1 {
    font-size: 2.2rem;
    color: #2c3e50;
    margin-bottom: 8px;
    font-weight: 600;
  }

  .page-header p {
    font-size: 1rem;
    color: #7f8c8d;
    margin: 0;
  }

  .stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
    margin-bottom: 50px;
  }

  .stat-card {
    background: #fff;
    padding: 30px 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    text-align: center;
    transition: all 0.3s ease;
    border-left: 4px solid #A8E6CF;
    margin-top: 50px;
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
  }

  .stat-card i {
    font-size: 2.5rem;
    color: #A8E6CF;
    margin-bottom: 15px;
  }

  .stat-card h3 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin: 10px 0;
    font-weight: 700;
  }

  .stat-card p {
    color: #7f8c8d;
    font-size: 0.95rem;
    margin: 0;
    font-weight: 500;
  }

  .table-container {
    width: 100%;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 40px;
    position: relative;
  }

  .table-container::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 6px;
    width: 100%;
    border-radius: 16px 16px 0 0;
    background: linear-gradient(to right, #A8E6CF, #FFE29D, #FFB6B9);
  }

  .button {
    padding: 10px 16px;
    background-color: #A8E6CF;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    margin: 5px 3px;
    display: inline-block;
    border: none;
    cursor: pointer;
  }

  .button:hover {
    background-color: #87d7b7;
    transform: translateY(-1px);
  }

  .button:active {
    transform: translateY(0);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 0;
  }

  th, td {
    padding: 16px 20px;
    text-align: left;
    font-size: 15px;
  }

  th {
    background-color: #A8E6CF;
    color: #2c3e50;
    font-weight: 600;
    border-bottom: 2px solid #e0e0e0;
  }

  tr:nth-child(even) {
    background-color: #f9f9f9;
  }

  tr:hover {
    background-color: #f1f1f1;
  }

  .badge {
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
  }

  .approved {
    background-color: #d4edda;
    color: #155724;
  }

  .pending {
    background-color: #fff3cd;
    color: #856404;
  }

  .cancelled {
    background-color: #f8d7da;
    color: #721c24;
  }

  .feedback {
    background-color: #e3f2fd;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #0d47a1;
  }

  .feedback em {
    color: #777;
  }

  p.success-message {
    text-align: center;
    color: green;
    font-weight: 600;
  }

  .appointment-header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
  }

  .back-button {
    position: fixed;
    top: 90px;
    left: 30px;
    background: none;
    border: none;
    color: #2c3e50;
    font-size: 18px;
    text-decoration: none;
    transition: all 0.3s ease;
    z-index: 100;
    font-weight: 600;
  }

  .back-button:hover {
    color: #16a085;
    transform: translateX(-3px);
  }

  .empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #666;
    animation: fadeIn 0.6s ease;
  }

  .empty-state i {
    font-size: 80px;
    color: #A8E6CF;
    margin-bottom: 25px;
    opacity: 0.7;
  }

  .empty-state h3 {
    color: #2c3e50;
    margin-bottom: 12px;
    font-size: 1.8rem;
    font-weight: 600;
  }

  .empty-state p {
    color: #7f8c8d;
    font-size: 1.1rem;
    margin-bottom: 30px;
  }

  .debug-info {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    font-family: monospace;
  }

  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @media (max-width: 768px) {
    .back-button {
      top: 80px;
      left: 20px;
      font-size: 16px;
    }

    .container {
      padding: 120px 20px 40px;
    }

    .page-header h1 {
      font-size: 1.8rem;
    }

    .page-header p {
      font-size: 0.95rem;
    }

    .stats-container {
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .stat-card {
      padding: 20px 15px;
    }

    .stat-card i {
      font-size: 1.5rem;
    }

    .stat-card h3 {
      font-size: 1.5rem;
    }

    .stat-card p {
      font-size: 0.85rem;
    }

    .table-container {
      padding: 25px 15px;
      border-radius: 16px;
      overflow-x: auto;
    }

    table {
      min-width: 800px;
      font-size: 0.85rem;
    }

    th, td {
      padding: 14px 12px;
      font-size: 0.85rem;
    }

    th {
      font-size: 0.75rem;
    }

    .button {
      padding: 8px 14px;
      font-size: 0.85rem;
      margin: 3px 2px;
    }

    .badge {
      padding: 5px 8px;
      font-size: 0.75rem;
    }

    .feedback {
      padding: 8px 10px;
      font-size: 0.85rem;
    }

    .empty-state {
      padding: 60px 20px;
    }

    .empty-state i {
      font-size: 60px;
    }

    .empty-state h3 {
      font-size: 1.5rem;
    }

    .empty-state p {
      font-size: 1rem;
    }
  }

  @media (max-width: 480px) {
    .stats-container {
      grid-template-columns: 1fr;
    }

    .page-header h1 {
      font-size: 1.5rem;
    }

    .table-container {
      padding: 20px 10px;
    }

    table {
      min-width: 700px;
    }

    th, td {
      padding: 12px 10px;
    }
  }

  @media (min-width: 769px) and (max-width: 1024px) {
    .container {
      padding: 130px 30px 50px;
    }

    .stats-container {
      grid-template-columns: repeat(3, 1fr);
    }

    .table-container {
      padding: 35px 25px;
    }

    th, td {
      padding: 16px 15px;
    }
  }

  /* Base navbar styles */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 30px;
  position: relative;
  z-index: 100;
}

/* Desktop nav menu - visible by default */
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

/* Hide hamburger by default (desktop) */
.hamburger {
  display: none;
}

/* ========================================
   DESKTOP DROPDOWN (Hover-based)
   ======================================== */
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
    transition: all 0.3s ease;
    margin-top: 8px;
    padding: 8px 0;
    z-index: 1000;
    list-style: none;
    pointer-events: none;
  }

  /* Show dropdown on hover */
  .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto;
  }

  /* Keep dropdown visible when hovering over menu items */
  .dropdown-menu:hover {
    opacity: 1;
    visibility: visible;
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

/* ========================================
   MOBILE STYLES (Click-based)
   ======================================== */
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

  /* Mobile dropdown */
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

/* Hide hamburger on desktop */
@media (min-width: 1025px) {
  .hamburger {
    display: none;
  }
  
  .nav-overlay {
    display: none;
  }
}

#rescheduleModal input[type="datetime-local"]:focus {
  outline: none;
  border-color: #A8E6CF;
  background: #fff;
}

#rescheduleModal input[type="datetime-local"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
}

@media (max-width: 640px) {
  #rescheduleModal > div {
    padding: 24px !important;
  }
  
  #rescheduleModal h3 {
    font-size: 20px !important;
  }
  
  #rescheduleModal > div > div:last-child {
    flex-direction: column-reverse !important;
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
  </style>
</head>
<body>
<header>
  <nav class="navbar section-content">
    <a href="#" class="navbar-logo">
      <img src="../homepage/images/pawsig.png" alt="Logo" class="icon" />
    </a>
    
    <!-- Hamburger Menu Button -->
    <button class="hamburger" id="hamburger">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Overlay for mobile -->
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

<div class="container">

    <!-- Add this right after opening <div class="container"> -->
  <?php if (isset($_SESSION['success'])): ?>
    <div style="background:#d4edda; color:#155724; padding:15px 20px; border-radius:8px; margin-bottom:20px; border-left:4px solid #28a745; font-weight:600; animation:slideDown 0.3s ease;">
      ✓ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div style="background:#f8d7da; color:#721c24; padding:15px 20px; border-radius:8px; margin-bottom:20px; border-left:4px solid #dc3545; font-weight:600; animation:slideDown 0.3s ease;">
      ✗ <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
  <?php endif; ?>

  <?php if ($row_count > 0): ?>
    <!-- Stats Overview -->
    <div class="stats-container">
      <?php
        // Calculate stats
        $total = $row_count;
        $approved = 0;
        $pending = 0;
        $completed = 0;
        $cancelled = 0;
        
        pg_result_seek($appointments, 0); // Reset pointer
        while ($row = pg_fetch_assoc($appointments)) {
          if ($row['status'] === 'cancelled') $cancelled++;
          elseif ($row['status'] === 'completed') $completed++;
          elseif ($row['is_approved']) $approved++;
          else $pending++;
        }
        pg_result_seek($appointments, 0); // Reset pointer again
      ?>
      
      <div class="stat-card">
        <h3><?= $total ?></h3>
        <p>Total Appointments</p>
      </div>
      
      <div class="stat-card">
        <h3><?= $approved ?></h3>
        <p>Approved</p>
      </div>
      
      <div class="stat-card">
        <h3><?= $pending ?></h3>
        <p>Pending</p>
      </div>
      
      <div class="stat-card">
        <h3><?= $completed ?></h3>
        <p>Completed</p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Table Container -->
  <div class="table-container">
    <?php if ($row_count > 0): ?>
      <table>
        <thead>
          <tr>
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
              <td><?= htmlspecialchars($row['pet_name']) ?></td>
              <td><?= htmlspecialchars($row['package_name']) ?></td>

              <td>
                <?= htmlspecialchars(date("M d, Y h:i A", strtotime($row['appointment_date']))) ?>
                
                  <!-- Show reschedule request info if pending -->
                <?php if (!empty($row['reschedule_requested']) && is_null($row['reschedule_approved'])): ?>
                  <div style="margin-top:8px; padding:8px; background:#fff3cd; border-left:3px solid #ffc107; border-radius:4px; font-size:0.85rem;">
                    <strong style="color:#856404;">Reschedule Requested:</strong><br>
                    <?php if (!empty($row['requested_date'])): ?>
                      <span style="color:#856404;"><?= htmlspecialchars(date("M d, Y h:i A", strtotime($row['requested_date']))) ?></span><br>
                    <?php else: ?>
                      <span style="color:#856404;">Date pending...</span><br>
                    <?php endif; ?>
                    <em style="color:#666; font-size:0.8rem;">Awaiting admin approval</em>
                  </div>
                <?php elseif (!empty($row['reschedule_requested']) && $row['reschedule_approved'] == 0): ?>
                  <div style="margin-top:8px; padding:8px; background:#f8d7da; border-left:3px solid #dc3545; border-radius:4px; font-size:0.85rem;">
                    <strong style="color:#721c24;">Reschedule Denied</strong><br>
                    <em style="color:#721c24; font-size:0.8rem;">Please contact us for assistance</em>
                  </div>
                <?php endif; ?>
              </td>
              
              <!-- RECOMMENDED COLUMN - Keep it clean -->
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
              <td><?= ucfirst($row['status']) ?></td>
              <td><?= !empty($row['notes']) ? nl2br(htmlspecialchars($row['notes'])) : '<em>No notes yet.</em>' ?></td>
              <td>
                <?php if ($row['status'] !== 'completed' && $row['status'] !== 'cancelled'): ?>
                  
                  <!-- Reschedule Button (only if not rescheduled before) -->
                  <?php if (($row['reschedule_count'] ?? 0) < 1): ?>
                    <button class="button" type="button" onclick="openRescheduleModal(<?= $row['appointment_id'] ?>)">Reschedule</button>
                  <?php else: ?>
                    <span style="color: #999; font-size: 0.85rem;">(Already rescheduled)</span>
                  <?php endif; ?>
                  
                  <!-- Cancel Button -->
                  <button class="button" type="button" onclick="openCancelModal(<?= $row['appointment_id'] ?>)">Cancel</button>
                  
                <?php endif; ?>

                <!-- Feedback Section -->
                <?php if ($row['status'] === 'completed' && is_null($row['rating'])): ?>
                  <button class="button" type="button" onclick="openFeedbackModal(<?= $row['appointment_id'] ?>)">⭐ Feedback</button>
                <?php elseif ($row['status'] === 'completed' && $row['rating'] !== null): ?>
                  <div class="feedback">
                    ⭐ <?= $row['rating'] ?>/5<br>
                    <?= !empty($row['feedback']) ? htmlspecialchars($row['feedback']) : '<em>No comment.</em>' ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3>No Appointments Found</h3>
        <p>You don't have any appointments yet. Book your first appointment!</p>
        <a href="../appointment/book-appointment.php" class="button" style="margin-top: 20px;">Book Now</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:2000;">
  <div style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:400px; position:relative;">
    <h3>Cancel Appointment</h3>
    <form action="../appointment/cancel-appointment.php" method="POST">
      <input type="hidden" name="appointment_id" id="cancel_appointment_id">
      <textarea name="cancel_reason" required placeholder="Reason for cancellation..." style="width:100%; padding:10px; border-radius:8px; margin:15px 0; border:1px solid #ddd;"></textarea>
      <div style="text-align:right;">
        <button type="button" onclick="closeCancelModal()" style="margin-right:10px; background:#ccc;" class="button">Close</button>
        <button type="submit" class="button">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); justify-content:center; align-items:center; z-index:2000; backdrop-filter:blur(4px);">
  <div style="background:#fff; border:1px solid #e0e0e0; padding:40px; border-radius:12px; width:90%; max-width:500px; position:relative; box-shadow:0 8px 32px rgba(0,0,0,0.15);">
    <h3 style="color:#2c3e50; font-size:24px; font-weight:600; margin-bottom:8px;">Reschedule Appointment</h3>
    <p style="color:#7f8c8d; font-size:14px; margin-bottom:32px;">Request a new date and time for your appointment</p>
    
    <form action="../appointment/rescheduler-handler.php" method="POST">
      <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
      
      <div style="margin-bottom:24px;">
        <label for="appointment_date" style="display:block; font-size:13px; color:#7f8c8d; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; font-weight:500;">New Date & Time</label>
        <input type="datetime-local" name="appointment_date" id="appointment_date" required 
               min="<?= date('Y-m-d\TH:i') ?>"
               style="width:100%; padding:12px; background:#f9f9f9; border:1px solid #e0e0e0; border-radius:6px; color:#2c3e50; font-size:16px; font-family:inherit; transition:border-color 0.2s;">
      </div>

      <div style="margin-bottom:24px;">
        <label for="reschedule_reason" style="display:block; font-size:13px; color:#7f8c8d; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; font-weight:500;">Reason for Rescheduling</label>
        <textarea name="reschedule_reason" id="reschedule_reason" rows="3" 
                  placeholder="Please explain why you need to reschedule..."
                  style="width:100%; padding:12px; background:#f9f9f9; border:1px solid #e0e0e0; border-radius:6px; color:#2c3e50; font-size:14px; font-family:inherit; resize:vertical; transition:border-color 0.2s;"></textarea>
      </div>
      
      <div style="display:flex; gap:12px; margin-top:32px;">
        <button type="button" onclick="closeRescheduleModal()" 
                style="flex:1; padding:14px 24px; background:#f9f9f9; color:#7f8c8d; border:1px solid #e0e0e0; border-radius:6px; font-size:15px; font-weight:500; cursor:pointer; transition:all 0.2s;" 
                onmouseover="this.style.borderColor='#A8E6CF'; this.style.color='#2c3e50';" 
                onmouseout="this.style.borderColor='#e0e0e0'; this.style.color='#7f8c8d';">
          Cancel
        </button>
        <button type="submit" 
                style="flex:1; padding:14px 24px; background:linear-gradient(135deg, #7FD4B3 0%, #A8E6CF 100%); color:#2c3e50; border:none; border-radius:6px; font-size:15px; font-weight:600; cursor:pointer; transition:all 0.2s;" 
                onmouseover="this.style.background='linear-gradient(135deg, #6CC4A3 0%, #97D6BF 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(127, 212, 179, 0.3)';" 
                onmouseout="this.style.background='linear-gradient(135deg, #7FD4B3 0%, #A8E6CF 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
          Request Reschedule
        </button>
      </div>
    </form>
  </div>
</div>


<!-- Feedback Modal -->
<div id="feedbackModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:2000;">
  <div style="background:#fff; padding:30px; border-radius:16px; width:90%; max-width:420px; position:relative;">
    <h3 style="color:#2a9d8f; margin-bottom:10px;">Rate Your Appointment</h3>
    <p style="font-size:14px; color:#555;">Please rate your experience. <strong>Tell us what you liked or what we can improve!</strong></p>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div style="background: #fdecea; color: #b71c1c; padding: 10px; border-radius: 6px; font-weight: 600; margin-bottom: 10px;">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
      <div style="background: #e6f4ea; color: #2e7d32; padding: 10px; border-radius: 6px; font-weight: 600; margin-bottom: 10px;">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
    <?php endif; ?>

    <form action="./feedback/rate-handler.php" method="POST" onsubmit="return validateFeedback();">
      <input type="hidden" name="appointment_id" id="feedback_appointment_id">

      <label style="font-weight: 600; margin-top: 15px;">Rating:</label>
      <select name="rating" required style="width:100%; padding:10px; border-radius:8px; font-size:14px; border:1px solid #ddd;">
        <option value="">Choose</option>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
        <?php endfor; ?>
      </select>

      <label style="font-weight: 600; margin-top: 15px;">Comments <small>(minimum 5 words)</small>:</label>
      <textarea name="feedback" id="feedback_text" required placeholder="E.g. I loved how gentle the groomer was with my dog." style="width:100%; padding:10px; border-radius:8px; margin:10px 0; border:1px solid #ddd;"></textarea>

      <div style="text-align:right;">
        <button type="button" onclick="closeFeedbackModal()" style="margin-right:10px; background:#ccc;" class="button">Close</button>
        <button type="submit" class="button">Submit</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openCancelModal(id) {
    document.getElementById('cancel_appointment_id').value = id;
    document.getElementById('cancelModal').style.display = 'flex';
  }

  function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
  }

  function openRescheduleModal(id) {
    document.getElementById('reschedule_appointment_id').value = id;
    document.getElementById('rescheduleModal').style.display = 'flex';
  }

  function closeRescheduleModal() {
    document.getElementById('rescheduleModal').style.display = 'none';
  }

  function openFeedbackModal(id) {
    document.getElementById('feedback_appointment_id').value = id;
    document.getElementById('feedbackModal').style.display = 'flex';
  }

  function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'none';
  }

  window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      if (event.target === modal) {
        modal.style.display = 'none';
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

  // Hamburger Menu Toggle
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

// Close menu when clicking overlay
navOverlay.addEventListener('click', function() {
  hamburger.classList.remove('active');
  navMenu.classList.remove('active');
  navOverlay.classList.remove('active');
  profileDropdown.classList.remove('active');
  document.body.style.overflow = '';
});

// Close menu when clicking on regular nav links
document.querySelectorAll('.nav-link:not(.profile-icon)').forEach(link => {
  link.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    document.body.style.overflow = '';
  });
});

// Handle profile dropdown - ONLY for mobile (click to toggle)
profileDropdown.addEventListener('click', function(e) {
  if (window.innerWidth <= 1024) {
    // Only prevent default and toggle on mobile
    if (e.target.closest('.profile-icon')) {
      e.preventDefault();
      this.classList.toggle('active');
    }
  }
  // On desktop, do nothing - CSS :hover handles it
});

// Close menu when clicking dropdown items
document.querySelectorAll('.dropdown-menu a').forEach(link => {
  link.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  });
});

// Reset on window resize
window.addEventListener('resize', function() {
  if (window.innerWidth > 1024) {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  }
});

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