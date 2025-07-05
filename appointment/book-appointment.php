<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : null;
$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : null;

// Fetch user's pets securely
$pets_stmt = $mysqli->prepare("SELECT * FROM pets WHERE user_id = ?");
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$pets_result = $pets_stmt->get_result();

// If pet is selected, check if the pet belongs to the user
if ($selected_pet_id) {
    $pet_check_stmt = $mysqli->prepare("SELECT * FROM pets WHERE pet_id = ? AND user_id = ?");
    $pet_check_stmt->bind_param("ii", $selected_pet_id, $user_id);
    $pet_check_stmt->execute();
    $valid_pet = $pet_check_stmt->get_result()->fetch_assoc();

    if (!$valid_pet) {
        echo "<p style='text-align:center;color:red;'>Invalid pet selection.</p>";
        exit;
    }

    // Load packages only if pet is valid
    $packages_stmt = $mysqli->prepare("SELECT * FROM packages WHERE is_active = 1");
    $packages_stmt->execute();
    $packages_result = $packages_stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <style>
    .card { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .btn { padding: 10px 20px; background: #A8E6CF; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .btn:hover { background: #FFD3B6; }
    .form-container { max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
    label { font-weight: bold; margin-top: 15px; display: block; }
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      padding: 12px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 20px;
    }
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      padding: 12px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<h2 style="text-align:center;">Book a Grooming Appointment</h2>

<?php if (isset($_SESSION['success'])): ?>
  <div class="alert-success"><?= $_SESSION['success'] ?></div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="alert-error"><?= $_SESSION['error'] ?></div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!$selected_pet_id): ?>
  <!-- Step 1: Choose pet -->
  <div class="form-container">
    <h3>Choose a pet to book an appointment for:</h3>
    <?php while ($pet = $pets_result->fetch_assoc()): ?>
      <div class="card">
        <strong><?= htmlspecialchars($pet['name']) ?></strong> (<?= htmlspecialchars($pet['breed']) ?>)
        <form method="GET" action="book-appointment.php" style="margin-top:10px;">
          <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
          <button class="btn" type="submit">Book for this Pet</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>

<?php else: ?>
  <!-- Step 2: Booking Form -->
  <div class="form-container">
    <a href="book-appointment.php" style="display:block;margin-bottom:20px;">← Choose another pet</a>

    <form method="POST" action="appointment-handler.php">
      <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">

      <label for="package_id">Select Grooming Package:</label>
      <select name="package_id" id="package_id" required>
        <?php while ($pkg = $packages_result->fetch_assoc()): ?>
          <option value="<?= $pkg['id'] ?>" <?= $package_id == $pkg['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($pkg['name']) ?> - ₱<?= number_format($pkg['price'], 2) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label for="appointment_date">Appointment Date and Time:</label>
      <input type="datetime-local" name="appointment_date" id="appointment_date" required>

      <label for="groomer_name">Preferred Groomer (optional):</label>
      <input type="text" name="groomer_name" id="groomer_name">

      <label for="notes">Notes (optional):</label>
      <textarea name="notes" id="notes" rows="3"></textarea>

      <button type="submit" class="btn">Book Appointment</button>
    </form>
  </div>
<?php endif; ?>

</body>
</html>
