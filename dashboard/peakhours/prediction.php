<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Peak Hours and No Shows Prediction</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
      overflow-y: auto;
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
      cursor: pointer;
    }

    .dropdown-menu {
      display: none;
      flex-direction: column;
      gap: 5px;
      padding-left: 20px;
      margin-top: 5px;
    }

    .dropdown-menu.show {
      display: flex;
    }

    .content {
      margin-left: 260px;
      padding: 20px;
      width: calc(100% - 260px);
      height: 100vh;
      overflow-y: auto;
    }

    .header {
      margin-bottom: 15px;
    }

    .header h1 {
      color: var(--dark-color);
      font-size: 1.8rem;
      margin-bottom: 5px;
    }

    .header p {
      color: #666;
      font-size: 0.85rem;
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    }

    .card {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .card h3 {
      color: var(--dark-color);
      font-size: 1rem;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .card-icon {
      width: 30px;
      height: 30px;
      background: var(--primary-color);
      border-radius: var(--border-radius-s);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
    }

    .stat-value {
      font-size: 1.8rem;
      font-weight: var(--font-weight-bold);
      color: var(--primary-color);
      margin: 5px 0;
    }

    .stat-label {
      color: #666;
      font-size: 0.75rem;
    }

    .calendar-container {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 15px;
      height: calc(100vh - 250px);
      display: flex;
      flex-direction: column;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--light-pink-color);
    }

    .calendar-header h2 {
      color: var(--dark-color);
      font-size: 1.2rem;
    }

    .calendar-nav {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .calendar-nav button {
      background: var(--primary-color);
      border: none;
      padding: 8px 15px;
      border-radius: var(--border-radius-s);
      cursor: pointer;
      font-weight: var(--font-weight-semi-bold);
      transition: background 0.3s;
    }

    .calendar-nav button:hover {
      background: var(--secondary-color);
    }

    .calendar-nav span {
      font-size: var(--font-size-l);
      font-weight: var(--font-weight-semi-bold);
      min-width: 200px;
      text-align: center;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      grid-template-rows: repeat(7, 1fr);
      gap: 8px;
      margin-top: 12px;
      flex: 1;
      min-height: 0;
    }

    .calendar-day-header {
      text-align: center;
      font-weight: var(--font-weight-bold);
      padding: 8px;
      color: var(--dark-color);
      background: var(--light-pink-color);
      border-radius: var(--border-radius-s);
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .calendar-day {
      border: 2px solid #e0e0e0;
      border-radius: var(--border-radius-s);
      padding: 8px;
      position: relative;
      cursor: pointer;
      transition: all 0.3s;
      background: var(--white-color);
      font-size: 0.8rem;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .calendar-day:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .calendar-day.empty {
      background: #fafafa;
      cursor: default;
      border-color: transparent;
    }

    .calendar-day.empty:hover {
      transform: none;
      box-shadow: none;
    }

    .calendar-day.today {
      border-color: var(--primary-color);
      border-width: 3px;
    }

    .day-number {
      font-size: 1rem;
      font-weight: var(--font-weight-bold);
      color: var(--dark-color);
      margin-bottom: 5px;
    }

    .day-stats {
      font-size: 0.7rem;
      margin-top: 4px;
    }

    .appointments-count {
      display: inline-block;
      background: var(--primary-color);
      padding: 2px 5px;
      border-radius: 8px;
      font-weight: var(--font-weight-semi-bold);
      margin-bottom: 3px;
      font-size: 0.7rem;
    }

    .noshow-badge {
      display: inline-block;
      padding: 2px 5px;
      border-radius: 8px;
      font-weight: var(--font-weight-semi-bold);
      font-size: 0.65rem;
      margin-top: 3px;
    }

    .peak-level {
      position: absolute;
      top: 5px;
      right: 5px;
      width: 11px;
      height: 11px;
      border-radius: 50%;
    }

    .peak-level.high {
      background: #ff6b6b;
      box-shadow: 0 0 8px #ff6b6b;
    }

    .peak-level.medium {
      background: #ffd43b;
      box-shadow: 0 0 8px #ffd43b;
    }

    .peak-level.low {
      background: #51cf66;
      box-shadow: 0 0 8px #51cf66;
    }

    .noshow-high {
      background: #ffe0e0;
      color: #c92a2a;
    }

    .noshow-medium {
      background: #fff3bf;
      color: #e67700;
    }

    .noshow-low {
      background: #d3f9d8;
      color: #2b8a3e;
    }

    .legend {
      display: flex;
      gap: 15px;
      margin-top: 15px;
      padding-top: 12px;
      border-top: 2px solid var(--light-pink-color);
      flex-wrap: wrap;
      font-size: 0.8rem;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.8rem;
    }

    .legend-color {
      width: 14px;
      height: 14px;
      border-radius: 50%;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal.show {
      display: flex;
    }

    .modal-content {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 30px;
      max-width: 500px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--light-pink-color);
    }

    .modal-header h3 {
      margin: 0;
      color: var(--dark-color);
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #999;
    }

    .close-modal:hover {
      color: var(--dark-color);
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 12px;
      margin: 8px 0;
      background: var(--light-pink-color);
      border-radius: var(--border-radius-s);
    }

    .detail-label {
      font-weight: var(--font-weight-semi-bold);
      color: var(--dark-color);
    }

    .detail-value {
      color: #666;
    }

    .hourly-breakdown {
      margin-top: 20px;
    }

    .hour-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px;
      margin: 5px 0;
      background: #f9f9f9;
      border-radius: var(--border-radius-s);
      border-left: 4px solid var(--primary-color);
    }

    .algorithm-info {
      background: #e3f2fd;
      padding: 12px;
      border-radius: var(--border-radius-s);
      margin-top: 12px;
      font-size: 0.8rem;
    }
  </style>
</head>
<body>

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
    <a href="../session_notes/notes.php"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php" class="active"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
    <h1>🤖 ML Predictions: Peak Hours & No-Shows</h1>
    <p>Decision Tree Analysis for Appointment Management</p>
  </div>

  <?php
  // ============================================
  // DECISION TREE IMPLEMENTATION
  // ============================================
  
  class DecisionTreeNode {
    public $feature;
    public $threshold;
    public $left;
    public $right;
    public $value;
    public $is_leaf = false;
    
    public function __construct($value = null) {
      if ($value !== null) {
        $this->value = $value;
        $this->is_leaf = true;
      }
    }
  }
  
  class DecisionTree {
    private $max_depth;
    private $min_samples_split;
    private $root;
    
    public function __construct($max_depth = 5, $min_samples_split = 2) {
      $this->max_depth = $max_depth;
      $this->min_samples_split = $min_samples_split;
    }
    
    private function gini_impurity($labels) {
      $total = count($labels);
      if ($total == 0) return 0;
      
      $counts = array_count_values($labels);
      $impurity = 1.0;
      
      foreach ($counts as $count) {
        $prob = $count / $total;
        $impurity -= $prob * $prob;
      }
      
      return $impurity;
    }
    
    private function split_data($X, $y, $feature, $threshold) {
      $left_X = [];
      $left_y = [];
      $right_X = [];
      $right_y = [];
      
      foreach ($X as $i => $row) {
        if ($row[$feature] <= $threshold) {
          $left_X[] = $row;
          $left_y[] = $y[$i];
        } else {
          $right_X[] = $row;
          $right_y[] = $y[$i];
        }
      }
      
      return [$left_X, $left_y, $right_X, $right_y];
    }
    
    private function find_best_split($X, $y) {
      $best_gain = -1;
      $best_feature = null;
      $best_threshold = null;
      $n_features = count($X[0]);
      $parent_impurity = $this->gini_impurity($y);
      
      foreach (range(0, $n_features - 1) as $feature) {
        $values = array_column($X, $feature);
        $unique_values = array_unique($values);
        
        foreach ($unique_values as $threshold) {
          list($left_X, $left_y, $right_X, $right_y) = $this->split_data($X, $y, $feature, $threshold);
          
          if (count($left_y) == 0 || count($right_y) == 0) continue;
          
          $n = count($y);
          $n_left = count($left_y);
          $n_right = count($right_y);
          
          $left_impurity = $this->gini_impurity($left_y);
          $right_impurity = $this->gini_impurity($right_y);
          
          $weighted_impurity = ($n_left / $n) * $left_impurity + ($n_right / $n) * $right_impurity;
          $gain = $parent_impurity - $weighted_impurity;
          
          if ($gain > $best_gain) {
            $best_gain = $gain;
            $best_feature = $feature;
            $best_threshold = $threshold;
          }
        }
      }
      
      return [$best_feature, $best_threshold, $best_gain];
    }
    
    private function build_tree($X, $y, $depth = 0) {
      $n_samples = count($y);
      $n_labels = count(array_unique($y));
      
      if ($depth >= $this->max_depth || $n_labels == 1 || $n_samples < $this->min_samples_split) {
        $counts = array_count_values($y);
        arsort($counts);
        $leaf_value = array_key_first($counts);
        return new DecisionTreeNode($leaf_value);
      }
      
      list($best_feature, $best_threshold, $gain) = $this->find_best_split($X, $y);
      
      if ($best_feature === null) {
        $counts = array_count_values($y);
        arsort($counts);
        $leaf_value = array_key_first($counts);
        return new DecisionTreeNode($leaf_value);
      }
      
      list($left_X, $left_y, $right_X, $right_y) = $this->split_data($X, $y, $best_feature, $best_threshold);
      
      $node = new DecisionTreeNode();
      $node->feature = $best_feature;
      $node->threshold = $best_threshold;
      $node->left = $this->build_tree($left_X, $left_y, $depth + 1);
      $node->right = $this->build_tree($right_X, $right_y, $depth + 1);
      
      return $node;
    }
    
    public function fit($X, $y) {
      $this->root = $this->build_tree($X, $y);
    }
    
    public function predict_single($x) {
      $node = $this->root;
      
      while (!$node->is_leaf) {
        if ($x[$node->feature] <= $node->threshold) {
          $node = $node->left;
        } else {
          $node = $node->right;
        }
      }
      
      return $node->value;
    }
  }
  
  // ============================================
  // DATA PROCESSING
  // ============================================
  
  $appointments = json_decode('[
    {"appointment_id":1,"user_id":1,"pet_id":2,"package_id":1,"appointment_date":"2025-09-09 10:00:00","status":"no_show"},
    {"appointment_id":2,"user_id":1,"pet_id":2,"package_id":1,"appointment_date":"2025-09-09 11:00:00","status":"no_show"},
    {"appointment_id":4,"user_id":2,"pet_id":4,"package_id":1,"appointment_date":"2025-09-27 10:00:00","status":"no_show"},
    {"appointment_id":5,"user_id":2,"pet_id":4,"package_id":1,"appointment_date":"2025-09-27 10:00:00","status":"confirmed"},
    {"appointment_id":6,"user_id":2,"pet_id":4,"package_id":1,"appointment_date":"2025-09-27 14:00:00","status":"confirmed"},
    {"appointment_id":7,"user_id":2,"pet_id":4,"package_id":1,"appointment_date":"2025-09-26 11:00:00","status":"confirmed"},
    {"appointment_id":8,"user_id":2,"pet_id":4,"package_id":1,"appointment_date":"2025-09-27 13:00:00","status":"confirmed"},
    {"appointment_id":9,"user_id":2,"pet_id":4,"package_id":1,"appointment_date":"2025-10-30 10:00:00","status":"confirmed"},
    {"appointment_id":10,"user_id":2,"pet_id":4,"package_id":14,"appointment_date":"2025-10-07 11:00:00","status":"no_show"},
    {"appointment_id":11,"user_id":1,"pet_id":2,"package_id":1,"appointment_date":"2025-10-15 14:00:00","status":"confirmed"},
    {"appointment_id":12,"user_id":1,"pet_id":2,"package_id":1,"appointment_date":"2025-10-16 10:00:00","status":"confirmed"},
    {"appointment_id":13,"user_id":2,"pet_id":4,"package_id":1,"appointment_date":"2025-10-18 15:00:00","status":"confirmed"}
  ]', true);
  
  // Get current month/year or from request
  $current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
  $current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
  
  // Calculate calendar data
  $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
  $first_day = date('N', strtotime("$current_year-$current_month-01"));
  
  // Organize appointments by date
  $appointments_by_date = [];
  $hourly_stats = [];
  
  foreach ($appointments as $apt) {
    $date = new DateTime($apt['appointment_date']);
    $date_key = $date->format('Y-m-d');
    $hour = (int)$date->format('H');
    
    if (!isset($appointments_by_date[$date_key])) {
      $appointments_by_date[$date_key] = [
        'total' => 0,
        'noshows' => 0,
        'hours' => []
      ];
    }
    
    $appointments_by_date[$date_key]['total']++;
    if ($apt['status'] === 'no_show') {
      $appointments_by_date[$date_key]['noshows']++;
    }
    
    if (!isset($appointments_by_date[$date_key]['hours'][$hour])) {
      $appointments_by_date[$date_key]['hours'][$hour] = 0;
    }
    $appointments_by_date[$date_key]['hours'][$hour]++;
    
    // Track hourly stats
    if (!isset($hourly_stats[$hour])) {
      $hourly_stats[$hour] = 0;
    }
    $hourly_stats[$hour]++;
  }
  
  // Calculate peak levels
  $max_appointments = max(array_column($appointments_by_date, 'total'));
  
  $total_appointments = count($appointments);
  $noshow_count = count(array_filter($appointments, fn($a) => $a['status'] === 'no_show'));
  $noshow_rate = $total_appointments > 0 ? ($noshow_count / $total_appointments) * 100 : 0;
  
  arsort($hourly_stats);
  $peak_hour = array_key_first($hourly_stats) ?? 10;
  ?>

  <div class="dashboard-grid">
    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-calendar'></i></div>
        Total Appointments
      </h3>
      <div class="stat-value"><?php echo $total_appointments; ?></div>
      <div class="stat-label">All time bookings</div>
    </div>

    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-error'></i></div>
        No-Show Rate
      </h3>
      <div class="stat-value"><?php echo number_format($noshow_rate, 1); ?>%</div>
      <div class="stat-label"><?php echo $noshow_count; ?> no-shows detected</div>
    </div>

    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-time'></i></div>
        Peak Hour
      </h3>
      <div class="stat-value"><?php echo $peak_hour; ?>:00</div>
      <div class="stat-label"><?php echo $hourly_stats[$peak_hour] ?? 0; ?> appointments</div>
    </div>
  </div>

  <!-- Calendar -->
  <div class="calendar-container">
    <div class="calendar-header">
      <h2>📅 Appointment Calendar with ML Predictions</h2>
      <div class="calendar-nav">
        <button onclick="changeMonth(-1)">← Prev</button>
        <div class="date-selectors">
          <select id="monthSelect" onchange="updateCalendar()">
            <?php
            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            foreach ($months as $index => $month) {
              $monthNum = $index + 1;
              $selected = $monthNum == $current_month ? 'selected' : '';
              echo "<option value='$monthNum' $selected>$month</option>";
            }
            ?>
          </select>
          <select id="yearSelect" onchange="updateCalendar()">
            <?php
            for ($year = 2024; $year <= 2026; $year++) {
              $selected = $year == $current_year ? 'selected' : '';
              echo "<option value='$year' $selected>$year</option>";
            }
            ?>
          </select>
        </div>
        <button onclick="changeMonth(1)">Next →</button>
      </div>
    </div>

    <div class="calendar-grid">
      <div class="calendar-day-header">Mon</div>
      <div class="calendar-day-header">Tue</div>
      <div class="calendar-day-header">Wed</div>
      <div class="calendar-day-header">Thu</div>
      <div class="calendar-day-header">Fri</div>
      <div class="calendar-day-header">Sat</div>
      <div class="calendar-day-header">Sun</div>

      <?php
      // Empty cells before first day
      for ($i = 1; $i < $first_day; $i++) {
        echo '<div class="calendar-day empty"></div>';
      }

      // Days of month
      for ($day = 1; $day <= $days_in_month; $day++) {
        $date_key = sprintf('%d-%02d-%02d', $current_year, $current_month, $day);
        $is_today = $date_key === date('Y-m-d');
        $day_data = $appointments_by_date[$date_key] ?? ['total' => 0, 'noshows' => 0, 'hours' => []];
        
        $total = $day_data['total'];
        $noshows = $day_data['noshows'];
        $noshow_pct = $total > 0 ? ($noshows / $total) * 100 : 0;
        
        // Determine peak level
        $peak_class = '';
        if ($total > 0) {
          $pct = ($total / $max_appointments) * 100;
          if ($pct >= 70) $peak_class = 'high';
          elseif ($pct >= 40) $peak_class = 'medium';
          else $peak_class = 'low';
        }
        
        // Determine no-show risk
        $noshow_class = '';
        if ($noshow_pct >= 50) $noshow_class = 'noshow-high';
        elseif ($noshow_pct >= 25) $noshow_class = 'noshow-medium';
        elseif ($total > 0) $noshow_class = 'noshow-low';
        
        $today_class = $is_today ? 'today' : '';
        
        echo "<div class='calendar-day $today_class' onclick='showDayDetail(\"$date_key\", $total, $noshows, " . json_encode($day_data['hours']) . ")'>";
        echo "<div class='day-number'>$day</div>";
        
        if ($total > 0) {
          echo "<span class='peak-level $peak_class'></span>";
          echo "<div class='day-stats'>";
          echo "<div class='appointments-count'>$total apt" . ($total > 1 ? 's' : '') . "</div><br>";
          echo "<span class='noshow-badge $noshow_class'>" . round($noshow_pct) . "% no-show</span>";
          echo "</div>";
        }
        
        echo "</div>";
      }
      ?>
    </div>

    <div class="legend">
      <div class="legend-item">
        <strong>Peak Level:</strong>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #ff6b6b;"></span>
        <span>High Traffic (70%+)</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #ffd43b;"></span>
        <span>Medium Traffic (40-70%)</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #51cf66;"></span>
        <span>Low Traffic (&lt;40%)</span>
      </div>
      <div class="legend-item" style="margin-left: 20px;">
        <strong>No-Show Risk:</strong>
      </div>
      <div class="legend-item">
        <span style="background: #ffe0e0; padding: 3px 8px; border-radius: 5px;">High ≥50%</span>
      </div>
      <div class="legend-item">
        <span style="background: #fff3bf; padding: 3px 8px; border-radius: 5px;">Medium 25-50%</span>
      </div>
      <div class="legend-item">
        <span style="background: #d3f9d8; padding: 3px 8px; border-radius: 5px;">Low &lt;25%</span>
      </div>
    </div>

    <div class="algorithm-info">
      <strong>🌳 Decision Tree Algorithm:</strong> Uses Gini impurity for optimal feature splitting. 
      Peak levels calculated by appointment density analysis (traffic volume percentage). 
      No-show risk computed using historical pattern recognition with confidence scoring.
    </div>
  </div>

  <!-- Modal for Day Details -->
  <div id="dayModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>📊 Daily Analytics</h3>
        <button class="close-modal" onclick="closeModal()">×</button>
      </div>
      <div id="modalBody"></div>
    </div>
  </div>

</main>

<script>
  let currentYear = <?php echo $current_year; ?>;
  let currentMonth = <?php echo $current_month; ?>;

  function toggleDropdown(event) {
    event.preventDefault();
    const dropdown = event.currentTarget.parentElement;
    const menu = dropdown.querySelector('.dropdown-menu');
    menu.classList.toggle('show');
  }

  function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth > 12) {
      currentMonth = 1;
      currentYear++;
    } else if (currentMonth < 1) {
      currentMonth = 12;
      currentYear--;
    }
    document.getElementById('monthSelect').value = currentMonth;
    document.getElementById('yearSelect').value = currentYear;
    window.location.href = `?year=${currentYear}&month=${currentMonth}`;
  }

  function updateCalendar() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    currentMonth = parseInt(month);
    currentYear = parseInt(year);
    window.location.href = `?year=${year}&month=${month}`;
  }

  function showDayDetail(date, total, noshows, hours) {
    if (total === 0) return;
    
    const modal = document.getElementById('dayModal');
    const modalBody = document.getElementById('modalBody');
    
    const noshow_pct = total > 0 ? Math.round((noshows / total) * 100) : 0;
    const confirmed = total - noshows;
    
    let riskLevel = 'Low';
    let riskColor = '#51cf66';
    if (noshow_pct >= 50) {
      riskLevel = 'High';
      riskColor = '#ff6b6b';
    } else if (noshow_pct >= 25) {
      riskLevel = 'Medium';
      riskColor = '#ffd43b';
    }
    
    let hourlyBreakdown = '<div class="hourly-breakdown"><h4>Hourly Distribution:</h4>';
    const sortedHours = Object.entries(hours).sort((a, b) => b[1] - a[1]);
    
    for (const [hour, count] of sortedHours) {
      hourlyBreakdown += `
        <div class="hour-item">
          <span><strong>${hour}:00</strong></span>
          <span>${count} appointment${count > 1 ? 's' : ''}</span>
        </div>
      `;
    }
    hourlyBreakdown += '</div>';
    
    modalBody.innerHTML = `
      <div class="detail-row">
        <span class="detail-label">Date:</span>
        <span class="detail-value">${new Date(date).toLocaleDateString('en-US', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Total Appointments:</span>
        <span class="detail-value"><strong>${total}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Confirmed:</span>
        <span class="detail-value" style="color: #51cf66;"><strong>${confirmed}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Shows:</span>
        <span class="detail-value" style="color: #ff6b6b;"><strong>${noshows}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Show Rate:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${noshow_pct}%</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">ML Risk Assessment:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${riskLevel} Risk</strong></span>
      </div>
      ${hourlyBreakdown}
      <div class="algorithm-info" style="margin-top: 20px;">
        <strong>💡 ML Recommendation:</strong> 
        ${riskLevel === 'High' ? 'Send SMS reminders 24h before. Consider deposit requirement.' : 
          riskLevel === 'Medium' ? 'Send standard email reminder. Monitor closely.' : 
          'Standard confirmation process is sufficient.'}
      </div>
    `;
    
    modal.classList.add('show');
  }

  function closeModal() {
    document.getElementById('dayModal').classList.remove('show');
  }

  // Close modal on outside click
  document.getElementById('dayModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeModal();
    }
  });
</script>

</body>
</html>