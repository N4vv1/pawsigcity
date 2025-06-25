<?php
// database connection
$mysqli = new mysqli("localhost", "root", "", "pet_grooming_system");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Assume logged-in user ID (replace with session-based logic)
$user_id = 1;

// Get the user's pet(s)
$pets = $mysqli->query("SELECT * FROM pets WHERE owner_id = $user_id");
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
    #logoPreview {width: 170px; height: 170px; object-fit: contain; margin-right: 10px; border-radius: 50%}
  </style>
</head>
<body>

<div style="display: flex; align-items: center; margin-bottom: 20px;">
  <img src="../uploads/logo.jpg" alt="Logo" id="logoPreview">
</div>


<div class="profile-card">
  <h2>My Pet Profile</h2>

  <div class="tabs">
    <div class="tab active" data-tab="health">Health Info</div>
    <div class="tab" data-tab="behavior">Behavior & Preferences</div>
    <div class="tab" data-tab="grooming">Grooming History</div>
  </div>

  <?php
require '../db.php';

if (isset($_GET['updated'])) {
  echo "<p style='color:green;'>Pet profile updated successfully!</p>";
}

$user_id = 1; // replace with session-based user ID
$pets = $mysqli->query("SELECT * FROM pets WHERE user_id = $user_id");

while ($pet = $pets->fetch_assoc()) {
  $pet_id = $pet['pet_id'];

  $health = $mysqli->query("SELECT * FROM health_info WHERE pet_id = $pet_id")->fetch_assoc();
  $behavior = $mysqli->query("SELECT * FROM behavior_preferences WHERE pet_id = $pet_id")->fetch_assoc();
  $history = $mysqli->query("SELECT * FROM grooming_history WHERE pet_id = $pet_id ORDER BY history_id DESC LIMIT 5");
?>

<div class="pet-profile" id="pet-<?= $pet_id ?>">
  <h3>
    <img src="<?= $pet['photo_url'] ?>" alt="<?= $pet['name'] ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 10px;">
    <?= htmlspecialchars($pet['name']) ?> (<?= $pet['breed'] ?>)
    <a href="#" class="edit-button" data-id="<?= $pet['pet_id'] ?>" style="float: right; font-size: 14px;">✏️ Edit</a>
  </h3>

  <?php include 'edit-pet-form.php'; ?>

  <!-- Tab headers -->
  <div class="tabs">
    <div class="tab active" data-tab="health-<?= $pet_id ?>">Health Info</div>
    <div class="tab" data-tab="behavior-<?= $pet_id ?>">Behavior & Preferences</div>
    <div class="tab" data-tab="grooming-<?= $pet_id ?>">Grooming History</div>
  </div>

  <div class="tab-content active" id="health-<?= $pet_id ?>">
    <strong>Allergies:</strong> <?= $health['allergies'] ?? 'None' ?><br>
    <strong>Medications:</strong> <?= $health['medications'] ?? 'None' ?><br>
    <strong>Medical Conditions:</strong> <?= $health['medical_conditions'] ?? 'None' ?>
  </div>

  <div class="tab-content" id="behavior-<?= $pet_id ?>">
    <strong>Behavior Notes:</strong> <?= $behavior['behavior_notes'] ?? 'None' ?><br>
    <strong>Nail Trimming:</strong> <?= $behavior['nail_trimming'] ?? 'Not specified' ?><br>
    <strong>Haircut Style:</strong> <?= $behavior['haircut_style'] ?? 'None' ?>
  </div>

  <div class="tab-content" id="grooming-<?= $pet_id ?>">
    <?php while ($row = $history->fetch_assoc()) { ?>
      <div>
        <strong>Date:</strong> <?= $row['summary'] ?><br>
        <strong>Notes:</strong> <?= $row['notes'] ?? 'N/A' ?><br>
        <strong>Tips:</strong> <?= $row['tips_for_next_time'] ?? 'None' ?><hr>
      </div>
    <?php } ?>
  </div>
</div>
<?php } ?>

<script>
  // Tab switching
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const petId = tab.dataset.tab.split('-')[1];
      document.querySelectorAll(`#pet-${petId} .tab`).forEach(t => t.classList.remove('active'));
      document.querySelectorAll(`#pet-${petId} .tab-content`).forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(tab.dataset.tab).classList.add('active');
    });
  });

  // Edit form toggle
  document.querySelectorAll('.edit-button').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const petId = btn.dataset.id;
      const form = document.getElementById('edit-form-' + petId);
      form.style.display = (form.style.display === 'none') ? 'block' : 'none';
    });
  });
</script>


</body>
</html>
