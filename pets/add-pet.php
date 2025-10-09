<?php
session_start();
require '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/loginform.php');
    exit;
}

$user_id = intval($_SESSION['user_id']); // sanitize user_id

// Query to get user's pets
$query = "SELECT * FROM pets WHERE user_id = $user_id";
$pets = pg_query($conn, $query);

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
  <title>Add New Pet</title>
  <link rel="stylesheet" href="../homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../pawsigcity/images/pawsig.png">
 <style>
  :root {
    --white: #ffffff;
    --dark: #252525;
    --primary: #A8E6CF;
    --primary-dark: #91dbc3;
    --secondary: #FFE29D;
    --accent: #FFB6B9;
    --gray: #ccc;
    --font: "Segoe UI", sans-serif;
    --radius: 14px;
    --transition: 0.3s ease;
  }

  body, html {
    margin: 0;
    padding: 0;
    min-height: 100%;
    overflow-y: auto;
    font-family: 'Poppins', sans-serif;
    background: #f5f5f5;
    color: var(--dark);
  }

  header {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 10;
    background-color: var(--primary);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
  }

    .back-button {
    position: absolute;
    top: 100px;
    left: 30px;
    background: none; /* remove box */
    color: var(--dark);
    padding: 6px;
    font-size: 20px;
    border: none;
    text-decoration: none;
    transition: color var(--transition);
  }

  .back-button:hover {
    color: var(--primary-dark);
  }

    .add-pet-container {
      width: 100%;
      padding: 140px 60px 40px; /* top padding accounts for fixed header */
      box-sizing: border-box;
  }

  .form-wrapper {
    width: 100%;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.15);
    padding: 50px 60px;
    position: relative;
  }

  .form-wrapper::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 8px;
    width: 100%;
    border-radius: var(--radius) var(--radius) 0 0;
    background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
  }

  .form-wrapper h2 {
    text-align: center;
    color: var(--dark);
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 30px;
    letter-spacing: 1px;
  }

  /* GRID FORM LAYOUT */
  .form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* 👉 change to 4 for 4 columns */
    gap: 20px;
  }

  .form-grid label {
    display: flex;
    flex-direction: column;
    font-weight: 600;
    font-size: 14px;
  }

  .form-grid input,
  .form-grid select,
  .form-grid textarea {
    width: 100%;
    padding: 10px 12px;
    margin-top: 6px;
    border: 2px solid var(--gray);
    border-radius: var(--radius);
    background: #fcfcfc;
    transition: all var(--transition);
    font-size: 14px;
    outline: none;
  }

  .form-grid input:hover,
  .form-grid select:hover,
  .form-grid textarea:hover {
    border-color: var(--primary);
    background-color: #f9fdfb;
    box-shadow: 0 2px 10px rgba(168, 230, 207, 0.2);
  }

  .form-grid input:focus,
  .form-grid select:focus,
  .form-grid textarea:focus {
    border-color: var(--primary-dark);
    box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.3);
  }

  .form-grid input::placeholder,
  .form-grid textarea::placeholder {
    color: #aaa;
    font-style: italic;
  }

  /* TEXTAREAS SPAN FULL WIDTH */
  .form-grid textarea {
    grid-column: span 3; /* match column count → span all */
    resize: vertical;
    min-height: 90px;
  }

  /* SECTION TITLE */
  .form-section-title {
    grid-column: 1 / -1; /* span all columns */
    font-size: 18px;
    font-weight: bold;
    color: #00796B;
    background-color: #f0fdf9;
    padding: 8px 12px;
    border-left: 5px solid var(--primary);
    border-radius: 6px;
    margin-top: 20px;
  }

  /* SUBMIT BUTTON */
  .submit-button {
    grid-column: 1 / -1; /* span all columns */
    padding: 14px;
    background: linear-gradient(135deg, var(--secondary), var(--accent));
    color: var(--dark);
    font-weight: bold;
    border: none;
    font-size: 16px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition);
    box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
    margin-top: 15px;
  }

  .submit-button:hover {
    transform: scale(1.03);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
  }

  /* FILE INPUT */
  input[type="file"]::file-selector-button {
    background-color: var(--primary);
    border: none;
    color: #333;
    padding: 8px 14px;
    border-radius: 6px;
    margin-right: 10px;
    cursor: pointer;
    transition: background var(--transition);
  }

  input[type="file"]::file-selector-button:hover {
    background-color: var(--primary-dark);
  }

  /* IMAGE PREVIEW */
  .preview-wrapper {
    margin-top: 8px;
  }

  .preview-wrapper img {
    display: none;
    max-width: 150px;
    max-height: 150px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  /* RESPONSIVE */
  @media (max-width: 1024px) {
    .form-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    .form-grid textarea,
    .form-section-title,
    .submit-button {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 768px) {
    .form-grid {
      grid-template-columns: 1fr;
    }
    .form-grid textarea,
    .form-section-title,
    .submit-button {
      grid-column: 1 / -1;
    }
    .back-button {
      top: 80px;
      left: 20px;
    }
    .form-wrapper {
      padding: 20px;
    }
  }
</style>



</head>
<body>
  
<header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../pawsigcity/images/pawsig.png" alt="Logo" class="icon" />
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

  <a href="pet-profile.php" class="back-button"><i class="fas fa-arrow-left"> BACK TO PETS</i></a>

  
  <div class="add-pet-container">
    <div class="form-wrapper">
      <form method="POST" action="add-pet-handler.php" enctype="multipart/form-data">
        <div class="form-grid">
          <label>Name:
            <input type="text" name="name" placeholder="Enter pet name" required>
          </label>

          <label>Breed:
            <input type="text" name="breed" placeholder="Enter breed" required>
          </label>

          <label>Gender:
            <select name="gender">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </label>

          <label>Age:
            <input type="text" name="age" placeholder="Enter age">
          </label>

          <label>Birthday:
            <input type="date" name="birthday">
          </label>

          <label>Color:
            <input type="text" name="color" placeholder="Enter color">
          </label>

          <label>Photo:
            <input type="file" name="photo" accept="image/*" onchange="previewImage(event)">
            <div class="preview-wrapper">
              <img id="preview" src="#" alt="Selected Photo" />
            </div>
          </label>

          <span class="form-section-title"><i class="fas fa-heartbeat"></i> Health Information</span>

          <label>Allergies:
            <textarea name="allergies" placeholder="Any allergies?"></textarea>
          </label>

          <label>Medications:
            <textarea name="medications" placeholder="Current medications"></textarea>
          </label>

          <label>Medical Conditions:
            <textarea name="medical_conditions" placeholder="Ongoing conditions"></textarea>
          </label>

          <span class="form-section-title"><i class="fas fa-dog"></i> Behavior & Preferences</span>

          <label>Behavior Notes:
            <textarea name="behavior_notes" placeholder="Describe pet behavior"></textarea>
          </label>

          <label>Nail Trimming:
            <select name="nail_trimming">
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </label>

          <label>Haircut Style:
            <input type="text" name="haircut_style" placeholder="Preferred haircut">
          </label>

          <button type="submit" class="submit-button">Add Pet</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function previewImage(event) {
      const input = event.target;
      const preview = document.getElementById('preview');

      if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);
      } else {
        preview.src = "#";
        preview.style.display = "none";
      }
    }
  </script>
</body>
</html>