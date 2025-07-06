<?php
session_start();
require '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/loginform.php');
  exit;
}

$user_id = $_SESSION['user_id']; // get user ID from session

// Query to get user's pets
$pets = $mysqli->query("SELECT * FROM pets WHERE user_id = $user_id");

if (!$pets) {
  echo "Query Error: " . $mysqli->error;
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pet Profile</title>
  <link rel="stylesheet" href="../homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      background-color: #F9F9F9;
      margin: 0;
      padding: 0;
    }

    .add-pet-button {
      display: block;
      width: fit-content;
      margin: 160px auto 20px;
      background-color: #6FCF97;
      color: white;
      padding: 10px 20px;
      border-radius: 50px;
      font-weight: bold;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: background-color 0.3s ease;
    }

    .add-pet-button:hover {
      background-color: #56b67e;
    }

    .profile-card {
      max-width: 850px;
      margin: 0 auto 60px;
      background-color: #fff;
      padding: 40px;
      border-radius: 18px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease-in-out;
    }

    .profile-card h2 {
      text-align: center;
      color: #2a2a2a;
      font-size: 28px;
      margin-bottom: 35px;
    }

    .pet-profile {
      background: #ffffff;
      border: 1px solid #e0e0e0;
      padding: 25px;
      border-radius: 14px;
      margin-bottom: 35px;
      transition: box-shadow 0.3s;
    }

    .pet-profile:hover {
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }

    .pet-profile h3 {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 0;
      font-size: 20px;
      color: #333;
    }

    .edit-button {
      background-color: #ffd166;
      padding: 6px 14px;
      border-radius: 10px;
      color: #222;
      font-weight: 600;
      text-decoration: none;
      transition: background 0.3s;
    }

    .edit-button:hover {
      background-color: #a8e6cf;
    }

    form button {
      background: #dc3545;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }

    form button:hover {
      background: #c82333;
    }

    .section {
      margin: 18px 0;
      line-height: 1.6;
      color: #444;
    }

    .tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin: 25px 0 10px;
    }

    .tab {
      background-color: #eeeeee;
      padding: 10px 20px;
      border-radius: 20px;
      cursor: pointer;
      font-weight: 600;
      color: #555;
      transition: all 0.3s;
    }

    .tab:hover {
      background-color: #ffd166;
      color: #000;
    }

    .tab.active {
      background-color: #a8e6cf;
      color: #000;
    }

    .tab-content {
      display: none;
      border-top: 1px solid #ddd;
      padding-top: 15px;
      margin-top: 10px;
    }

    .tab-content.active {
      display: block;
    }

    .pet-profile img {
      border: 3px solid #ccc;
      border-radius: 50%;
      width: 80px;
      height: 80px;
      object-fit: cover;
      margin-right: 15px;
      vertical-align: middle;
    }

    .form-wrapper {
      background-color: #A8E6CF;
      padding: 20px;
      border-radius: 12px;
      margin-top: 15px;
    }

    .form-wrapper input,
    .form-wrapper select {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      width: 100%;
      margin-bottom: 10px;
    }

    .form-submit-btn {
      background-color: #FFE29D;
      padding: 8px 20px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      color: #333;
    }

    .form-submit-btn:hover {
      background-color: #fdd87c;
    }

    hr {
      border: none;
      border-top: 1px solid #ddd;
      margin: 25px 0;
    }
  </style>
</head>
<body>

<!-- Navbar -->
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
          <li><a href="./logout/logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </nav>
</header>

<!-- Main Section -->
<div class="profile-card">
  <a href="add-pet.php" class="add-pet-button">➕ Add Pet</a>
  <h2>My Pet Profile</h2>

  <?php if ($pets->num_rows > 0): ?>
    <?php while ($pet = $pets->fetch_assoc()):
      $pet_id = $pet['pet_id'];
      $health = $mysqli->query("SELECT * FROM health_info WHERE pet_id = $pet_id")->fetch_assoc();
      $behavior = $mysqli->query("SELECT * FROM behavior_preferences WHERE pet_id = $pet_id")->fetch_assoc();
      $history = $mysqli->query("SELECT * FROM grooming_history WHERE pet_id = $pet_id ORDER BY history_id DESC LIMIT 5");
    ?>

    <div class="pet-profile" id="pet-<?= $pet_id ?>">
      <h3>
        <img src="../<?= htmlspecialchars($pet['photo_url']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>"
             onerror="this.onerror=null;this.src='../uploads/default.jpg';">
        <?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['breed']) ?>)

        <a href="#" class="edit-button" data-id="<?= $pet_id ?>">✏️ Edit</a>
        <form action="delete-pet.php" method="POST" style="float:right; margin-right:10px;" onsubmit="return confirm('Delete this pet?');">
          <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
          <button type="submit">🗑 Delete</button>
        </form>
      </h3>

      <!-- 🟢 EDIT FORM INCLUDED HERE -->
      <div id="edit-form-<?= $pet_id ?>" class="form-wrapper" style="display:none;">
        <form action="update-pet.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
          <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" placeholder="Pet Name" required>
          <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>" placeholder="Breed" required>
          <input type="number" name="age" value="<?= htmlspecialchars($pet['age']) ?>" placeholder="Age" required>
          <input type="text" name="color" value="<?= htmlspecialchars($pet['color']) ?>" placeholder="Color" required>
          <select name="gender" required>
            <option value="Male" <?= $pet['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $pet['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
          </select>
          <input type="date" name="birthday" value="<?= htmlspecialchars($pet['birthday']) ?>" required>
          <input type="file" name="photo_url">
          <button type="submit" class="form-submit-btn">Update Pet</button>
        </form>
      </div>

      <!-- Pet Info -->
      <div class="section">
        <strong>Age:</strong> <?= htmlspecialchars($pet['age']) ?><br><br>
        <strong>Birthday:</strong> <?= htmlspecialchars($pet['birthday']) ?><br><br>
        <strong>Gender:</strong> <?= htmlspecialchars($pet['gender']) ?><br><br>
        <strong>Color:</strong> <?= htmlspecialchars($pet['color']) ?><br>
      </div>

      <div class="tabs">
        <div class="tab active" data-tab="health-<?= $pet_id ?>">Health Info</div>
        <div class="tab" data-tab="behavior-<?= $pet_id ?>">Behavior & Preferences</div>
        <div class="tab" data-tab="grooming-<?= $pet_id ?>">Grooming History</div>
      </div>

      <div class="tab-content active" id="health-<?= $pet_id ?>">
        <div class="section"><strong>Allergies:</strong> <?= $health['allergies'] ?? 'None' ?></div>
        <div class="section"><strong>Medications:</strong> <?= $health['medications'] ?? 'None' ?></div>
        <div class="section"><strong>Medical Conditions:</strong> <?= $health['medical_conditions'] ?? 'None' ?></div>
      </div>

      <div class="tab-content" id="behavior-<?= $pet_id ?>">
        <div class="section"><strong>Behavior Notes:</strong> <?= $behavior['behavior_notes'] ?? 'None' ?></div>
        <div class="section"><strong>Nail Trimming:</strong> <?= $behavior['nail_trimming'] ?? 'Not specified' ?></div>
        <div class="section"><strong>Haircut Style:</strong> <?= $behavior['haircut_style'] ?? 'None' ?></div>
      </div>

      <div class="tab-content" id="grooming-<?= $pet_id ?>">
        <?php while ($row = $history->fetch_assoc()): ?>
          <div class="section">
            <strong>Date:</strong> <?= $row['summary'] ?><br>
            <strong>Notes:</strong> <?= $row['notes'] ?? 'N/A' ?><br>
            <strong>Tips:</strong> <?= $row['tips_for_next_time'] ?? 'None' ?><hr>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
    <hr><br>
    <?php endwhile; ?>
  <?php else: ?>
    <p>You haven’t added any pets yet. <a href="add-pet.php">Add one now</a>.</p>
  <?php endif; ?>
</div>

<script>
  // Tab switch logic
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const petId = tab.dataset.tab.split('-')[1];
      const wrapper = document.querySelector(`#pet-${petId}`);
      wrapper.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      wrapper.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      wrapper.querySelector(`#${tab.dataset.tab}`).classList.add('active');
    });
  });

  // Edit toggle
  document.querySelectorAll('.edit-button').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const form = document.getElementById('edit-form-' + btn.dataset.id);
      form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });
  });
</script>

</body>
</html>
