<?php
session_start();
include '../db.php';

$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.status,
        a.groomer_name,
        p.package_id,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed,
        u.first_name,
        u.last_name,
        CONCAT(u.first_name, ' ', u.last_name) AS customer_name
    FROM appointments a
    LEFT JOIN packages p ON a.package_id::text = p.package_id
    LEFT JOIN pets pet ON a.pet_id = pet.pet_id
    LEFT JOIN users u ON LPAD(a.user_id::text, 5, '0') = u.user_id
    ORDER BY a.appointment_date DESC
";

$result = pg_query($conn, $query);

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

$groomers_query = "SELECT DISTINCT groomer_name FROM groomer ORDER BY groomer_name";
$groomers_result = pg_query($conn, $groomers_query);

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
      --disabled-color: #e0e0e0;
      --edit-color: #4CAF50;
      --delete-color: #F44336;
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

    /* TABLE SECTION */
    .table-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      overflow-x: auto;
    }

    .table-section h2 {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
    }

    table {
      width: 100%;
      min-width: 1000px;
      border-collapse: collapse;
    }

    th, td {
      padding: 15px 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }

    th {
      background-color: #fafafa;
      color: var(--dark-color);
      font-weight: 600;
      font-size: 0.9rem;
      position: sticky;
      top: 0;
    }

    tbody tr:hover {
      background-color: #fafafa;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-badge.confirmed {
      background: rgba(76, 175, 80, 0.1);
      color: #4CAF50;
    }

    .status-badge.completed {
      background: rgba(33, 150, 243, 0.1);
      color: #2196F3;
    }

    .status-badge.cancelled {
      background: rgba(244, 67, 54, 0.1);
      color: #F44336;
    }

    .status-badge.no_show {
      background: rgba(255, 193, 7, 0.1);
      color: #FFC107;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .action-buttons button {
      padding: 6px 14px;
      font-size: 0.85rem;
      font-weight: 600;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .action-buttons button:disabled {
      background-color: var(--disabled-color) !important;
      color: #999 !important;
      cursor: not-allowed;
      opacity: 0.6;
    }

    .edit-btn {
      background: rgba(76, 175, 80, 0.1);
      color: var(--edit-color);
    }

    .edit-btn:hover:not(:disabled) {
      background: var(--edit-color);
      color: var(--white-color);
    }

    .cancel-btn-table {
      background: rgba(244, 67, 54, 0.1);
      color: var(--delete-color);
    }

    .cancel-btn-table:hover:not(:disabled) {
      background: var(--delete-color);
      color: var(--white-color);
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-content h2 {
      margin-bottom: 25px;
      color: var(--dark-color);
      font-size: 1.5rem;
      font-weight: 600;
    }

    .close {
      position: absolute;
      right: 20px;
      top: 20px;
      font-size: 1.8rem;
      color: #999;
      cursor: pointer;
      transition: color 0.2s;
    }

    .close:hover {
      color: var(--dark-color);
    }

    .modal-content form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .modal-content label {
      display: block;
      margin-bottom: 8px;
      color: var(--dark-color);
      font-weight: 500;
      font-size: 0.9rem;
    }

    .modal-content input,
    .modal-content select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: var(--light-pink-color);
      font-size: 1rem;
      color: var(--dark-color);
      transition: all 0.2s;
    }

    .modal-content input:focus,
    .modal-content select:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 10px;
      flex-wrap: wrap;
    }

    .modal-content button {
      padding: 14px 20px;
      border: none;
      border-radius: var(--border-radius-s);
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.9rem;
    }

    .modal-content button[type="submit"] {
      background-color: var(--dark-color);
      color: var(--white-color);
    }

    .modal-content button[type="submit"]:hover {
      background-color: #1a1a1a;
      transform: translateY(-1px);
    }

    .modal-content .cancel-btn {
      background-color: #FF6B6B;
      color: var(--white-color);
    }

    .modal-content .cancel-btn:hover {
      background-color: #FF4B4B;
    }

    @media screen and (max-width: 1024px) {
      table {
        font-size: 0.85rem;
        min-width: 900px;
      }

      th, td {
        padding: 12px 10px;
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

      .table-section {
        padding: 20px;
      }

      table {
        font-size: 0.8rem;
        min-width: 800px;
      }

      th, td {
        padding: 10px 8px;
      }

      .action-buttons {
        flex-direction: column;
        gap: 5px;
      }

      .action-buttons button {
        width: 100%;
      }

      .modal-content {
        width: 95%;
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

      table {
        font-size: 0.75rem;
        min-width: 700px;
      }

      th, td {
        padding: 8px 5px;
      }

      .modal-content {
        padding: 20px 15px;
      }

      .modal-content h2 {
        font-size: 1.2rem;
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

<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar">
  <div class="logo">
    <img src="../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="#" class="active"><i class='bx bx-home'></i>All Appointments</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>All Appointments</h1>
    <p>Manage and track all grooming appointments</p>
  </div>

  <div class="table-section">
    <h2>Appointment List</h2>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Appointment ID</th>
          <th>Date</th>
          <th>Customer</th>
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
          <?php
            $status = strtolower($row['status']);
            $is_disabled = ($status === 'completed' || $status === 'cancelled');
          ?>
          <tr>
            <td><?= $counter++ ?></td>
            <td><?= htmlspecialchars($row['appointment_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['appointment_date'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['package_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['pet_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['pet_breed'] ?? 'N/A') ?></td>
            <td>
              <span class="status-badge <?= $status ?>">
                <?= ucfirst(str_replace('_', ' ', $status)) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($row['groomer_name']) ?></td>
            <td>
              <div class="action-buttons">
                <button class="edit-btn"
                        data-id="<?= $row['appointment_id'] ?>"
                        data-date="<?= $row['appointment_date'] ?>"
                        data-package="<?= $row['package_id'] ?>"
                        data-status="<?= $row['status'] ?>"
                        data-groomer="<?= htmlspecialchars($row['groomer_name']) ?>"
                        <?= $is_disabled ? 'disabled title="Cannot edit completed/cancelled appointments"' : '' ?>>
                  <i class='bx bx-edit'></i> Edit
                </button>
                <button class="cancel-btn-table"
                        onclick="if(confirm('Cancel this appointment? The customer will be notified via email.')) { window.location.href='cancel_appointment.php?id=<?= $row['appointment_id'] ?>'; }"
                        <?= $is_disabled ? 'disabled title="Already completed/cancelled"' : '' ?>>
                  <i class='bx bx-trash'></i> Cancel
                </button>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Edit Appointment</h2>
    <form id="editForm" method="POST" action="edit_appointment.php">
      <input type="hidden" name="appointment_id" id="modalAppointmentId">

      <div>
        <label>Date:</label>
        <input type="datetime-local" name="appointment_date" id="modalDate" required>
      </div>

      <div>
        <label>Package:</label>
        <select name="package_id" id="modalPackage" required>
          <?php
          pg_result_seek($packages_result, 0);
          while ($pkg = pg_fetch_assoc($packages_result)):
          ?>
          <option value="<?= $pkg['package_id'] ?>"><?= htmlspecialchars($pkg['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Groomer:</label>
        <select name="groomer_name" id="modalGroomer" required>
          <?php
          pg_result_seek($groomers_result, 0);
          while ($g = pg_fetch_assoc($groomers_result)):
          ?>
          <option value="<?= htmlspecialchars($g['groomer_name']) ?>"><?= htmlspecialchars($g['groomer_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Status:</label>
        <select name="status" id="modalStatus" required>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="no_show">No Show</option>
        </select>
      </div>

      <div class="modal-buttons">
        <button type="submit">Update Appointment</button>
        <button type="button" class="cancel-btn" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

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

  const modal = document.getElementById("editModal");
  const closeBtn = modal.querySelector(".close");
  const editButtons = document.querySelectorAll(".edit-btn");

  editButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      if (btn.disabled) return;
      
      document.getElementById("modalAppointmentId").value = btn.dataset.id;
      
      let dateValue = btn.dataset.date;
      if (dateValue) {
        dateValue = dateValue.substring(0, 16).replace(' ', 'T');
      }
      document.getElementById("modalDate").value = dateValue;
      
      document.getElementById("modalPackage").value = btn.dataset.package;
      document.getElementById("modalStatus").value = btn.dataset.status;
      document.getElementById("modalGroomer").value = btn.dataset.groomer;

      modal.style.display = "flex";
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

<?php if (isset($_SESSION['success_message'])): ?>
  <script>
    showToast('<?= addslashes($_SESSION['success_message']); ?>', 'success');
  </script>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <script>
    showToast('<?= addslashes($_SESSION['error_message']); ?>', 'error');
  </script>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

</body>
</html>