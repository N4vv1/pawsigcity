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

$archived_pets_result = pg_query($conn, "SELECT COUNT(*) AS count FROM pets WHERE deleted_at IS NOT NULL");
$archived_pets = $archived_pets_result ? pg_fetch_result($archived_pets_result, 0, 'count') : 0;

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

// Handle pet restoration (admin only)
if (isset($_GET['restore_pet_id'])) {
    $restore_id = $_GET['restore_pet_id'];
    
    $result = pg_query_params(
        $conn, 
        "UPDATE pets SET deleted_at = NULL WHERE pet_id = $1", 
        [$restore_id]
    );
    
    if ($result && pg_affected_rows($result) > 0) {
        $_SESSION['success'] = "Pet restored successfully!";
    } else {
        $_SESSION['error'] = "Failed to restore pet.";
    }
    
    header("Location: admin.php?show=archived_pets");
    exit;
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
      --secondary-color: #3ABB87;
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
    <img src="../../homepage/images/pawsig2.png " alt="Logo" />
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
        <a href="../groomer_management/groomer_accounts.php"><i class='bx bx-user-circle'></i> Groomers</a>
      </div>
    </div>
    <hr>
    <div class="dropdown">
        <a href="../service/services.php"><i class='bx bx-list-ul'></i>Services</a>
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

    <div class="card">
      <div class="card-icon"><i class='bx bx-archive'></i></div>
      <h3>Archived Pets</h3>
      <p><?= $archived_pets ?></p>
      <a href="javascript:void(0)" onclick="openModal('archived_pets')">View Archived</a>
    </div>

    <div class="card">
      <div class="card-icon"><i class='bx bx-message-dots'></i></div>
      <h3>Customer Feedback</h3>
      <p><?php 
        $feedback_count = pg_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE rating IS NOT NULL");
        echo $feedback_count ? pg_fetch_result($feedback_count, 0, 'count') : 0;
      ?></p>
      <a href="javascript:void(0)" onclick="openModal('feedback')">View Feedback</a>
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
        $userList = pg_query($conn, "SELECT user_id, first_name, middle_name, last_name, email FROM users ORDER BY user_id ASC");
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
        $petList = pg_query($conn, "SELECT pet_id, name, breed, user_id FROM pets WHERE deleted_at IS NULL ORDER BY pet_id ASC");
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
            ORDER BY a.appointment_id ASC
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
            ORDER BY a.appointment_id ASC
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
            ORDER BY a.appointment_id ASC
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
            ORDER BY a.appointment_id ASC
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
          <th style="min-width: 150px;">Reschedule Reason</th>
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
            ORDER BY a.appointment_id DESC
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
            
            <td>
              <?php if (!empty($row['cancel_reason']) && $row['status'] !== 'cancelled'): ?>
                <span style="color: red; font-weight: bold; font-size: 0.8rem;">Cancel Request</span>
              
              <?php elseif (!empty($row['reschedule_reason']) && $row['reschedule_approved'] !== true): ?>
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
            
            <td style="font-size: 0.75rem; color: #d32f2f; max-width: 150px;">
              <?php if (!empty($row['cancel_reason'])): ?>
                <strong></strong> <?= htmlspecialchars(substr($row['cancel_reason'], 0, 60)) ?><?= strlen($row['cancel_reason']) > 60 ? '...' : '' ?>
              <?php else: ?>
                <span style="color: #999;">-</span>
              <?php endif; ?>
            </td>

            <td style="font-size: 0.75rem; color: #ff9800; max-width: 150px;">
              <?php if (!empty($row['reschedule_reason'])): ?>
                <strong>Reason:</strong> <?= htmlspecialchars(substr($row['reschedule_reason'], 0, 60)) ?><?= strlen($row['reschedule_reason']) > 60 ? '...' : '' ?>
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

<!-- ARCHIVED PETS MODAL -->
<div id="archived_petsModal" class="modal">
  <div class="modal-content" style="max-width: 1200px;">
    <h2>Archived Pets</h2>
    <p style="color: #666; margin-bottom: 20px; font-size: 0.9rem;">
      These pets have been archived by their owners. You can restore them if needed.
    </p>
    <table>
      <thead>
        <tr>
          <th>Pet ID</th>
          <th>Name</th>
          <th>Breed</th>
          <th>Owner</th>
          <th>Owner Email</th>
          <th>Archived Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="archived_petsTableBody">
        <?php
        $archivedPetsQuery = "
            SELECT p.pet_id, p.name, p.breed, p.deleted_at,
                   u.user_id, u.first_name, u.middle_name, u.last_name, u.email
            FROM pets p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.deleted_at IS NOT NULL
            ORDER BY p.pet_id ASC
        ";
        $archivedPetsList = pg_query($conn, $archivedPetsQuery);
        
        if ($archivedPetsList && pg_num_rows($archivedPetsList) > 0):
          while ($pet = pg_fetch_assoc($archivedPetsList)):
            $ownerName = trim($pet['first_name'] . ' ' . $pet['middle_name'] . ' ' . $pet['last_name']);
        ?>
          <tr>
            <td><?= htmlspecialchars($pet['pet_id']) ?></td>
            <td><strong><?= htmlspecialchars($pet['name']) ?></strong></td>
            <td><?= htmlspecialchars($pet['breed']) ?></td>
            <td><?= htmlspecialchars($ownerName) ?></td>
            <td><?= htmlspecialchars($pet['email']) ?></td>
            <td style="font-size: 0.85rem; color: #666;">
              <?= date('M d, Y g:i A', strtotime($pet['deleted_at'])) ?>
            </td>
            <td>
              <div class="action-buttons">
                <a href="?restore_pet_id=<?= $pet['pet_id'] ?>" 
                   class="button" 
                   style="padding: 5px 10px; font-size: 0.75rem; background-color: #A8E6CF;"
                   onclick="return confirm('Restore <?= htmlspecialchars($pet['name']) ?>? The pet will be visible to the owner again.')">
                  <i class='bx bx-undo'></i> Restore
                </a>
              </div>
            </td>
          </tr>
        <?php 
          endwhile;
        else:
        ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
              <i class='bx bx-archive' style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
              <strong>No archived pets</strong>
              <p style="margin-top: 5px; font-size: 0.9rem;">All pets are active</p>
            </td>
          </tr>
        <?php
        endif;
        ?>
      </tbody>
    </table>
    <div id="archived_petsPagination" class="pagination"></div>
    <button onclick="closeModal('archived_petsModal')">Close</button>
  </div>
</div>

<!-- FEEDBACK MODAL -->
<div id="feedbackModal" class="modal">
  <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
    <h2>Customer Feedback Analysis</h2>
    
    <!-- Filter Section -->
    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 20px 0;">
      <label style="font-weight: 600; margin-right: 10px;">Filter by Sentiment:</label>
      <select id="feedbackFilter" onchange="filterFeedbackTable()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd; margin-right: 20px;">
        <option value="all">All Feedback</option>
        <option value="positive">Positive (4-5 ⭐)</option>
        <option value="neutral">Neutral (3 ⭐)</option>
        <option value="negative">Negative (1-2 ⭐)</option>
      </select>
      
      <label style="font-weight: 600; margin-right: 10px;">Time Period:</label>
      <select id="timeFilter" onchange="filterFeedbackTable()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd;">
        <option value="all">All Time</option>
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month">This Month</option>
        <option value="year">This Year</option>
      </select>
    </div>
    
    <?php
    // Fetch all feedback with customer and pet details
    $feedbackQuery = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.rating,
            a.feedback,
            u.first_name,
            u.middle_name,
            u.last_name,
            p.name AS pet_name,
            p.breed AS pet_breed,
            pk.name AS package_name,
            a.groomer_name,
            CASE 
                WHEN a.rating >= 4 THEN 'POSITIVE'
                WHEN a.rating = 3 THEN 'NEUTRAL'
                ELSE 'NEGATIVE'
            END AS sentiment
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN packages pk ON a.package_id = pk.package_id
        WHERE a.rating IS NOT NULL
        ORDER BY a.appointment_date DESC
    ";
    
    $feedbackResult = pg_query($conn, $feedbackQuery);
    
    // Calculate statistics
    $positive_count = 0;
    $neutral_count = 0;
    $negative_count = 0;
    $total_rating = 0;
    $feedback_data = [];
    
    while ($row = pg_fetch_assoc($feedbackResult)) {
        $feedback_data[] = $row;
        
        if ($row['rating'] >= 4) {
            $positive_count++;
        } elseif ($row['rating'] == 3) {
            $neutral_count++;
        } else {
            $negative_count++;
        }
        
        $total_rating += $row['rating'];
    }
    
    $total_feedback = count($feedback_data);
    $average_rating = $total_feedback > 0 ? round($total_rating / $total_feedback, 2) : 0;
    $positive_percent = $total_feedback > 0 ? round(($positive_count / $total_feedback) * 100, 1) : 0;
    $neutral_percent = $total_feedback > 0 ? round(($neutral_count / $total_feedback) * 100, 1) : 0;
    $negative_percent = $total_feedback > 0 ? round(($negative_count / $total_feedback) * 100, 1) : 0;
    ?>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
      <div style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin: 0; font-size: 2.5rem; font-weight: bold;"><?= $positive_count ?></h3>
        <p style="margin: 5px 0; font-size: 1rem; font-weight: 600;">POSITIVE</p>
        <small style="font-size: 0.9rem; opacity: 0.9;"><?= $positive_percent ?>% of total</small>
      </div>
      
      <div style="background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin: 0; font-size: 2.5rem; font-weight: bold;"><?= $neutral_count ?></h3>
        <p style="margin: 5px 0; font-size: 1rem; font-weight: 600;">NEUTRAL</p>
        <small style="font-size: 0.9rem; opacity: 0.9;"><?= $neutral_percent ?>% of total</small>
      </div>
      
      <div style="background: linear-gradient(135deg, #F44336 0%, #d32f2f 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin: 0; font-size: 2.5rem; font-weight: bold;"><?= $negative_count ?></h3>
        <p style="margin: 5px 0; font-size: 1rem; font-weight: 600;">NEGATIVE</p>
        <small style="font-size: 0.9rem; opacity: 0.9;"><?= $negative_percent ?>% of total</small>
      </div>
      
      <div style="background: linear-gradient(135deg, #A8E6CF 0%, #3ABB87 100%); color: #252525; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin: 0; font-size: 2.5rem; font-weight: bold;"><?= $average_rating ?></h3>
        <p style="margin: 5px 0; font-size: 1rem; font-weight: 600;">AVERAGE RATING</p>
        <small style="font-size: 0.9rem;">out of 5.0 stars</small>
      </div>
    </div>
    
    <!-- Feedback Table -->
    <h3 style="margin: 25px 0 15px 0; color: #252525;">All Feedback (Page 1 of 1)</h3>
    <div style="overflow-x: auto;">
      <table id="feedbackTableMain" style="font-size: 0.9rem;">
        <thead>
          <tr>
            <th style="min-width: 120px;">Date</th>
            <th style="min-width: 140px;">Customer</th>
            <th style="min-width: 120px;">Pet</th>
            <th style="min-width: 100px;">Service</th>
            <th style="min-width: 100px;">Groomer</th>
            <th style="min-width: 100px;">Rating</th>
            <th style="min-width: 250px;">Feedback</th>
            <th style="min-width: 100px;">Sentiment</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($feedback_data)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                <i class='bx bx-comment-x' style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                <strong>No feedback yet</strong>
                <p style="margin-top: 5px; font-size: 0.9rem;">Completed appointments with ratings will appear here</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($feedback_data as $fb): 
              $customer_name = trim($fb['first_name'] . ' ' . $fb['middle_name'] . ' ' . $fb['last_name']);
              $sentiment_lower = strtolower($fb['sentiment']);
              
              // Sentiment colors
              $sentiment_colors = [
                'positive' => '#4CAF50',
                'neutral' => '#FF9800',
                'negative' => '#F44336'
              ];
              $sentiment_bg = $sentiment_colors[$sentiment_lower];
            ?>
            <tr class="feedback-row" data-sentiment="<?= $sentiment_lower ?>" data-date="<?= $fb['appointment_date'] ?>">
              <td style="font-size: 0.85rem; white-space: nowrap;">
                <?= date('M d, Y', strtotime($fb['appointment_date'])) ?><br>
                <small style="color: #666;"><?= date('g:i A', strtotime($fb['appointment_date'])) ?></small>
              </td>
              
              <td>
                <strong><?= htmlspecialchars($customer_name) ?></strong>
              </td>
              
              <td>
                <strong><?= htmlspecialchars($fb['pet_name']) ?></strong><br>
                <small style="color: #666;"><?= htmlspecialchars($fb['pet_breed']) ?></small>
              </td>
              
              <td><?= htmlspecialchars($fb['package_name']) ?></td>
              
              <td><?= !empty($fb['groomer_name']) ? htmlspecialchars($fb['groomer_name']) : '<em style="color: #999;">Not assigned</em>' ?></td>
              
              <td>
                <div style="color: #FFD700; font-size: 1.1rem; margin-bottom: 3px;">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?= $i <= $fb['rating'] ? '★' : '☆' ?>
                  <?php endfor; ?>
                </div>
                <small style="color: #666;"><?= $fb['rating'] ?>/5</small>
              </td>
              
              <td style="max-width: 300px; line-height: 1.4;">
                <?php if (!empty($fb['feedback'])): ?>
                  <span style="font-style: italic; color: #333;">
                    "<?= htmlspecialchars($fb['feedback']) ?>"
                  </span>
                <?php else: ?>
                  <em style="color: #999;">No comment provided</em>
                <?php endif; ?>
              </td>
              
              <td>
                <span style="background: <?= $sentiment_bg ?>; color: white; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; display: inline-block; text-transform: uppercase;">
                  <?= $fb['sentiment'] ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <button onclick="closeModal('feedbackModal')" style="margin-top: 25px;">Close</button>
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
    history: 'historyModal',
    archived_pets: 'archived_petsModal',
    feedback: 'feedbackModal'
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

function filterFeedbackTable() {
  const sentimentFilter = document.getElementById('feedbackFilter').value;
  const timeFilter = document.getElementById('timeFilter').value;
  const rows = document.querySelectorAll('.feedback-row');
  
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
  const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
  const yearAgo = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
  
  let visibleCount = 0;
  
  rows.forEach(row => {
    const sentiment = row.getAttribute('data-sentiment');
    const dateStr = row.getAttribute('data-date');
    const rowDate = new Date(dateStr);
    
    // Check sentiment filter
    let sentimentMatch = sentimentFilter === 'all' || sentiment === sentimentFilter;
    
    // Check time filter
    let timeMatch = true;
    if (timeFilter === 'today') {
      timeMatch = rowDate >= today;
    } else if (timeFilter === 'week') {
      timeMatch = rowDate >= weekAgo;
    } else if (timeFilter === 'month') {
      timeMatch = rowDate >= monthAgo;
    } else if (timeFilter === 'year') {
      timeMatch = rowDate >= yearAgo;
    }
    
    if (sentimentMatch && timeMatch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });
  
  // Show message if no results
  const tbody = document.querySelector('#feedbackTableMain tbody');
  const noResultsRow = tbody.querySelector('.no-results-row');
  
  if (visibleCount === 0 && !noResultsRow) {
    const tr = document.createElement('tr');
    tr.className = 'no-results-row';
    tr.innerHTML = '<td colspan="8" style="text-align: center; padding: 30px; color: #999;"><i class="bx bx-search-alt" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i><strong>No feedback matches your filters</strong></td>';
    tbody.appendChild(tr);
  } else if (visibleCount > 0 && noResultsRow) {
    noResultsRow.remove();
  }
}

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

document.addEventListener('DOMContentLoaded', function() {
  console.log('=== MODAL DEBUG ===');
  console.log('confirmedModal exists:', document.getElementById('confirmedModal') !== null);
  console.log('completedModal exists:', document.getElementById('completedModal') !== null);
  console.log('appointmentsModal exists:', document.getElementById('appointmentsModal') !== null);
  console.log('feedbackModal exists:', document.getElementById('feedbackModal') !== null);
  
  // Auto-open archived pets modal if there's a show parameter
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('show') === 'archived_pets') {
    openModal('archived_pets');
  }
  
  console.log('Page loaded, pagination ready');
});
</script>

<?php if (isset($_SESSION['success'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      showToast('<?= addslashes($_SESSION['success']); ?>');
    });
  </script>
  <?php unset($_SESSION['success']); ?>
<?php elseif (isset($_SESSION['error'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      let toast = document.getElementById('toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.style.cssText = 'position: fixed; bottom: 30px; right: 30px; background: #F44336; color: white; padding: 15px 20px; border-radius: 10px; z-index: 9999; font-weight: 600; display: block;';
        document.body.appendChild(toast);
      }
      toast.textContent = '<?= addslashes($_SESSION['error']); ?>';
      toast.style.display = 'block';
      setTimeout(() => toast.style.display = 'none', 3000);
    });
  </script>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>