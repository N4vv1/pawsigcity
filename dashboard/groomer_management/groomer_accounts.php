<?php
session_start();
require '../../db.php';

// Handle new groomer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_groomer'])) {
    $groomer_name = trim($_POST['groomer_name']);
    $email        = trim($_POST['email']);
    $password     = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if email exists
    pg_prepare($conn, "check_groomer", "SELECT 1 FROM groomer WHERE email=$1");
    $check = pg_execute($conn, "check_groomer", [$email]);

    if ($check === false) {
        $_SESSION['error'] = "Database error: " . pg_last_error($conn);
    } elseif (pg_num_rows($check) > 0) {
        $_SESSION['error'] = "Email is already registered.";
    } else {
        pg_prepare($conn, "insert_groomer", "INSERT INTO groomer (groomer_name, email, password) VALUES ($1,$2,$3)");
        $result = pg_execute($conn, "insert_groomer", [$groomer_name, $email, $password]);

        if ($result) {
            $_SESSION['success'] = "Groomer account created successfully.";
        } else {
            $_SESSION['error'] = "Something went wrong: " . pg_last_error($conn);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle groomer update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_groomer'])) {
    $id           = intval($_POST['groomer_id']);
    $groomer_name = trim($_POST['groomer_name']);
    $email        = trim($_POST['email']);

    pg_prepare($conn, "update_groomer", "UPDATE groomer SET groomer_name=$1, email=$2 WHERE groomer_id=$3");
    $result = pg_execute($conn, "update_groomer", [$groomer_name, $email, $id]);

    if ($result) {
        $_SESSION['success'] = "Groomer updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update groomer: " . pg_last_error($conn);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch groomers
$groomers = pg_query($conn, "SELECT * FROM groomer ORDER BY groomer_name ASC");
if ($groomers === false) {
    die("Query failed: " . pg_last_error($conn));
}

// If editing specific groomer
$edit_groomer = null;
if (isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    pg_prepare($conn, "get_groomer", "SELECT * FROM groomer WHERE groomer_id=$1");
    $result = pg_execute($conn, "get_groomer", [$edit_id]);
    if ($result !== false) {
        $edit_groomer = pg_fetch_assoc($result);
    } else {
        $_SESSION['error'] = "Failed to fetch groomer: " . pg_last_error($conn);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Groomer Management</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="../pawsigcity/icons/pawsig.png">
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

/* Sidebar */
.sidebar {
  width: 260px;
  height: 100vh;
  background-color: var(--primary-color);
  padding: 30px 20px;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.sidebar .logo {
  text-align: center;
  margin-bottom: 20px;
}

.sidebar hr {
    border: none; /* remove default border */
    height: 1px; /* thin line */
    background-color: #FFE29D; /* yellow color */
    margin: 10px 0; /* spacing */
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
  font-weight: var(--font-weight-semi-bold);
  transition: 0.3s;
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

/* Main content */
.content {
  margin-left: 260px;
  padding: 40px;
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
  color: var(--dark-color);
  font-weight: var(--font-weight-semi-bold);
  cursor: pointer;
  border: none;
  transition: 0.3s;
  margin-bottom: 20px;
}

.add-btn:hover {
  background-color: var(--secondary-color);
}

/* Table */
table {
  width: 100%;
  border-collapse: collapse;
  background-color: var(--white-color);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border-radius: var(--border-radius-s);
  overflow: hidden;
}

th, td {
  padding: 14px 10px;
  border-bottom: 1px solid var(--medium-gray-color);
  text-align: center;
}

th {
  background-color: var(--primary-color);
  font-weight: var(--font-weight-bold);
  color: var(--dark-color);
}

.actions a {
  padding: 6px 14px;
  font-size: var(--font-size-s);
  font-weight: var(--font-weight-semi-bold);
  text-decoration: none;
  border-radius: var(--border-radius-s);
  display: inline-block;
  margin: 0 5px;
  transition: 0.2s;
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

/* Modal */
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
  max-width: 450px;
  position: relative;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-content h2 {
  margin-bottom: 1rem;
  text-align: center;
  color: var(--dark-color);
}

.close {
  position: absolute;
  right: 1rem;
  top: 1rem;
  font-size: 1.5rem;
  color: var(--dark-color);
  cursor: pointer;
}

/* Input Fields */
.input_box { margin-bottom: 1.2rem; position: relative; }
.input-field {
  width: 100%;
  padding: 0.9rem;
  border-radius: var(--border-radius-s);
  border: 1px solid var(--medium-gray-color);
  font-size: var(--font-size-n);
  transition: 0.3s;
}

.input-field:focus {
  outline: none;
  border-color: var(--primary-color);
}

/* Submit button */
.input-submit {
  width: 100%;
  padding: 0.9rem;
  border-radius: var(--border-radius-s);
  border: none;
  background-color: var(--primary-color);
  font-weight: var(--font-weight-semi-bold);
  color: var(--dark-color);
  cursor: pointer;
  transition: 0.3s;
}

.input-submit:hover {
  background-color: var(--secondary-color);
}

/* Toast Notifications */
.toast {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 14px 20px;
  border-radius: var(--border-radius-s);
  font-size: var(--font-size-s);
  font-weight: var(--font-weight-semi-bold);
  box-shadow: 0 5px 12px rgba(0,0,0,0.15);
  z-index: 9999;
  animation: fadeOut 4s forwards;
}

.toast-success {
  background-color: #eaffea;
  color: #2d8a2d;
}

.toast-error {
  background-color: #ffeaea;
  color: #e74c3c;
}

@keyframes fadeOut {
  0%, 90% { opacity: 1; }
  100% { opacity: 0; transform: translateY(-20px); }
}
</style>

</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <img src="../pawsigcity/icons/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>
    <a href="../manage_accounts/accounts.php"><i class='bx bx-camera'></i>User Management</a>
    <hr>
    <a href="../groomer_management/groomer_accounts.php" class="active"><i class='bx bx-user'></i>Groomer Management</a>
    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="#"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>


<main class="content">
  <button class="add-btn" onclick="openModal()">➕ Add Groomer</button>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
<?php if ($groomers && pg_num_rows($groomers) > 0): ?>
    <?php while($g = pg_fetch_assoc($groomers)): ?>
        <tr>
            <td><?= $g['groomer_id'] ?></td>
            <td><?= htmlspecialchars($g['groomer_name']) ?></td>
            <td><?= htmlspecialchars($g['email']) ?></td>
            <td class="actions">
                <a href="?id=<?= $g['groomer_id'] ?>" class="edit-btn">Edit</a>
                <a href="delete_groomer.php?id=<?= $g['groomer_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="4">No groomers found.</td></tr>
<?php endif; ?>
</tbody>

  </table>

  <!-- Add Groomer Modal -->
  <div id="groomerModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Create Groomer</h2>
    <form method="POST">
      <input type="hidden" name="create_groomer" value="1">
      <div class="input_box">
        <input type="text" name="groomer_name" class="input-field" placeholder="Enter Groomer Name" required>
      </div>
      <div class="input_box">
        <input type="email" name="email" class="input-field" placeholder="Enter Email Address" required>
      </div>
      <div class="input_box">
        <input type="password" name="password" class="input-field" placeholder="Enter Password" required>
      </div>
      <input type="submit" class="input-submit" value="Create Groomer">
    </form>
  </div>
</div>


  <!-- Edit Groomer Modal -->
  <?php if(isset($edit_groomer)): ?>
    <div id="editGroomerModal" class="modal" style="display:flex;">
      <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit Groomer</h2>
        <form method="POST">
          <input type="hidden" name="groomer_id" value="<?= $edit_groomer['groomer_id'] ?>">
          <div class="input_box">
            <input type="text" name="groomer_name" class="input-field" value="<?= htmlspecialchars($edit_groomer['groomer_name']) ?>" required>
          </div>
          <div class="input_box">
            <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($edit_groomer['email']) ?>" required>
          </div>
          <input type="submit" name="update_groomer" class="input-submit" value="Update Groomer">
        </form>
      </div>
    </div>
  <?php endif; ?>

</main>

<script>
function openModal() { document.getElementById('groomerModal').style.display='flex'; }
function closeModal() { document.getElementById('groomerModal').style.display='none'; }
function closeEditModal() { document.getElementById('editGroomerModal').style.display='none'; window.history.replaceState(null,null,window.location.pathname); }

window.onclick = function(e){
  const modal = document.getElementById('groomerModal');
  if(e.target === modal) closeModal();
}
</script>

<?php if (isset($_SESSION['success'])): ?>
  <div class="toast toast-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
  <div class="toast toast-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

</body>
</html>
