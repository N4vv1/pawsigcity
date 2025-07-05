<?php
session_start();
require '../db.php';

$user_id = 1; // Replace with $_SESSION['user_id'] when login is active

$pets = $mysqli->query("SELECT * FROM pets WHERE user_id = $user_id");
$packages = $mysqli->query("SELECT * FROM packages WHERE is_active = 1");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment</title>
  <link rel="stylesheet" href="../homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>

    h2 {
      text-align: center;
      color: #252525;
      margin-bottom: 30px;
    }

    .form-container {
      background: white;
      max-width: 500px;
      margin: 0 auto;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .form-container label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      color: #252525;
    }

    .form-container input,
    .form-container select,
    .form-container textarea {
      width: 100%;
      padding: 10px 14px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
    }

    .form-container button {
      width: 100%;
      background-color: #A8E6CF;
      color: #252525;
      padding: 12px;
      border: none;
      border-radius: 30px;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      transition: 0.3s;
    }

    .form-container button:hover {
      background-color: #FFE29D;
    }

    .alert-success {
      width: fit-content;
      margin: 0 auto 20px;
      background-color: #FFE29D;
      color: #252525;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-align: center;
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
      <li class="nav-item"><a href="#home" class="nav-link active">Home</a></li>
      <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
      <li class="nav-item"><a href="#service" class="nav-link">Services</a></li>
      <li class="nav-item"><a href="#gallery" class="nav-link">Gallery</a></li>
      <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
      <li class="nav-item"><a href="../pets/pet-profile.php" class="nav-link">Pet</a></li>
      <li class="nav-item"><a href="./logout/logout.php" class="logout-button">Logout</a></li>
    </ul>
  </nav>
</header>

<?php if (isset($_SESSION['success'])): ?>
  <div class="alert-success"><?= $_SESSION['success'] ?></div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<h2>Book a Grooming Appointment</h2>

<div class="form-container">
  <form action="appointment-handler.php" method="POST">
    <label for="pet_id">Choose your pet:</label>
    <select name="pet_id" id="pet_id" required>
      <?php while ($pet = $pets->fetch_assoc()): ?>
        <option value="<?= $pet['pet_id'] ?>">
          <?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['breed']) ?>)
        </option>
      <?php endwhile; ?>
    </select>

    <label for="package_id">Select Grooming Package:</label>
    <select name="package_id" id="package_id" required>
      <?php while ($package = $packages->fetch_assoc()): ?>
        <option value="<?= $package['id'] ?>">
          <?= htmlspecialchars($package['name']) ?> - ₱<?= number_format($package['price'], 2) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <label for="appointment_date">Appointment Date and Time:</label>
    <input type="datetime-local" name="appointment_date" id="appointment_date" required>

    <label for="groomer_name">Preferred Groomer (optional):</label>
    <input type="text" name="groomer_name" id="groomer_name">

    <label for="notes">Notes (optional):</label>
    <textarea name="notes" id="notes" rows="3"></textarea>

    <button type="submit">Book Appointment</button>
  </form>
</div>

</body>
</html>
