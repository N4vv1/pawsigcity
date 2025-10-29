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

// ✅ Fetch user's pets securely
$pets_result = pg_query_params(
    $conn,
    "SELECT * FROM pets WHERE user_id = $1",
    [$user_id]
);

$recommended_package = null;

// ✅ Function to get appointment counts by date and hour (SIMPLIFIED - NO ML)
function getAppointmentCounts($conn) {
    $query = "
        SELECT 
            DATE(appointment_date) as date,
            EXTRACT(HOUR FROM appointment_date) AS hour,
            COUNT(*) AS appointment_count
        FROM appointments 
        WHERE appointment_date >= NOW()
        AND status != 'cancelled'
        GROUP BY DATE(appointment_date), EXTRACT(HOUR FROM appointment_date)
    ";
    
    $result = pg_query($conn, $query);
    $counts = [];

    while ($row = pg_fetch_assoc($result)) {
        $date = $row['date'];
        $hour = (int)$row['hour'];
        $count = (int)$row['appointment_count'];
        
        if (!isset($counts[$date])) {
            $counts[$date] = [];
        }
        $counts[$date][$hour] = $count;
    }

    return $counts;
}

// ✅ Get appointment counts (NO ML PREDICTIONS - JUST REAL DATA)
$appointment_counts = getAppointmentCounts($conn);

// ✅ Check pet ownership if selected
if ($selected_pet_id) {
    $pet_check = pg_query_params(
        $conn,
        "SELECT * FROM pets WHERE pet_id = $1 AND user_id = $2",
        [$selected_pet_id, $user_id]
    );
    $valid_pet = pg_fetch_assoc($pet_check);

    if (!$valid_pet) {
        echo "<p style='text-align:center;color:red;'>Invalid pet selection.</p>";
        exit;
    }

    // API call for package recommendation
    $api_url = "https://pawsigcity-1.onrender.com/recommend";
    $payload = json_encode([
        "breed" => $valid_pet['breed'],
    ]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // ✅ FIXED: Better error handling
    if ($curl_error) {
        error_log("API Error: " . $curl_error);
        $_SESSION['error'] = "⚠️ Could not connect to recommendation service.";
        $recommended_package = null;
    } elseif ($http_code !== 200) {
        error_log("API HTTP Error: " . $http_code . " Response: " . $response);
        $_SESSION['error'] = "⚠️ Recommendation service unavailable.";
        $recommended_package = null;
    } else {
        $response_data = json_decode($response, true);
        
        if (isset($response_data['recommended_package'])) {
            $recommended_package = $response_data['recommended_package'];
            
            // ✅ FIXED: Verify the package exists in database
            $package_verify = pg_query_params(
                $conn,
                "SELECT p.name FROM packages p WHERE p.name ILIKE '%' || $1 || '%' LIMIT 1",
                [$recommended_package]
            );
            
            if (!pg_fetch_assoc($package_verify)) {
                error_log("Recommended package not found in DB: " . $recommended_package);
                $_SESSION['error'] = "⚠️ Recommended package '{$recommended_package}' not available.";
                $recommended_package = null;
            }
        } elseif (isset($response_data['error'])) {
            $_SESSION['error'] = "⚠️ " . htmlspecialchars($response_data['error']);
            $recommended_package = null;
        }
    }

    // Fetch packages for dropdown
    $packages_result = pg_query($conn, "
        SELECT pp.price_id, p.name, pp.species, pp.size, pp.min_weight, pp.max_weight, pp.price
        FROM package_prices pp
        JOIN packages p ON pp.package_id = p.package_id
        ORDER BY 
            p.name,
            CASE pp.species
                WHEN 'Dog' THEN 1
                WHEN 'Cat' THEN 2
                ELSE 3
            END,
            CASE pp.size
                WHEN 'Small' THEN 1
                WHEN 'Medium' THEN 2
                WHEN 'Large' THEN 3
                ELSE 4
            END,
            pp.min_weight
    ");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="../homepage/images/pawsig.png">
  
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

  .submit-btn:disabled {
    background-color: #ccc !important;
    color: #666 !important;
    cursor: not-allowed !important;
    opacity: 0.6 !important;
    transform: none !important;
    box-shadow: none !important;
  }
  
  .submit-btn:disabled:hover {
    background-color: #ccc !important;
    transform: none !important;
    box-shadow: none !important;
  }

  /* SIMPLIFIED AVAILABILITY INDICATOR (NO ML) */
  .availability-indicator {
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

  .availability-indicator.show {
    opacity: 1;
  }

  .availability-indicator.full {
    background: #e57373;
  }

  .availability-indicator.available {
    background: #81c784;
  }

  .datetime-container {
    position: relative;
  }

  /* CALENDAR LEGEND */
  .legend-container {
    background-color: #f4f7f8;
    padding: 16px;
    border-radius: 12px;
    margin-top: 10px;
  }

  .legend-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #34495e;
    margin-bottom: 8px;
  }

  .legend-items {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
  }

  .legend-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid #ccc;
  }

  .legend-color.available {
    background-color: #e8f5e8;
  }

  .legend-color.busy {
    background-color: #fff3e0;
  }

  .legend-color.full {
    background-color: #ffebee;
  }
</style>

</head>
<body>
  <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/pawsig.png" alt="Logo" class="icon" />
      </a>
      <ul class="nav-menu">
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Home</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">About</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Services</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Gallery</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link">Contact</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link profile-icon active">
            <i class="fas fa-user"></i>
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

      <?php if (!$selected_pet_id): ?>
        <div class="form-container">
          <h3>Choose a pet to book an appointment for:</h3>
          <?php while ($pet = pg_fetch_assoc($pets_result)): ?>
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
                <?php while ($pkg = pg_fetch_assoc($packages_result)): ?>
                  <option value="<?= $pkg['price_id'] ?>" 
                    <?= ($pkg['name'] == $recommended_package) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pkg['name']) ?> 
                    (<?= htmlspecialchars($pkg['species']) ?> - <?= htmlspecialchars($pkg['size']) ?>,
                    <?= $pkg['min_weight'] ?> - <?= $pkg['max_weight'] ?>) 
                    - ₱<?= number_format($pkg['price'], 2) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="appointment_date"><i class="fas fa-calendar-alt"></i> Appointment Date and Time:</label>
              <div class="datetime-container">
                <input type="text" name="appointment_date" id="appointment_date" class="flatpickr" placeholder="Select date and time" required>
                <div class="availability-indicator" id="availabilityIndicator">Select date/time</div>
              </div>
              <div class="legend-container">
                <div class="legend-title">📅 Calendar Legend:</div>
                <div class="legend-items">
                  <div class="legend-item">
                    <span class="legend-color available"></span>
                    <span>Available (0-2 bookings)</span>
                  </div>
                  <div class="legend-item">
                    <span class="legend-color busy"></span>
                    <span>Busy (3-4 bookings)</span>
                  </div>
                  <div class="legend-item">
                    <span class="legend-color full"></span>
                    <span>Full (5+ bookings - unavailable)</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label for="notes"><i class="fas fa-sticky-note"></i> Notes (optional):</label>
              <textarea name="notes" id="notes" rows="3" placeholder="Any special instructions..."></textarea>
            </div>

            <button type="submit" class="btn submit-btn">Book Appointment</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  // =====================================================
  // SIMPLIFIED BOOKING SYSTEM (NO ML PREDICTIONS)
  // Just shows real appointment counts from database
  // =====================================================
  
  const appointmentCounts = <?= json_encode($appointment_counts) ?>;
  const MAX_APPOINTMENTS = 5; // Maximum appointments per time slot

  // Get actual appointment count for specific date and hour
  function getAppointmentCount(date, hour) {
    const dateStr = date.toISOString().split('T')[0];
    if (appointmentCounts[dateStr] && appointmentCounts[dateStr][hour] !== undefined) {
      return appointmentCounts[dateStr][hour];
    }
    return 0;
  }

  // Check if slot is available (not full)
  function isSlotAvailable(date, hour) {
    const count = getAppointmentCount(date, hour);
    return count < MAX_APPOINTMENTS;
  }

  // Update availability indicator when date/time is selected
  function updateAvailabilityIndicator() {
    const dateInput = document.getElementById('appointment_date');
    const indicator = document.getElementById('availabilityIndicator');
    const submitBtn = document.querySelector('.submit-btn');

    if (!dateInput.value) {
      indicator.className = 'availability-indicator';
      indicator.textContent = 'Select date/time';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Book Appointment';
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.backgroundColor = '';
      submitBtn.style.opacity = '';
      return;
    }

    const selectedDate = new Date(dateInput.value);
    const hour = selectedDate.getHours();
    
    // Check if time is within business hours (9 AM - 6 PM)
    if (hour < 9 || hour > 18) {
      indicator.className = 'availability-indicator show full';
      indicator.innerHTML = '⛔ Outside Business Hours';
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Outside Business Hours (9 AM - 6 PM Only)';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.style.backgroundColor = '#ccc';
      submitBtn.style.opacity = '0.6';
      return;
    }
    
    const appointmentCount = getAppointmentCount(selectedDate, hour);
    const available = isSlotAvailable(selectedDate, hour);

    // Update indicator based on availability
    if (available) {
      indicator.className = 'availability-indicator show available';
      indicator.innerHTML = `✓ Available (${appointmentCount}/${MAX_APPOINTMENTS} booked)`;
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Book Appointment';
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.backgroundColor = '#A8E6CF';
      submitBtn.style.opacity = '1';
    } else {
      indicator.className = 'availability-indicator show full';
      indicator.innerHTML = `✕ Full (${appointmentCount}/${MAX_APPOINTMENTS} booked)`;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Time Slot Full - Please Choose Another';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.style.backgroundColor = '#ccc';
      submitBtn.style.opacity = '0.6';
    }
  }

  // Initialize event listeners
  document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('appointment_date');
    if (dateInput) {
      dateInput.addEventListener('change', updateAvailabilityIndicator);
      dateInput.addEventListener('input', updateAvailabilityIndicator);

      // Set minimum date to today
      const now = new Date();
      const today = now.toISOString().split('T')[0];
      dateInput.setAttribute('min', today + 'T09:00');
      dateInput.setAttribute('max', '2025-12-31T18:00');
    }
  });

  // Initialize Flatpickr calendar with color-coded availability
  flatpickr("#appointment_date", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    minDate: "today",
    defaultHour: 10,
    minTime: "09:00",
    maxTime: "18:00",
    time_24hr: true,
    minuteIncrement: 30,
    allowInput: true,
    clickOpens: true,
    onChange: function(selectedDates, dateStr, instance) {
      updateAvailabilityIndicator();
    },
    // Disable full time slots (5+ bookings) and outside business hours
    disable: [
      function(date) {
        const hour = date.getHours();
        const minute = date.getMinutes();
        
        // If it's just a date (no specific time), allow it
        if (hour === 0 && minute === 0) {
          return false;
        }
        
        // Check business hours and availability
        if (hour < 9 || hour > 18) {
          return true; // Disable outside business hours
        }
        
        return !isSlotAvailable(date, hour); // Disable if full
      }
    ],
    // Color-code calendar days based on average bookings
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      const date = dayElem.dateObj;
      if (date) {
        let totalAppointments = 0;
        let hoursChecked = 0;
        
        // Check all business hours for this day (9 AM - 6 PM)
        for (let h = 9; h <= 18; h++) {
          const testDate = new Date(date);
          testDate.setHours(h);
          totalAppointments += getAppointmentCount(testDate, h);
          hoursChecked++;
        }
        
        const avgAppointments = totalAppointments / hoursChecked;
        
        // Color code based on average bookings per hour
        if (avgAppointments >= 4) {
          dayElem.style.backgroundColor = '#ffebee'; // Red - Busy/Full
          dayElem.title = `Busy day - avg ${avgAppointments.toFixed(1)} bookings per hour`;
        } else if (avgAppointments >= 2) {
          dayElem.style.backgroundColor = '#fff3e0'; // Yellow - Moderate
          dayElem.title = `Moderate day - avg ${avgAppointments.toFixed(1)} bookings per hour`;
        } else {
          dayElem.style.backgroundColor = '#e8f5e8'; // Green - Available
          dayElem.title = `Available day - avg ${avgAppointments.toFixed(1)} bookings per hour`;
        }
      }
    }
  });
  </script>

</body>
</html>