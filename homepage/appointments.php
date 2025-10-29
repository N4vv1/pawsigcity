<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
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
    padding: 20px 40px 30px;
    box-sizing: border-box;
  }

  .table-container {
    width: 100%;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 25px 45px rgba(0,0,0,0.15);
    padding: 50px 60px;
    position: relative;
    margin-top: 150px;
  }

  .table-container::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 8px;
    width: 100%;
    border-radius: 14px 14px 0 0;
    background: linear-gradient(to right, #A8E6CF, #FFE29D, #FFB6B9);
  }

  .button {
    padding: 8px 14px;
    background-color: #A8E6CF;
    color: #252525;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: background-color 0.3s;
    margin: 5px 3px;
    display: inline-block;
  }

  .button:hover {
    background-color: #87d7b7;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-radius: 12px;
    overflow: hidden;
    margin-top: 20px;
    margin-bottom: 40px;
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
    position: absolute;
    top: 100px;
    left: 30px;
    background: none;
    border: none;
    color: var(--dark);
    font-size: 22px;
    text-decoration: none;
    transition: color 0.3s ease;
  }

  .back-button:hover {
    color: var(--primary-dark);
    transform: translateX(-3px);
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
  }

  .empty-state i {
    font-size: 64px;
    color: #ccc;
    margin-bottom: 20px;
  }

  .empty-state h3 {
    color: #333;
    margin-bottom: 10px;
  }

  .debug-info {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    font-family: monospace;
  }

  @media (max-width: 768px) {
    .back-button {
      top: 80px;
      left: 20px;
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
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-home"></i>Home</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-info-circle"></i>About</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-concierge-bell"></i>Services</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-images"></i>Gallery</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-envelope"></i>Contact</a></li>
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

  <!-- Back Button -->
  <div class="section-header" style="display: flex; justify-content: flex-start; margin-top: 40px; margin-bottom: 20px;">
    <a href="main.php" class="back-button"><i class="fas fa-arrow-left"> BACK</i></a>
  </div>

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
              <td><?= htmlspecialchars(date("M d, Y h:i A", strtotime($row['appointment_date']))) ?></td>
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
                  <button class="button" type="button" onclick="openRescheduleModal(<?= $row['appointment_id'] ?>)">Reschedule</button>
                  <button class="button" type="button" onclick="openCancelModal(<?= $row['appointment_id'] ?>)">Cancel</button>
                <?php endif; ?>

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
<div id="cancelModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
  <div style="background:#fff; padding:30px; border-radius:12px; width:400px; position:relative;">
    <h3>Cancel Appointment</h3>
    <form action="../appointment/cancel-appointment.php" method="POST">
      <input type="hidden" name="appointment_id" id="cancel_appointment_id">
      <textarea name="cancel_reason" required placeholder="Reason for cancellation..." style="width:100%; padding:10px; border-radius:8px; margin:15px 0;"></textarea>
      <div style="text-align:right;">
        <button type="button" onclick="closeCancelModal()" style="margin-right:10px; background:#ccc;" class="button">Close</button>
        <button type="submit" class="button">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
  <div style="background:#fff; padding:30px; border-radius:12px; width:400px; position:relative;">
    <h3>Reschedule Appointment</h3>
    <form action="../appointment/rescheduler-handler.php" method="POST">
      <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
      <label for="appointment_date">New Date & Time:</label>
      <input type="datetime-local" name="appointment_date" required style="width:100%; padding:10px; margin:10px 0; border-radius:8px;">
      <div style="text-align:right;">
        <button type="button" onclick="closeRescheduleModal()" style="margin-right:10px; background:#ccc;" class="button">Close</button>
        <button type="submit" class="button">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
  <div style="background:#fff; padding:30px; border-radius:16px; width:420px; position:relative;">
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
      <select name="rating" required style="width:100%; padding:10px; border-radius:8px; font-size:14px;">
        <option value="">Choose</option>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
        <?php endfor; ?>
      </select>

      <label style="font-weight: 600; margin-top: 15px;">Comments <small>(minimum 5 words)</small>:</label>
      <textarea name="feedback" id="feedback_text" required placeholder="E.g. I loved how gentle the groomer was with my dog." style="width:100%; padding:10px; border-radius:8px; margin:10px 0;"></textarea>

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