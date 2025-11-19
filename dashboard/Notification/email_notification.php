<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Fetch all active users for the recipient selection
$users_query = pg_query($conn, "SELECT user_id, first_name, middle_name, last_name, email, role 
                                 FROM users WHERE deleted_at IS NULL ORDER BY first_name ASC");
$all_users = [];
while ($user = pg_fetch_assoc($users_query)) {
    $all_users[] = $user;
}

// Load email templates
$templates = include('email_templates.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | Email Notifications</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig2.png">

  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #3ABB87;
      --light-pink-color: #faf4f5;
      --medium-gray-color: #ccc;
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

    /* SIDEBAR */
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
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      overflow-y: auto;
      z-index: 999;
      transition: transform 0.3s;
    }

    .sidebar .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .sidebar .logo img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
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
      font-weight: 600;
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
      border-radius: 14px;
      transition: background 0.3s, color 0.3s;
      font-weight: 600;
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

    /* MAIN CONTENT */
    main {
      margin-left: 260px;
      padding: 40px;
      width: calc(100% - 260px);
    }

    .header {
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: 2rem;
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .header p {
      color: #666;
      font-size: 0.95rem;
    }

    /* CARDS */
    .card {
      background: var(--white-color);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 25px;
    }

    .card h2 {
      font-size: 1.3rem;
      margin-bottom: 20px;
      color: var(--dark-color);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .card h2 i {
      font-size: 1.5rem;
      color: var(--secondary-color);
    }

    /* FORM STYLES */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--dark-color);
      font-weight: 600;
      font-size: 0.95rem;
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: var(--light-pink-color);
      font-size: 1rem;
      color: var(--dark-color);
      transition: all 0.2s;
      font-family: "Montserrat", sans-serif;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-color);
      background-color: var(--white-color);
      box-shadow: 0 0 0 3px rgba(168, 230, 207, 0.1);
    }

    textarea.form-control {
      min-height: 150px;
      resize: vertical;
    }

    select.form-control {
      cursor: pointer;
    }

    /* TEMPLATE PREVIEW */
    .template-preview {
      background: #f9f9f9;
      border: 2px dashed #ddd;
      border-radius: 8px;
      padding: 25px;
      margin-top: 15px;
    }

    .template-preview h3 {
      color: var(--dark-color);
      margin-bottom: 15px;
      font-size: 1.1rem;
    }

    .template-preview-content {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    /* RECIPIENT SELECTION */
    .recipient-options {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
    }

    .radio-option {
      flex: 1;
      position: relative;
    }

    .radio-option input[type="radio"] {
      position: absolute;
      opacity: 0;
    }

    .radio-option label {
      display: block;
      padding: 15px 20px;
      border: 2px solid #ddd;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 600;
    }

    .radio-option input[type="radio"]:checked + label {
      border-color: var(--secondary-color);
      background: rgba(168, 230, 207, 0.1);
      color: var(--dark-color);
    }

    /* USER SELECTION */
    .user-selection {
      display: none;
      max-height: 300px;
      overflow-y: auto;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      background: white;
    }

    .user-item {
      display: flex;
      align-items: center;
      padding: 10px;
      border-bottom: 1px solid #f0f0f0;
      transition: background 0.2s;
    }

    .user-item:last-child {
      border-bottom: none;
    }

    .user-item:hover {
      background: #fafafa;
    }

    .user-item input[type="checkbox"] {
      margin-right: 12px;
      width: 18px;
      height: 18px;
      cursor: pointer;
    }

    .user-info {
      flex: 1;
    }

    .user-name {
      font-weight: 600;
      color: var(--dark-color);
      margin-bottom: 3px;
    }

    .user-email {
      font-size: 0.85rem;
      color: #666;
    }

    .role-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .role-badge.admin { background: rgba(244, 67, 54, 0.1); color: #F44336; }
    .role-badge.customer { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
    .role-badge.groomer { background: rgba(255, 152, 0, 0.1); color: #FF9800; }
    .role-badge.receptionist { background: rgba(168, 230, 207, 0.3); color: #2d8a5d; }

    /* BUTTONS */
    .btn {
      padding: 14px 30px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-family: "Montserrat", sans-serif;
    }

    .btn-primary {
      background: var(--dark-color);
      color: var(--white-color);
    }

    .btn-primary:hover {
      background: #1a1a1a;
      transform: translateY(-1px);
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background: #5a6268;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* LOADING SPINNER */
    .spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid var(--secondary-color);
      border-radius: 50%;
      width: 20px;
      height: 20px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* TOAST */
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
      animation: slideInToast 0.4s;
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
    .toast.hide { animation: slideOutToast 0.4s forwards; }

    .toast-success {
      background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
      color: white;
    }

    .toast-error {
      background: linear-gradient(135deg, #F44336 0%, #e53935 100%);
      color: white;
    }

    .toast i { font-size: 24px; }

    .toast-close {
      cursor: pointer;
      font-size: 20px;
      opacity: 0.8;
      transition: opacity 0.2s;
    }

    .toast-close:hover { opacity: 1; }

    /* MOBILE RESPONSIVE */
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
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .mobile-menu-btn i {
      font-size: 24px;
      color: var(--dark-color);
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
      transition: opacity 0.3s;
    }

    .sidebar-overlay.active {
      display: block;
      opacity: 1;
    }

    @media screen and (max-width: 768px) {
      .mobile-menu-btn { display: block; }
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
      main {
        margin-left: 0;
        width: 100%;
        padding: 80px 20px 40px;
      }
      .recipient-options {
        flex-direction: column;
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
    <img src="../../homepage/images/pawsig2.png" alt="Logo" />
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
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/sentiment_dashboard.php"><i class='bx bx-comment-detail'></i>Sentiment Analysis</a>
    <hr>
    <a href="../Notification/email_notifications.php" class="active"><i class='bx bx-mail-send'></i>Email Notifications</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main>
  <div class="header">
    <h1>📧 Email Notifications</h1>
    <p>Send announcements, promotions, and updates to your users</p>
  </div>

  <form id="emailForm">
    <!-- Template Selection -->
    <div class="card">
      <h2><i class='bx bx-layout'></i> Select Email Template</h2>
      <div class="form-group">
        <label for="template">Choose Template Type</label>
        <select class="form-control" id="template" name="template_type" required>
          <option value="">-- Select Template --</option>
          <option value="discount">🎉 Discount/Promotion</option>
          <option value="closure">⚠️ Closure Notice</option>
          <option value="reopening">🎊 Reopening Announcement</option>
          <option value="announcement">📢 General Announcement</option>
          <option value="custom">✏️ Custom Message</option>
        </select>
      </div>

      <div id="templatePreview" class="template-preview" style="display: none;">
        <h3>Preview</h3>
        <div class="template-preview-content" id="previewContent">
          <!-- Preview will be loaded here -->
        </div>
      </div>
    </div>

    <!-- Custom Content (shown for custom template, announcement, or override) -->
    <div class="card" id="customContent" style="display: none;">
      <h2><i class='bx bx-edit'></i> Customize Your Message</h2>
      <div class="form-group">
        <label for="customSubject">Email Subject</label>
        <input type="text" class="form-control" id="customSubject" name="custom_subject" placeholder="Enter email subject">
      </div>
      <div class="form-group">
        <label for="customMessage">Email Content (HTML supported)</label>
        <textarea class="form-control" id="customMessage" name="custom_content" placeholder="Enter your message here... You can use HTML tags for formatting."></textarea>
      </div>
    </div>

    <!-- Recipient Selection -->
    <div class="card">
      <h2><i class='bx bx-user-check'></i> Select Recipients</h2>
      <div class="recipient-options">
        <div class="radio-option">
          <input type="radio" id="sendAll" name="send_to" value="all" checked>
          <label for="sendAll">
            <i class='bx bx-group'></i><br>
            Send to All Users
          </label>
        </div>
        <div class="radio-option">
          <input type="radio" id="sendSpecific" name="send_to" value="specific">
          <label for="sendSpecific">
            <i class='bx bx-user-pin'></i><br>
            Select Specific Users
          </label>
        </div>
      </div>

      <div id="userSelection" class="user-selection">
        <div style="margin-bottom: 15px; display: flex; gap: 10px;">
          <button type="button" class="btn btn-secondary" onclick="selectAllUsers()">
            <i class='bx bx-check-square'></i> Select All
          </button>
          <button type="button" class="btn btn-secondary" onclick="deselectAllUsers()">
            <i class='bx bx-square'></i> Deselect All
          </button>
        </div>

        <?php foreach ($all_users as $user): ?>
        <div class="user-item">
          <input type="checkbox" name="recipient_ids[]" value="<?= $user['user_id'] ?>" class="user-checkbox">
          <div class="user-info">
            <div class="user-name">
              <?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']) ?>
            </div>
            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
          </div>
          <span class="role-badge <?= strtolower($user['role']) ?>">
            <?= ucfirst($user['role']) ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Send Button -->
    <div class="card">
      <button type="submit" class="btn btn-primary" id="sendBtn">
        <i class='bx bx-send'></i>
        <span id="btnText">Send Email</span>
        <span id="btnSpinner" style="display: none;" class="spinner"></span>
      </button>
    </div>
  </form>
</main>

<script>
const templates = <?= json_encode($templates) ?>;

// Template preview
document.getElementById('template').addEventListener('change', function() {
  const templateType = this.value;
  const preview = document.getElementById('templatePreview');
  const previewContent = document.getElementById('previewContent');
  const customContent = document.getElementById('customContent');
  
  if (templateType) {
    const template = templates[templateType];
    
    // Show custom content for custom template OR general announcement
    if (templateType === 'custom') {
      // For custom template: no preview, show editable fields
      preview.style.display = 'none';
      customContent.style.display = 'block';
      document.getElementById('customSubject').value = template.subject;
      document.getElementById('customMessage').value = template.content;
    } else if (templateType === 'announcement') {
      // For general announcement: no preview, show editable fields with default content
      preview.style.display = 'none';
      customContent.style.display = 'block';
      document.getElementById('customSubject').value = template.subject;
      document.getElementById('customMessage').value = template.content;
    } else {
      // For other templates: show preview, hide custom content
      preview.style.display = 'block';
      customContent.style.display = 'none';
      
      previewContent.innerHTML = `
        <h3 style="color: #2d5f4a; margin-bottom: 15px;">${template.title}</h3>
        <p style="font-size: 14px; color: #666; margin-bottom: 10px;"><strong>Subject:</strong> ${template.subject}</p>
        <div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
          ${template.content}
        </div>
      `;
      
      document.getElementById('customSubject').value = '';
      document.getElementById('customMessage').value = '';
    }
  } else {
    preview.style.display = 'none';
    customContent.style.display = 'none';
  }
});

// Recipient selection
document.querySelectorAll('input[name="send_to"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const userSelection = document.getElementById('userSelection');
    if (this.value === 'specific') {
      userSelection.style.display = 'block';
    } else {
      userSelection.style.display = 'none';
    }
  });
});

function selectAllUsers() {
  document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.checked = true;
  });
}

function deselectAllUsers() {
  document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.checked = false;
  });
}

// Form submission
document.getElementById('emailForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const templateType = document.getElementById('template').value;
  if (!templateType) {
    showToast('Please select an email template', 'error');
    return;
  }
  
  const sendTo = document.querySelector('input[name="send_to"]:checked').value;
  
  if (sendTo === 'specific') {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    if (checkedBoxes.length === 0) {
      showToast('Please select at least one recipient', 'error');
      return;
    }
  }
  
  // Validate custom content for custom and announcement templates
  if (templateType === 'custom' || templateType === 'announcement') {
    const customSubject = document.getElementById('customSubject').value.trim();
    const customMessage = document.getElementById('customMessage').value.trim();
    
    if (!customSubject || !customMessage) {
      showToast('Please fill in both subject and message content', 'error');
      return;
    }
  }
  
  const confirmMsg = sendTo === 'all' 
    ? 'Are you sure you want to send this email to ALL users?' 
    : `Are you sure you want to send this email to ${document.querySelectorAll('.user-checkbox:checked').length} selected user(s)?`;
  
  if (!confirm(confirmMsg)) {
    return;
  }
  
  const sendBtn = document.getElementById('sendBtn');
  const btnText = document.getElementById('btnText');
  const btnSpinner = document.getElementById('btnSpinner');
  
  sendBtn.disabled = true;
  btnText.textContent = 'Sending...';
  btnSpinner.style.display = 'inline-block';
  
  try {
    const formData = new FormData(this);
    
    const response = await fetch('send_bulk_email.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      showToast(result.message, 'success');
      
      // Show detailed results if available
      if (result.details) {
        setTimeout(() => {
          let detailMsg = `✓ Sent: ${result.details.success}`;
          if (result.details.failed > 0) {
            detailMsg += `\n✗ Failed: ${result.details.failed}`;
          }
          showToast(detailMsg, 'success');
        }, 2000);
      }
      
      // Reset form
      this.reset();
      document.getElementById('templatePreview').style.display = 'none';
      document.getElementById('customContent').style.display = 'none';
      document.getElementById('userSelection').style.display = 'none';
    } else {
      showToast(result.message || 'Failed to send emails', 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showToast('An error occurred while sending emails', 'error');
  } finally {
    sendBtn.disabled = false;
    btnText.textContent = 'Send Email';
    btnSpinner.style.display = 'none';
  }
});

// Toast notification
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
  setTimeout(() => hideToast(toast), 5000);
}

function hideToast(toast) {
  toast.classList.add('hide');
  setTimeout(() => toast.remove(), 400);
}

function closeToast(closeBtn) {
  hideToast(closeBtn.closest('.toast'));
}

// Sidebar toggle
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('active');
  document.querySelector('.sidebar-overlay').classList.toggle('active');
}

// Dropdown
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation();
  const dropdown = event.currentTarget.nextElementSibling;
  dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
}

document.addEventListener('click', function(event) {
  if (!event.target.closest('.dropdown')) {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.style.display = 'none';
    });
  }
});
</script>

</body>
</html>