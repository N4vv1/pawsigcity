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

    // API call
    $api_url = "http://127.0.0.1:5000/recommend";
    $payload = json_encode([
        "breed" => $valid_pet['breed'],
        "gender" => $valid_pet['gender'],
        "age" => (int)$valid_pet['age']
    ]);

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

    $response_data = json_decode($response, true);
    $recommended_package = $response_data['recommended_package'] ?? null;

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 0;
    }

    .header-wrapper {
      margin: 0;
      padding: 0;
    }

    .form-wrapper {
      margin-top: 100px; /* Pushes form below header */
    }

    .page-content {
      max-width: 800px;
      margin: 0 auto;
      padding: 30px;
      background-color: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    }

    h2, h3 {
      color: #2c3e50;
      text-align: center;
      margin-bottom: 24px;
    }

    .form-container {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .card {
      background-color: #f4f7f8;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .card strong {
      font-size: 1.1rem;
      color: #34495e;
    }

    .btn {
      margin-top: 10px;
      background-color: #A8E6CF;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      font-weight: 600;
      color: #252525;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn:hover {
      background-color: #87d7b7;
    }

    select, input[type="datetime-local"], input[type="text"], textarea {
      width: 100%;
      padding: 10px 12px;
      margin-top: 6px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      background-color: #fff;
    }

    label {
      font-weight: 600;
      color: #333;
      margin-top: 10px;
    }

    textarea {
      resize: vertical;
    }

    .alert-success, .alert-error {
      padding: 12px 16px;
      border-radius: 8px;
      font-weight: 500;
      margin-bottom: 20px;
    }

    .alert-success {
      background-color: #e0fce0;
      color: #2e7d32;
      border: 1px solid #b2dfdb;
    }

    .alert-error {
      background-color: #ffe3e3;
      color: #c62828;
      border: 1px solid #ffcdd2;
    }

    a {
      text-decoration: none;
      color: #3498db;
    }

    a:hover {
      text-decoration: underline;
    }

    .booking-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 20px;
  background: #fafafa;
  border: 1px solid #eee;
  border-radius: 12px;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.03);
}

.form-group {
  display: flex;
  flex-direction: column;
}

label i {
  margin-right: 8px;
  color: #A8E6CF;
}

input[type="datetime-local"]:focus,
input[type="text"]:focus,
select:focus,
textarea:focus {
  border-color: #A8E6CF;
  outline: none;
  box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.4);
}

.back-link {
  display: inline-block;
  color: #3498db;
  font-weight: 600;
  margin-bottom: 16px;
}

.recommendation-box {
  background-color: #e8fff3;
  border-left: 5px solid #A8E6CF;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  color: #2c3e50;
}

.recommend {
  color: #16a085;
  font-weight: bold;
}

.submit-btn {
  display: inline-block;
  font-size: 1.05rem;
  padding: 12px 20px;
  background-color: #A8E6CF;
  border-radius: 8px;
  color: #252525;
  border: none;
  font-weight: bold;
  transition: all 0.3s ease;
}

.submit-btn:hover {
  background-color: #87d7b7;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
select,
input[type="datetime-local"],
input[type="text"],
textarea {
  width: 100%;
  padding: 12px 16px;
  margin-top: 6px;
  font-size: 1rem;
  font-family: inherit;
  border: 1px solid #ccc;
  border-radius: 10px;
  background-color: #fff;
  color: #333;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
  transition: all 0.25s ease-in-out;
}

select:focus,
input[type="datetime-local"]:focus,
input[type="text"]:focus,
textarea:focus {
  border-color: #A8E6CF;
  box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.4);
  outline: none;
  background-color: #fcfffc;
}

  </style>
</head>
<body>
  <div class="header-wrapper">
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
  </div>
    <div style="height: 60px;"></div>
  <div class="form-wrapper">
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
        <div class="form-container">
  <a href="book-appointment.php" class="back-link">← Choose another pet</a>

  <form method="POST" action="appointment-handler.php" class="booking-form">
    <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">

    <?php if ($recommended_package): ?>
      <div class="recommendation-box">
        🐾 Recommended Package for <strong><?= htmlspecialchars($valid_pet['name']) ?></strong>:
        <span class="recommend"><?= htmlspecialchars($recommended_package) ?></span>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label for="package_id"><i class="fas fa-box"></i> Select Grooming Package:</label>
      <select name="package_id" id="package_id" required>
        <?php while ($pkg = $packages_result->fetch_assoc()): ?>
          <option value="<?= $pkg['id'] ?>" <?= $package_id == $pkg['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($pkg['name']) ?> - ₱<?= number_format($pkg['price'], 2) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="appointment_date"><i class="fas fa-calendar-alt"></i> Appointment Date and Time:</label>
      <input type="datetime-local" name="appointment_date" id="appointment_date" required>
    </div>

    <div class="form-group">
      <label for="groomer_name"><i class="fas fa-user"></i> Preferred Groomer (optional):</label>
      <input type="text" name="groomer_name" id="groomer_name" placeholder="Enter name...">
    </div>

    <div class="form-group">
      <label for="notes"><i class="fas fa-sticky-note"></i> Notes (optional):</label>
      <textarea name="notes" id="notes" rows="3" placeholder="Any special instructions..."></textarea>
    </div>

    <button type="submit" class="btn submit-btn">📅 Book Appointment</button>
  </form>
</div>
      <?php endif; ?>

    </div>
  </div>
</body>
</html>