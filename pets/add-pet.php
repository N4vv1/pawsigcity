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
  <title>Add New Pet</title>
  <link rel="stylesheet" href="../homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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

  body {
    margin: 0;
    padding: 0;
    font-family: var(--font);
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

  .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 40px;
  }

  .nav-menu {
    list-style: none;
    display: flex;
    gap: 20px;
    margin: 0;
    padding: 0;
  }

  .nav-item a {
    text-decoration: none;
    color: var(--dark);
    font-weight: 600;
    transition: color var(--transition);
  }

  .nav-item a:hover {
    color: #00796B;
  }

  .preview-wrapper {
  margin-top: 10px;
}

.preview-wrapper img {
  display: none;
  max-width: 150px;
  max-height: 150px;
  border-radius: 10px;
  object-fit: cover;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}


  .back-button {
    position: absolute;
    top: 140px;
    left: 30px;
    background-color: var(--secondary);
    color: #333;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: bold;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: background var(--transition);
  }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #F9F9F9;
      margin: 0;
      padding: 0;
    }

    .back-button {
      position: absolute;
      top: 170px;
      left: 30px;
      background-color: #FFE29D;
      color: #333;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: background 0.3s ease;
      font-size: 14px;
      border: 1px solid #ccc;
    }

    .back-button:hover {
      background-color: #ffefc3;
    }


  .back-button:hover {
    background-color: #ffefc3;
  }

  .add-pet-container {
    max-width: 1100px;
    margin: 180px auto 60px;
    padding: 20px;
  }
=======
    .form-wrapper {
      background: #fff;
      border-radius: 16px;
      padding: 50px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }


  .form-wrapper {
    position: relative;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    padding: 50px 40px;
  }

  .form-wrapper::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 8px;
    width: 100%;
    background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
  }

  .form-wrapper h2 {
    text-align: center;
    color: var(--dark);
    font-size: 36px;
    font-weight: bold;
    margin-bottom: 30px;
    letter-spacing: 1px;
  }

  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px 40px;
  }

  .form-grid label {
    font-weight: 600;
    font-size: 15px;
    display: flex;
    flex-direction: column;
    color: #444;
  }

  .form-grid input,
  .form-grid select,
  .form-grid textarea {
    padding: 14px 16px;
    margin-top: 8px;
    border: 2px solid var(--gray);
    border-radius: var(--radius);
    background: #fcfcfc;
    transition: all var(--transition);
    font-size: 15px;
  }

  .form-grid input:focus,
  .form-grid select:focus,
  .form-grid textarea:focus {
    border-color: var(--primary-dark);
    outline: none;
    box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.3);
  }

  .form-grid textarea {
    grid-column: span 2;
    resize: vertical;
    min-height: 100px;
  }

  .form-section-title {
    grid-column: span 2;
    font-size: 18px;
    font-weight: bold;
    color: #00796B;
    background-color: #f0fdf9;
    padding: 10px 15px;
    border-left: 5px solid var(--primary);
    border-radius: 6px;
    margin-top: 40px;
  }

  .submit-button {
    grid-column: span 2;
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
  }

  .submit-button:hover {
    transform: scale(1.03);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
  }

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

  @media (max-width: 768px) {
    .form-grid {
      grid-template-columns: 1fr;
    }
    .form-grid textarea,
    .submit-button {
      grid-column: span 1;


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
      padding: 12px 15px;
      margin-top: 8px;
      border: 2px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      resize: none;
      background-color: #fff;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      outline: none;
    }

    .form-grid input:hover,
    .form-grid select:hover,
    .form-grid textarea:hover {
      border-color: #A8E6CF;
      background-color: #f9fdfb;
      box-shadow: 0 2px 10px rgba(168, 230, 207, 0.2);
    }

    .form-grid input:focus,
    .form-grid select:focus,
    .form-grid textarea:focus {
      border-color: #00796B;
      box-shadow: 0 0 0 4px rgba(0, 121, 107, 0.2);
      background-color: #fff;
    }

    .form-grid input::placeholder,
    .form-grid textarea::placeholder {
      color: #aaa;
      font-style: italic;
    }

    .form-grid textarea {
      grid-column: span 2;
    }

    .form-section-title {
      grid-column: span 2;
      font-size: 20px;
      font-weight: 700;
      color: #444;
      margin-top: 40px;
    }

    .submit-button {
      grid-column: span 2;
      padding: 12px;
      background-color: #A8E6CF;
      color: #252525;
      font-weight: bold;
      border-radius: 6px;
      font-size: 17px;
      border: none;
      cursor: pointer;
      transition: background 0.3s ease, transform 0.2s ease;
      margin-top: 20px;

    }
    .navbar {
      flex-direction: column;
    }

     .submit-button:hover {
      background-color: #89d7ba;
      transform: translateY(-2px);
    }

    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }

      .back-button {
        top: 100px;
        left: 20px;
      }

      .form-wrapper {
        padding: 30px 20px;
      }
    }
  }
</head>
<body>
  <header>

    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/Logo.jpg" alt="Logo" class="icon" style="width: 80px; height: 80px; border-radius: 50%;" />
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
<a href="pet-profile.php" class="back-button">&larr; Back</a>
<div class="add-pet-container">
  <div class="form-wrapper">
    <h2>Add a Pet</h2>
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
          <input type="file" name="photo" accept="image/*">
        </label>


          <label>Photo:
          <input type="file" name="photo" accept="image/*" onchange="previewImage(event)">
          <div class="preview-wrapper">
            <img id="preview" src="#" alt="Selected Photo" />
          </div>
          </label>


          <span class="form-section-title">Health Info</span>

        <span class="form-section-title">Health Info</span>

        <label>Allergies:
          <textarea name="allergies" placeholder="Any allergies?"></textarea>
        </label>


        <label>Medications:
          <textarea name="medications" placeholder="Current medications"></textarea>
        </label>

        <label>Medical Conditions:
          <textarea name="medical_conditions" placeholder="Ongoing conditions"></textarea>
        </label>

        <span class="form-section-title">Behavior & Preferences</span>

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


</div>
</body>
</html>
