<?php
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
    JOIN packages p ON a.package_id = p.package_id
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
  <title>Receptionist Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../../homepage/images/Logo.jpg">
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
      --light-pink-color: #faf4f5;
      --font-size-s: 0.9rem;
      --font-weight-semi-bold: 600;
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
    /* Content */
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
    .modal {
  display: none;
  position: fixed;
  z-index: 100;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.5);
}

.modal-content {
  background-color: var(--white-color);
  margin: 10% auto;
  padding: 30px 25px;
  width: 450px;
  border-radius: var(--border-radius-s);
  box-shadow: 0 10px 25px rgba(0,0,0,0.25);
  position: relative;
  font-family: "Montserrat", sans-serif;
  transition: all 0.3s ease;
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
  background-color: #fafafa; /* subtle accent background */
  padding: 15px;
  border-radius: var(--border-radius-s);
}

.modal-content label {
  font-weight: var(--font-weight-semi-bold);
  color: var(--dark-color);
  margin-bottom: 5px;
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
}

.modal-content button {
  padding: 10px 18px;
  border: none;
  border-radius: var(--border-radius-s);
  font-weight: var(--font-weight-semi-bold);
  cursor: pointer;
  transition: background 0.3s;
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
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
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
    <a href="receptionist_home.php" class="active"><i class='bx bx-home'></i>All Appointments</a>
  </nav>
</aside>

<!-- Main Content -->
<main class="content">
  <h2>All Appointments</h2>
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
      <td style="display:flex; gap:8px; justify-content:center;">
        <button class="edit-btn"
                data-id="<?= $row['appointment_id'] ?>"
                data-date="<?= $row['appointment_date'] ?>"
                data-package="<?= $row['package_id'] ?>"
                data-status="<?= $row['status'] ?>"
                data-groomer="<?= htmlspecialchars($row['groomer_name']) ?>"
                style="padding:6px 12px; border:none; border-radius:8px; background-color:var(--primary-color); color:var(--dark-color); font-weight:600; cursor:pointer;">
          Edit
        </button>
        <button onclick="if(confirm('Cancel this appointment?')) { window.location.href='cancel_appointment.php?id=<?= $row['appointment_id'] ?>'; }"
                style="padding:6px 12px; border:none; border-radius:8px; background-color:#FF6B6B; color:#fff; font-weight:600; cursor:pointer;">
          Cancel
        </button>
      </td>
    </tr>
  <?php endwhile; ?>
</tbody>
  </table>
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
        // Reset the result pointer to beginning
        pg_result_seek($packages_result, 0);
        while ($pkg = pg_fetch_assoc($packages_result)):
        ?>
        <option value="<?= $pkg['package_id'] ?>"><?= htmlspecialchars($pkg['name']) ?></option>
        <?php endwhile; ?>
      </select>

      <label>Groomer:</label>
      <select name="groomer_name" id="modalGroomer" required>
        <?php
        // Reset the result pointer to beginning
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
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("editModal");
  const closeBtn = modal.querySelector(".close");
  const editButtons = document.querySelectorAll(".edit-btn");

  editButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      // Populate modal with data from button's data attributes
      document.getElementById("modalAppointmentId").value = btn.dataset.id;
      
      // Format the date properly for datetime-local input
      let dateValue = btn.dataset.date;
      if (dateValue) {
        // Remove seconds if present and replace space with 'T'
        dateValue = dateValue.substring(0, 16).replace(' ', 'T');
      }
      document.getElementById("modalDate").value = dateValue;
      
      document.getElementById("modalPackage").value = btn.dataset.package;
      document.getElementById("modalStatus").value = btn.dataset.status;
      document.getElementById("modalGroomer").value = btn.dataset.groomer;

      // Show the modal
      modal.style.display = "block";
    });
  });

  // Close modal when clicking the close button
  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // Close modal when clicking outside of the modal content
  window.addEventListener("click", (event) => {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
});
</script>

</body>
</html>