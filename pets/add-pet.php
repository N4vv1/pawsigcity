<!DOCTYPE html>
<html>
<head>
  <title>Add New Pet</title>
  <link rel="stylesheet" href="../homepage/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 999;
      background-color: #A8E6CF;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 40px;
    }

    .nav-menu {
      list-style: none;
      display: flex;
      gap: 20px;
      margin: 0;
    }

    .nav-item a {
      text-decoration: none;
      color: #333;
      font-weight: 600;
    }

    .nav-item a:hover {
      color: #00796B;
    }

    .back-button {
      position: absolute;
      top: 140px; /* increased from 100px */
      left: 30px;
      background-color: #FFE29D;
      color: #333;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: background 0.3s ease;
    }


    .back-button:hover {
      background-color: #ffefc3;
    }

    .add-pet-container {
      max-width: 1000px;
      margin: 160px auto 60px;
      padding: 0 20px;
    }

    .form-wrapper {
      background: #A8E6CF;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .form-wrapper h2 {
      text-align: center;
      color: #333;
      margin-bottom: 40px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px 40px;
    }

    .form-grid label {
      display: flex;
      flex-direction: column;
      font-weight: 600;
      color: #333;
      font-size: 16px;
    }

    .form-grid input,
    .form-grid select,
    .form-grid textarea {
      padding: 10px 12px;
      margin-top: 8px;
      border: 1.5px solid #aaa;
      border-radius: 6px;
      font-size: 15px;
      resize: none;
    }

    .form-grid textarea {
      grid-column: span 2;
    }

    .form-section-title {
      grid-column: span 2;
      font-size: 18px;
      font-weight: bold;
      color: #00796B;
      margin-top: 30px;
    }

    .submit-button {
      grid-column: span 2;
      padding: 12px;
      background-color: #FFE29D;
      color: #333;
      font-weight: bold;
      border-radius: 6px;
      font-size: 16px;
      border: none;
      cursor: pointer;
      transition: background 0.3s ease;
      margin-top: 20px;
    }

    .submit-button:hover {
      background-color: #ffefc3;
      color: #333;
      border: 1px solid #ccc;
    }
  </style>
</head>

<body>
  <!-- Navbar Header -->
  <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/Logo.jpg" alt="Logo" class="icon" style="width: 80px; height: 80px; border-radius: 50%;" />
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

  <!-- 🔙 Back Button -->
  <a href="pet-profile.php" class="back-button">←</a>

  <div class="add-pet-container">
    <div class="form-wrapper">
      <h2>Add a Pet</h2>
      <form method="POST" action="add-pet-handler.php" enctype="multipart/form-data">
        <div class="form-grid">
          <label>Name:
            <input type="text" name="name" required>
          </label>

          <label>Breed:
            <input type="text" name="breed" required>
          </label>

          <label>Gender:
            <select name="gender">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </label>

          <label>Age:
            <input type="text" name="age">
          </label>

          <label>Birthday:
            <input type="date" name="birthday">
          </label>

          <label>Color:
            <input type="text" name="color">
          </label>

          <label>Photo:
            <input type="file" name="photo" accept="image/*">
          </label>

          <span class="form-section-title">Health Info</span>

          <label>Allergies:
            <textarea name="allergies"></textarea>
          </label>

          <label>Medications:
            <textarea name="medications"></textarea>
          </label>

          <label>Medical Conditions:
            <textarea name="medical_conditions"></textarea>
          </label>

          <span class="form-section-title">Behavior & Preferences</span>

          <label>Behavior Notes:
            <textarea name="behavior_notes"></textarea>
          </label>

          <label>Nail Trimming:
            <select name="nail_trimming">
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </label>

          <label>Haircut Style:
            <input type="text" name="haircut_style">
          </label>

          <button type="submit" class="submit-button">Add Pet</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
