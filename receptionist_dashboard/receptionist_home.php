<?php
session_start();
include '../db.php'; // connection file

$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.status,
        a.groomer_name,
        p.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed
    FROM appointments a
    JOIN packages p ON a.package_id::text = p.package_id
    JOIN pets pet ON a.pet_id = pet.pet_id
    ORDER BY a.appointment_date DESC
";

$result = pg_query($conn, $query);

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

// Get all groomers for the dropdown
$groomers_query = "SELECT DISTINCT groomer_name FROM groomer ORDER BY groomer_name";
$groomers_result = pg_query($conn, $groomers_query);

// Get all packages for the dropdown
$packages_query = "SELECT package_id, name FROM packages ORDER BY name";
$packages_result = pg_query($conn, $packages_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receptionist Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
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
    }

    /* NOTIFICATION MESSAGES */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 16px 20px;
      border-radius: var(--border-radius-s);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      max-width: 400px;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .notification.success {
      background-color: #4CAF50;
      color: white;
    }

    .notification.error {
      background-color: #FF6B6B;
      color: white;
    }

    .notification i {
      font-size: 24px;
    }

    .notification .close-notification {
      margin-left: auto;
      cursor: pointer;
      font-size: 20px;
      opacity: 0.8;
      transition: opacity 0.2s;
    }

    .notification .close-notification:hover {
      opacity: 1;
    }

    /* MOBILE MENU BUTTON */
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

    /* SIDEBAR OVERLAY */
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

    /* Content */
    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
      transition: margin-left var(--transition-speed), width var(--transition-speed);
      overflow-x: auto;
    }

    h2 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 25px;
    }

    /* Table Container for horizontal scroll on mobile */
    .table-container {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table {
      width: 100%;
      min-width: 900px;
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

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #ffe29d33;
    }

    /* Action buttons in table */
    .action-buttons {
      display: flex;
      gap: 8px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .action-buttons button {
      padding: 6px 12px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.3s;
    }

    .edit-btn {
      background-color: var(--primary-color);
      color: var(--dark-color);
    }

    .edit-btn:hover {
      background-color: var(--secondary-color);
    }

    .cancel-btn-table {
      background-color: #FF6B6B;
      color: #fff;
    }

    .cancel-btn-table:hover {
      background-color: #FF4949;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
      backdrop-filter: blur(3px);
    }

    .modal-content {
      background-color: var(--white-color);
      margin: 5% auto;
      padding: 30px 25px;
      width: 90%;
      max-width: 450px;
      border-radius: var(--border-radius-s);
      box-shadow: 0 10px 25px rgba(0,0,0,0.25);
      position: relative;
      transition: all 0.3s ease;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-content h2 {
      font-size: var(--font-size-l);
      color: var(--dark-color);
      margin-bottom: 10px;
      padding-bottom: 5px;
      border-bottom: 2px solid var(--primary-color);
    }

    .modal-content hr {
      border: none;
      border-top: 1px solid var(--medium-gray-color);
      margin: 10px 0 20px 0;
    }

    .modal-content form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      background-color: #fafafa;
      padding: 15px;
      border-radius: var(--border-radius-s);
    }

    .modal-content label {
      font-weight: var(--font-weight-semi-bold);
      color: var(--dark-color);
      margin-bottom: 5px;
      font-size: 0.95rem;
    }

    .modal-content input,
    .modal-content select {
      width: 100%;
      padding: 10px;
      border: 1px solid var(--medium-gray-color);
      border-radius: var(--border-radius-s);
      font-size: var(--font-size-n);
    }

    .modal-content input:focus,
    .modal-content select:focus {
      border-color: var(--primary-color);
      outline: none;
    }

    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 10px;
      flex-wrap: wrap;
    }

    .modal-content button {
      padding: 10px 18px;
      border: none;
      border-radius: var(--border-radius-s);
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
      transition: background 0.3s;
      font-size: 0.9rem;
    }

    .modal-content button[type="submit"] {
      background-color: var(--primary-color);
      color: var(--dark-color);
    }

    .modal-content button[type="submit"]:hover {
      background-color: var(--secondary-color);
    }

    .modal-content .cancel-btn {
      background-color: #FF6B6B;
      color: var(--white-color);
    }

    .modal-content .cancel-btn:hover {
      background-color: #FF4B4B;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      line-height: 1;
    }

    .close:hover,
    .close:focus {
      color: black;
    }

    /* RESPONSIVE DESIGN */
    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.9rem;
        min-width: 800px;
      }

      th, td {
        padding: 12px 8px;
      }

      .action-buttons button {
        font-size: 0.8rem;
        padding: 5px 10px;
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

      h2 {
        font-size: 1.6rem;
      }

      table {
        font-size: 0.85rem;
        min-width: 750px;
      }

      th, td {
        padding: 10px 6px;
      }

      .action-buttons {
        flex-direction: column;
        gap: 5px;
      }

      .action-buttons button {
        width: 100%;
        font-size: 0.8rem;
      }

      .modal-content {
        margin: 10% auto;
        padding: 25px 20px;
      }

      .modal-content h2 {
        font-size: 1.3rem;
      }

      .notification {
        right: 10px;
        left: 10px;
        max-width: calc(100% - 20px);
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

      .menu a i {
        font-size: 18px;
      }

      h2 {
        font-size: 1.4rem;
        margin-bottom: 20px;
      }

      table {
        font-size: 0.75rem;
        min-width: 650px;
      }

      th, td {
        padding: 8px 4px;
      }

      .action-buttons button {
        font-size: 0.75rem;
        padding: 5px 8px;
      }

      .modal-content {
        width: 95%;
        padding: 20px 15px;
      }

      .modal-content h2 {
        font-size: 1.2rem;
      }

      .modal-content label {
        font-size: 0.85rem;
      }

      .modal-content input,
      .modal-content select {
        padding: 8px;
        font-size: 0.9rem;
      }

      .modal-content button {
        padding: 8px 14px;
        font-size: 0.85rem;
      }

      .modal-buttons {
        flex-direction: column;
      }

      .modal-buttons button {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<!-- Notification Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="notification success" id="notification">
  <i class='bx bx-check-circle'></i>
  <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
  <span class="close-notification" onclick="closeNotification()">&times;</span>
</div>
<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="notification error" id="notification">
  <i class='bx bx-error-circle'></i>
  <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
  <span class="close-notification" onclick="closeNotification()">&times;</span>
</div>
<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <img src="../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="#" class="active"><i class='bx bx-home'></i>All Appointments</a>
  </nav>
</aside>

<!-- Main Content -->
<main class="content">
  <h2>All Appointments</h2>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Appointment ID</th>
          <th>Date</th>
          <th>Package</th>
          <th>Pet Name</th>
          <th>Breed</th>
          <th>Status</th>
          <th>Groomer</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $counter = 1; ?>
        <?php while ($row = pg_fetch_assoc($result)): ?>
          <tr>
            <td><?= $counter++ ?></td>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td><?= htmlspecialchars($row['package_name']) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= htmlspecialchars($row['pet_breed']) ?></td>
            <td>
              <?php
                $status = strtolower($row['status']);
                $status_color = match($status) {
                  'confirmed' => '#4CAF50',
                  'completed' => '#2196F3',
                  'cancelled' => '#FF6B6B',
                  'no_show'  => '#FFC107',
                  default => '#ccc',
                };
              ?>
              <span style="color:<?= $status_color ?>; font-weight:600;"><?= ucfirst($status) ?></span>
            </td>
            <td><?= htmlspecialchars($row['groomer_name']) ?></td>
            <td>
              <div class="action-buttons">
                <button class="edit-btn"
                        data-id="<?= $row['appointment_id'] ?>"
                        data-date="<?= $row['appointment_date'] ?>"
                        data-package="<?= $row['package_id'] ?>"
                        data-status="<?= $row['status'] ?>"
                        data-groomer="<?= htmlspecialchars($row['groomer_name']) ?>">
                  Edit
                </button>
                <button class="cancel-btn-table"
                        onclick="if(confirm('Cancel this appointment? The customer will be notified via email.')) { window.location.href='cancel_appointment.php?id=<?= $row['appointment_id'] ?>'; }">
                  Cancel
                </button>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Edit Appointment</h2>
    <hr>
    <form id="editForm" method="POST" action="edit_appointment.php">
      <input type="hidden" name="appointment_id" id="modalAppointmentId">

      <label>Date:</label>
      <input type="datetime-local" name="appointment_date" id="modalDate" required>

      <label>Package:</label>
      <select name="package_id" id="modalPackage" required>
        <?php
        pg_result_seek($packages_result, 0);
        while ($pkg = pg_fetch_assoc($packages_result)):
        ?>
        <option value="<?= $pkg['package_id'] ?>"><?= htmlspecialchars($pkg['name']) ?></option>
        <?php endwhile; ?>
      </select>

      <label>Groomer:</label>
      <select name="groomer_name" id="modalGroomer" required>
        <?php
        pg_result_seek($groomers_result, 0);
        while ($g = pg_fetch_assoc($groomers_result)):
        ?>
        <option value="<?= htmlspecialchars($g['groomer_name']) ?>"><?= htmlspecialchars($g['groomer_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <label>Status:</label>
      <select name="status" id="modalStatus" required>
        <option value="confirmed">Confirmed</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        <option value="no_show">No Show</option>
      </select>

      <div class="modal-buttons">
        <button type="submit">Update Appointment</button>
        <button type="button" class="cancel-btn" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

function closeNotification() {
  const notification = document.getElementById('notification');
  if (notification) {
    notification.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => {
      notification.remove();
    }, 300);
  }
}

// Auto-hide notification after 5 seconds
setTimeout(() => {
  const notification = document.getElementById('notification');
  if (notification) {
    closeNotification();
  }
}, 5000);

// Close sidebar when clicking a link on mobile
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

  // Modal functionality
  const modal = document.getElementById("editModal");
  const closeBtn = modal.querySelector(".close");
  const editButtons = document.querySelectorAll(".edit-btn");

  editButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      document.getElementById("modalAppointmentId").value = btn.dataset.id;
      
      let dateValue = btn.dataset.date;
      if (dateValue) {
        dateValue = dateValue.substring(0, 16).replace(' ', 'T');
      }
      document.getElementById("modalDate").value = dateValue;
      
      document.getElementById("modalPackage").value = btn.dataset.package;
      document.getElementById("modalStatus").value = btn.dataset.status;
      document.getElementById("modalGroomer").value = btn.dataset.groomer;

      modal.style.display = "block";
    });
  });

  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (event) => {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
});
</script>

<style>
@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}
</style>

</body>
</html>