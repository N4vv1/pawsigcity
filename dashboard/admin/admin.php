<?php
session_start();
require_once '../../db.php';
require_once 'check_admin.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (pg_connection_status($conn) !== PGSQL_CONNECTION_OK) {
    die('Database connection failed: ' . pg_last_error());
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
   header("Location: ../homepage/main.php");
   exit;
}

// Count metrics with error handling
$total_users_result = pg_query($conn, "SELECT COUNT(*) AS count FROM users");
$total_users = $total_users_result ? pg_fetch_result($total_users_result, 0, 'count') : 0;

$total_pets_result = pg_query($conn, "SELECT COUNT(*) AS count FROM pets");
$total_pets = $total_pets_result ? pg_fetch_result($total_pets_result, 0, 'count') : 0;

$total_appointments_result = pg_query($conn, "SELECT COUNT(*) AS count FROM appointments");
$total_appointments = $total_appointments_result ? pg_fetch_result($total_appointments_result, 0, 'count') : 0;

$confirmed_query = pg_query($conn, "SELECT COUNT(*) AS count FROM appointments WHERE status = 'confirmed'");
$confirmed_appointments = $confirmed_query ? pg_fetch_result($confirmed_query, 0, 'count') : 0;

$completed_query = pg_query($conn, "SELECT COUNT(*) AS count FROM appointments WHERE status = 'completed'");
$completed_appointments = $completed_query ? pg_fetch_result($completed_query, 0, 'count') : 0;

$cancelled_query = pg_query($conn, "SELECT COUNT(*) AS count FROM appointments WHERE status = 'cancelled'");
$cancelled_appointments = $cancelled_query ? pg_fetch_result($cancelled_query, 0, 'count') : 0;

$noshow_query = pg_query($conn, "SELECT COUNT(*) AS count FROM appointments WHERE status = 'no_show'");
$noshow_appointments = $noshow_query ? pg_fetch_result($noshow_query, 0, 'count') : 0;

// Auto check for no-shows
date_default_timezone_set('Asia/Manila');
$now = new DateTime();

$autoCheck = pg_query($conn, "
  SELECT appointment_id, appointment_date
  FROM appointments
  WHERE status = 'confirmed'
");

$noShowCount = 0;
while ($row = pg_fetch_assoc($autoCheck)) {
    $appointmentTime = new DateTime($row['appointment_date']);
    $graceEnd = clone $appointmentTime;
    $graceEnd->modify('+15 minutes');

    if ($now > $graceEnd) {
        $id = $row['appointment_id'];
        $update = pg_query($conn, "UPDATE appointments SET status = 'no_show' WHERE appointment_id = $id");
        if ($update) {
            $noShowCount++;
        }
    }
}

if ($noShowCount > 0) {
    header("Location: admin.php?noshows=$noShowCount&show=appointments");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin | Overview</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig2.png">
  
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
      --border-radius-s: 14px;
      --border-radius-circle: 50%;
      --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
      --transition-speed: 0.3s;
      --sidebar-width: 260px;
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
      box-shadow: var(--shadow-light);
      overflow-y: auto;
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

    .dropdown-toggle:hover {
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
      transition: margin-left var(--transition-speed), width var(--transition-speed);  
    }

    .dashboard {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
      padding-top: 50px;
    }

    /* CARDS */
    .card {
      background: var(--white-color);
      padding: 30px;
      border-radius: var(--border-radius-s);
      box-shadow: var(--shadow-light);
      transition: var(--transition-speed);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      text-align: center;
      min-height: 230px;
      position: relative;
    }

    .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }

    .card-icon {
      font-size: 2.5rem;
      color: var(--secondary-color);
      margin-bottom: 10px;
    }

    .card h3 {
      font-size: 1.1rem;
      font-weight: var(--font-weight-semi-bold);
      color: var(--dark-color);
      margin-bottom: 8px;
    }

    .card p {
      font-size: 2rem;
      font-weight: var(--font-weight-bold);
      color: var(--primary-color);
    }

    .card a {
      margin-top: 20px;
      font-size: var(--font-size-n);
      text-decoration: none;
      color: var(--dark-color);
      background-color: var(--secondary-color);
      padding: 10px 18px;
      border-radius: var(--border-radius-s);
      border: none;
      transition: var(--transition-speed);
      display: inline-block;
    }

    .card a:hover {
      background-color: var(--primary-color);
      color: var(--white-color);
    }

    /* MODALS */
    .modal {
      display: none;
      position: fixed;
      z-index: 200;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.4);
      align-items: center;
      justify-content: flex-end;
      padding-right: 300px;
    }

    .modal-content {
      background-color: var(--white-color);
      padding: 25px 30px;
      border-radius: var(--border-radius-s);
      width: 90%;
      max-width: 1000px;
      max-height: 85vh;
      overflow-y: auto;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
      animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    .modal-content h2 {
      margin-bottom: 18px;
      color: var(--dark-color);
      font-size: var(--font-size-l);
    }

    .modal-content table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .modal-content table th,
    .modal-content table td {
      padding: 12px 10px;
      text-align: left;
      border-bottom: 1px solid var(--medium-gray-color);
      font-size: var(--font-size-s);
    }

    .modal-content table th {
      background-color: var(--primary-color);
      color: var(--dark-color);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .modal-content button {
      margin-top: 20px;
      padding: 10px 20px;
      background-color: var(--primary-color);
      color: var(--dark-color);
      border: none;
      border-radius: var(--border-radius-s);
      cursor: pointer;
      transition: var(--transition-speed);
      font-weight: var(--font-weight-semi-bold);
    }

    .modal-content button:hover {
      background-color: var(--secondary-color);
    }

    .feedback-box {
      padding: 0;
      margin: 0;
      background: none;
      border: none;
      box-shadow: none;
      font-size: inherit;
      display: block;
    }

    .feedback-stars {
      color: #FFD700;
      font-size: 1rem;
      display: flex;
      gap: 2px;
      margin-bottom: 5px;
    }

    .feedback-comment {
      color: #333;
      line-height: 1.3;
      font-style: italic;
      word-wrap: break-word;
    }

    .feedback-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .button {
      padding: 7px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      text-decoration: none;
      color: #252525;
      background: #A8E6CF;
      font-weight: 600;
      transition: 0.2s;
      display: inline-block;
    }

    .button:hover {
      background: #80d1b8;
      color: #000;
    }

    .button.danger {
      background-color: #FFB6B6;
    }

    .button.danger:hover {
      background-color: #e67d7d;
    }

    .button.secondary {
      background-color: #FFE29D;
    }

    .button.secondary:hover {
      background-color: #f8d775;
    }

    .button.view-history {
      background-color: #dcdcdc;
    }

    .button.view-history:hover {
      background-color: #c0c0c0;
    }

    #toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: #4CAF50;
      color: white;
      padding: 15px 20px;
      border-radius: 10px;
      z-index: 9999;
      font-weight: 600;
      display: none;
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
    <img src="../../homepage/images/pawsig2.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php" class="active"><i class='bx bx-home'></i>Overview</a>
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
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/sentiment_dashboard.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main>
  <div class="dashboard">
    <div class="card">
      <div class="card-icon"><i class='bx bx-user'></i></div>
      <h3>Total Users</h3>
      <p><?= $total_users ?></p>
      <a href="javascript:void(0)" onclick="openModal('users')">View Users</a>
    </div>

    <div class="card">
      <div class="card-icon"><i class='bx bx-heart'></i></div>
      <h3>Total Pets</h3>
      <p><?= $total_pets ?></p>
      <a href="javascript:void(0)" onclick="openModal('pets')">View Pets</a>
    </div>

    <div class="card">
      <div class="card-icon"><i class='bx bx-calendar'></i></div>
      <h3>Total Appointments</h3>
      <p><?= $total_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('appointments')">Manage Appointments</a>
    </div>

    <div class="card">
      <div class="card-icon"><i class='bx bx-time'></i></div>
      <h3>Cancelled Appointments</h3>
      <p><?= $cancelled_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('cancelled')">View Cancelled</a>  
    </div>

    <div class="card">
      <div class="card-icon"><i class='bx bx-x-circle'></i></div>
      <h3>No-Show Appointments</h3>
      <p><?= $noshow_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('noshow')">View No-Shows</a>
    </div>

    <div class="card">
      <div class="card-icon"><i class='bx bx-check-circle'></i></div>
      <h3>Confirmed Appointments</h3>
      <p><?= $confirmed_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('confirmed')">View Confirmed</a>
    </div>

    <div class="card">
      <div class="card-icon"><i class='bx bx-badge-check'></i></div>
      <h3>Completed Appointments</h3>
      <p><?= $completed_appointments ?></p>
      <a href="javascript:void(0)" onclick="openModal('completed')">View Completed</a>
    </div>
  </div>
</main>

<!-- USERS MODAL -->
<div id="usersModal" class="modal">
  <div class="modal-content">
    <h2>User List</h2>
    <table>
      <thead>
        <tr>
          <th>User ID</th>
          <th>Full Name</th>
          <th>Email</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $userList = pg_query($conn, "SELECT user_id, first_name, middle_name, last_name, email FROM users");
        if ($userList):
          while ($user = pg_fetch_assoc($userList)):
            $fullName = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
        ?>
          <tr>
            <td><?= htmlspecialchars($user['user_id']) ?></td>
            <td><?= htmlspecialchars($fullName) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
          </tr>
        <?php 
          endwhile;
        endif;
        ?>
      </tbody>
    </table>
    <button onclick="closeModal('usersModal')">Close</button>
  </div>
</div>

<!-- PETS MODAL -->
<div id="petsModal" class="modal">
  <div class="modal-content">
    <h2>Pet List</h2>
    <table>
      <thead>
        <tr>
          <th>Pet ID</th>
          <th>Name</th>
          <th>Breed</th>
          <th>Owner ID</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $petList = pg_query($conn, "SELECT pet_id, name, breed, user_id FROM pets");
        if ($petList):
          while ($pet = pg_fetch_assoc($petList)):
        ?>
          <tr>
            <td><?= htmlspecialchars($pet['pet_id']) ?></td>
            <td><?= htmlspecialchars($pet['name']) ?></td>
            <td><?= htmlspecialchars($pet['breed']) ?></td>
            <td><?= htmlspecialchars($pet['user_id']) ?></td>
          </tr>
        <?php 
          endwhile;
        endif;
        ?>
      </tbody>
    </table>
    <button onclick="closeModal('petsModal')">Close</button>
  </div>
</div>

<!-- CANCELLED APPOINTMENTS MODAL -->
<div id="cancelledModal" class="modal">
  <div class="modal-content">
    <h2>Cancelled Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Appointment ID</th>
          <th>Pet Name</th>
          <th>Owner Name</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $cancelledQuery = "
            SELECT a.appointment_id, a.appointment_date, a.status,
                  p.name AS pet_name, p.breed,
                  u.first_name, u.middle_name, u.last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.status = 'cancelled'
            ORDER BY a.appointment_date DESC
        ";
        $cancelledResult = pg_query($conn, $cancelledQuery);
        if ($cancelledResult):
          while ($row = pg_fetch_assoc($cancelledResult)): 
            $ownerName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        ?>
          <tr>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($ownerName) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
          </tr>
        <?php 
          endwhile;
        endif;
        ?>
      </tbody>
    </table>
    <button onclick="closeModal('cancelledModal')">Close</button>
  </div>
</div>

<!-- NO-SHOW APPOINTMENTS MODAL -->
<div id="noshowModal" class="modal">
  <div class="modal-content">
    <h2>No-Show Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Appointment ID</th>
          <th>Pet Name</th>
          <th>Owner Name</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $noshowQuery = "
            SELECT a.appointment_id, a.appointment_date, a.status,
                  p.name AS pet_name, p.breed,
                  u.first_name, u.middle_name, u.last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.status = 'no_show'
            ORDER BY a.appointment_date DESC
        ";
        $noshowResult = pg_query($conn, $noshowQuery);
        if ($noshowResult):
          while ($row = pg_fetch_assoc($noshowResult)): 
            $ownerName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        ?>
          <tr>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($ownerName) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
          </tr>
        <?php 
          endwhile;
        endif;
        ?>
      </tbody>
    </table>
    <button onclick="closeModal('noshowModal')">Close</button>
  </div>
</div>

<!-- CONFIRMED APPOINTMENTS MODAL -->
<div id="confirmedModal" class="modal">
  <div class="modal-content">
    <h2>Confirmed Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Appointment ID</th>
          <th>Pet Name</th>
          <th>Owner Name</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $confirmedQuery = "
            SELECT a.appointment_id, a.appointment_date, a.status,
                  p.name AS pet_name, p.breed,
                  u.first_name, u.middle_name, u.last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.status = 'confirmed'
            ORDER BY a.appointment_date DESC
        ";
        $confirmedResult = pg_query($conn, $confirmedQuery);
        if ($confirmedResult):
          while ($row = pg_fetch_assoc($confirmedResult)): 
            $ownerName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        ?>
          <tr>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($ownerName) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
          </tr>
        <?php 
          endwhile;
        endif;
        ?>
      </tbody>
    </table>
    <button onclick="closeModal('confirmedModal')">Close</button>
  </div>
</div>

<!-- COMPLETED APPOINTMENTS MODAL -->
<div id="completedModal" class="modal">
  <div class="modal-content">
    <h2>🎉 Completed Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Appointment ID</th>
          <th>Pet Name</th>
          <th>Owner Name</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $completedQuery = "
            SELECT a.appointment_id, a.appointment_date, a.status,
                  p.name AS pet_name, p.breed,
                  u.first_name, u.middle_name, u.last_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.status = 'completed'
            ORDER BY a.appointment_date DESC
        ";
        $completedResult = pg_query($conn, $completedQuery);
        if ($completedResult):
          while ($row = pg_fetch_assoc($completedResult)): 
            $ownerName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        ?>
          <tr>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($ownerName) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
          </tr>
        <?php 
          endwhile;
        endif;
        ?>
      </tbody>
    </table>
    <button onclick="closeModal('completedModal')">Close</button>
  </div>
</div>

<!-- ALL APPOINTMENTS MODAL -->
<div id="appointmentsModal" class="modal">
  <div class="modal-content"
     style="max-width: 1400px; max-height: 85vh; overflow-y: auto;
            position: absolute; right: 80px; top: 50%; transform: translateY(-50%);">



    <h2>All Appointments</h2>
    <div style="overflow-x: auto;">
    <table style="font-size: 0.85rem; min-width: 100%;">
      <thead>
        <tr>
          <th style="min-width: 120px;">Client</th>
          <th style="min-width: 100px;">Pet</th>
          <th style="min-width: 100px;">Service</th>
          <th style="min-width: 140px;">Date</th>
          <th style="min-width: 90px;">Status</th>
          <th style="min-width: 80px;">Approval</th>
          <th style="min-width: 100px;">Groomer</th>
          <th style="min-width: 120px;">Notes</th>
          <th style="min-width: 150px;">Cancel Reason</th>
          <th style="min-width: 100px;">Feedback</th>
          <th style="min-width: 180px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $appointmentQuery = "
            SELECT a.*,
                  u.first_name,
                  u.middle_name,
                  u.last_name,
                  u.user_id,
                  p.name AS pet_name,
                  p.breed AS pet_breed,
                  pk.name AS package_name
            FROM appointments a
            JOIN users u ON a.user_id = u.user_id
            JOIN pets p ON a.pet_id = p.pet_id
            JOIN packages pk ON a.package_id = pk.package_id
            ORDER BY a.appointment_date DESC
        ";
        $appointmentList = pg_query($conn, $appointmentQuery);
        if ($appointmentList):
          while ($row = pg_fetch_assoc($appointmentList)): 
            $clientName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        ?>
          <tr>
            <td><?= htmlspecialchars($clientName) ?></td>
            <td>
              <strong><?= htmlspecialchars($row['pet_name']) ?></strong><br>
              <small style="color: #666;"><?= htmlspecialchars($row['pet_breed']) ?></small>
            </td>
            <td><?= htmlspecialchars($row['package_name']) ?></td>
            <td style="font-size: 0.8rem;">
              <?= date('M d, Y g:i A', strtotime($row['appointment_date'])) ?>
            </td>
            
            <!-- STATUS COLUMN - FIXED -->
            <td>
              <?php if (!empty($row['cancel_reason']) && $row['status'] !== 'cancelled'): ?>
                <span style="color: red; font-weight: bold; font-size: 0.8rem;">Cancel Request</span>
              <?php elseif (!empty($row['reschedule_requested']) && is_null($row['reschedule_approved'])): ?>
                <span style="color: orange; font-weight: bold; font-size: 0.8rem;">Reschedule</span>
              <?php elseif ($row['status'] === 'no_show'): ?>
                <span style="color: red; font-weight: bold; font-size: 0.8rem;">No Show</span>
              <?php elseif ($row['status'] === 'cancelled'): ?>
                <span style="color: red; font-size: 0.8rem;">Cancelled</span>
              <?php elseif ($row['status'] === 'completed'): ?>
                <span style="color: green; font-size: 0.8rem;">Completed</span>
              <?php elseif ($row['status'] === 'confirmed'): ?>
                <span style="color: green; font-size: 0.8rem;">Confirmed</span>
              <?php else: ?>
                <span style="color: orange; font-size: 0.8rem;">Pending</span>
              <?php endif; ?>
            </td>
            
            <td style="font-size: 0.8rem;">
              <?= $row['status'] === 'cancelled' ? '<span style="color:red;">✗</span>' :
                  (!empty($row['is_approved']) ? '<span style="color:green;">✓</span>' : '<span style="color:orange;">⏳</span>') ?>
            </td>
            <td style="font-size: 0.8rem;"><?= !empty($row['groomer_name']) ? htmlspecialchars($row['groomer_name']) : '<em style="color:#999;">Not assigned</em>' ?></td>
            <td style="font-size: 0.75rem; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
              <?= !empty($row['notes']) ? htmlspecialchars(substr($row['notes'], 0, 50)) . (strlen($row['notes']) > 50 ? '...' : '') : '-' ?>
            </td>
            
            <!-- CANCEL REASON COLUMN - ADDED DATA -->
            <td style="font-size: 0.75rem; color: #d32f2f; max-width: 150px;">
              <?php if (!empty($row['cancel_reason'])): ?>
                <strong></strong> <?= htmlspecialchars(substr($row['cancel_reason'], 0, 60)) ?><?= strlen($row['cancel_reason']) > 60 ? '...' : '' ?>
              <?php else: ?>
                <span style="color: #999;">-</span>
              <?php endif; ?>
            </td>
            
            <td style="font-size: 0.75rem;">
            <?php if (isset($row['rating'])): ?>
              <div class="feedback-box">
                <div class="feedback-stars" style="font-size: 0.7rem;">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa<?= $i <= $row['rating'] ? 's' : 'r' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
                <div class="feedback-comment" style="font-size: 0.7rem; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  <?= !empty($row['feedback']) ? htmlspecialchars(substr($row['feedback'], 0, 30)) . '...' : '<em>No comment</em>' ?>
                </div>
              </div>
            <?php else: ?>
              <em style="color:#999; font-size: 0.7rem;">No feedback</em>
            <?php endif; ?>
          </td>
          
            <td>
              <div class="action-buttons" style="gap: 4px;">
                <?php
                  $status = strtolower($row['status']);
                  $appointmentId = $row['appointment_id'];
                  $isApproved = !empty($row['is_approved']);
                ?>

                <?php if ($status === 'pending' && !$isApproved): ?>
                  <a href="../../admin/approve/approve-handler.php?id=<?= $appointmentId ?>" class="button" style="padding: 5px 10px; font-size: 0.75rem;">Approve</a>
                  <a href="../../appointment/delete-appointment.php?id=<?= $appointmentId ?>" class="button danger" style="padding: 5px 10px; font-size: 0.75rem;" onclick="return confirm('Delete?')">Delete</a>

                <?php elseif ($status === 'confirmed'): ?>
                  <a href="../../appointment/mark-completed.php?id=<?= $appointmentId ?>" class="button" style="padding: 5px 10px; font-size: 0.75rem;" onclick="return confirm('Mark as completed?');">Complete</a>
                  <a href="../../appointment/delete-appointment.php?id=<?= $appointmentId ?>" class="button danger" style="padding: 5px 10px; font-size: 0.75rem;" onclick="return confirm('Delete?')">Delete</a>

                <?php elseif ($status === 'cancelled' || $status === 'no_show' || $status === 'completed'): ?>
                  <a href="../../appointment/delete-appointment.php?id=<?= $appointmentId ?>" class="button danger" style="padding: 5px 10px; font-size: 0.75rem;" onclick="return confirm('Delete?')">Delete</a>
                <?php endif; ?>

                <?php if (!empty($row['cancel_reason']) && $status !== 'cancelled'): ?>
                  <a href="../../appointment/cancel-approve.php?id=<?= $appointmentId ?>&action=approve" class="button danger" style="padding: 5px 10px; font-size: 0.75rem;" onclick="return confirm('Confirm cancellation?')">Process Cancel</a>
                <?php endif; ?>

                <a href="javascript:void(0)" class="button view-history" style="padding: 5px 10px; font-size: 0.75rem;" onclick="viewHistory(<?= $row['user_id'] ?>)">History</a>
              </div>
            </td>
          </tr>
        <?php 
          endwhile;
        endif;
        ?>
      </tbody>
    </table>
    </div>
    <button onclick="closeModal('appointmentsModal')">Close</button>
  </div>
</div>

<!-- HISTORY MODAL -->
<div id="historyModal" class="modal" style="justify-content: center; padding-left: 600px;">
  <div class="modal-content" id="historyContent">
    <h3>Appointment History</h3>
    <div id="historyTable">Loading...</div>
    <button onclick="closeModal('historyModal')">Close</button>
  </div>
</div>

<script>
// Define functions IMMEDIATELY in global scope
window.openModal = function(type) {
  const modals = {
    users: 'usersModal',
    pets: 'petsModal',
    pending: 'pendingModal',
    cancelled: 'cancelledModal',
    noshow: 'noshowModal',
    confirmed: 'confirmedModal',
    completed: 'completedModal',
    appointments: 'appointmentsModal',
    history: 'historyModal'
  };
  
  console.log('openModal called with:', type);
  const modalId = modals[type];
  if (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'flex';
      console.log('Modal opened successfully:', modalId);
    } else {
      console.error('Modal element not found:', modalId);
    }
  } else {
    console.error('Unknown modal type:', type);
  }
};

window.closeModal = function(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = 'none';
};

window.viewHistory = function(userId) {
  openModal('history');
  const historyContainer = document.getElementById('historyTable');
  if (historyContainer) {
    historyContainer.innerHTML = 'Loading...';
    fetch('../../appointment/fetch-history.php?user_id=' + userId)
      .then(response => response.text())
      .then(html => historyContainer.innerHTML = html)
      .catch(() => historyContainer.innerHTML = 'Failed to load history.');
  }
};

window.showToast = function(message) {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 10px; z-index: 9999; font-weight: 600; display: none;';
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.style.display = 'block';
  setTimeout(() => toast.style.display = 'none', 3000);
};

window.toggleDropdown = function(event) {
  event.preventDefault();
  const menu = event.currentTarget.nextElementSibling;
  if (menu && menu.classList.contains('dropdown-menu')) {
    const isVisible = menu.style.display === 'block';
    document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
    menu.style.display = isVisible ? 'none' : 'block';
  }
};

window.toggleSidebar = function() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
};

// Debug on page load
document.addEventListener('DOMContentLoaded', function() {
  console.log('=== MODAL DEBUG ===');
  console.log('confirmedModal exists:', document.getElementById('confirmedModal') !== null);
  console.log('completedModal exists:', document.getElementById('completedModal') !== null);
  console.log('appointmentsModal exists:', document.getElementById('appointmentsModal') !== null);
});
</script>

</body>
</html>