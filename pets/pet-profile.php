<?php
session_start();
require '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/loginform.php');
  exit;
}

$user_id = $_SESSION['user_id'];

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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #F9F9F9;
      margin: 0;
      padding: 0;
    }

    .add-pet-button {
      display: block;
      width: fit-content;
      margin: 20px auto;
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
      margin: 120px auto 60px;
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
      display: flex;
      flex-direction: column;
      border: 1px solid #ddd;
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 40px;
      background: #fff;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .pet-header {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 24px;
    }

    .pet-header img {
      border-radius: 50%;
      width: 100px;
      height: 100px;
      object-fit: cover;
      border: 3px solid #eee;
    }

    .pet-details {
      flex-grow: 1;
    }

    .pet-details h3 {
      margin: 0;
      font-size: 24px;
      color: #2c3e50;
    }

    .pet-meta {
      margin-top: 8px;
      color: #555;
      font-size: 14px;
    }

    .actions {
      margin-top: 10px;
    }

    .edit-button, form button {
      margin-right: 10px;
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

    .tabs {
      background: #f9f9f9;
      padding: 10px 15px;
      border-radius: 12px;
      display: flex;
      gap: 10px;
      margin-bottom: 16px;
      justify-content: center;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
      flex-wrap: wrap;
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
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
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

    .section {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 20px;
      line-height: 1.6;
      color: #444;
      margin-bottom: 10px;
    }

    .form-wrapper {
      background-color: #F1FAEE;
      padding: 20px;
      border-radius: 12px;
      margin-top: 15px;
      border: 2px dashed #A8DADC;
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

    @media (max-width: 600px) {
      .pet-header {
        flex-direction: column;
        text-align: center;
      }

      .pet-header img {
        width: 80px;
        height: 80px;
      }

      .section {
        grid-template-columns: 1fr;
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
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Home</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link">About</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Services</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Gallery</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Contact</a></li>
      <li class="nav-item dropdown">
        <a href="#" class="nav-link profile-icon active">
          <i class="fas fa-user-circle"></i>
        </a>
        <ul class="dropdown-menu">
          <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
          <li><a href="add-pet.php">Add Pet</a></li>
          <li><a href="../homepage/appointments.php">Appointments</a></li>
          <li><a href="../homepage/logout/logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </nav>
</header>

<div style="height: 60px;"></div>
<div class="profile-card">
  <h2>Pet Profile</h2>

  <?php if ($pets->num_rows > 0): ?>
    <?php while ($pet = $pets->fetch_assoc()):
      $pet_id = $pet['pet_id'];
      $health = $mysqli->query("SELECT * FROM health_info WHERE pet_id = $pet_id")->fetch_assoc();
      $behavior = $mysqli->query("SELECT * FROM behavior_preferences WHERE pet_id = $pet_id")->fetch_assoc();
    ?>
    <div class="pet-profile" id="pet-<?= $pet_id ?>">
      <div class="pet-header">
        <img src="../<?= htmlspecialchars($pet['photo_url']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>"
             onerror="this.onerror=null;this.src='../uploads/default.jpg';">
        <div class="pet-details">
          <h3><?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['breed']) ?>)</h3>
          <div class="pet-meta">
            Age: <?= htmlspecialchars($pet['age']) ?> |
            Gender: <?= htmlspecialchars($pet['gender']) ?> |
            Color: <?= htmlspecialchars($pet['color']) ?><br>
            Birthday: <?= htmlspecialchars($pet['birthday']) ?>
          </div>
          <div class="actions">
            <a href="#" class="edit-button" data-id="<?= $pet_id ?>">✏️ Edit</a>
            <form action="delete-pet.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this pet?');">
              <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
              <button type="submit">🗑 Delete</button>
            </form>
          </div>
        </div>
      </div>

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

      <div class="tabs">
        <div class="tab active" data-tab="health-<?= $pet_id ?>">Health Info</div>
        <div class="tab" data-tab="behavior-<?= $pet_id ?>">Behavior & Preferences</div>
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
    </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>You haven’t added any pets yet. <a href="add-pet.php">Add one now</a>.</p>
  <?php endif; ?>
</div>

<script>
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
  