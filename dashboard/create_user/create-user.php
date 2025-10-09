<?php
session_start();
require '../../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $first_name  = trim($_POST['first_name']);
  $middle_name = trim($_POST['middle_name']);
  $last_name   = trim($_POST['last_name']);
  $email       = trim($_POST['email']);
  $password    = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $phone       = trim($_POST['phone']);
  $role        = 'admin'; // Fixed role

  // Check if email is already registered
  $checkQuery = "SELECT 1 FROM users WHERE email = $1";
  $checkResult = pg_query_params($conn, $checkQuery, [$email]);

  if (pg_num_rows($checkResult) > 0) {
    $error = "Email is already registered.";
  } else {
    $insertQuery = "
      INSERT INTO users (first_name, middle_name, last_name, email, password, phone, role) 
      VALUES ($1, $2, $3, $4, $5, $6, $7)
    ";
    $insertResult = pg_query_params($conn, $insertQuery, [
      $first_name, $middle_name, $last_name, $email, $password, $phone, $role
    ]);

    if ($insertResult) {
      $success = "Admin account created successfully.";
    } else {
      $error = "Something went wrong. Please try again. " . pg_last_error($conn);
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Gallery Dashboard Template</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">
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
      border-top: 1px solid var(--secondary-color);
      margin: 9px 0;
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
    
    /* ===== Admin Create Form Floating Label Inputs ===== */
.input_box {
  position: relative;
  margin-bottom: 1.5rem;
}

.input-field {
  width: 100%;
  padding: 0.9rem 2.5rem 0.9rem 2.5rem;
  border: 1px solid var(--medium-gray-color);
  border-radius: var(--border-radius-s);
  background-color: var(--light-pink-color);
  font-size: var(--font-size-n);
  color: var(--dark-color);
  transition: border 0.3s ease, background-color 0.3s ease;
}

.input-field:focus {
  outline: none;
  border-color: var(--primary-color);
  background-color: var(--white-color);
}

.label {
  position: absolute;
  left: 2.5rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: var(--font-size-s);
  color: var(--dark-color);
  transition: 0.3s ease;
  pointer-events: none;
}

.input-field:focus + .label,
.input-field:valid + .label {
  top: -0.6rem;
  left: 1rem;
  background-color: var(--white-color);
  padding: 0 0.3rem;
  font-size: 0.75rem;
  color: var(--primary-color);
}

/* Input Icons */
.icon {
  position: absolute;
  top: 50%;
  left: 0.8rem;
  transform: translateY(-50%);
  font-size: 1.2rem;
  color: var(--dark-color);
}

/* Submit Button Consistent Style */
.input-submit {
  width: 100%;
  padding: 0.9rem;
  background-color: var(--primary-color);
  color: var(--dark-color);
  font-size: var(--font-size-n);
  border: none;
  border-radius: var(--border-radius-s);
  font-weight: var(--font-weight-semi-bold);
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.input-submit:hover {
  background-color: var(--secondary-color);
}

/* Optional: Feedback Message Styling */
.message-success {
  color: green;
  background: #eaffea;
  padding: 0.8rem;
  border-radius: var(--border-radius-s);
  margin-bottom: 1rem;
  font-size: var(--font-size-s);
  text-align: center;
}

.message-error {
  color: red;
  background: #ffeaea;
  padding: 0.8rem;
  border-radius: var(--border-radius-s);
  margin-bottom: 1rem;
  font-size: var(--font-size-s);
  text-align: center;
}

.form-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 80vh;
  padding: 2rem;
}

.card {
  background-color: var(--white-color);
  padding: 3rem;
  border-radius: var(--border-radius-s);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 650px; /* was 500px */
  transition: transform 0.3s ease;
}

.input-field {
  font-size: 1.05rem;
  padding: 1.1rem 2.7rem 1.1rem 2.7rem;
}

.label {
  font-size: 1rem;
}

  </style>
</head>
<body>

  <!-- Sidebar -->
   <aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="home.php" class="active"><i class='bx bx-home'></i>Home</a>
    <hr>
    <a href="../manage_accounts/accounts.php"><i class='bx bx-camera'></i>User Management</a>
    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

 <!-- Main Content -->
<main class="content">
  <div class="form-container">
    <div class="wrapper">
      <div class="card">
        <form method="POST" class="login_box">
          <div class="login-header">
            <span>Create Admin Account</span>
          </div><br>

          <?php if (isset($error)): ?>
            <p class="message-error"><?= $error ?></p>
          <?php elseif (isset($success)): ?>
            <p class="message-success"><?= $success ?></p>
          <?php endif; ?>

          <div class="input_box">
            <input type="text" class="input-field" name="full_name" id="full_name" required />
            <label for="full_name" class="label">Full Name</label>
            <i class='bx bx-user icon'></i>
          </div>

          <div class="input_box">
            <input type="email" class="input-field" name="email" id="email" required />
            <label for="email" class="label">Email</label>
            <i class='bx bx-envelope icon'></i>
          </div>

          <div class="input_box">
            <input type="password" class="input-field" name="password" id="password" required />
            <label for="password" class="label">Password</label>
            <i class='bx bx-lock-alt icon'></i>
          </div>

          <div class="input_box">
            <input type="text" class="input-field" name="phone" id="phone" required />
            <label for="phone" class="label">Phone Number</label>
            <i class='bx bx-phone icon'></i>
          </div>

          <div class="input_box">
            <input type="submit" class="input-submit" value="Create Admin" />
          </div>
        </form>
      </div>
    </div>
  </div>
</main>


</body>
</html>
