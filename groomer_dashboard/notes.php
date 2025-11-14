<?php
session_start();
require_once '../db.php';

// Check if groomer is logged in
if (!isset($_SESSION['groomer_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$groomer_id = $_SESSION['groomer_id'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id']);
    $notes = pg_escape_string($conn, $_POST['notes']);
    
    $query = "UPDATE appointments SET notes = '$notes' WHERE appointment_id = $appointment_id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $success_message = "Notes saved successfully!";
    } else {
        $error_message = "Failed to save notes. Please try again.";
    }
}

// FIXED: Query with proper type casting - only show appointments for THIS groomer
$query = "
  SELECT a.appointment_id, 
         DATE(a.appointment_date) AS appointment_date, 
         p.name AS pet_name, 
         p.breed, 
         u.first_name || ' ' || COALESCE(u.middle_name || ' ', '') || u.last_name AS full_name,
         a.notes
  FROM appointments a
  JOIN pets p ON a.pet_id = p.pet_id
  JOIN users u ON p.user_id::text = u.user_id
  WHERE a.groomer_id = $1
  ORDER BY a.appointment_date DESC
";

$appointments = pg_query_params($conn, $query, [$groomer_id]);

if (!$appointments) {
    die("Query failed: " . pg_last_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Groomer | Session Notes</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../homepage/images/pawsig.png">
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
      --light-pink-color: #faf4f5;
      --medium-gray-color: #ccc;
      --edit-color: #4CAF50;
      --font-size-s: 0.9rem;
      --font-size-n: 1rem;
      --font-size-l: 1.5rem;
      --font-size-xl: 2rem;
      --font-weight-semi-bold: 600;
      --font-weight-bold: 700;
      --border-radius-s: 8px;
      --border-radius-circle: 50%;
      --sidebar-width: 260px;
      --transition-speed: 0.3s;
      --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
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
      min-height: 100vh;
    }

    /* TOAST NOTIFICATION */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      padding: 16px 24px;
      border-radius: 10px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 300px;
      max-width: 400px;
      font-weight: 500;
      font-size: 0.95rem;
      animation: slideInToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      opacity: 0;
    }

    @keyframes slideInToast {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOutToast {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }

    .toast.show {
      opacity: 1;
    }

    .toast.hide {
      animation: slideOutToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
    }

    .toast-success {
      background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
      color: white;
    }

    .toast-error {
      background: linear-gradient(135deg, #F44336 0%, #e53935 100%);
      color: white;
    }

    .toast i {
      font-size: 24px;
      flex-shrink: 0;
    }

    .toast-message {
      flex: 1;
    }

    .toast-close {
      cursor: pointer;
      font-size: 20px;
      opacity: 0.8;
      transition: opacity 0.2s;
      flex-shrink: 0;
    }

    .toast-close:hover {
      opacity: 1;
    }

    .mobile-menu-btn {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1001;
      background: var(--primary-color);
      border: none;
      border-radius: 8px;
      padding: 12px;
      cursor: pointer;
      box-shadow: var(--shadow-light);
      transition: var(--transition-speed);
    }

    .mobile-menu-btn i {
      font-size: 24px;
      color: var(--dark-color);
    }

    .mobile-menu-btn:hover {
      background: var(--secondary-color);
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 998;
      opacity: 0;
      transition: opacity var(--transition-speed);
    }

    .sidebar-overlay.active {
      display: block;
      opacity: 1;
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
      overflow-y: auto;
      box-shadow: var(--shadow-light);
      transition: transform var(--transition-speed);
      z-index: 999;
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
      border-radius: 14px;
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
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    .header {
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 10px;
      font-weight: 600;
    }

    .header p {
      color: #666;
      font-size: 0.95rem;
    }

    .form-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      max-width: 800px;
      margin: 0 auto;
    }

    .form-section h2 {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
    }

    .form-section form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-group label {
      font-weight: 600;
      color: var(--dark-color);
      font-size: 0.95rem;
    }

    .form-group select,
    .form-group textarea {
      padding: 12px 15px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      color: var(--dark-color);
      background-color: var(--light-pink-color);
      transition: all 0.2s;
      font-family: "Montserrat", sans-serif;
    }

    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 150px;
    }

    .submit-btn {
      background: var(--dark-color);
      color: var(--white-color);
      padding: 14px 30px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .submit-btn:hover {
      background: #1a1a1a;
      transform: translateY(-1px);
    }

    .submit-btn i {
      font-size: 20px;
    }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 80px;
      color: #ddd;
      margin-bottom: 20px;
      display: block;
    }

    .empty-state h3 {
      font-size: 1.5rem;
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .empty-state p {
      font-size: 1rem;
      color: #999;
    }

    @media screen and (max-width: 1024px) {
      .form-section {
        padding: 30px;
      }
    }

    @media screen and (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .content {
        margin-left: 0;
        width: 100%;
        padding: 80px 20px 40px;
      }

      .header h1 {
        font-size: 1.6rem;
      }

      .form-section {
        padding: 25px;
      }

      .toast {
        bottom: 20px;
        right: 20px;
        left: 20px;
        min-width: auto;
      }
    }

    @media screen and (max-width: 480px) {
      .content {
        padding: 70px 10px 30px;
      }

      .sidebar .logo img {
        width: 60px;
        height: 60px;
      }

      .menu a {
        padding: 8px 10px;
        font-size: 0.9rem;
      }

      .header h1 {
        font-size: 1.4rem;
      }

      .form-section {
        padding: 20px;
      }

      .form-section h2 {
        font-size: 1.1rem;
      }

      .form-group label {
        font-size: 0.85rem;
      }

      .form-group select,
      .form-group textarea {
        padding: 10px 12px;
        font-size: 0.9rem;
      }

      .submit-btn {
        padding: 12px 24px;
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>

<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar">
  <div class="logo">
    <img src="../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="home_groomer.php"><i class='bx bx-calendar-check'></i>Appointments</a>
    <hr>
    <a href="history_log.php"><i class='bx bx-history'></i>History Logs</a>
    <hr>
    <a href="notes.php" class="active"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="https://pawsigcity.onrender.com/homepage/login/loginform.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>Session Notes</h1>
    <p>Document important details about your grooming sessions</p>
  </div>

  <?php if (pg_num_rows($appointments) == 0): ?>
    <div class="form-section">
      <div class="empty-state">
        <i class='bx bx-note'></i>
        <h3>No Appointments Available</h3>
        <p>You don't have any appointments to add notes to</p>
      </div>
    </div>
  <?php else: ?>
    <div class="form-section">
      <h2>Add or Update Notes</h2>
      <form method="POST">
        <div class="form-group">
          <label for="appointment_id">Select Appointment:</label>
          <select name="appointment_id" id="appointment_id" required onchange="loadNotes(this.value)">
            <option value="">-- Choose an appointment --</option>
            <?php 
            $appointments_array = [];
            while ($row = pg_fetch_assoc($appointments)): 
              $appointments_array[] = $row;
            ?>
              <option value="<?= $row['appointment_id'] ?>" 
                      data-notes="<?= htmlspecialchars($row['notes'] ?? '') ?>">
                <?= $row['appointment_date'] ?> - <?= htmlspecialchars($row['full_name']) ?> - <?= htmlspecialchars($row['pet_name']) ?> (<?= htmlspecialchars($row['breed']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="notes">Session Notes:</label>
          <textarea 
            name="notes" 
            id="notes" 
            placeholder="Enter details about the grooming session, pet behavior, special instructions, etc..." 
            required></textarea>
        </div>

        <button type="submit" class="submit-btn">
          <i class='bx bx-save'></i> Save Notes
        </button>
      </form>
    </div>
  <?php endif; ?>
</main>

<script>
function showToast(message, type = 'success') {
  const existingToasts = document.querySelectorAll('.toast');
  existingToasts.forEach(toast => toast.remove());

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  const icon = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';
  
  toast.innerHTML = `
    <i class='bx ${icon}'></i>
    <span class="toast-message">${message}</span>
    <i class='bx bx-x toast-close' onclick="closeToast(this)"></i>
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.classList.add('show');
  }, 10);
  
  setTimeout(() => {
    hideToast(toast);
  }, 4000);
}

function hideToast(toast) {
  toast.classList.add('hide');
  setTimeout(() => {
    toast.remove();
  }, 400);
}

function closeToast(closeBtn) {
  const toast = closeBtn.closest('.toast');
  hideToast(toast);
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

function loadNotes(appointmentId) {
  const select = document.getElementById('appointment_id');
  const selectedOption = select.options[select.selectedIndex];
  const notes = selectedOption.getAttribute('data-notes');
  
  document.getElementById('notes').value = notes || '';
}

document.addEventListener('DOMContentLoaded', function() {
  const menuLinks = document.querySelectorAll('.menu a');
  menuLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      }
    });
  });
});

<?php if ($success_message): ?>
  showToast('<?= addslashes($success_message) ?>', 'success');
<?php elseif ($error_message): ?>
  showToast('<?= addslashes($error_message) ?>', 'error');
<?php endif; ?>
</script>

</body>
</html>