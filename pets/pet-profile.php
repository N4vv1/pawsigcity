<?php
session_start();
require '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/loginform.php');
    exit;
}

$user_id = intval($_SESSION['user_id']); // sanitize

// Fetch the logged-in user's info
$user_result = pg_query_params($conn, "SELECT * FROM users WHERE user_id = $1", [$user_id]);
if ($user_result && pg_num_rows($user_result) > 0) {
    $user = pg_fetch_assoc($user_result);
} else {
    $user = null;
}

// Query to get user's pets
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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../homepage/images/pawsig.png">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
      margin: 0;
      padding: 0;
      min-height: 100vh;
      padding-top: 85px;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
    }

    .container {
      max-width: 1600px;
      margin: 0 auto;
      padding: 20px 20px;
    }

    .main-grid {
      display: grid;
      grid-template-columns: 480px 1fr;
      gap: 20px;
      align-items: start;
    }

    /* Sidebar - User Account */
    .sidebar {
      position: sticky;
      top: 95px;
    }

    .user-card {
      background: white;
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
      border-top: 4px solid #A8E6CF;
    }

    .user-card h2 {
      font-size: 18px;
      margin: 0 0 18px 0;
      color: #2a2a2a;
      font-weight: 600;
      text-align: center;
      padding-bottom: 12px;
      border-bottom: 2px solid #f0f0f0;
    }

    .user-info h3 {
      font-size: 20px;
      margin: 0 0 12px 0;
      color: #333;
      font-weight: 600;
      text-align: center;
    }

    .user-info p {
      margin: 8px 0;
      color: #666;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-info p i {
      color: #A8E6CF;
      width: 18px;
    }

    .edit-btn {
      display: block;
      width: 100%;
      text-align: center;
      background: #A8E6CF;
      color: #333;
      padding: 10px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      margin-top: 15px;
      transition: all 0.2s;
      font-size: 14px;
      border: none;
      cursor: pointer;
    }

    .edit-btn:hover {
      background: #91dbc3;
      transform: translateY(-1px);
    }

    /* Main Content - Pets */
    .main-content h1 {
      font-size: 20px;
      margin: 0 0 15px 0;
      color: #2a2a2a;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background: white;
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
      border-top: 4px solid #A8E6CF;
    }

    .main-content h1 i {
      color: #2a2a2a;
      font-size: 22px;
    }

    .pet-card {
      background: white;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 15px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
      border-left: 4px solid #FFE29D;
      transition: all 0.3s;
    }

    .pet-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 18px rgba(0,0,0,0.12);
    }

    .pet-header {
      display: grid;
      grid-template-columns: 90px 1fr auto;
      gap: 18px;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f8f9fa;
    }

    .pet-avatar {
      width: 90px;
      height: 90px;
      border-radius: 12px;
      object-fit: cover;
      border: 3px solid #f0f0f0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .pet-info h3 {
      margin: 0 0 8px 0;
      font-size: 22px;
      color: #333;
      font-weight: 600;
    }

    .pet-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      color: #666;
      font-size: 13px;
    }

    .pet-meta span {
      display: flex;
      align-items: center;
      gap: 5px;
      background: #f8f9fa;
      padding: 4px 10px;
      border-radius: 6px;
    }

    .pet-meta i {
      color: #A8E6CF;
      font-size: 12px;
    }

    .pet-actions {
      display: flex;
      gap: 8px;
      flex-direction: column;
    }

    .btn-edit {
      background: #ffd166;
      color: #333;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      font-size: 13px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      white-space: nowrap;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-edit:hover {
      background: #ffbe3d;
      transform: translateY(-1px);
    }

    .btn-delete {
      background: #ff6b6b;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 13px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-delete:hover {
      background: #ee5a52;
      transform: translateY(-1px);
    }

    /* Tabs */
    .tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
      padding-bottom: 8px;
    }

    .tab {
      padding: 8px 16px;
      background: transparent;
      border: none;
      color: #666;
      font-weight: 500;
      cursor: pointer;
      border-radius: 6px;
      transition: all 0.2s;
      font-size: 13px;
    }

    .tab:hover {
      background: #f8f9fa;
      color: #333;
    }

    .tab.active {
      background: #A8E6CF;
      color: #333;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .info-row {
      padding: 10px 0;
      border-bottom: 1px solid #f0f0f0;
      display: grid;
      grid-template-columns: 140px 1fr;
      gap: 15px;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-row strong {
      color: #555;
      font-weight: 500;
      font-size: 13px;
    }

    .info-row span {
      color: #333;
      font-size: 13px;
    }

    /* Pet Edit Form - Same styling as User Edit Form */
    .pet-card .edit-form {
      display: none;
      background: #f8f9fa;
      padding: 20px;
      border-radius: 12px;
      margin-top: 18px;
      border: 2px solid #e0e0e0;
    }

    .pet-card .edit-form.show {
      display: block;
    }

    .pet-card .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .pet-card .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .pet-card .form-group.full-width {
      grid-column: 1 / -1;
    }

    .pet-card .form-group label {
      font-size: 13px;
      font-weight: 500;
      color: #555;
    }

    .pet-card .form-group input,
    .pet-card .form-group select {
      padding: 10px 12px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      transition: border 0.2s;
    }

    .pet-card .form-group input:focus,
    .pet-card .form-group select:focus {
      outline: none;
      border-color: #A8E6CF;
    }

    .pet-card .form-actions {
      grid-column: 1 / -1;
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .pet-card .btn-save {
      flex: 1;
      background: #A8E6CF;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .pet-card .btn-save:hover {
      background: #91dbc3;
    }

    .pet-card .btn-cancel {
      flex: 1;
      background: #e0e0e0;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .pet-card .btn-cancel:hover {
      background: #d0d0d0;
    }

    /* Edit Form */
    .edit-form {
      display: none;
      background: #f8f9fa;
      padding: 20px;
      border-radius: 12px;
      margin-top: 18px;
      border: 2px solid #e0e0e0;
    }

    .edit-form.show {
      display: block;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-group label {
      font-size: 13px;
      font-weight: 500;
      color: #555;
    }

    .form-group input,
    .form-group select {
      padding: 10px 12px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      transition: border 0.2s;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #A8E6CF;
    }

    .form-actions {
      grid-column: 1 / -1;
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .btn-save {
      flex: 1;
      background: #A8E6CF;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .btn-save:hover {
      background: #91dbc3;
    }

    .btn-cancel {
      flex: 1;
      background: #e0e0e0;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .btn-cancel:hover {
      background: #d0d0d0;
    }

    .empty-state {
      text-align: center;
      padding: 50px 20px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    }

    .empty-state i {
      font-size: 60px;
      color: #A8E6CF;
      opacity: 0.5;
      margin-bottom: 15px;
    }

    .empty-state p {
      color: #666;
      margin-bottom: 18px;
      font-size: 15px;
    }

    .empty-state a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #A8E6CF;
      color: #333;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s;
    }

    .empty-state a:hover {
      background: #91dbc3;
      transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .main-grid {
        grid-template-columns: 1fr;
      }

      .sidebar {
        position: static;
      }

      .user-card {
        margin-bottom: 20px;
      }
    }

    @media (max-width: 768px) {
      .container {
        padding: 10px 15px;
      }

      .pet-header {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 12px;
      }

      .pet-avatar {
        margin: 0 auto;
      }

      .pet-actions {
        flex-direction: row;
        width: 100%;
      }

      .info-row {
        grid-template-columns: 1fr;
        gap: 5px;
      }

      .pet-actions form {
        flex: 1;
      }

      .btn-edit,
      .btn-delete {
        width: 100%;
      }

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

  <div class="container">
    <div class="main-grid">
      <!-- Sidebar - User Account -->
      <aside class="sidebar">
        <div class="user-card">
          <h2></i> My Account</h2>
          <?php if (!empty($user)): ?>
            <div class="user-info">
              <h3><?= htmlspecialchars($user['first_name'] ?? '') ?> <?= htmlspecialchars($user['last_name'] ?? '') ?></h3>
              <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email'] ?? '') ?></p>
              <p><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone'] ?? '') ?></p>
              <p><i class="fas fa-id-badge"></i> <?= htmlspecialchars($user['role'] ?? '') ?></p>
              <button class="edit-btn" onclick="toggleUserEdit()">
                <i class="fas fa-edit"></i> Edit Account
              </button>
            </div>

            <!-- User Edit Form -->
            <div id="user-edit-form" class="edit-form">
              <form action="user-edit-handler.php" method="POST">
                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                <div class="form-grid">
                  <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name']) ?>">
                  </div>
                  <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                  </div>
                  <div class="form-group full-width">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                  </div>
                  <div class="form-group full-width">
                    <label>New Password (optional)</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current">
                  </div>
                  <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="button" class="btn-cancel" onclick="toggleUserEdit()">Cancel</button>
                  </div>
                </div>
              </form>
            </div>
          <?php else: ?>
            <p>User information not found.</p>
          <?php endif; ?>
        </div>
      </aside>

      <!-- Main Content - Pets -->
      <main class="main-content">

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

          <div class="pet-card">
            <div class="pet-header">
              <img src="../<?= htmlspecialchars($pet['photo_url']) ?>" 
                   alt="<?= htmlspecialchars($pet['name']) ?>"
                   class="pet-avatar"
                   onerror="this.onerror=null;this.src='../uploads/default.jpg';">
              <div class="pet-info">
                <h3><?= htmlspecialchars($pet['name']) ?></h3>
                <div class="pet-meta">
                  <span><i class="fas fa-dog"></i> <?= htmlspecialchars($pet['breed']) ?></span>
                  <span><i class="fas fa-calendar"></i> <?= htmlspecialchars($pet['age']) ?> years</span>
                  <span><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($pet['gender']) ?></span>
                  <span><i class="fas fa-palette"></i> <?= htmlspecialchars($pet['color']) ?></span>
                  <span><i class="fas fa-birthday-cake"></i> <?= htmlspecialchars($pet['birthday']) ?></span>
                </div>
              </div>
              <div class="pet-actions">
                <button class="btn-edit" onclick="togglePetEdit(<?= $pet_id ?>)">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <form action="delete-pet.php" method="POST" onsubmit="return confirm('Delete this pet?');">
                  <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
                  <button type="submit" class="btn-delete">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </form>
              </div>
            </div>

            <!-- Pet Edit Form -->
            <div id="pet-edit-<?= $pet_id ?>" class="edit-form">
              <form action="pet-edit-handler.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
                <div class="form-grid">
                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Breed</label>
                    <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" value="<?= htmlspecialchars($pet['age']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                      <option value="Male" <?= $pet['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                      <option value="Female" <?= $pet['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Color</label>
                    <input type="text" name="color" value="<?= htmlspecialchars($pet['color']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" name="birthday" value="<?= htmlspecialchars($pet['birthday']) ?>" required>
                  </div>
                  <div class="form-group full-width">
                    <label>Photo</label>
                    <input type="file" name="photo_url">
                  </div>
                  <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="button" class="btn-cancel" onclick="togglePetEdit(<?= $pet_id ?>)">Cancel</button>
                  </div>
                </div>
              </form>
            </div>

            <!-- Tabs -->
            <div class="tabs">
              <button class="tab active" onclick="switchTab(<?= $pet_id ?>, 'health')">Health Info</button>
              <button class="tab" onclick="switchTab(<?= $pet_id ?>, 'behavior')">Behavior & Preferences</button>
            </div>

            <!-- Health Tab -->
            <div id="health-<?= $pet_id ?>" class="tab-content active">
              <div class="info-row">
                <strong>Allergies</strong>
                <span><?= htmlspecialchars($health['allergies'] ?? 'None') ?></span>
              </div>
              <div class="info-row">
                <strong>Medications</strong>
                <span><?= htmlspecialchars($health['medications'] ?? 'None') ?></span>
              </div>
              <div class="info-row">
                <strong>Medical Conditions</strong>
                <span><?= htmlspecialchars($health['medical_conditions'] ?? 'None') ?></span>
              </div>
            </div>

            <!-- Behavior Tab -->
            <div id="behavior-<?= $pet_id ?>" class="tab-content">
              <div class="info-row">
                <strong>Behavior Notes</strong>
                <span><?= htmlspecialchars($behavior['behavior_notes'] ?? 'None') ?></span>
              </div>
              <div class="info-row">
                <strong>Nail Trimming</strong>
                <span><?= htmlspecialchars($behavior['nail_trimming'] ?? 'Not specified') ?></span>
              </div>
              <div class="info-row">
                <strong>Haircut Style</strong>
                <span><?= htmlspecialchars($behavior['haircut_style'] ?? 'None') ?></span>
              </div>
            </div>
          </div>

          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-paw"></i>
            <p>You haven't added any pets yet.</p>
            <a href="add-pet.php">
              <i class="fas fa-plus-circle"></i> Add Your First Pet
            </a>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <script>
    function switchTab(petId, tabName) {
      const petCard = document.querySelector(`#health-${petId}`).closest('.pet-card');
      const tabs = petCard.querySelectorAll('.tab');
      const contents = petCard.querySelectorAll('.tab-content');
      
      tabs.forEach(tab => tab.classList.remove('active'));
      contents.forEach(content => content.classList.remove('active'));
      
      event.target.classList.add('active');
      document.getElementById(`${tabName}-${petId}`).classList.add('active');
    }

    function togglePetEdit(petId) {
      const form = document.getElementById(`pet-edit-${petId}`);
      form.classList.toggle('show');
    }

    function toggleUserEdit() {
      const form = document.getElementById('user-edit-form');
      form.classList.toggle('show');
    }
  </script>
</body>
</html>