<?php
session_start();
require '../../db.php';
//if ($_SESSION['role'] !== 'admin') {
  //header("Location: ../homepage/main.php");
  //exit;
//}
// Handle new user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
  $full_name = trim($_POST['full_name']);
  $email     = trim($_POST['email']);
  $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $phone     = trim($_POST['phone']);
  $role      = 'admin';

  $check = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
  $check->bind_param("s", $email);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email is already registered.";
  } else {
    $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $password, $phone, $role);

    if ($stmt->execute()) {
      $_SESSION['success'] = "Admin account created successfully.";
    } else {
      $_SESSION['error'] = "Something went wrong. Please try again.";
    }
  }
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
  $id = intval($_POST['user_id']);
  $full_name = trim($_POST['full_name']);
  $email     = trim($_POST['email']);
  $phone     = trim($_POST['phone']);

  $stmt = $mysqli->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
  $stmt->bind_param("sssi", $full_name, $email, $phone, $id);

  if ($stmt->execute()) {
    $_SESSION['success'] = "User updated successfully.";
  } else {
    $_SESSION['error'] = "Failed to update user.";
  }
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

$users = $mysqli->query("SELECT * FROM users ORDER BY full_name ASC");
$edit_user = null;
if (isset($_GET['id'])) {
  $edit_id = intval($_GET['id']);
  $edit_stmt = $mysqli->prepare("SELECT * FROM users WHERE user_id = ?");
  $edit_stmt->bind_param("i", $edit_id);
  $edit_stmt->execute();
  $edit_user = $edit_stmt->get_result()->fetch_assoc();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Management</title>
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

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
    }

    h2 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 25px;
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
      cursor: pointer;
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

    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: var(--white-color);
      padding: 2rem;
      border-radius: var(--border-radius-s);
      width: 100%;
      max-width: 500px;
      position: relative;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .modal-content h2 {
      margin-bottom: 1rem;
      color: var(--dark-color);
      text-align: center;
    }

    .close {
      position: absolute;
      right: 1rem;
      top: 1rem;
      font-size: 1.5rem;
      color: var(--dark-color);
      cursor: pointer;
    }

    /* Input Form Styles */
    .input_box {
      position: relative;
      margin-bottom: 1.5rem;
    }

    .input-field {
      width: 100%;
      padding: 0.9rem 2.5rem;
      border: 1px solid var(--medium-gray-color);
      border-radius: var(--border-radius-s);
      background-color: var(--light-pink-color);
      font-size: var(--font-size-n);
      color: var(--dark-color);
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

    .icon {
      position: absolute;
      top: 50%;
      left: 0.8rem;
      transform: translateY(-50%);
      font-size: 1.2rem;
      color: var(--dark-color);
    }

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
    }

    .input-submit:hover {
      background-color: var(--secondary-color);
    }

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

    .toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #eaffea;
  color: #2d8a2d;
  padding: 14px 20px;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  animation: fadeOut 4s forwards;
}

.toast-error {
  background-color: #ffeaea;
  color: #e74c3c;
}

@keyframes fadeOut {
  0% { opacity: 1; }
  90% { opacity: 1; }
  100% { opacity: 0; transform: translateY(-20px); }
}

    
  </style>
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/Logo.jpg" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../home_dashboard/home.php"><i class='bx bx-home'></i>Home</a>
    <hr>
    <a href="../manage_accounts/accounts.php" class="active"><i class='bx bx-camera'></i>User Management</a>
    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php" class="button">📝 View Feedback Reports</a>
    <hr>
    <a href="#"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

  <?php if (isset($success)): ?>
    <div id="toast" class="toast"><?= $success ?></div>
  <?php endif; ?>


  <!-- Main Content -->
  <main class="content">
    <h2>User Management</h2>
    <button class="add-btn" onclick="openModal()">➕ Add New User</button>
    <table>
      <thead>
        <tr>
          <th>User ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($user = $users->fetch_assoc()): ?>
        <tr>
          <td><?= $user['user_id'] ?></td>
          <td><?= htmlspecialchars($user['full_name']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['phone']) ?></td>
          <td><?= $user['role'] ?></td>
          <td class="actions">
            <a href="?id=<?= $user['user_id'] ?>" class="edit-btn">Edit</a>

            <a href="delete.php?id=<?= $user['user_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <!-- Modal -->
    <div id="userModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Create Admin Account</h2>

        <?php if (isset($error)): ?>
          <p class="message-error"><?= $error ?></p>
        <?php elseif (isset($success)): ?>
          <p class="message-success"><?= $success ?></p>
          <script>setTimeout(closeModal, 1500);</script>
        <?php endif; ?>

        <form method="POST">
          <div class="input_box">
            <input type="text" class="input-field" name="full_name" required />
            <label class="label">Full Name</label>
            <i class='bx bx-user icon'></i>
          </div>

          <div class="input_box">
            <input type="email" class="input-field" name="email" required />
            <label class="label">Email</label>
            <i class='bx bx-envelope icon'></i>
          </div>

          <div class="input_box">
            <input type="password" class="input-field" name="password" required />
            <label class="label">Password</label>
            <i class='bx bx-lock-alt icon'></i>
          </div>

          <div class="input_box">
            <input type="text" class="input-field" name="phone" required />
            <label class="label">Phone Number</label>
            <i class='bx bx-phone icon'></i>
          </div>

          <div class="input_box">
            <input type="submit" class="input-submit" value="Create Admin" />
          </div>
        </form>
      </div>
    </div>

    <?php if (isset($edit_user)): ?>
  <div id="editModal" class="modal" style="display:flex;">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2>Edit User</h2>
      <form method="POST">
        <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
        <div class="input_box">
          <input type="text" name="full_name" class="input-field" value="<?= htmlspecialchars($edit_user['full_name']) ?>" required>
          <label class="label">Full Name</label>
          <i class='bx bx-user icon'></i>
        </div>
        <div class="input_box">
          <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
          <label class="label">Email</label>
          <i class='bx bx-envelope icon'></i>
        </div>
        <div class="input_box">
          <input type="text" name="phone" class="input-field" value="<?= htmlspecialchars($edit_user['phone']) ?>" required>
          <label class="label">Phone</label>
          <i class='bx bx-phone icon'></i>
        </div>
        <div class="input_box">
          <input type="submit" name="update_user" class="input-submit" value="Update User">
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

  </main>

  <script>
    function openModal() {
      document.getElementById('userModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('userModal').style.display = 'none';
    }

    window.onclick = function(e) {
      const modal = document.getElementById('userModal');
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    }
  </script>
  <script>
  function closeModal() {
    document.getElementById('editModal').style.display = 'none';
    window.history.replaceState(null, null, window.location.pathname); // remove ?id=xx
  }
</script>


</body>
<?php if (isset($_SESSION['success'])): ?>
  <div class="toast toast-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
  <div class="toast toast-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

</html>