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
  // Debug if the query failed
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
  <style>
    body { font-family: 'Arial', sans-serif; background: #8CE7BE; padding: 2rem; }
    .profile-card { background: #FFE29D; padding: 1.5rem; border-radius: 12px; max-width: 1100px; margin: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    .tabs { display: flex; gap: 1rem; margin-top: 1rem; border-bottom: 1px solid #ccc; }
    .tab { padding: 0.5rem; cursor: pointer; border-bottom: 2px solid transparent; }
    .tab.active { border-bottom: 2px solid teal; font-weight: bold; }
    .tab-content { display: none; margin-top: 1rem; }
    .tab-content.active { display: block; }
    .section { margin-bottom: 1rem; }
    #logoPreview { width: 170px; height: 170px; object-fit: contain; margin-right: 10px; border-radius: 50% }
  </style>
</head>
<a href="add-pet.php" style="
    display: inline-block;
    background: #28a745;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    margin-bottom: 20px;
">
  ➕ Add Pet
</a>

<body>

<div style="display: flex; align-items: center; margin-bottom: 20px;">
  <img src="../uploads/logo.jpg" alt="Logo" id="logoPreview">
</div>

<div class="profile-card">
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
            style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; vertical-align: middle; margin-right: 10px;"
            onerror="this.onerror=null;this.src='../uploads/default.jpg';">
        <?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['breed']) ?>)

        <a href="#" class="edit-button" data-id="<?= $pet_id ?>" style="float: right; font-size: 14px;">✏️ Edit</a>

        <form action="delete-pet.php" method="POST" style="float: right; margin-right: 10px;" 
              onsubmit="return confirm('Are you sure you want to delete this pet?');">
          <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
          <button type="submit" style="background:#dc3545; color:white; border:none; padding:4px 8px; cursor:pointer; font-size:14px;">
            🗑 Delete
          </button>
        </form>
      </h3>

      <?php include 'pet-edit.php'; ?>

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
  // Tab switching per pet profile
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

  // Toggle Edit Form
  document.querySelectorAll('.edit-button').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const petId = btn.dataset.id;
      const form = document.getElementById('edit-form-' + petId);
      form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });
  });
</script>

</body>
</html>
