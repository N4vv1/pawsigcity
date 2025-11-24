<?php
session_start();
require_once '../../db.php';
require_once '../admin/check_admin.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
   header("Location: ../homepage/main.php");
   exit;
}

// Pagination settings
$records_per_page = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Date filter
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$custom_start = isset($_GET['custom_start']) ? $_GET['custom_start'] : '';
$custom_end = isset($_GET['custom_end']) ? $_GET['custom_end'] : '';

// Build date condition
$date_condition = "";
switch ($date_filter) {
    case 'today':
        $date_condition = "AND DATE(a.appointment_date) = CURRENT_DATE";
        break;
    case 'yesterday':
        $date_condition = "AND DATE(a.appointment_date) = CURRENT_DATE - INTERVAL '1 day'";
        break;
    case 'this_week':
        $date_condition = "AND a.appointment_date >= DATE_TRUNC('week', CURRENT_DATE)";
        break;
    case 'this_month':
        $date_condition = "AND a.appointment_date >= DATE_TRUNC('month', CURRENT_DATE)";
        break;
    case 'last_month':
        $date_condition = "AND a.appointment_date >= DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month') 
                          AND a.appointment_date < DATE_TRUNC('month', CURRENT_DATE)";
        break;
    case 'custom':
        if ($custom_start && $custom_end) {
            $date_condition = "AND DATE(a.appointment_date) BETWEEN '$custom_start' AND '$custom_end'";
        }
        break;
}

// Get sentiment counts (with date filter)
$positive_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments a WHERE sentiment = 'positive' $date_condition"), 0, 0);
$neutral_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments a WHERE sentiment = 'neutral' $date_condition"), 0, 0);
$negative_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments a WHERE sentiment = 'negative' $date_condition"), 0, 0);
$pending_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments a WHERE feedback IS NOT NULL AND (sentiment IS NULL OR sentiment IN ('pending', '', ' ')) $date_condition"), 0, 0);

$total_feedback = $positive_count + $neutral_count + $negative_count + $pending_count;

// Calculate percentages
$positive_percent = $total_feedback > 0 ? round(($positive_count / $total_feedback) * 100, 1) : 0;
$neutral_percent = $total_feedback > 0 ? round(($neutral_count / $total_feedback) * 100, 1) : 0;
$negative_percent = $total_feedback > 0 ? round(($negative_count / $total_feedback) * 100, 1) : 0;

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total
    FROM appointments a
    WHERE a.feedback IS NOT NULL $date_condition
";
$count_result = pg_query($conn, $count_query);
$total_records = pg_fetch_result($count_result, 0, 0);
$total_pages = ceil($total_records / $records_per_page);

// Get feedback with pagination
$feedback_query = "
    SELECT a.appointment_id, a.feedback, a.rating, a.sentiment, a.appointment_date,
           u.first_name, u.middle_name, u.last_name,
           p.name AS pet_name
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE a.feedback IS NOT NULL $date_condition
    ORDER BY a.appointment_date DESC
    LIMIT $records_per_page OFFSET $offset
";
$feedback_result = pg_query($conn, $feedback_query);

// Get all feedback for export (without pagination)
$all_feedback_query = "
    SELECT a.appointment_id, a.feedback, a.rating, a.sentiment, a.appointment_date,
           u.first_name, u.middle_name, u.last_name,
           p.name AS pet_name
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE a.feedback IS NOT NULL $date_condition
    ORDER BY a.sentiment DESC, a.appointment_date DESC
";
$all_feedback_result = pg_query($conn, $all_feedback_query);

// Organize feedback by sentiment
$feedback_by_sentiment = [
    'positive' => [],
    'neutral' => [],
    'negative' => [],
    'pending' => []
];

while ($row = pg_fetch_assoc($all_feedback_result)) {
    $sentiment = $row['sentiment'] ?? 'pending';
    $sentiment_key = in_array($sentiment, ['positive', 'neutral', 'negative']) ? $sentiment : 'pending';
    $feedback_by_sentiment[$sentiment_key][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedbacks</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig2.png">
  
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #3ABB87;
      --light-pink-color: #faf4f5;
      --positive-color: #4CAF50;
      --neutral-color: #FF9800;
      --negative-color: #F44336;
      --pending-color: #9E9E9E;
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

    /* Dropdown */
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
      margin-bottom: 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }

    .header-left h1 {
      font-size: 2rem;
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .header-left p {
      color: #666;
      font-size: 0.95rem;
    }

    .export-btn {
      background: var(--primary-color);
      color: var(--dark-color);
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .export-btn:hover {
      background: #8fd4b3;
      transform: translateY(-1px);
    }

    .export-btn i {
      font-size: 1.1rem;
    }

    /* DATE FILTER */
    .date-filter-section {
      background: var(--white-color);
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 30px;
    }

    .date-filter-section h3 {
      font-size: 1rem;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-weight: 600;
    }

    .date-filter-controls {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .filter-group label {
      font-size: 0.85rem;
      color: #666;
      font-weight: 500;
    }

    .filter-group select,
    .filter-group input[type="date"] {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 0.9rem;
      background: white;
      color: var(--dark-color);
      cursor: pointer;
      min-width: 150px;
    }

    .filter-group select:focus,
    .filter-group input[type="date"]:focus {
      outline: none;
      border-color: var(--primary-color);
    }

    .apply-filter-btn {
      padding: 10px 25px;
      background: var(--dark-color);
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .apply-filter-btn:hover {
      background: #1a1a1a;
    }

    .custom-date-inputs {
      display: none;
      gap: 10px;
    }

    .custom-date-inputs.active {
      display: flex;
    }

    /* FEEDBACK TABLE */
    .feedback-section {
      background: var(--white-color);
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .feedback-section h2 {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-weight: 600;
    }

    .filter-buttons {
      margin-bottom: 25px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .filter-btn {
      padding: 8px 18px;
      border: 1px solid #ddd;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;
      color: var(--dark-color);
      font-size: 0.9rem;
    }

    .filter-btn.active {
      background: var(--dark-color);
      color: var(--white-color);
      border-color: var(--dark-color);
    }

    .filter-btn:hover {
      border-color: var(--dark-color);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    table th,
    table td {
      padding: 15px 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }

    table th {
      background-color: #fafafa;
      color: var(--dark-color);
      font-weight: 600;
      font-size: 0.9rem;
      position: sticky;
      top: 0;
    }

    table tbody tr:hover {
      background-color: #fafafa;
    }

    .sentiment-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .sentiment-badge.positive {
      background: rgba(76, 175, 80, 0.1);
      color: var(--positive-color);
    }

    .sentiment-badge.neutral {
      background: rgba(255, 152, 0, 0.1);
      color: var(--neutral-color);
    }

    .sentiment-badge.negative {
      background: rgba(244, 67, 54, 0.1);
      color: var(--negative-color);
    }

    .sentiment-badge.pending {
      background: rgba(158, 158, 158, 0.1);
      color: var(--pending-color);
    }

    .stars {
      color: #FFD700;
      font-size: 0.9rem;
    }

    /* PAGINATION */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-top: 30px;
      flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
      padding: 8px 14px;
      border: 1px solid #ddd;
      border-radius: 6px;
      text-decoration: none;
      color: var(--dark-color);
      font-weight: 500;
      transition: all 0.2s;
      min-width: 40px;
      text-align: center;
    }

    .pagination a:hover {
      background: var(--primary-color);
      border-color: var(--primary-color);
    }

    .pagination span.current {
      background: var(--dark-color);
      color: white;
      border-color: var(--dark-color);
    }

    .pagination .disabled {
      opacity: 0.5;
      pointer-events: none;
    }

    /* TOAST */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: var(--dark-color);
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      z-index: 9999;
      display: none;
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .toast.error {
      background: var(--negative-color);
    }

    /* PRINT STYLES */
    @media print {
      .sidebar, .export-btn, .filter-buttons, .pagination, .apply-filter-btn, .date-filter-section, #screenDisplay {
        display: none !important;
      }
      
      main {
        margin-left: 0;
        width: 100%;
        padding: 20px;
      }

      .header {
        margin-bottom: 30px;
      }

      .header h1 {
        font-size: 1.8rem;
      }

      #exportDisplay {
        display: block !important;
      }

      .sentiment-section {
        page-break-inside: avoid;
        margin-bottom: 30px;
      }

      .sentiment-summary {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 15px;
      }

      .sentiment-header {
        font-size: 1.1rem;
        font-weight: 700;
        padding: 12px;
        margin-bottom: 12px;
        border-radius: 4px;
      }

      .sentiment-header.positive {
        background-color: rgba(76, 175, 80, 0.15);
        color: #2e7d32;
      }

      .sentiment-header.neutral {
        background-color: rgba(255, 152, 0, 0.15);
        color: #e65100;
      }

      .sentiment-header.negative {
        background-color: rgba(244, 67, 54, 0.15);
        color: #c62828;
      }

      .sentiment-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
      }

      .sentiment-table th,
      .sentiment-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
        font-size: 0.9rem;
      }

      .sentiment-table th {
        background-color: #f0f0f0;
        font-weight: 600;
      }

      .no-feedback {
        text-align: center;
        padding: 15px;
        color: #999;
        font-style: italic;
      }
    }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig2.png" alt="Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>

    <!-- USERS DROPDOWN -->
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
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/sentiment_dashboard.php" class="active"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../Notification/email_notifications.php"><i class='bx bx-mail-send'></i>Email Notifications</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main>
  <!-- Header -->
  <div class="header">
    <div class="header-left">
      <h1>Feedbacks</h1>
      <p>Customer feedback reports</p>
    </div>
    <button class="export-btn" onclick="exportToPDF()">
      <i class='bx bx-download'></i>
      Export to PDF
    </button>
  </div>

  <!-- Date Filter Section -->
  <div class="date-filter-section">
    <h3><i class='bx bx-calendar'></i> Filter by Date</h3>
    <form method="GET" action="" id="dateFilterForm">
      <div class="date-filter-controls">
        <div class="filter-group">
          <label>Time Period</label>
          <select name="date_filter" id="dateFilterSelect" onchange="toggleCustomDates()">
            <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>All Time</option>
            <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Today</option>
            <option value="yesterday" <?= $date_filter == 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
            <option value="this_week" <?= $date_filter == 'this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="this_month" <?= $date_filter == 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="last_month" <?= $date_filter == 'last_month' ? 'selected' : '' ?>>Last Month</option>
            <option value="custom" <?= $date_filter == 'custom' ? 'selected' : '' ?>>Custom Range</option>
          </select>
        </div>

        <div class="custom-date-inputs <?= $date_filter == 'custom' ? 'active' : '' ?>" id="customDateInputs">
          <div class="filter-group">
            <label>Start Date</label>
            <input type="date" name="custom_start" value="<?= htmlspecialchars($custom_start) ?>">
          </div>
          <div class="filter-group">
            <label>End Date</label>
            <input type="date" name="custom_end" value="<?= htmlspecialchars($custom_end) ?>">
          </div>
        </div>

        <button type="submit" class="apply-filter-btn">
          <i class='bx bx-filter-alt'></i> Apply
        </button>
      </div>
    </form>
  </div>

  <!-- Screen Display Feedback Table -->
  <div class="feedback-section" id="screenDisplay">
    <h2>All Feedback (Page <?= $page ?> of <?= $total_pages ?>)</h2>
    
    <div class="filter-buttons">
      <button class="filter-btn active" onclick="filterFeedback('all')">All</button>
      <button class="filter-btn" onclick="filterFeedback('positive')">Positive</button>
      <button class="filter-btn" onclick="filterFeedback('neutral')">Neutral</button>
      <button class="filter-btn" onclick="filterFeedback('negative')">Negative</button>
      <button class="filter-btn" onclick="filterFeedback('pending')">Pending</button>
    </div>

    <div style="overflow-x: auto;">
      <table id="feedbackTable">
        <thead>
          <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Pet</th>
            <th>Rating</th>
            <th>Feedback</th>
            <th>Sentiment</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          if (pg_num_rows($feedback_result) > 0):
            while ($row = pg_fetch_assoc($feedback_result)): 
              $customer_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
              $sentiment = $row['sentiment'] ?? 'pending';
              $sentiment_class = in_array($sentiment, ['positive', 'neutral', 'negative']) ? $sentiment : 'pending';
          ?>
          <tr data-sentiment="<?= $sentiment_class ?>">
            <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
            <td><?= htmlspecialchars($customer_name) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td>
              <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fa<?= $i <= $row['rating'] ? 's' : 'r' ?> fa-star"></i>
                <?php endfor; ?>
              </div>
            </td>
            <td style="max-width: 300px;"><?= nl2br(htmlspecialchars($row['feedback'])) ?></td>
            <td>
              <span class="sentiment-badge <?= $sentiment_class ?>">
                <?= ucfirst($sentiment_class) ?>
              </span>
            </td>
          </tr>
          <?php 
            endwhile;
          else:
          ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
              No feedback found for the selected date range.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php
      $query_params = http_build_query(array_merge($_GET, ['page' => '']));
      $query_params = $query_params ? '&' . $query_params : '';
      ?>
      
      <a href="?page=1<?= $query_params ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">
        <i class='bx bx-chevrons-left'></i>
      </a>
      
      <a href="?page=<?= max(1, $page - 1) ?><?= $query_params ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">
        <i class='bx bx-chevron-left'></i>
      </a>

      <?php
      $start_page = max(1, $page - 2);
      $end_page = min($total_pages, $page + 2);
      
      for ($i = $start_page; $i <= $end_page; $i++):
      ?>
        <?php if ($i == $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?><?= $query_params ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <a href="?page=<?= min($total_pages, $page + 1) ?><?= $query_params ?>" class="<?= $page >= $total_pages ? 'disabled' : '' ?>">
        <i class='bx bx-chevron-right'></i>
      </a>
      
      <a href="?page=<?= $total_pages ?><?= $query_params ?>" class="<?= $page >= $total_pages ? 'disabled' : '' ?>">
        <i class='bx bx-chevrons-right'></i>
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- PDF Export Display (Hidden from screen, visible on print) -->
  <div id="exportDisplay" style="display: none;">

    <!-- POSITIVE FEEDBACK -->
    <div class="sentiment-section">
      <div class="sentiment-header positive">
         POSITIVE FEEDBACK (<?= count($feedback_by_sentiment['positive']) ?> responses)
      </div>
      <div class="sentiment-summary">
        <strong>Count:</strong> <?= count($feedback_by_sentiment['positive']) ?> | 
        <strong>Percentage:</strong> <?= $positive_percent ?>% of total feedback
      </div>
      <?php if (count($feedback_by_sentiment['positive']) > 0): ?>
      <table class="sentiment-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Pet</th>
            <th>Rating</th>
            <th>Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($feedback_by_sentiment['positive'] as $row): 
            $customer_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
          ?>
          <tr>
            <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
            <td><?= htmlspecialchars($customer_name) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= $row['rating'] ?>/5 ⭐</td>
            <td><?= htmlspecialchars($row['feedback']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="no-feedback">No positive feedback in this period.</p>
      <?php endif; ?>
    </div>

    <!-- NEUTRAL FEEDBACK -->
    <div class="sentiment-section">
      <div class="sentiment-header neutral">
         NEUTRAL FEEDBACK (<?= count($feedback_by_sentiment['neutral']) ?> responses)
      </div>
      <div class="sentiment-summary">
        <strong>Count:</strong> <?= count($feedback_by_sentiment['neutral']) ?> | 
        <strong>Percentage:</strong> <?= $neutral_percent ?>% of total feedback
      </div>
      <?php if (count($feedback_by_sentiment['neutral']) > 0): ?>
      <table class="sentiment-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Pet</th>
            <th>Rating</th>
            <th>Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($feedback_by_sentiment['neutral'] as $row): 
            $customer_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
          ?>
          <tr>
            <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
            <td><?= htmlspecialchars($customer_name) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= $row['rating'] ?>/5 ⭐</td>
            <td><?= htmlspecialchars($row['feedback']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="no-feedback">No neutral feedback in this period.</p>
      <?php endif; ?>
    </div>

    <!-- NEGATIVE FEEDBACK -->
    <div class="sentiment-section">
      <div class="sentiment-header negative">
         NEGATIVE FEEDBACK (<?= count($feedback_by_sentiment['negative']) ?> responses)
      </div>
      <div class="sentiment-summary">
        <strong>Count:</strong> <?= count($feedback_by_sentiment['negative']) ?> | 
        <strong>Percentage:</strong> <?= $negative_percent ?>% of total feedback
      </div>
      <?php if (count($feedback_by_sentiment['negative']) > 0): ?>
      <table class="sentiment-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Pet</th>
            <th>Rating</th>
            <th>Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($feedback_by_sentiment['negative'] as $row): 
            $customer_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
          ?>
          <tr>
            <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
            <td><?= htmlspecialchars($customer_name) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= $row['rating'] ?>/5 ⭐</td>
            <td><?= htmlspecialchars($row['feedback']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="no-feedback">No negative feedback in this period.</p>
      <?php endif; ?>
    </div>

    <!-- PENDING FEEDBACK -->
    <?php if (count($feedback_by_sentiment['pending']) > 0): ?>
    <div class="sentiment-section">
      <div class="sentiment-header" style="background-color: rgba(158, 158, 158, 0.15); color: #616161;">
        ⏳ PENDING ANALYSIS (<?= count($feedback_by_sentiment['pending']) ?> responses)
      </div>
      <table class="sentiment-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Pet</th>
            <th>Rating</th>
            <th>Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($feedback_by_sentiment['pending'] as $row): 
            $customer_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
          ?>
          <tr>
            <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
            <td><?= htmlspecialchars($customer_name) ?></td>
            <td><?= htmlspecialchars($row['pet_name']) ?></td>
            <td><?= $row['rating'] ?>/5 ⭐</td>
            <td><?= htmlspecialchars($row['feedback']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</main>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
// Dropdown functionality
function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation();
  const dropdown = event.currentTarget.nextElementSibling;
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
  if (!event.target.closest('.dropdown')) {
    const dropdowns = document.getElementsByClassName("dropdown-menu");
    for (let i = 0; i < dropdowns.length; i++) {
      dropdowns[i].style.display = 'none';
    }
  }
});

// Toggle custom date inputs
function toggleCustomDates() {
  const select = document.getElementById('dateFilterSelect');
  const customInputs = document.getElementById('customDateInputs');
  
  if (select.value === 'custom') {
    customInputs.classList.add('active');
  } else {
    customInputs.classList.remove('active');
  }
}

// Filter feedback table
function filterFeedback(sentiment) {
  const rows = document.querySelectorAll('#feedbackTable tbody tr');
  const buttons = document.querySelectorAll('.filter-btn');
  
  // Update active button
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
  
  // Filter rows
  rows.forEach(row => {
    if (sentiment === 'all' || row.dataset.sentiment === sentiment) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Show toast notification
function showToast(message, isError = false) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className = 'toast' + (isError ? ' error' : '');
  toast.style.display = 'block';
  setTimeout(() => toast.style.display = 'none', 4000);
}

// Export to PDF
function exportToPDF() {
  window.print();
}
</script>

</body>
</html>