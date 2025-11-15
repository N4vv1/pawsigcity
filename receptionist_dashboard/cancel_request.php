<?php
session_start();
include '../db.php';

// Fetch only appointments with cancel_requested = true
$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.status,
        a.cancel_reason,
        a.created_at,
        p.name AS package_name,
        pet.name AS pet_name,
        pet.breed AS pet_breed,
        u.email AS user_email,
        CONCAT(u.first_name, ' ', u.last_name) AS customer_name
    FROM appointments a
    LEFT JOIN packages p ON a.package_id::text = p.package_id
    LEFT JOIN pets pet ON a.pet_id = pet.pet_id
    LEFT JOIN users u ON LPAD(a.user_id::text, 5, '0') = u.user_id
    WHERE a.cancel_requested = true 
    AND a.status != 'cancelled'
    ORDER BY a.created_at DESC
";

$result = pg_query($conn, $query);

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reception | Cancellation Requests</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../pawsigcity/icons/pawsig2.png">
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #3ABB87;
      --light-pink-color: #faf4f5;
      --delete-color: #F44336;
      --font-size-xl: 2rem;
      --font-weight-semi-bold: 600;
      --border-radius-s: 8px;
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
      font-weight: 500;
      font-size: 0.95rem;
      animation: slideInToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      opacity: 0;
    }

    @keyframes slideInToast {
      from { transform: translateX(400px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideOutToast {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(400px); opacity: 0; }
    }

    .toast.show { opacity: 1; }
    .toast.hide { animation: slideOutToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards; }
    .toast-success { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; }
    .toast-error { background: linear-gradient(135deg, #F44336 0%, #e53935 100%); color: white; }
    .toast i { font-size: 24px; flex-shrink: 0; }
    .toast-message { flex: 1; }
    .toast-close { cursor: pointer; font-size: 20px; opacity: 0.8; transition: opacity 0.2s; flex-shrink: 0; }
    .toast-close:hover { opacity: 1; }

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

    .mobile-menu-btn i { font-size: 24px; color: var(--dark-color); }
    .mobile-menu-btn:hover { background: var(--secondary-color); }

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

    .sidebar-overlay.active { display: block; opacity: 1; }

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

    .sidebar .logo { text-align: center; margin-bottom: 20px; }
    .sidebar .logo img { width: 80px; height: 80px; border-radius: 50%; }

    .menu { display: flex; flex-direction: column; gap: 10px; }
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
    .menu a i { margin-right: 10px; font-size: 20px; }
    .menu a:hover, .menu a.active { background-color: var(--secondary-color); color: var(--dark-color); }
    .menu hr { border: none; border-top: 1px solid var(--secondary-color); margin: 9px 0; }

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    .header { margin-bottom: 30px; }
    .header h1 { font-size: var(--font-size-xl); color: var(--dark-color); margin-bottom: 10px; font-weight: 600; }
    .header p { color: #666; font-size: 0.95rem; }

    .table-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      overflow-x: auto;
    }

    .table-section h2 { font-size: 1.3rem; color: var(--dark-color); font-weight: 600; margin-bottom: 25px; }

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

    tbody tr:hover { background-color: #fafafa; }

    .cancel-btn {
      padding: 8px 16px;
      background: rgba(244, 67, 54, 0.1);
      color: var(--delete-color);
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .cancel-btn:hover {
      background: var(--delete-color);
      color: var(--white-color);
      transform: translateY(-1px);
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }

    .empty-state i {
      font-size: 4rem;
      color: #ddd;
      margin-bottom: 20px;
      display: block;
    }

    .empty-state h3 {
      font-size: 1.3rem;
      color: #666;
      margin-bottom: 10px;
    }

    .empty-state p {
      font-size: 0.95rem;
    }

    @media screen and (max-width: 768px) {
      .mobile-menu-btn { display: block; }
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      .content { margin-left: 0; width: 100%; padding: 80px 20px 40px; }
      .table-section { padding: 20px; }
      table { font-size: 0.8rem; min-width: 900px; }
      th, td { padding: 10px 8px; }
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
    <a href="receptionist_home.php"><i class='bx bx-home'></i>All Appointments</a>
    <a href="cancel_request.php" class="active"><i class='bx bx-x-circle'></i>Cancellation Requests</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>Cancellation Requests</h1>
    <p>Review and process customer cancellation requests</p>
  </div>

  <div class="table-section">
    <h2>Pending Cancellations</h2>

    <?php if (pg_num_rows($result) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Appointment ID</th>
          <th>Customer</th>
          <th>Pet Name</th>
          <th>Breed</th>
          <th>Package</th>
          <th>Appointment Date</th>
          <th>Reason</th>
          <th>Requested On</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $counter = 1; ?>
        <?php while ($row = pg_fetch_assoc($result)): ?>
          <tr>
            <td><?= $counter++ ?></td>
            <td><?= htmlspecialchars($row['appointment_id']) ?></td>
            <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['pet_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['pet_breed'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['package_name'] ?? 'N/A') ?></td>
            <td><?= date('M d, Y h:i A', strtotime($row['appointment_date'])) ?></td>
            <td><?= htmlspecialchars($row['cancel_reason'] ?? 'No reason provided') ?></td>
            <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
            <td>
              <button class="cancel-btn" 
                      onclick="if(confirm('Approve this cancellation request? The customer will be notified via email.')) { 
                        window.location.href='./process_cancellation.php?id=<?= $row['appointment_id'] ?>'; 
                      }">
                <i class='bx bx-check'></i> Approve Cancel
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
      <i class='bx bx-check-circle'></i>
      <h3>No Pending Requests</h3>
      <p>There are no cancellation requests at the moment</p>
    </div>
    <?php endif; ?>
  </div>
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
  
  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => hideToast(toast), 4000);
}

function hideToast(toast) {
  toast.classList.add('hide');
  setTimeout(() => toast.remove(), 400);
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