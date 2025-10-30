<?php
session_start();
require_once '../../db.php';
require_once '../admin/check_admin.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
   header("Location: ../homepage/main.php");
   exit;
}

// Get sentiment counts
$positive_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments WHERE sentiment = 'positive'"), 0, 0);
$neutral_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments WHERE sentiment = 'neutral'"), 0, 0);
$negative_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments WHERE sentiment = 'negative'"), 0, 0);
$pending_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM appointments WHERE feedback IS NOT NULL AND (sentiment IS NULL OR sentiment IN ('pending', '', ' '))"), 0, 0);

$total_feedback = $positive_count + $neutral_count + $negative_count + $pending_count;

// Calculate percentages
$positive_percent = $total_feedback > 0 ? round(($positive_count / $total_feedback) * 100, 1) : 0;
$neutral_percent = $total_feedback > 0 ? round(($neutral_count / $total_feedback) * 100, 1) : 0;
$negative_percent = $total_feedback > 0 ? round(($negative_count / $total_feedback) * 100, 1) : 0;

// Get all feedback with sentiment
$feedback_query = "
    SELECT a.appointment_id, a.feedback, a.rating, a.sentiment, a.appointment_date,
           u.first_name, u.middle_name, u.last_name,
           p.name AS pet_name
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN pets p ON a.pet_id = p.pet_id
    WHERE a.feedback IS NOT NULL
    ORDER BY a.appointment_date DESC
";
$feedback_result = pg_query($conn, $feedback_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sentiment Analysis Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>
    :root {
      --white-color: #fff;
      --dark-color: #252525;
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
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

    /* STATS CARDS */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--white-color);
      padding: 25px;
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-card .icon {
      font-size: 2.5rem;
      margin-bottom: 10px;
    }

    .stat-card.positive .icon { color: var(--positive-color); }
    .stat-card.neutral .icon { color: var(--neutral-color); }
    .stat-card.negative .icon { color: var(--negative-color); }
    .stat-card.pending .icon { color: var(--pending-color); }

    .stat-card h3 {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-card .count {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--dark-color);
      margin-bottom: 5px;
    }

    .stat-card .percentage {
      font-size: 0.9rem;
      color: #999;
    }

    /* ANALYZE BUTTON */
    .analyze-section {
      background: var(--white-color);
      padding: 30px;
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      margin-bottom: 40px;
      text-align: center;
    }

    .analyze-btn {
      background: linear-gradient(135deg, var(--primary-color), #80d1b8);
      color: var(--dark-color);
      padding: 15px 40px;
      border: none;
      border-radius: 14px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(168, 230, 207, 0.3);
    }

    .analyze-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(168, 230, 207, 0.4);
    }

    .analyze-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .analyze-btn i {
      margin-right: 8px;
    }

    /* CHARTS */
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
      max-width: 800px; /* Limit total width */
    }

    .chart-card {
      background: var(--white-color);
      padding: 20px;
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      max-height: 350px; /* Limit card height */
    }

    .chart-card h2 {
      font-size: 1.1rem;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-weight: 600;
    }

    .chart-card canvas {
      max-height: 250px !important; /* Limit canvas height */
      width: 100% !important;
    }

    /* FEEDBACK TABLE */
    .feedback-section {
      background: var(--white-color);
      padding: 30px;
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .feedback-section h2 {
      font-size: 1.5rem;
      margin-bottom: 20px;
      color: var(--dark-color);
    }

    .filter-buttons {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .filter-btn {
      padding: 8px 16px;
      border: 2px solid var(--primary-color);
      background: transparent;
      border-radius: 20px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s;
      color: var(--dark-color);
    }

    .filter-btn.active,
    .filter-btn:hover {
      background: var(--primary-color);
      color: var(--dark-color);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    table th,
    table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    table th {
      background-color: var(--primary-color);
      color: var(--dark-color);
      font-weight: 600;
      position: sticky;
      top: 0;
    }

    .sentiment-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
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

    /* TOAST */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: var(--positive-color);
      color: white;
      padding: 15px 25px;
      border-radius: 10px;
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

    /* LOADING SPINNER */
    .spinner {
      border: 3px solid rgba(168, 230, 207, 0.3);
      border-top: 3px solid var(--primary-color);
      border-radius: 50%;
      width: 20px;
      height: 20px;
      animation: spin 1s linear infinite;
      display: inline-block;
      margin-right: 10px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Logo">
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>
    <a href="../manage_accounts/accounts.php"><i class='bx bx-user'></i>Users</a>
    <hr>
    <a href="../session_notes/notes.php"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="sentiment_dashboard.php" class="active"><i class='bx bx-comment-detail'></i>Sentiment Analysis</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main>
  <!-- Header -->
  <div class="header">
    <h1>📊 Sentiment Analysis Dashboard</h1>
    <p>Analyze customer feedback and sentiment trends</p>
  </div>

  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card positive">
      <div class="icon">😊</div>
      <h3>Positive</h3>
      <div class="count"><?= $positive_count ?></div>
      <div class="percentage"><?= $positive_percent ?>% of total</div>
    </div>

    <div class="stat-card neutral">
      <div class="icon">😐</div>
      <h3>Neutral</h3>
      <div class="count"><?= $neutral_count ?></div>
      <div class="percentage"><?= $neutral_percent ?>% of total</div>
    </div>

    <div class="stat-card negative">
      <div class="icon">😞</div>
      <h3>Negative</h3>
      <div class="count"><?= $negative_count ?></div>
      <div class="percentage"><?= $negative_percent ?>% of total</div>
    </div>

    <div class="stat-card pending">
      <div class="icon">⏳</div>
      <h3>Pending Analysis</h3>
      <div class="count"><?= $pending_count ?></div>
      <div class="percentage">Not analyzed yet</div>
    </div>
  </div>

  <!-- Analyze Button -->
  <?php if ($pending_count > 0): ?>
  <div class="analyze-section">
    <h2>🤖 Run Sentiment Analysis</h2>
    <p style="margin: 15px 0; color: #666;">
      Analyze <?= $pending_count ?> pending feedback<?= $pending_count > 1 ? 's' : '' ?> using VADER sentiment analysis
    </p>
    <button class="analyze-btn" onclick="runSentimentAnalysis()">
      <i class='bx bx-brain'></i> Analyze Now
    </button>
  </div>
  <?php endif; ?>

  <!-- Charts -->
  <div class="charts-grid">
    <div class="chart-card">
      <h2>Sentiment Distribution</h2>
      <canvas id="sentimentPieChart"></canvas>
    </div>

    <div class="chart-card">
      <h2>Sentiment Overview</h2>
      <canvas id="sentimentBarChart"></canvas>
    </div>
  </div>

  <!-- Feedback Table -->
  <div class="feedback-section">
    <h2>All Feedback</h2>
    
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
          <?php while ($row = pg_fetch_assoc($feedback_result)): 
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
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
// Sentiment data for charts
const sentimentData = {
  positive: <?= $positive_count ?>,
  neutral: <?= $neutral_count ?>,
  negative: <?= $negative_count ?>,
  pending: <?= $pending_count ?>
};

// Pie Chart - Updated for smaller size
const pieCtx = document.getElementById('sentimentPieChart').getContext('2d');
new Chart(pieCtx, {
  type: 'doughnut',
  data: {
    labels: ['Positive', 'Neutral', 'Negative', 'Pending'],
    datasets: [{
      data: [sentimentData.positive, sentimentData.neutral, sentimentData.negative, sentimentData.pending],
      backgroundColor: ['#4CAF50', '#FF9800', '#F44336', '#9E9E9E'],
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.5, /* Make it wider than tall */
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 10,
          font: {
            size: 11
          }
        }
      }
    }
  }
});

// Bar Chart - Updated for smaller size
const barCtx = document.getElementById('sentimentBarChart').getContext('2d');
new Chart(barCtx, {
  type: 'bar',
  data: {
    labels: ['Positive', 'Neutral', 'Negative', 'Pending'],
    datasets: [{
      label: 'Number of Feedback',
      data: [sentimentData.positive, sentimentData.neutral, sentimentData.negative, sentimentData.pending],
      backgroundColor: ['#4CAF50', '#FF9800', '#F44336', '#9E9E9E'],
      borderRadius: 8,
      barThickness: 40 /* Control bar width */
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.5, /* Make it wider than tall */
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: 1,
          font: {
            size: 11
          }
        }
      },
      x: {
        ticks: {
          font: {
            size: 11
          }
        }
      }
    },
    plugins: {
      legend: {
        display: false
      }
    }
  }
});

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

// Run sentiment analysis
function runSentimentAnalysis() {
  const btn = event.target;
  const originalText = btn.innerHTML;
  
  // Disable button and show loading
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>Analyzing...';
  
  // Call Python script via PHP handler
  fetch('run_sentiment_analysis.php')
    .then(response => response.json())
    .then(data => {
      console.log('Full response:', data); // Debug: log full response
      
      if (data.success) {
        showToast('✅ ' + data.message);
        setTimeout(() => location.reload(), 2000);
      } else {
        // Show detailed error
        let errorDetails = data.message;
        if (data.error) {
          errorDetails += '\n\nError output:\n' + data.error;
        }
        if (data.command) {
          errorDetails += '\n\nCommand: ' + data.command;
        }
        if (data.return_code) {
          errorDetails += '\n\nReturn code: ' + data.return_code;
        }
        
        console.error('Error details:', errorDetails);
        alert(errorDetails); // Show full error in alert
        showToast('❌ ' + data.message, true);
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      showToast('❌ Error running analysis: ' + error.message, true);
      btn.disabled = false;
      btn.innerHTML = originalText;
    });
}
</script>

</body>
</html>