<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Fetch all gallery images
$query = "SELECT * FROM gallery ORDER BY id ASC";
$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | Pet Gallery</title>
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

    .dropdown {
      position: relative;
    }

    .dropdown-toggle {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 12px;
      text-decoration: none;
      color: var(--dark-color);
      border-radius: var(--border-radius-s);
      transition: background 0.3s, color 0.3s;
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
    }

    .dropdown-toggle:hover,
    .dropdown-toggle.active {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .dropdown-menu {
      display: none;
      flex-direction: column;
      gap: 5px;
      margin-left: 20px;
      margin-top: 5px;
    }

    .dropdown-menu a {
      padding: 8px 12px;
      font-size: 0.9rem;
    }

    .content {
      margin-left: 260px;
      padding: 40px;
      flex-grow: 1;
      width: calc(100% - 260px);
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    h2 {
      font-size: var(--font-size-xl);
      color: var(--dark-color);
      margin-bottom: 25px;
    }

    .add-btn {
      background: var(--primary-color);
      padding: 12px 24px;
      border-radius: var(--border-radius-s);
      text-decoration: none;
      color: var(--dark-color);
      font-weight: var(--font-weight-semi-bold);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
      cursor: pointer;
      border: none;
      font-size: var(--font-size-n);
      transition: background 0.3s;
    }

    .add-btn:hover {
      background: var(--secondary-color);
    }

    /* Table Container */
    .table-container {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      box-shadow: var(--shadow-light);
      overflow: hidden;
    }

    .table-wrapper {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }

    thead {
      background-color: var(--primary-color);
    }

    thead th {
      padding: 18px 20px;
      text-align: left;
      font-weight: var(--font-weight-semi-bold);
      color: var(--dark-color);
      font-size: var(--font-size-n);
      white-space: nowrap;
    }

    tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: background 0.2s;
    }

    tbody tr:hover {
      background-color: #f9f9f9;
    }

    tbody td {
      padding: 18px 20px;
      color: var(--dark-color);
      font-size: 0.95rem;
      vertical-align: middle;
    }

    .image-preview {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: var(--border-radius-s);
      cursor: pointer;
      transition: transform 0.3s;
    }

    .image-preview:hover {
      transform: scale(1.05);
    }

    .actions-cell {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .btn {
      padding: 8px 16px;
      border-radius: var(--border-radius-s);
      font-weight: var(--font-weight-semi-bold);
      font-size: 0.85rem;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }

    .btn-edit {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .btn-edit:hover {
      background-color: #fdd56c;
    }

    .btn-delete {
      background-color: #ff6b6b;
      color: var(--white-color);
    }

    .btn-delete:hover {
      background-color: #ff4949;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: var(--white-color);
      padding: 2rem;
      border-radius: var(--border-radius-s);
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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

    .file-input-wrapper {
      position: relative;
      width: 100%;
      margin-bottom: 1.5rem;
    }

    .file-input-wrapper input[type="file"] {
      display: none;
    }

    .file-input-label {
      display: block;
      width: 100%;
      padding: 0.9rem;
      background-color: var(--light-pink-color);
      border: 2px dashed var(--medium-gray-color);
      border-radius: var(--border-radius-s);
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }

    .file-input-label:hover {
      border-color: var(--primary-color);
      background-color: var(--white-color);
    }

    .file-preview {
      margin-top: 15px;
      text-align: center;
    }

    .file-preview img {
      max-width: 100%;
      max-height: 300px;
      border-radius: var(--border-radius-s);
      box-shadow: var(--shadow-light);
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

    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 14px 20px;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
      z-index: 10000;
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

    /* Image View Modal */
    .image-modal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.9);
      justify-content: center;
      align-items: center;
    }

    .image-modal-content {
      max-width: 90%;
      max-height: 90vh;
      position: relative;
    }

    .image-modal-content img {
      width: 100%;
      height: auto;
      border-radius: var(--border-radius-s);
    }

    .image-modal .close {
      position: absolute;
      top: -40px;
      right: 0;
      font-size: 2rem;
      color: var(--white-color);
      cursor: pointer;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 4rem;
      color: var(--medium-gray-color);
      margin-bottom: 20px;
    }

    /* Responsive */
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

      table {
        min-width: 600px;
      }

      thead th,
      tbody td {
        padding: 12px 10px;
        font-size: 0.85rem;
      }

      .image-preview {
        width: 60px;
        height: 60px;
      }

      .actions-cell {
        flex-direction: column;
        gap: 6px;
        align-items: stretch;
      }

      .btn {
        width: 100%;
        justify-content: center;
      }
    }

    @media screen and (max-width: 480px) {
      .content {
        padding: 70px 15px 30px;
      }

      h2 {
        font-size: 1.5rem;
      }

      .add-btn {
        width: 100%;
        justify-content: center;
      }

      table {
        min-width: 500px;
      }

      .modal-content {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>

    <div class="dropdown">
      <a href="javascript:void(0)" class="dropdown-toggle" onclick="toggleDropdown(event)">
        <span><i class='bx bx-user'></i> Users</span>
        <i class='bx bx-chevron-down'></i>
      </a>
      <div class="dropdown-menu">
        <a href="../manage_accounts/accounts.php"><i class='bx bx-user-circle'></i> All Users</a>
        <a href="../groomer_management/groomer_accounts.php"><i class='bx bx-scissors'></i> Groomers</a>
      </div>
    </div>

    <hr>

     <!-- SERVICES DROPDOWN -->
      <div class="dropdown">
        <a href="javascript:void(0)" class="dropdown-toggle" onclick="toggleDropdown(event)">
          <span><i class='bx bx-spa'></i> Services</span>
          <i class='bx bx-chevron-down'></i>
        </a>
        <div class="dropdown-menu">
          <a href="../service/services.php"><i class='bx bx-list-ul'></i> All Services</a>
          <a href="../service/manage_prices.php"><i class='bx bx-dollar'></i> Manage Pricing</a>
        </div>
      </div>

    <hr>
    <a href="../session_notes/notes.php"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php" class="active"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<!-- Main Content -->
<main class="content">
  <h2>Pet Gallery</h2>
  
  <button class="add-btn" onclick="openAddModal()">
    ➕ Add New User
  </button>

  <div class="table-container">
    <div class="table-wrapper">
      <?php if (pg_num_rows($result) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Image ID</th>
            <th>Preview</th>
            <th>Uploaded Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($image = pg_fetch_assoc($result)): ?>
          <tr>
            <td><?= $image['id'] ?></td>
            <td>
              <img src="uploads/<?= basename(htmlspecialchars($image['image_path'])) ?>" 
                   alt="Pet Gallery Image"
                   class="image-preview"
                   onclick="viewImage('uploads/<?= basename(htmlspecialchars($image['image_path'])) ?>')">
            </td>
            <td><?= date('F j, Y', strtotime($image['uploaded_at'])) ?></td>
            <td>
              <div class="actions-cell">
                <button class="btn btn-edit" onclick="openEditModal(<?= $image['id'] ?>, 'uploads/<?= basename(htmlspecialchars($image['image_path'])) ?>')">
                  Edit
                </button>
                <form method="POST" action="delete_image.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?')">
                  <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                  <button type="submit" class="btn btn-delete">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <i class='bx bx-image'></i>
        <h3>No images in gallery</h3>
        <p>Click "Add New Image" to upload your first image</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Add Image Modal -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAddModal()">&times;</span>
    <h2>Add New Image</h2>
    <form method="POST" action="add_image.php" enctype="multipart/form-data">
      <div class="file-input-wrapper">
        <input type="file" id="imageFile" name="image" accept="image/*" required onchange="previewImage(this, 'addPreview')">
        <label for="imageFile" class="file-input-label">
          <i class='bx bx-upload' style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
          <strong>Choose Image File</strong>
          <p style="font-size: 0.85rem; color: #666; margin-top: 5px;">JPG, PNG, GIF, WEBP (Max 5MB)</p>
        </label>
        <div id="addPreview" class="file-preview"></div>
      </div>

      <div>
        <input type="submit" class="input-submit" value="Upload Image" />
      </div>
    </form>
  </div>
</div>

<!-- Edit Image Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Replace Image</h2>
    <form method="POST" action="edit_image.php" enctype="multipart/form-data">
      <input type="hidden" id="edit_image_id" name="image_id">
      <input type="hidden" id="edit_current_path" name="current_image_path">

      <div class="file-preview" id="editCurrentImage" style="margin-bottom: 15px;">
        <p style="margin-bottom: 10px; font-weight: 600;">Current Image:</p>
      </div>

      <div class="file-input-wrapper">
        <input type="file" id="editImageFile" name="image" accept="image/*" required onchange="previewImage(this, 'editPreview')">
        <label for="editImageFile" class="file-input-label">
          <i class='bx bx-upload' style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
          <strong>Choose New Image</strong>
          <p style="font-size: 0.85rem; color: #666; margin-top: 5px;">JPG, PNG, GIF, WEBP (Max 5MB)</p>
        </label>
        <div id="editPreview" class="file-preview"></div>
      </div>

      <div>
        <input type="submit" class="input-submit" value="Replace Image" />
      </div>
    </form>
  </div>
</div>

<!-- Image View Modal -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
  <div class="image-modal-content">
    <span class="close">&times;</span>
    <img id="modalImage" src="" alt="">
  </div>
</div>

<script>
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation();
  const dropdown = event.currentTarget.nextElementSibling;
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(event) {
  if (!event.target.closest('.dropdown')) {
    const dropdowns = document.getElementsByClassName("dropdown-menu");
    for (let i = 0; i < dropdowns.length; i++) {
      dropdowns[i].style.display = 'none';
    }
  }
});

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
}

function openAddModal() {
  document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
  document.getElementById('addModal').style.display = 'none';
  document.getElementById('addPreview').innerHTML = '';
}

function openEditModal(id, imagePath) {
  document.getElementById('edit_image_id').value = id;
  document.getElementById('edit_current_path').value = imagePath;
  
  document.getElementById('editCurrentImage').innerHTML = 
    '<p style="margin-bottom: 10px; font-weight: 600;">Current Image:</p>' +
    '<img src="' + imagePath + '" style="max-width: 100%; max-height: 200px; border-radius: 8px;">';
  
  document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  document.getElementById('editPreview').innerHTML = '';
}

function viewImage(imageUrl) {
  document.getElementById('modalImage').src = imageUrl;
  document.getElementById('imageModal').style.display = 'flex';
}

function closeImageModal() {
  document.getElementById('imageModal').style.display = 'none';
}

function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  preview.innerHTML = '';
  
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
    }
    reader.readAsDataURL(input.files[0]);
  }
}

window.onclick = function(event) {
  const addModal = document.getElementById('addModal');
  const editModal = document.getElementById('editModal');
  
  if (event.target === addModal) closeAddModal();
  if (event.target === editModal) closeEditModal();
}
</script>

<?php if (isset($_SESSION['success'])): ?>
  <div class="toast toast-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
  <div class="toast toast-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

</body>
</html>