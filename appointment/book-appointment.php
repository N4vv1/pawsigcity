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
    // Check pet ownership
    $pet_check_stmt = $mysqli->prepare("SELECT * FROM pets WHERE pet_id = ? AND user_id = ?");
    $pet_check_stmt->bind_param("ii", $selected_pet_id, $user_id);
    $pet_check_stmt->execute();
    $valid_pet = $pet_check_stmt->get_result()->fetch_assoc();

    if (!$valid_pet) {
        echo "<p style='text-align:center;color:red;'>Invalid pet selection.</p>";
        exit;
    }

    // Prepare JSON payload for API
    $api_url = "http://127.0.0.1:5000/recommend";
    $payload = json_encode([
        "breed" => $valid_pet['breed'],
        "gender" => $valid_pet['gender'],
        "age" => (int)$valid_pet['age']
    ]);

    // Make cURL POST request
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    $response = curl_exec($ch);
    curl_close($ch);

    // Decode result
    $response_data = json_decode($response, true);
    $recommended_package = $response_data['recommended_package'] ?? null;

    // Load packages
    $packages_stmt = $mysqli->prepare("SELECT * FROM packages WHERE is_active = 1");
    $packages_stmt->execute();
    $packages_result = $packages_stmt->get_result();
}

if (isset($response_data['error'])) {
    $recommended_package = null;
    $_SESSION['error'] = "⚠️ Recommendation not available for this breed: " . htmlspecialchars($valid_pet['breed']);
}

?>
<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment</title>
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
    background: #f9f9f9;
  }

 h2 {
  color: #444;
  margin-top: 50px; /* 🔼 Push it down more */
  margin-bottom: 1rem;
  text-align: center;
}
  h3 {
    color: #444;
    margin-bottom: 1rem;
  }

 .form-container {
  max-width: 960px;
  margin: 60px auto 40px;
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}


  .card {
    border: 2px solid var(--secondary-color);
    padding: 20px 25px;
    border-radius: 10px;
    background: var(--secondary-color);
    margin: 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
  }

  .card strong {
    font-size: 1.2rem;
    color: #333;
  }

  .btn {
    background: var(--primary-color);
    border: none;
    color: #333;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .btn:hover {
    background: #91d6b8;
  }

  form {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
  }

  select,
  input[type="text"],
  input[type="datetime-local"],
  textarea {
    padding: 12px 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    width: 100%;
    transition: border-color 0.3s ease;
  }

  select:focus,
  input:focus,
  textarea:focus {
    border-color: var(--primary-color);
    outline: none;
  }

  textarea {
    resize: vertical;
    min-height: 80px;
  }

  .alert-success {
    background-color: #d4edda;
    color: #155724;
    padding: 15px 20px;
    margin: 30px auto 10px;
    width: 90%;
    border-left: 6px solid #28a745;
    border-radius: 8px;
  }

  .alert-error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 15px 20px;
    margin: 30px auto 10px;
    width: 90%;
    border-left: 6px solid #dc3545;
    border-radius: 8px;
  }

  a {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.95rem;
    margin-bottom: 15px;
  }

  a:hover {
    text-decoration: underline;
  }

  @media (max-width: 768px) {
    .card {
      flex-direction: column;
      align-items: flex-start;
    }

    .btn {
      width: 100%;
      text-align: center;
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
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link ">Home</a></li>
        <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
        <li class="nav-item"><a href="#service" class="nav-link active">Services</a></li>
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

<!-- ✅ Start page content wrapper -->
<div class="page-content">

<h2>Book a Grooming Appointment</h2>

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

      <?php if ($recommended_package): ?>
        <p style="font-weight: bold; color: #333;">
          🐾 Recommended Package for <?= htmlspecialchars($valid_pet['name']) ?>:
          <span style="color: #2c3e50;"><?= htmlspecialchars($recommended_package) ?></span>
        </p>
      <?php endif; ?>


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

</div> <!-- ✅ End page-content -->

</body>
</html>