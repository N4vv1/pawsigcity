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
    body { font-family: 'Arial', sans-serif; background: #f9f9f9; padding: 2rem; }
    .profile-card { background: white; padding: 1.5rem; border-radius: 12px; max-width: 800px; margin: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    .tabs { display: flex; gap: 1rem; margin-top: 1rem; border-bottom: 1px solid #ccc; }
    .tab { padding: 0.5rem; cursor: pointer; border-bottom: 2px solid transparent; }
    .tab.active { border-bottom: 2px solid teal; font-weight: bold; }
    .tab-content { display: none; margin-top: 1rem; }
    .tab-content.active { display: block; }
    .section { margin-bottom: 1rem; }
  </style>
</head>
<body>

<div class="profile-card">
  <h2>My Pet Profile</h2>

  <div class="tabs">
    <div class="tab active" data-tab="health">Health Info</div>
    <div class="tab" data-tab="behavior">Behavior & Preferences</div>
    <div class="tab" data-tab="grooming">Grooming History</div>
  </div>

  <?php while ($pet = $pets->fetch_assoc()) {
    $pet_id = $pet['pet_id'];

    // Fetch related tables
    $health = $mysqli->query("SELECT * FROM health_info WHERE pet_id = $pet_id")->fetch_assoc();
    $behavior = $mysqli->query("SELECT * FROM behavior_preferences WHERE pet_id = $pet_id")->fetch_assoc();
    $history = $mysqli->query("SELECT * FROM grooming_history WHERE pet_id = $pet_id ORDER BY history_id DESC LIMIT 5");
  ?>

  <h3><?= htmlspecialchars($pet['name']) ?> (<?= $pet['breed'] ?>)</h3>

  <div class="tab-content active" id="health">
    <div class="section">
      <strong>Allergies:</strong> <?= $health['allergies'] ?? 'None' ?>
    </div>
    <div class="section">
      <strong>Medications:</strong> <?= $health['medications'] ?? 'None' ?>
    </div>
    <div class="section">
      <strong>Medical Conditions:</strong> <?= $health['medical_conditions'] ?? 'None' ?>
    </div>
  </div>

  <div class="tab-content" id="behavior">
    <div class="section">
      <strong>Behavior Notes:</strong> <?= $behavior['behavior_notes'] ?? 'None' ?>
    </div>
    <div class="section">
      <strong>Nail Trimming:</strong> <?= $behavior['nail_trimming'] ?? 'Not specified' ?>
    </div>
    <div class="section">
      <strong>Haircut Style:</strong> <?= $behavior['haircut_style'] ?? 'None' ?>
    </div>
  </div>

  <div class="tab-content" id="grooming">
    <?php while ($row = $history->fetch_assoc()) { ?>
      <div class="section">
        <strong>Date:</strong> <?= $row['summary'] ?><br><br>
        <strong>Notes:</strong> <?= $row['notes'] ?? 'N/A' ?><br><br>
        <strong>Tips:</strong> <?= $row['tips_for_next_time'] ?? 'None' ?><br><br><hr>
      </div>
    <?php } ?>
  </div>

  <?php } ?>
</div>

<script>
  const tabs = document.querySelectorAll('.tab');
  const contents = document.querySelectorAll('.tab-content');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      contents.forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(tab.dataset.tab).classList.add('active');
    });
  });
</script>

</body>
</html>
