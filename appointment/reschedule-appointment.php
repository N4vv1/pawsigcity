<?php
require '../db.php';

if (!isset($_GET['id'])) {
  echo "Invalid appointment ID.";
  exit;
}

$appointment_id = (int)$_GET['id'];
$result = $mysqli->query("SELECT * FROM appointments WHERE appointment_id = $appointment_id");
$appointment = $result->fetch_assoc();

if (!$appointment) {
  echo "Appointment not found.";
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Reschedule Appointment</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    :root {
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f9f9f9;
    }

    .page-content {
      padding-top: 120px;
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #444;
    }

    .form-container {
      max-width: 600px;
      margin: 0 auto 50px;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    label {
      font-weight: 600;
      color: #333;
      display: block;
      margin-bottom: 8px;
    }

    input[type="datetime-local"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
    }

    input[type="datetime-local"]:focus {
      border-color: var(--primary-color);
      outline: none;
    }

    button {
      background: var(--primary-color);
      color: #333;
      font-weight: bold;
      border: none;
      padding: 12px 20px;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #91d6b8;
    }

    @media (max-width: 768px) {
      .form-container {
        margin: 20px;
        padding: 20px;
      }
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
        <li class="nav-item"><a href="#home" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
        <li class="nav-item"><a href="#service" class="nav-link">Services</a></li>
        <li class="nav-item"><a href="#gallery" class="nav-link">Gallery</a></li>
        <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link profile-icon">
            <i class="fas fa-user-circle"></i>
          </a>
          <ul class="dropdown-menu">
            <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
            <li><a href="../homepage/logout/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <div class="page-content">
    <h2>Reschedule Appointment</h2>

    <div class="form-container">
      <form method="POST" action="reschedule-handler.php">
        <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">

        <label for="appointment_date">New Date and Time:</label>
        <input type="datetime-local" name="appointment_date" id="appointment_date"
          value="<?= date('Y-m-d\TH:i', strtotime($appointment['appointment_date'])) ?>" required>

        <button type="submit">Update Appointment</button>
      </form>
    </div>
  </div>
</body>
</html>
