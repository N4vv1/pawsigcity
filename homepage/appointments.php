<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$result = $mysqli->prepare("SELECT a.*, p.name AS pet_name, pk.name AS package_name
                            FROM appointments a
                            JOIN pets p ON a.pet_id = p.pet_id
                            JOIN packages pk ON a.package_id = pk.id
                            WHERE a.user_id = ?
                            ORDER BY a.appointment_date DESC");
$result->bind_param("i", $user_id);
$result->execute();
$appointments = $result->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Your Appointments</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="style.css">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f2f2f2;
      margin: 0;
      padding-top: 90px; /* offset for fixed navbar */
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      background: #A8E6CF;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 40px;
    }

    .navbar-logo img {
      height: 50px;
    }

    .nav-menu {
      list-style: none;
      display: flex;
      gap: 20px;
      margin: 0;
      padding: 0;
    }

    .nav-link {
      text-decoration: none;
      color: #333;
      font-weight: 600;
    }

    .nav-link.active,
    .nav-link:hover {
      color: #2a9d8f;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      background: white;
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    .dropdown:hover .dropdown-menu {
      display: block;
    }

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
      max-width: 1100px;
      margin: auto;
      padding: 20px;
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
  </style>
</head>
<body>

<header>
  <nav class="navbar section-content">
    <a href="#" class="navbar-logo">
      <img src="../homepage/images/Logo.jpg" alt="Logo" class="icon" />
    </a>
    <ul class="nav-menu">
      <li><a href="../homepage/main.php" class="nav-link">Home</a></li>
      <li><a href="../homepage/main.php" class="nav-link">About</a></li>
      <li><a href="../homepage/main.php" class="nav-link active">Services</a></li>
      <li><a href="../homepage/main.php" class="nav-link">Gallery</a></li>
      <li><a href="../homepage/main.php" class="nav-link">Contact</a></li>
      <li class="nav-item dropdown">
        <a href="#" class="nav-link profile-icon">
          <i class="fas fa-user-circle"></i>
        </a>
        <ul class="dropdown-menu">
          <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
          <li><a href="../pets/add-pet.php">Add Pet</a></li>
          <li><a href="../appointment/book-appointment.php">Book</a></li>
          <li><a href="../homepage/appointments.php">Appointments</a></li>
          <li><a href="../homepage/logout/logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </nav>
</header>

<div class="container">
  <?php if (isset($_SESSION['success'])): ?>
    <p class="success-message"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
  <?php endif; ?>

  <div style="margin-top: 60px;">
  <a href="main.php" class="button" style="margin-left: -400px;">⬅ Back</a>
</div>

  <h2>🐾 Your Appointments</h2>

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
      <?php while ($row = $appointments->fetch_assoc()): ?>
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
              <a class="button" href="./feedback/leave-feedback.php?id=<?= $row['appointment_id'] ?>">⭐ Feedback</a>
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

  // Close modal if background is clicked
  window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
      closeCancelModal();
      closeRescheduleModal();
    }
  };
</script>

</body>
</html>
