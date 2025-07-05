<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$appointment_id = $_GET['appointment_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$appointment_id) {
    echo "Invalid appointment.";
    exit;
}

// Fetch appointment and package info
$stmt = $mysqli->prepare("SELECT a.*, p.name AS pet_name, pk.name AS package_name, pk.price 
                          FROM appointments a
                          JOIN pets p ON a.pet_id = p.pet_id
                          JOIN packages pk ON a.package_id = pk.id
                          WHERE a.appointment_id = ? AND a.user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    echo "Appointment not found.";
    exit;
}

$amount = $appointment['price'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Confirm Payment</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <style>
    .container {
      max-width: 600px;
      margin: 40px auto;
      padding: 30px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    label { font-weight: bold; margin-top: 15px; display: block; }
    input, select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 8px; border: 1px solid #ccc; }
    .btn { margin-top: 20px; padding: 12px; background: #A8E6CF; border: none; border-radius: 5px; font-weight: bold; width: 100%; }
    .btn:hover { background: #FFD3B6; }
  </style>
</head>
<body>

<div class="container">
  <h2>Payment for <?= htmlspecialchars($appointment['package_name']) ?></h2>
  <p><strong>Pet:</strong> <?= htmlspecialchars($appointment['pet_name']) ?></p>
  <p><strong>Amount:</strong> ₱<?= number_format($amount, 2) ?></p>
  <p><strong>Appointment Date:</strong> <?= htmlspecialchars($appointment['appointment_date']) ?></p>

  <form action="payment-handler.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="appointment_id" value="<?= $appointment_id ?>">
    <input type="hidden" name="amount" value="<?= $amount ?>">

    <label for="method">Payment Method:</label>
    <select name="method" id="method" required>
      <option value="">Select</option>
      <option value="gcash">GCash</option>
      <option value="cash">Cash on Visit</option>
    </select>

    <label>Upload GCash Screenshot:</label>
    <input type="file" name="gcash_screenshot" accept="image/*">

    <label for="paid_at">Payment Date/Time (GCash):</label>
    <input type="datetime-local" name="paid_at">

    <button type="submit" class="btn">Submit Payment</button>
  </form>
</div>

</body>
</html>
