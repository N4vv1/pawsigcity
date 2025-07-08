<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    $_SESSION['error'] = "No appointment ID specified.";
    header("Location: ../homepage/dashboard.php");
    exit;
}

// Fetch the appointment
$stmt = $mysqli->prepare("SELECT a.*, p.name AS pet_name FROM appointments a JOIN pets p ON a.pet_id = p.pet_id WHERE a.appointment_id = ? AND a.user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../homepage/dashboard.php");
    exit;
}

$appointment = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Reschedule Appointment</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <style>
    .form-container {
      max-width: 500px;
      margin: 50px auto;
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    label {
      font-weight: bold;
      display: block;
      margin-bottom: 10px;
    }

    input, button {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    button {
      background-color: #A8E6CF;
      font-weight: bold;
      cursor: pointer;
    }

    button:hover {
      background-color: #FFD3B6;
    }
  </style>
</head>
<body>

<div class="form-container">
  <h2>Reschedule Appointment for <?= htmlspecialchars($appointment['pet_name']) ?></h2>

  <form action="rescheduler-handler.php" method="POST">
    <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">

    <label for="appointment_date">New Date and Time:</label>
    <input type="datetime-local" name="appointment_date" required>

    <button type="submit">Submit</button>
  </form>
</div>

</body>
</html>
