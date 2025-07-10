<?php
require_once '../../db.php';

//if ($_SESSION['role'] !== 'admin') {
  //header("Location: ../homepage/main.php");
  //exit;
//}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appointment_id = $_POST['appointment_id'];
  $notes = $mysqli->real_escape_string($_POST['notes']);
  $mysqli->query("UPDATE appointments SET notes = '$notes' WHERE appointment_id = $appointment_id");
  echo "Notes saved.";
}

$appointments = $mysqli->query("SELECT * FROM appointments ORDER BY appointment_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Gallery Dashboard Template</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
      --light-pink-color: #faf4f5;
      --medium-gray-color: #ccc;
      --font-size-s: 0.9rem;
      --font-size-n: 1rem;
      --font-size-l: 1.5rem;
      --font-size-xl: 2rem;
      --font-weight-semi-bold: 600;
      --font-weight-bold: 700;
      --border-radius-s: 8px;
      --border-radius-circle: 50%;
      --site-max-width: 1300px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Montserrat", sans-serif;
    }

    body {
      background: var(--light-pink-color);
      display: flex;
    }

    .sidebar {
      width: 260px;
      height: 100vh;
      background-color: var(--primary-color);
      padding: 30px 20px;
      position: fixed;
      left: 0;
      top: 0;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .sidebar .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .sidebar .logo img {
      width: 80px;
      height: 80px;
      border-radius: var(--border-radius-circle);
    }

    .menu {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .menu a {
      display: flex;
      align-items: center;
      padding: 10px 12px;
      text-decoration: none;
      color: var(--dark-color);
      border-radius: var(--border-radius-s);
      transition: background 0.3s, color 0.3s;
      font-weight: var(--font-weight-semi-bold);
    }

    .menu a i {
      margin-right: 10px;
      font-size: 20px;
    }

    .menu a:hover,
    .menu a.active {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .menu hr {
      border: none;
      border-top: 1px solid var(--medium-gray-color);
      margin: 8px 0;
    }

    .submenu {
      margin-left: 30px;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .submenu a {
      font-size: var(--font-size-s);
    }

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
    }

    .add-btn {
      background: var(--primary-color);
      padding: 10px 20px;
      border-radius: var(--border-radius-s);
      text-decoration: none;
      color: var(--dark-color);
      font-weight: var(--font-weight-semi-bold);
      display: inline-block;
      margin-bottom: 20px;
    }

    .add-btn:hover {
      background: var(--secondary-color);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: var(--white-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    th, td {
      padding: 14px 10px;
      border: 1px solid var(--medium-gray-color);
      text-align: center;
    }

    th {
      background: var(--primary-color);
      font-weight: var(--font-weight-bold);
      color: var(--dark-color);
    }

    img {
      width: 100px;
      height: auto;
      border-radius: var(--border-radius-s);
    }

    .actions a {
      padding: 6px 14px;
      font-size: var(--font-size-s);
      font-weight: var(--font-weight-semi-bold);
      text-decoration: none;
      margin: 0 5px;
      border-radius: var(--border-radius-s);
      display: inline-block;
    }

    .edit-btn {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .edit-btn:hover {
      background-color: #fdd56c;
    }

    .delete-btn {
      background-color: #ff6b6b;
      color: var(--white-color);
    }

    .delete-btn:hover {
      background-color: #ff4949;
    }

    .form-card {
  background-color: var(--white-color);
  padding: 30px;
  border-radius: var(--border-radius-s);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
  max-width: 600px;
  margin: 0 auto;
}

.form-card form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.form-card label {
  font-weight: var(--font-weight-semi-bold);
  color: var(--dark-color);
}

.form-card select,
.form-card textarea {
  padding: 10px;
  font-size: 1rem;
  border: 1px solid var(--medium-gray-color);
  border-radius: var(--border-radius-s);
  background-color: #fefefe;
  resize: vertical;
}

.form-card button {
  background-color: var(--primary-color);
  color: var(--dark-color);
  border: none;
  padding: 12px 20px;
  font-weight: var(--font-weight-semi-bold);
  font-size: 1rem;
  border-radius: var(--border-radius-s);
  cursor: pointer;
  transition: background-color 0.3s;
}

.form-card button:hover {
  background-color: var(--secondary-color);
}

.form-card {
  background-color: var(--white-color);
  padding: 40px 50px;
  border-radius: var(--border-radius-s);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
  max-width: 750px; /* increased width */
  width: 100%;
  margin: 0 auto;
}

.form-card select,
.form-card textarea {
  padding: 14px;
  font-size: 1rem;
  border: 1px solid var(--medium-gray-color);
  border-radius: var(--border-radius-s);
  background-color: #fefefe;
  resize: vertical;
}

.form-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 85vh; /* vertical center */
}

.form-card {
  background-color: var(--white-color);
  padding: 40px 50px;
  border-radius: var(--border-radius-s);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
  max-width: 750px; /* increased width */
  width: 100%;
}

.form-card form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.form-card label {
  font-weight: var(--font-weight-semi-bold);
  color: var(--dark-color);
}

.form-card select,
.form-card textarea {
  padding: 14px;
  font-size: 1rem;
  border: 1px solid var(--medium-gray-color);
  border-radius: var(--border-radius-s);
  background-color: #fefefe;
  resize: vertical;
}

.form-card button {
  background-color: var(--primary-color);
  color: var(--dark-color);
  border: none;
  padding: 14px;
  font-weight: var(--font-weight-semi-bold);
  font-size: 1rem;
  border-radius: var(--border-radius-s);
  cursor: pointer;
  transition: background-color 0.3s;
}

.form-card button:hover {
  background-color: var(--secondary-color);
}
  </style>
</head>
<body>

  <!-- Sidebar -->
   <aside class="sidebar">
  <div class="logo">
    <img src="../homepage/images/Logo.jpg" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../../dashboard/home_dashboard/home.php"><i class='bx bx-home'></i>Dashboard / Home</a>
    <hr>
    <a href="../create_user/create-user.php"><i class='bx bx-user-plus'></i>Create User</a>
    <hr>
    <a href="../session_notes.php/notes.php" class="active"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="#"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

  <main class="content">
  <div class="form-wrapper">
    <div class="form-card">
      <form method="POST">
        <label for="appointment_id">Select Appointment:</label>
        <select name="appointment_id" required>
          <?php while ($row = $appointments->fetch_assoc()): ?>
            <option value="<?= $row['appointment_id'] ?>">
              #<?= $row['appointment_id'] ?> - <?= $row['appointment_date'] ?>
            </option>
          <?php endwhile; ?>
        </select>

        <label for="notes">Notes:</label>
        <textarea name="notes" rows="6" placeholder="Enter session notes here..." required></textarea>

        <button type="submit">💾 Save Notes</button>
      </form>
    </div>
  </div>
</main>




</body>
</html>
