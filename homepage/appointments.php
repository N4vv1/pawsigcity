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
  <title>User Dashboard</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
   <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/Logo.jpg" alt="Logo" class="icon" />
      </a>
      <ul class="nav-menu">
        <li class="nav-item"><a href="#home" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
        <li class="nav-item"><a href="#service" class="nav-link">Services</a></li>
        <li class="nav-item"><a href="#gallery" class="nav-link">Gallery</a></li>
        <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link profile-icon active">
            <i class="fas fa-user-circle"></i>
          </a>
          <ul class="dropdown-menu">
            <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="../homepage/logout/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

<a href="main.php" class="button" style="margin-bottom: 15px; display: inline-block;">⬅ Back</a>

<h2>🐾 Your Appointments</h2>

<?php if (isset($_SESSION['success'])): ?>
  <p style="color: green;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Pet</th>
      <th>Service</th>
      <th>Date & Time</th>
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
        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
        <td>
  <?php if ($row['status'] === 'cancelled'): ?>
    <span class="badge pending" style="background: #f8d7da; color: #721c24;">Cancelled</span>
  <?php elseif ($row['is_approved']): ?>
    <span class="badge approved">Approved</span>
  <?php else: ?>
    <span class="badge pending">Waiting for Approval</span>
  <?php endif; ?>
</td>
        <td><?= ucfirst($row['status']) ?></td>
        <td><?= !empty($row['notes']) ? nl2br(htmlspecialchars($row['notes'])) : '<em>No notes yet.</em>' ?></td>
        <td>
  <?php if ($row['status'] !== 'completed' && $row['status'] !== 'cancelled'): ?>
    <a class="button" href="../appointment/reschedule-appointment.php?id=<?= $row['appointment_id'] ?>">Reschedule</a>
    <a class="button" href="../appointment/cancel-request.php?id=<?= $row['appointment_id'] ?>" onclick="return confirm('Are you sure you want to cancel this appointment?');">Cancel</a>
  <?php endif; ?>

  <!-- Feedback logic -->
  <?php if ($row['status'] === 'completed' && is_null($row['rating'])): ?>
    <a class="button" href="./feedback/leave-feedback.php?id=<?= $row['appointment_id'] ?>">⭐ Leave Feedback</a>
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

</body>
</html>
