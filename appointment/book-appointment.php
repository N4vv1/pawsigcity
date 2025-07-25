<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : null;
$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : null;

// Fetch user's pets securely
$pets_stmt = $mysqli->prepare("SELECT * FROM pets WHERE user_id = ?");
$pets_stmt->bind_param("i", $user_id);
$pets_stmt->execute();
$pets_result = $pets_stmt->get_result();

$recommended_package = null;

// Function to get peak hours data
function getPeakHoursData($mysqli) {
    $peak_hours_query = "
        SELECT 
            DAYOFWEEK(appointment_date) as day_of_week,
            COUNT(*) as appointment_count
        FROM appointments 
        WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND status != 'cancelled'
        GROUP BY DAYOFWEEK(appointment_date)
        ORDER BY appointment_count DESC
    ";
    
    $result = $mysqli->query($peak_hours_query);
    $peak_data = [];
    
    while ($row = $result->fetch_assoc()) {
        $peak_data[] = $row;
    }
    
    return $peak_data;
}

// Function to predict peak hours for a given date
function predictPeakHours($mysqli, $date) {
    $day_of_week = date('N', strtotime($date)) + 1; // Convert to MySQL DAYOFWEEK format
    
    // Get average appointments per hour for this day of week
    $query = "
        SELECT 
            HOUR(appointment_date) as hour,
            COUNT(*) as avg_appointments,
            CASE 
                WHEN COUNT(*) >= 8 THEN 'high'
                WHEN COUNT(*) >= 4 THEN 'medium'
                ELSE 'low'
            END as peak_level
        FROM appointments 
        WHERE DAYOFWEEK(appointment_date) = ?
        AND appointment_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND status != 'cancelled'
        GROUP BY HOUR(appointment_date)
        ORDER BY hour ASC
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $day_of_week);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hourly_data = [];
    while ($row = $result->fetch_assoc()) {
        $hourly_data[$row['hour']] = $row;
    }
    
    return $hourly_data;
}

// Get peak hours data for display
$peak_hours_data = getPeakHoursData($mysqli);

// If pet is selected, check if the pet belongs to the user
if ($selected_pet_id) {
    $pet_check_stmt = $mysqli->prepare("SELECT * FROM pets WHERE pet_id = ? AND user_id = ?");
    $pet_check_stmt->bind_param("ii", $selected_pet_id, $user_id);
    $pet_check_stmt->execute();
    $valid_pet = $pet_check_stmt->get_result()->fetch_assoc();

    if (!$valid_pet) {
        echo "<p style='text-align:center;color:red;'>Invalid pet selection.</p>";
        exit;
    }

    // API call
    $api_url = "http://127.0.0.1:5000/recommend";
    $payload = json_encode([
        "breed" => $valid_pet['breed'],
        "gender" => $valid_pet['gender'],
        "age" => (int)$valid_pet['age']
    ]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $_SESSION['error'] = "⚠️ API request error: " . curl_error($ch);
        curl_close($ch);
        header("Location: book-appointment.php");
        exit;
    }
    curl_close($ch);

    $response_data = json_decode($response, true);
    $recommended_package = $response_data['recommended_package'] ?? null;

    $packages_stmt = $mysqli->prepare("SELECT * FROM packages WHERE is_active = 1");
    $packages_stmt->execute();
    $packages_result = $packages_stmt->get_result();
}

if (isset($response_data) && isset($response_data['error'])) {
    $recommended_package = null;
    $_SESSION['error'] = "⚠️ Recommendation not available for this breed: " . htmlspecialchars($valid_pet['breed']);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="../homepage/images/Logo.jpg">
  <!-- Add inside <head> -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


 <style>
  * {
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
  }

  .header-wrapper {
    margin: 0;
    padding: 0;
  }

  .form-wrapper {
    margin-top: 100px;
  }

  .page-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px;
    background-color: #fff;
    border-radius: 20px;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease-in-out;
  }

  h2, h3 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 24px;
    font-weight: 700;
  }

  .form-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  .card {
    background-color: #f4f7f8;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  }

  .card strong {
    font-size: 1.1rem;
    color: #34495e;
  }

  .btn, .submit-btn {
    background-color: #A8E6CF;
    border: none;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    color: #252525;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
  }

  .btn:hover, .submit-btn:hover {
    background-color: #87d7b7;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  select,
  input[type="datetime-local"],
  input[type="text"],
  textarea {
    width: 100%;
    padding: 12px 16px;
    margin-top: 6px;
    font-size: 1rem;
    font-family: inherit;
    border: 1px solid #ccc;
    border-radius: 10px;
    background-color: #fff;
    color: #333;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: all 0.25s ease-in-out;
  }

  select:focus,
  input[type="datetime-local"]:focus,
  input[type="text"]:focus,
  textarea:focus {
    border-color: #A8E6CF;
    box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.4);
    outline: none;
    background-color: #fcfffc;
  }

  textarea {
    resize: vertical;
    min-height: 100px;
  }

  label {
    font-weight: 600;
    color: #333;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  label i {
    color: #A8E6CF;
  }

  .alert-success,
  .alert-error {
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: 500;
    margin-bottom: 20px;
  }

  .alert-success {
    background-color: #e0fce0;
    color: #2e7d32;
    border: 1px solid #b2dfdb;
  }

  .alert-error {
    background-color: #ffe3e3;
    color: #c62828;
    border: 1px solid #ffcdd2;
  }

  .booking-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #fafafa;
    border: 1px solid #eee;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.03);
  }

  .form-group {
    display: flex;
    flex-direction: column;
  }

  .recommendation-box {
    background-color: #e8fff3;
    border-left: 5px solid #A8E6CF;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 500;
    color: #2c3e50;
  }

  .recommend {
    color: #16a085;
    font-weight: bold;
  }

  .back-link {
    display: inline-block;
    color: #3498db;
    font-weight: 600;
    margin-bottom: 16px;
    transition: color 0.3s ease;
  }

  a, a:hover {
    text-decoration: none;
    color: #3498db;
  }

  a:hover {
    color: #2c80b4;
  }

  /* Peak Hours Matching Theme Styles */
.peak-hours-container {
  background: #f4f7f8;
  color: #2c3e50;
  padding: 24px;
  border-radius: 16px;
  margin: 20px 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid #ddd;
}

.peak-hours-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #34495e;
}

.peak-info {
  background-color: #ffffff;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e2e2;
  font-size: 0.95rem;
  line-height: 1.4;
  margin-bottom: 16px;
}

.peak-legend {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 16px;
  font-size: 0.9rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid #ccc;
}

.legend-color.high {
  background-color: #ffcccc;
}

.legend-color.medium {
  background-color: #fff3cd;
}

.legend-color.low {
  background-color: #d4edda;
}

.peak-hours-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.peak-hour-item {
  background-color: #ffffff;
  padding: 12px;
  border-radius: 10px;
  text-align: center;
  border: 1px solid #ddd;
  box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.peak-hour-item.high {
  background-color: #ffefef;
  border-color: #f8d7da;
}

.peak-hour-item.medium {
  background-color: #fffbea;
  border-color: #ffeeba;
}

.peak-hour-item.low {
  background-color: #ecfdf3;
  border-color: #c3e6cb;
}

.hour-time {
  font-weight: 600;
  font-size: 1.1rem;
}

.hour-level {
  font-size: 0.9rem;
  margin-top: 4px;
  color: #666;
}

/* Peak Indicator over input */
.peak-indicator {
  position: absolute;
  top: -10px;
  right: -10px;
  background: #ccc;
  color: white;
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 600;
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: 10;
}

.peak-indicator.show {
  opacity: 1;
}

.peak-indicator.high {
  background: #e57373;
}

.peak-indicator.medium {
  background: #f0ad4e;
  color: #333;
}

.peak-indicator.low {
  background: #81c784;
}

.datetime-container {
  position: relative;
}




</style>

</head>
<body>
  <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/Logo.jpg" alt="Logo" class="icon" />
      </a>
      <ul class="nav-menu">
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">About</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link active">Services</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Gallery</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Contact</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link profile-icon">
            <i class="fas fa-user-circle"></i>
          </a>
          <ul class="dropdown-menu">
            <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
            <li><a href="../pets/add-pet.php">Add Pet</a></li>
            <li><a href="../appointment/book-appointment.php">Book</a></li>
            <li><a href="../homepage/appointments.php">Appointments</a></li>
            <li><a href="../../Purrfect-paws/ai/chatbot/index.html">Help Center</a></li>
            <li><a href="../homepage/logout/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

<div style="height: 60px;"></div>
  <div class="form-wrapper">
    <div class="page-content">
      <h2>Book a Grooming Appointment</h2>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- Peak Hours Information -->
      <div class="peak-hours-container">
        <div class="peak-hours-title">
          <i class="fas fa-chart-line"></i>
          Peak Hours Prediction
        </div>
        
        <div class="peak-info">
          <strong>💡 Smart Scheduling:</strong> Based on historical data, we predict busy and quiet times to help you choose the best appointment slot. Green hours typically have shorter wait times!
        </div>
        
        <div class="peak-legend">
          <div class="legend-item">
            <div class="legend-color high"></div>
            <span>High Demand</span>
          </div>
          <div class="legend-item">
            <div class="legend-color medium"></div>
            <span>Moderate Demand</span>
          </div>
          <div class="legend-item">
            <div class="legend-color low"></div>
            <span>Low Demand</span>
          </div>
        </div>
      </div>

      <?php if (!$selected_pet_id): ?>
        <div class="form-container">
          <h3>Choose a pet to book an appointment for:</h3>
          <?php while ($pet = $pets_result->fetch_assoc()): ?>
            <div class="card">
              <strong><?= htmlspecialchars($pet['name']) ?></strong> (<?= htmlspecialchars($pet['breed']) ?>)
              <form method="GET" action="book-appointment.php" style="margin-top:10px;">
                <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
                <button class="btn" type="submit">Book for this Pet</button>
              </form>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="form-container">
          <a href="book-appointment.php" class="back-link">← Choose another pet</a>

          <form method="POST" action="appointment-handler.php" class="booking-form">
            <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">

            <?php if ($recommended_package): ?>
              <input type="hidden" name="recommended_package" value="<?= htmlspecialchars($recommended_package) ?>">
              <div class="recommendation-box">
                🐾 Recommended Package for <strong><?= htmlspecialchars($valid_pet['name']) ?></strong>:
                <span class="recommend"><?= htmlspecialchars($recommended_package) ?></span>
              </div>
            <?php endif; ?>

            <div class="form-group">
              <label for="package_id"><i class="fas fa-box"></i> Select Grooming Package:</label>
              <select name="package_id" id="package_id" required>
                <?php while ($pkg = $packages_result->fetch_assoc()): ?>
                  <option value="<?= $pkg['id'] ?>" <?= ($pkg['name'] == $recommended_package) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pkg['name']) ?> - ₱<?= number_format($pkg['price'], 2) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="appointment_date"><i class="fas fa-calendar-alt"></i> Appointment Date and Time:</label>
              <div class="datetime-container">
                <input type="text" name="appointment_date" id="appointment_date" class="flatpickr" placeholder="Select date and time" required>
                <div class="peak-indicator" id="peakIndicator">Select date/time</div>
              </div>
              <small style="color: #666; margin-top: 5px;">💡 The indicator above shows demand level for your selected time</small>
            </div>
            
            <div class="form-group">
              <label for="notes"><i class="fas fa-sticky-note"></i> Notes (optional):</label>
              <textarea name="notes" id="notes" rows="3" placeholder="Any special instructions..."></textarea>
            </div>

            <button type="submit" class="btn submit-btn">📅 Book Appointment</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  // Peak hours data from PHP
  const peakHoursData = <?= json_encode($peak_hours_data) ?>;

  // Create a map for quick lookup by day of week
  const peakHoursMap = {};
  peakHoursData.forEach(item => {
    const key = `${item.day_of_week}`;
    peakHoursMap[key] = item.appointment_count;
  });

  // Function to get peak level based on appointment count
  function getPeakLevel(count) {
    if (count >= 5) return 'high';
    if (count >= 3) return 'medium';
    return 'low';
  }

  // Function to update peak indicator and disable high demand days
  function updatePeakIndicator() {
    const dateInput = document.getElementById('appointment_date');
    const indicator = document.getElementById('peakIndicator');
    const submitBtn = document.querySelector('.submit-btn');

    if (!dateInput.value) {
      indicator.className = 'peak-indicator';
      indicator.textContent = 'Select date/time';
      submitBtn.disabled = false;
      submitBtn.textContent = '📅 Book Appointment';
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.backgroundColor = '';
      return;
    }

    const selectedDate = new Date(dateInput.value);
    const dayOfWeek = selectedDate.getDay() + 1; // Convert to MySQL DAYOFWEEK (1 = Sunday)

    const appointmentCount = peakHoursMap[dayOfWeek] || 0;
    const peakLevel = getPeakLevel(appointmentCount);

    // Update peak indicator design
    indicator.className = `peak-indicator show ${peakLevel}`;

    let text = '';
    let icon = '';
    switch (peakLevel) {
      case 'high':
        text = 'High Demand';
        icon = '🔴';
        break;
      case 'medium':
        text = 'Moderate';
        icon = '🟡';
        break;
      case 'low':
        text = 'Low Demand';
        icon = '🟢';
        break;
    }
    indicator.textContent = `${icon} ${text}`;

    // Disable booking on high demand days
    if (peakLevel === 'high') {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Unavailable (High Demand)';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.style.backgroundColor = '#ccc';
    } else {
      submitBtn.disabled = false;
      submitBtn.textContent = '📅 Book Appointment';
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.backgroundColor = '';
    }
  }

  // Set up listeners when the page loads
  document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('appointment_date');
    if (dateInput) {
      dateInput.addEventListener('change', updatePeakIndicator);
      dateInput.addEventListener('input', updatePeakIndicator);

      // Set minimum date to now
      const now = new Date();
      const offset = now.getTimezoneOffset() * 60000;
      const localISOTime = new Date(now.getTime() - offset).toISOString().slice(0, 16);
      dateInput.min = localISOTime;
    }
  });

  // Optional: Developer console view of weekly pattern
  function displayWeeklyPeakHours() {
    const summary = {};
    for (let day = 1; day <= 7; day++) {
      const count = peakHoursMap[day] || 0;
      summary[day] = {
        count: count,
        level: getPeakLevel(count)
      };
    }
    console.log('📊 Peak Demand Per Day:', summary);
  }

  document.addEventListener('DOMContentLoaded', displayWeeklyPeakHours);

  flatpickr("#appointment_date", {
  enableTime: true,
  dateFormat: "Y-m-d H:i",
  minDate: "today",
  defaultHour: 10,
  time_24hr: true,
  onChange: function(selectedDates, dateStr, instance) {
    updatePeakIndicator(); // Your custom logic for peak indicator
  }
});
</script>

</body>
</html>