<?php
require_once '../../db.php';

$result = pg_query($conn, "SELECT * FROM gallery ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Gallery Dashboard</title>
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
      cursor: pointer;
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
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(5px);
      /* display: flex;  ❌ REMOVE THIS LINE */
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }


    .modal-content {
      background: var(--white-color);
      padding: 40px 30px;
      border-radius: 20px;
      width: 95%;
      max-width: 520px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.25s ease-in-out;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }


    .modal-content .close-btn {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 26px;
      font-weight: bold;
      cursor: pointer;
      color: #888;
      transition: color 0.2s ease-in-out;
    }

    .modal-content .close-btn:hover {
      color: #000;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
  </style>
</head>
<body>

  <aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>
    <a href="../manage_accounts/accounts.php"><i class='bx bx-camera'></i>User Management</a>
    <hr>
    <a href="../groomer_management/groomer_accounts.php" ><i class='bx bx-user'></i>Groomer Management</a>
    <hr>
    <a href="../session_notes.php/notes.php"><i class='bx bx-note'></i>Session Notes</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php" class="active"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <button class="add-btn" onclick="openModal('../gallery_dashboard/gallery_add.php')">+ Add New Image</button>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Preview</th>
        <th>Filename</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (pg_num_rows($result) > 0): ?>
        <?php while ($row = pg_fetch_assoc($result)): ?>

          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><img src="../gallery_images/<?php echo htmlspecialchars($row['image_path']); ?>" alt="Gallery Image"></td>
            <td><?php echo htmlspecialchars($row['image_path']); ?></td>
            <td class="actions">
              <a class="edit-btn" onclick="openModal('../gallery_dashboard/gallery_edit.php?id=<?php echo $row['id']; ?>')">Edit</a>
              <a href="../gallery_dashboard/gallery_delete.php?id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this image?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4">No images found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>

<!-- Modal -->
<div class="modal" id="dynamicModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <div id="modalBody">Loading...</div>
  </div>
</div>

<script>
function openModal(url) {
  const modal = document.getElementById("dynamicModal");
  const modalBody = document.getElementById("modalBody");
  modal.style.display = "flex"; // ← this will correctly show the modal only when needed
  modalBody.innerHTML = "Loading...";
  fetch(url)
    .then(res => res.text())
    .then(html => modalBody.innerHTML = html)
    .catch(() => modalBody.innerHTML = '<p style="color:red">Failed to load content.</p>');
}

function closeModal() {
  document.getElementById("dynamicModal").style.display = "none";
  document.getElementById("modalBody").innerHTML = "";
}
</script>

</body>
</html>