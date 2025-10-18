<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Peak Hours and No Shows Prediction</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
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
      padding: 15px;
      width: calc(100% - 260px);
      height: 100vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .header {
      margin-bottom: 10px;
    }

    .header h1 {
      color: var(--dark-color);
      font-size: 1.6rem;
      margin-bottom: 3px;
    }

    .header p {
      color: #666;
      font-size: 0.8rem;
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 10px;
    }

    .card {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .card h3 {
      color: var(--dark-color);
      font-size: 0.9rem;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .card-icon {
      width: 28px;
      height: 28px;
      background: var(--primary-color);
      border-radius: var(--border-radius-s);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    .stat-value {
      font-size: 1.6rem;
      font-weight: var(--font-weight-bold);
      color: var(--primary-color);
      margin: 3px 0;
    }

    .stat-label {
      color: #666;
      font-size: 0.7rem;
    }

    .calendar-container {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--light-pink-color);
    }

    .calendar-header h2 {
      color: var(--dark-color);
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 8px;
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

    .calendar-nav select {
      padding: 8px 12px;
      border-radius: var(--border-radius-s);
      border: 1px solid var(--medium-gray-color);
      background: white;
      cursor: pointer;
      font-weight: var(--font-weight-semi-bold);
    }

    .calendar-nav span {
      font-size: var(--font-size-l);
      font-weight: var(--font-weight-semi-bold);
      min-width: 150px;
      text-align: center;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      grid-template-rows: auto repeat(6, 1fr);
      gap: 6px;
      flex: 1;
      min-height: 0;
    }

    .calendar-day-header {
      text-align: center;
      font-weight: var(--font-weight-bold);
      padding: 10px 5px;
      color: var(--white-color);
      background: linear-gradient(135deg, var(--primary-color), #8DD9B4);
      border-radius: var(--border-radius-s);
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .calendar-day {
      border: 2px solid #e8e8e8;
      border-radius: var(--border-radius-s);
      padding: 6px;
      position: relative;
      cursor: pointer;
      transition: all 0.3s;
      background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
      font-size: 0.75rem;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .calendar-day:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.15);
      border-color: var(--primary-color);
    }

    .calendar-day.empty {
      background: #f9f9f9;
      cursor: default;
      border-color: #f0f0f0;
      box-shadow: none;
    }

    .calendar-day.empty:hover {
      transform: none;
      box-shadow: none;
      border-color: #f0f0f0;
    }

    .calendar-day.today {
      border-color: var(--secondary-color);
      border-width: 3px;
      background: linear-gradient(135deg, #fffbf0 0%, #fff9e6 100%);
      box-shadow: 0 3px 10px rgba(255, 228, 157, 0.4);
    }

    .day-number {
      font-size: 0.95rem;
      font-weight: var(--font-weight-bold);
      color: var(--dark-color);
      margin-bottom: 4px;
    }

    .day-stats {
      font-size: 0.68rem;
      margin-top: 3px;
    }

    .appointments-count {
      display: inline-block;
      background: linear-gradient(135deg, var(--primary-color), #95DCBE);
      padding: 2px 6px;
      border-radius: 10px;
      font-weight: var(--font-weight-semi-bold);
      margin-bottom: 2px;
      font-size: 0.68rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      color: var(--dark-color);
    }

    .noshow-badge {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 10px;
      font-weight: var(--font-weight-semi-bold);
      font-size: 0.62rem;
      margin-top: 2px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .peak-level {
      position: absolute;
      top: 4px;
      right: 4px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      border: 2px solid white;
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
      gap: 12px;
      margin-top: 10px;
      padding: 10px;
      border-top: 2px solid var(--light-pink-color);
      flex-wrap: wrap;
      font-size: 0.75rem;
      background: var(--light-pink-color);
      border-radius: var(--border-radius-s);
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.75rem;
      padding: 3px 8px;
      background: var(--white-color);
      border-radius: 15px;
    }

    .legend-color {
      width: 13px;
      height: 13px;
      border-radius: 50%;
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
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
    <a href="../session_notes/notes.php" class="active"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">
  <div class="header">
  </div>

  <div class="dashboard-grid">
    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-calendar'></i></div>
        Total Appointments
      </h3>
      <div class="stat-value" id="totalAppointments">0</div>
      <div class="stat-label">All time bookings</div>
    </div>

    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-error'></i></div>
        No-Show Rate
      </h3>
      <div class="stat-value" id="noshowRate">0%</div>
      <div class="stat-label"><span id="noshowCount">0</span> no-shows detected</div>
    </div>

    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-time'></i></div>
        Peak Hour
      </h3>
      <div class="stat-value" id="peakHour">--:00</div>
      <div class="stat-label" id="peakHourCount">0 appointments</div>
    </div>
  </div>

  <div class="calendar-container">
    <div class="calendar-header">
      <div class="calendar-nav">
        <button onclick="changeMonth(-1)">← Prev</button>
        <select id="monthSelect" onchange="updateCalendar()">
          <option value="1">January</option>
          <option value="2">February</option>
          <option value="3">March</option>
          <option value="4">April</option>
          <option value="5">May</option>
          <option value="6">June</option>
          <option value="7">July</option>
          <option value="8">August</option>
          <option value="9">September</option>
          <option value="10">October</option>
          <option value="11">November</option>
          <option value="12">December</option>
        </select>
        <select id="yearSelect" onchange="updateCalendar()">
          <option value="2024">2024</option>
          <option value="2025">2025</option>
          <option value="2026">2026</option>
          <option value="2026">2027</option>
          <option value="2026">2028</option>
          <option value="2026">2029</option>
          <option value="2026">2030</option>
        </select>
        <button onclick="changeMonth(1)">Next →</button>
      </div>
    </div>

    <div class="calendar-grid" id="calendarGrid"></div>

    <div class="legend">
      <div style="font-weight: bold;">Peak Level:</div>
      <div class="legend-item">
        <span class="legend-color" style="background: #ff6b6b;"></span>
        <span>High</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #ffd43b;"></span>
        <span>Medium</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #51cf66;"></span>
        <span>Low</span>
      </div>
      <div style="font-weight: bold; margin-left: 20px;">No-Show Risk:</div>
      <div class="legend-item">
        <span style="background: #ffe0e0; padding: 2px 6px; border-radius: 5px; font-size: 0.7rem;">High</span>
      </div>
      <div class="legend-item">
        <span style="background: #fff3bf; padding: 2px 6px; border-radius: 5px; font-size: 0.7rem;">Medium</span>
      </div>
      <div class="legend-item">
        <span style="background: #d3f9d8; padding: 2px 6px; border-radius: 5px; font-size: 0.7rem;">Low</span>
      </div>
    </div>
  </div>

  <div id="dayModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Daily Analytics</h3>
        <button class="close-modal" onclick="closeModal()">×</button>
      </div>
      <div id="modalBody"></div>
    </div>
  </div>
</main>

<script>
  class DecisionTreeNode {
    constructor(value = null) {
      this.feature = null;
      this.threshold = null;
      this.left = null;
      this.right = null;
      this.value = value;
      this.is_leaf = value !== null;
    }
  }
  
  class DecisionTree {
    constructor(max_depth = 5, min_samples_split = 2) {
      this.max_depth = max_depth;
      this.min_samples_split = min_samples_split;
      this.root = null;
    }
    
    gini_impurity(labels) {
      const total = labels.length;
      if (total === 0) return 0;
      const counts = {};
      labels.forEach(label => counts[label] = (counts[label] || 0) + 1);
      let impurity = 1.0;
      Object.values(counts).forEach(count => {
        const prob = count / total;
        impurity -= prob * prob;
      });
      return impurity;
    }
    
    split_data(X, y, feature, threshold) {
      const left_X = [], left_y = [], right_X = [], right_y = [];
      X.forEach((row, i) => {
        if (row[feature] <= threshold) {
          left_X.push(row);
          left_y.push(y[i]);
        } else {
          right_X.push(row);
          right_y.push(y[i]);
        }
      });
      return [left_X, left_y, right_X, right_y];
    }
    
    find_best_split(X, y) {
      let best_gain = -1, best_feature = null, best_threshold = null;
      const n_features = X[0].length;
      const parent_impurity = this.gini_impurity(y);
      
      for (let feature = 0; feature < n_features; feature++) {
        const unique_values = [...new Set(X.map(row => row[feature]))];
        unique_values.forEach(threshold => {
          const [left_X, left_y, right_X, right_y] = this.split_data(X, y, feature, threshold);
          if (left_y.length === 0 || right_y.length === 0) return;
          
          const n = y.length;
          const weighted_impurity = (left_y.length / n) * this.gini_impurity(left_y) + (right_y.length / n) * this.gini_impurity(right_y);
          const gain = parent_impurity - weighted_impurity;
          
          if (gain > best_gain) {
            best_gain = gain;
            best_feature = feature;
            best_threshold = threshold;
          }
        });
      }
      return [best_feature, best_threshold, best_gain];
    }
    
    build_tree(X, y, depth = 0) {
      const n_samples = y.length;
      const n_labels = new Set(y).size;
      
      if (depth >= this.max_depth || n_labels === 1 || n_samples < this.min_samples_split) {
        const counts = {};
        y.forEach(label => counts[label] = (counts[label] || 0) + 1);
        return new DecisionTreeNode(parseInt(Object.keys(counts).reduce((a, b) => counts[a] > counts[b] ? a : b)));
      }
      
      const [best_feature, best_threshold] = this.find_best_split(X, y);
      if (best_feature === null) {
        const counts = {};
        y.forEach(label => counts[label] = (counts[label] || 0) + 1);
        return new DecisionTreeNode(parseInt(Object.keys(counts).reduce((a, b) => counts[a] > counts[b] ? a : b)));
      }
      
      const [left_X, left_y, right_X, right_y] = this.split_data(X, y, best_feature, best_threshold);
      const node = new DecisionTreeNode();
      node.feature = best_feature;
      node.threshold = best_threshold;
      node.left = this.build_tree(left_X, left_y, depth + 1);
      node.right = this.build_tree(right_X, right_y, depth + 1);
      return node;
    }
    
    fit(X, y) {
      this.root = this.build_tree(X, y);
    }
    
    predict_single(x) {
      let node = this.root;
      while (!node.is_leaf) {
        node = x[node.feature] <= node.threshold ? node.left : node.right;
      }
      return node.value;
    }
  }

  let appointments = [];
  let peakHourTree = null;
  let noshowTree = null;
  let currentYear = new Date().getFullYear();
  let currentMonth = new Date().getMonth() + 1;

  function trainModels() {
    if (appointments.length < 3) return;
    
    const X_peak = [], y_peak = [];
    appointments.forEach(apt => {
      const date = new Date(apt.appointment_date);
      X_peak.push([date.getDay(), date.getDate(), date.getMonth() + 1]);
      y_peak.push(date.getHours());
    });
    peakHourTree = new DecisionTree(3, 2);
    peakHourTree.fit(X_peak, y_peak);
    
    const X_noshow = [], y_noshow = [];
    appointments.forEach(apt => {
      const date = new Date(apt.appointment_date);
      const dayOfWeek = date.getDay();
      const hour = date.getHours();
      const dayOfMonth = date.getDate();
      X_noshow.push([dayOfWeek, hour, dayOfMonth]);
      y_noshow.push(apt.status === 'no_show' ? 1 : 0);
    });
    noshowTree = new DecisionTree(3, 2);
    noshowTree.fit(X_noshow, y_noshow);
  }

  function updateDashboard() {
    const total = appointments.length;
    const noshows = appointments.filter(apt => apt.status === 'no_show').length;
    const noshowPct = total > 0 ? (noshows / total) * 100 : 0;
    
    document.getElementById('totalAppointments').textContent = total;
    document.getElementById('noshowRate').textContent = noshowPct.toFixed(1) + '%';
    document.getElementById('noshowCount').textContent = noshows;

    const hourCounts = {};
    appointments.forEach(apt => {
      const hour = new Date(apt.appointment_date).getHours();
      hourCounts[hour] = (hourCounts[hour] || 0) + 1;
    });
    const peakHour = Object.keys(hourCounts).reduce((a, b) => hourCounts[a] > hourCounts[b] ? a : b, 10);
    document.getElementById('peakHour').textContent = peakHour + ':00';
    document.getElementById('peakHourCount').textContent = (hourCounts[peakHour] || 0) + ' appointments';
  }

  function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';
    
    const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    dayNames.forEach(day => {
      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.textContent = day;
      grid.appendChild(header);
    });

    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
    const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
    const adjustedFirstDay = firstDay === 0 ? 6 : firstDay - 1;

    const monthAppointments = appointments.filter(apt => {
      const aptDate = new Date(apt.appointment_date);
      return aptDate.getMonth() + 1 === currentMonth && aptDate.getFullYear() === currentYear;
    });
    const avgPerDay = monthAppointments.length > 0 ? monthAppointments.length / daysInMonth : 0;

    for (let i = 0; i < adjustedFirstDay; i++) {
      const empty = document.createElement('div');
      empty.className = 'calendar-day empty';
      grid.appendChild(empty);
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const currentDate = new Date(currentYear, currentMonth - 1, day);
      const dayEl = document.createElement('div');
      const isToday = dateStr === new Date().toISOString().split('T')[0];
      dayEl.className = `calendar-day ${isToday ? 'today' : ''}`;

      const dayAppointments = appointments.filter(apt => apt.appointment_date.startsWith(dateStr));
      const total = dayAppointments.length;
      const actualNoshows = dayAppointments.filter(apt => apt.status === 'no_show').length;
      
      const isPastDate = currentDate < today;
      
      if (total > 0) {
        // ACTUAL DATA ONLY - No predictions when there are real bookings
        let peakClass = '';
        let noshowClass = '';
        
        // Calculate peak level based on actual volume vs month average
        if (avgPerDay > 0) {
          const ratio = total / avgPerDay;
          if (ratio >= 1.5) peakClass = 'high';
          else if (ratio >= 1.0) peakClass = 'medium';
          else peakClass = 'low';
        } else {
          // First bookings in the month
          if (total >= 3) peakClass = 'high';
          else if (total >= 2) peakClass = 'medium';
          else peakClass = 'low';
        }

        // Calculate no-show rate based on ACTUAL outcomes only
        const noshowPct = Math.round((actualNoshows / total) * 100);
        if (noshowPct >= 50) noshowClass = 'noshow-high';
        else if (noshowPct >= 25) noshowClass = 'noshow-medium';
        else noshowClass = 'noshow-low';

        // Display actual data
        let content = `<div class="day-number">${day}</div>`;
        content += `<span class="peak-level ${peakClass}"></span>`;
        content += `<div class="day-stats">`;
        content += `<span class="appointments-count">${total} apt${total > 1 ? 's' : ''}</span><br>`;
        content += `<span class="noshow-badge ${noshowClass}">${noshowPct}% actual</span>`;
        content += `</div>`;
        
        dayEl.innerHTML = content;
        dayEl.onclick = () => showDayDetail(dateStr, total, actualNoshows, dayAppointments);
      } else if (!isPastDate && appointments.length >= 5) {
        // PREDICTIONS - Future days without bookings
        const dayOfWeek = currentDate.getDay();
        
        // Get historical data for this day of week
        const sameDayAppointments = appointments.filter(apt => {
          const aptDate = new Date(apt.appointment_date);
          return aptDate.getDay() === dayOfWeek;
        });
        
        // Calculate expected appointments based on day of week pattern
        const avgForThisDay = sameDayAppointments.length > 0 ? 
          sameDayAppointments.length / (appointments.length / 30) : avgPerDay;
        
        // Predict peak hour using ML or fallback to historical average
        let predictedHour = 10; // default
        if (peakHourTree && sameDayAppointments.length > 0) {
          predictedHour = peakHourTree.predict_single([dayOfWeek, day, currentMonth]);
        } else if (sameDayAppointments.length > 0) {
          const hourCounts = {};
          sameDayAppointments.forEach(apt => {
            const h = new Date(apt.appointment_date).getHours();
            hourCounts[h] = (hourCounts[h] || 0) + 1;
          });
          predictedHour = parseInt(Object.keys(hourCounts).reduce((a, b) => hourCounts[a] > hourCounts[b] ? a : b, 10));
        }
        
        // Determine peak level based on expected volume
        if (avgPerDay > 0) {
          if (avgForThisDay > avgPerDay * 1.5) peakClass = 'high';
          else if (avgForThisDay > avgPerDay * 0.8) peakClass = 'medium';
          else peakClass = 'low';
        } else {
          peakClass = 'medium';
        }
        
        // Calculate no-show risk based on historical patterns
        const sameDayNoshows = sameDayAppointments.filter(apt => apt.status === 'no_show').length;
        const historicalNoshowRate = sameDayAppointments.length > 0 ? 
          (sameDayNoshows / sameDayAppointments.length) * 100 : 30;
        
        // Use ML prediction if available
        let predictedNoshow = 0;
        if (noshowTree) {
          predictedNoshow = noshowTree.predict_single([dayOfWeek, predictedHour, day]);
        }
        
        noshowPct = Math.round(historicalNoshowRate);
        
        if (historicalNoshowRate >= 40 || predictedNoshow === 1) noshowClass = 'noshow-high';
        else if (historicalNoshowRate >= 20) noshowClass = 'noshow-medium';
        else noshowClass = 'noshow-low';

        let content = `<div class="day-number">${day}</div>`;
        content += `<span class="peak-level ${peakClass}" style="opacity: 0.8;"></span>`;
        content += `<div class="day-stats">`;
        const expectedCount = Math.max(1, Math.round(avgForThisDay));
        content += `<span class="appointments-count" style="opacity: 0.7; font-style: italic;">~${expectedCount} expected</span><br>`;
        content += `<span class="noshow-badge ${noshowClass}" style="opacity: 0.7;">${noshowPct}% risk</span>`;
        content += `</div>`;
        
        dayEl.innerHTML = content;
        dayEl.onclick = () => showPredictionDetail(dateStr, dayOfWeek, predictedHour, noshowPct, peakClass, expectedCount, sameDayAppointments.length);
      } else {
        // Not enough data or past date
        dayEl.innerHTML = `<div class="day-number">${day}</div>`;
      }

      grid.appendChild(dayEl);
    }
  }

  function showDayDetail(dateStr, total, noshows, dayAppointments) {
    const modal = document.getElementById('dayModal');
    const modalBody = document.getElementById('modalBody');
    
    const date = new Date(dateStr);
    const formattedDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    const noshowPct = total > 0 ? Math.round((noshows / total) * 100) : 0;
    const confirmed = total - noshows;
    
    let riskLevel = 'Low';
    let riskColor = '#51cf66';
    if (noshowPct >= 50) {
      riskLevel = 'High';
      riskColor = '#ff6b6b';
    } else if (noshowPct >= 25) {
      riskLevel = 'Medium';
      riskColor = '#ffd43b';
    }

    // Get hourly breakdown from ACTUAL appointments
    const hours = {};
    let peakHour = '--';
    let peakHourCount = 0;
    
    dayAppointments.forEach(apt => {
      const hour = new Date(apt.appointment_date).getHours();
      hours[hour] = (hours[hour] || 0) + 1;
      if (hours[hour] > peakHourCount) {
        peakHourCount = hours[hour];
        peakHour = hour;
      }
    });

    let hourlyBreakdown = '<div class="hourly-breakdown"><h4>Hourly Distribution (Actual):</h4>';
    const sortedHours = Object.entries(hours).sort((a, b) => b[1] - a[1]);
    
    if (sortedHours.length > 0) {
      sortedHours.forEach(([hour, count]) => {
        const isPeak = hour == peakHour;
        hourlyBreakdown += `
          <div class="hour-item" style="${isPeak ? 'border-left: 4px solid #FFD43B; background: #fffbf0;' : ''}">
            <span><strong>${hour}:00</strong> ${isPeak ? '⭐ Peak' : ''}</span>
            <span>${count} appointment${count > 1 ? 's' : ''}</span>
          </div>
        `;
      });
    } else {
      hourlyBreakdown += '<p style="color: #999; font-style: italic;">No hourly data available</p>';
    }
    hourlyBreakdown += '</div>';

    const recommendation = noshowPct >= 50 
      ? 'High no-show rate detected. Send SMS reminders 24h before and consider requiring deposits.'
      : noshowPct >= 25 
      ? 'Moderate no-show rate. Send email reminders and follow up with clients.'
      : noshowPct === 0 && total > 0
      ? 'Perfect attendance! Continue with current confirmation process.'
      : 'Low no-show rate. Standard confirmation process is working well.';

    modalBody.innerHTML = `
      <div class="detail-row">
        <span class="detail-label">Date:</span>
        <span class="detail-value">${formattedDate}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Total Appointments:</span>
        <span class="detail-value"><strong>${total}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Confirmed/Completed:</span>
        <span class="detail-value" style="color: #51cf66;"><strong>${confirmed}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Shows:</span>
        <span class="detail-value" style="color: #ff6b6b;"><strong>${noshows}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Show Rate:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${noshowPct}%</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Risk Assessment:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${riskLevel} Risk</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Peak Hour (Actual):</span>
        <span class="detail-value"><strong>${peakHour}:00</strong> (${peakHourCount} apt${peakHourCount > 1 ? 's' : ''})</span>
      </div>
      ${hourlyBreakdown}
      <div class="algorithm-info">
        <strong>📊 100% Actual Data:</strong> All statistics shown are based on real bookings for this specific day. No predictions or estimates are included.
      </div>
      <div class="algorithm-info">
        <strong>💡 Recommendation:</strong> ${recommendation}
      </div>
    `;

    modal.classList.add('show');
  }

  function showPredictionDetail(dateStr, dayOfWeek, predictedHour, noshowPct, peakClass, expectedCount, historicalCount) {
    const modal = document.getElementById('dayModal');
    const modalBody = document.getElementById('modalBody');
    
    const date = new Date(dateStr);
    const formattedDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    let riskLevel = 'Low';
    let riskColor = '#51cf66';
    if (noshowPct >= 40) {
      riskLevel = 'High';
      riskColor = '#ff6b6b';
    } else if (noshowPct >= 20) {
      riskLevel = 'Medium';
      riskColor = '#ffd43b';
    }
    
    let peakLevel = peakClass === 'high' ? 'High' : peakClass === 'medium' ? 'Medium' : 'Low';
    let peakColor = peakClass === 'high' ? '#ff6b6b' : peakClass === 'medium' ? '#ffd43b' : '#51cf66';

    const recommendation = noshowPct >= 40 
      ? 'High risk day. Send multiple reminders and consider overbooking slightly.'
      : noshowPct >= 20 
      ? 'Moderate risk. Send reminders 24-48h in advance.'
      : 'Low risk day. Standard procedures recommended.';

    modalBody.innerHTML = `
      <div class="detail-row">
        <span class="detail-label">Date:</span>
        <span class="detail-value">${formattedDate}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Expected Appointments:</span>
        <span class="detail-value"><strong>~${expectedCount}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Predicted Peak Hour:</span>
        <span class="detail-value"><strong>${predictedHour}:00</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Expected Demand:</span>
        <span class="detail-value"><strong style="color: ${peakColor};">${peakLevel}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Show Risk:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${noshowPct}% (${riskLevel})</strong></span>
      </div>
      <div class="algorithm-info">
      </div>
      <div class="algorithm-info">

      <div class="algorithm-info">
      </div>
    `;

    modal.classList.add('show');
  }

  function closeModal() {
    document.getElementById('dayModal').classList.remove('show');
  }

  document.getElementById('dayModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });

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
    renderCalendar();
  }

  function updateCalendar() {
    currentMonth = parseInt(document.getElementById('monthSelect').value);
    currentYear = parseInt(document.getElementById('yearSelect').value);
    renderCalendar();
  }

  function toggleDropdown(event) {
    event.preventDefault();
    const dropdown = event.currentTarget.parentElement;
    const menu = dropdown.querySelector('.dropdown-menu');
    menu.classList.toggle('show');
  }

  window.addEventListener('DOMContentLoaded', async () => {
    try {
      const SUPABASE_URL = 'https://pgapbbukmyitwuvfbgho.supabase.co';
      const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A';
      
      const response = await fetch(`${SUPABASE_URL}/rest/v1/appointments?select=*`, {
        headers: {
          'apikey': SUPABASE_KEY,
          'Authorization': `Bearer ${SUPABASE_KEY}`,
        }
      });
      
      if (response.ok) {
        appointments = await response.json();
      } else {
        console.error('Error fetching appointments');
        appointments = [];
      }
    } catch (error) {
      console.error('Error:', error);
      appointments = [];
    }

    document.getElementById('monthSelect').value = currentMonth;
    document.getElementById('yearSelect').value = currentYear;
    trainModels();
    updateDashboard();
    renderCalendar();
  });
</script>

</body>
</html>