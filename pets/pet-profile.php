<?php
session_start();
require '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/loginform.php');
    exit;
}

$user_id = intval($_SESSION['user_id']); // sanitize

// Query to get user's pets using pg_query_params
$pets = pg_query_params($conn, "SELECT * FROM pets WHERE user_id = $1", [$user_id]);

if (!$pets) {
    echo "Query Error: " . pg_last_error($conn);
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAWsig City | Pet Profile</title>
  <link rel="stylesheet" href="../homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../homepage/images/pawsig.png>
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
  max-width: 1200px;      /* wider container */
  margin: 120px auto 60px;
  background-color: #fff;
  padding: 50px 60px;
  border-radius: 18px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease-in-out;
}

.profile-card h2 {
  text-align: center;
  color: #2a2a2a;
  font-size: 32px;
  margin-bottom: 40px;
  letter-spacing: 1px;
}

/* Each pet profile card */
.pet-profile {
  display: flex;
  flex-direction: column;
  border: 1px solid #e5e5e5;
  border-radius: 16px;
  padding: 30px;
  margin-bottom: 40px;
  background: #fafafa;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s ease;
}

.pet-profile:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.pet-header img {
  border-radius: 50%;
  width: 110px;
  height: 110px;
  object-fit: cover;
  border: 4px solid #f0f0f0;
}

.pet-details h3 {
  margin: 0;
  font-size: 26px;
  font-weight: 700;
  color: #333;
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
  background: #fff;
  padding: 40px 50px;
  border-radius: 14px;
  margin-top: 20px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  position: relative;
}

/* Gradient header bar like add-pet form */
.form-wrapper::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  height: 6px;
  width: 100%;
  border-radius: 14px 14px 0 0;
  background: linear-gradient(to right, #A8E6CF, #FFE29D, #FFB6B9);
}

/* Grid layout */
.form-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

/* Inputs & Select */
.form-grid input,
.form-grid select {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #ccc;
  border-radius: 12px;
  font-size: 14px;
  background: #fcfcfc;
  transition: all 0.3s ease;
}

.form-grid input:focus,
.form-grid select:focus {
  border-color: #91dbc3;
  box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.3);
  outline: none;
}

/* File input */
.form-grid input[type="file"]::file-selector-button {
  background-color: #A8E6CF;
  border: none;
  color: #333;
  padding: 8px 14px;
  border-radius: 6px;
  margin-right: 10px;
  cursor: pointer;
}

.form-grid input[type="file"]::file-selector-button:hover {
  background-color: #91dbc3;
}

/* Submit Button */
.form-submit-btn {
  grid-column: 1 / -1;
  background: linear-gradient(135deg, #FFE29D, #FFB6B9);
  padding: 12px;
  border: none;
  border-radius: 12px;
  font-weight: bold;
  cursor: pointer;
  font-size: 16px;
  margin-top: 15px;
  transition: all 0.3s ease;
}

.form-submit-btn:hover {
  transform: scale(1.03);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 1024px) {
  .form-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

  </style>
</head>
<body>
 <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/pawsig.png" alt="Logo" class="icon" />
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
            <li><a href="../pets/add-pet.php">Add Pet</a></li>
            <li><a href="../appointment/book-appointment.php">Book</a></li>
            <li><a href="../homepage/appointments.php">Appointments</a></li>
            <li><a href="../../Purrfect-paws/ai/chatbot/index.html">Help Center</a></li>
            <li><a href="../homepage/logout/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

<div style="height: 60px;"></div>
<div class="profile-card">

  <?php if (pg_num_rows($pets) > 0): ?>
    <?php while ($pet = pg_fetch_assoc($pets)):
        $pet_id = $pet['pet_id'];

        // Get health info
        $health_result = pg_query_params($conn, "SELECT * FROM health_info WHERE pet_id = $1", [$pet_id]);
        $health = pg_fetch_assoc($health_result);

        // Get behavior preferences
        $behavior_result = pg_query_params($conn, "SELECT * FROM behavior_preferences WHERE pet_id = $1", [$pet_id]);
        $behavior = pg_fetch_assoc($behavior_result);
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
  <form action="pet-edit-handler.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="pet_id" value="<?= $pet_id ?>">

    <div class="form-grid">
      <label>Name:
        <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" required>
      </label>

      <label>Breed:
        <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>" required>
      </label>

      <label>Age:
        <input type="number" name="age" value="<?= htmlspecialchars($pet['age']) ?>" required>
      </label>

      <label>Color:
        <input type="text" name="color" value="<?= htmlspecialchars($pet['color']) ?>" required>
      </label>

      <label>Gender:
        <select name="gender" required>
          <option value="Male" <?= $pet['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= $pet['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
        </select>
      </label>

      <label>Birthday:
        <input type="date" name="birthday" value="<?= htmlspecialchars($pet['birthday']) ?>" required>
      </label>

      <label>Photo:
        <input type="file" name="photo_url">
      </label>

      <button type="submit" class="form-submit-btn">Update Pet</button>
    </div>
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
  