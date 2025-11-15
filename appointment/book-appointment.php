<?php
session_start();
require '../db.php';

// Clear any previous errors when loading the pet selection page
if (!isset($_GET['pet_id'])) {
    unset($_SESSION['error']);
    unset($_SESSION['success']);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_pet_id = isset($_GET['pet_id']) ? $_GET['pet_id'] : null;
$package_id = isset($_GET['package_id']) ? $_GET['package_id'] : null;

// Initialize ALL variables at the top
$valid_pet = null;
$packages_result = null;
$recommended_package = null;
$groomers_array = [];

// Fetch user's pets securely
$pets_result = pg_query_params(
    $conn,
    "SELECT * FROM pets WHERE user_id = $1",
    [$user_id]
);

// Function to get detailed appointment statistics by date
function getAppointmentStatsByDate($conn) {
    $query = "
        SELECT 
            DATE(appointment_date) as date,
            COUNT(*) AS total_appointments,
            COUNT(CASE WHEN EXTRACT(HOUR FROM appointment_date) BETWEEN 9 AND 12 THEN 1 END) as morning_count,
            COUNT(CASE WHEN EXTRACT(HOUR FROM appointment_date) BETWEEN 13 AND 18 THEN 1 END) as afternoon_count
        FROM appointments 
        WHERE appointment_date >= CURRENT_DATE
        AND status IN ('confirmed', 'pending')
        GROUP BY DATE(appointment_date)
        ORDER BY DATE(appointment_date)
    ";
    
    $result = pg_query($conn, $query);
    $stats = [];

    while ($row = pg_fetch_assoc($result)) {
        $stats[$row['date']] = [
            'total' => (int)$row['total_appointments'],
            'morning' => (int)$row['morning_count'],
            'afternoon' => (int)$row['afternoon_count']
        ];
    }

    return $stats;
}

// Function to get appointment counts by date and hour
function getAppointmentCountsByHour($conn) {
    $query = "
        SELECT 
            DATE(appointment_date) as date,
            EXTRACT(HOUR FROM appointment_date) AS hour,
            COUNT(*) AS appointment_count
        FROM appointments 
        WHERE appointment_date >= CURRENT_DATE
        AND status IN ('confirmed', 'pending')
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

$appointment_stats = getAppointmentStatsByDate($conn);
$appointment_counts = getAppointmentCountsByHour($conn);

// Fetch groomers with their active status
$groomers_query = "
    SELECT groomer_id, groomer_name, is_active 
    FROM groomer 
    ORDER BY is_active DESC, groomer_name ASC
";

$groomers_result = pg_query($conn, $groomers_query);

if (!$groomers_result) {
    die("Groomer query failed: " . pg_last_error($conn));
}

// Store groomers in an array to reuse and avoid pointer issues
$groomers_array = [];
while ($groomer = pg_fetch_assoc($groomers_result)) {
    $groomers_array[] = $groomer;
}

if ($selected_pet_id) {
    // Clean and prepare IDs
    $selected_pet_id = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', trim((string)$selected_pet_id));
    $user_id_trimmed = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', trim((string)$user_id));
    
    // Enhanced debugging
    error_log("=== CLICKED BUTTON - Starting validation ===");
    error_log("GET pet_id: " . var_export($_GET['pet_id'], true));
    error_log("Selected pet_id: " . var_export($selected_pet_id, true));
    error_log("Session user_id: " . var_export($user_id, true));
    
    // Validate pet ownership
    $pet_check = pg_query_params(
        $conn,
        "SELECT * FROM pets WHERE LOWER(pet_id) = LOWER($1) AND LOWER(user_id) = LOWER($2)",
        [$selected_pet_id, $user_id_trimmed]
    );
    
    if (!$pet_check) {
        error_log("Query failed: " . pg_last_error($conn));
        die("Database error: " . pg_last_error($conn));
    }
    
    $valid_pet = pg_fetch_assoc($pet_check);
    
    // Debug query result
    if ($valid_pet) {
        error_log("Pet found: " . $valid_pet['name']);
        
        // CRITICAL: Verify pet has required size information
        if (empty($valid_pet['species']) || empty($valid_pet['size']) || empty($valid_pet['weight'])) {
            $pet_name = isset($valid_pet['name']) ? $valid_pet['name'] : 'This pet';
            error_log("Pet missing required info:");
            error_log("  - Species: '" . ($valid_pet['species'] ?? 'NULL') . "'");
            error_log("  - Size: '" . ($valid_pet['size'] ?? 'NULL') . "'");
            error_log("  - Weight: '" . ($valid_pet['weight'] ?? 'NULL') . "'");
            
            $_SESSION['error'] = "{$pet_name} is missing size information. Please update the pet profile first.";
            header("Location: ../pets/pet-profile.php");
            exit;
        }
        
        error_log("Pet validation PASSED");
    } else {
        error_log("No pet found with query");
        
        // Check if pet exists at all
        $pet_exists_check = pg_query_params(
            $conn,
            "SELECT pet_id, user_id, name FROM pets WHERE pet_id = $1",
            [$selected_pet_id]
        );
        
        $pet_data = pg_fetch_assoc($pet_exists_check);
        
        if ($pet_data) {
            error_log("Pet EXISTS in database:");
            error_log("  - Pet ID: '{$pet_data['pet_id']}'");
            error_log("  - Pet's User ID: '{$pet_data['user_id']}'");
            error_log("  - Session User ID: '{$user_id_trimmed}'");
            
            $_SESSION['error'] = "This pet doesn't belong to your account. Please select your own pet.";
        } else {
            error_log("Pet does NOT exist in database with pet_id: '{$selected_pet_id}'");
            $_SESSION['error'] = "Invalid pet selection. The pet ID doesn't exist.";
        }
        
        error_log("=== PET VALIDATION END ===");
        header("Location: book-appointment.php");
        exit;
    }
    
    error_log("=== PET VALIDATION END ===");

    // API call for package recommendation - INSIDE THE IF BLOCK
    $api_url = "https://pawsigcity-1.onrender.com/recommend";
    $payload = json_encode([
        "breed" => $valid_pet['breed'],
    ]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        error_log("API Error: " . $curl_error);
        $recommended_package = null;
    } elseif ($http_code !== 200) {
        error_log("API HTTP Error: " . $http_code . " Response: " . $response);
        $recommended_package = null;
    } else {
        $response_data = json_decode($response, true);
        
        if (isset($response_data['recommended_package'])) {
            $recommended_package = $response_data['recommended_package'];
            
            // Verify the package exists in database
            $package_verify = pg_query_params(
                $conn,
                "SELECT p.name FROM packages p WHERE p.name ILIKE '%' || $1 || '%' LIMIT 1",
                [$recommended_package]
            );
            
            if (!pg_fetch_assoc($package_verify)) {
                error_log("Recommended package not found in DB: " . $recommended_package);
                $recommended_package = null;
            }
        } elseif (isset($response_data['error'])) {
            error_log("API returned error: " . $response_data['error']);
            $recommended_package = null;
        }
    }

    // CRITICAL: Fetch ONLY packages matching pet's registered species, size, and weight
    $packages_result = pg_query_params($conn, "
        SELECT pp.price_id, pp.package_id, p.name, pp.species, pp.size, pp.min_weight, pp.max_weight, pp.price
        FROM package_prices pp
        JOIN packages p ON pp.package_id = p.package_id
        WHERE pp.species = $1
        AND pp.size = $2
        AND pp.min_weight <= $3
        AND pp.max_weight >= $3
        ORDER BY p.name, pp.price
    ", [$valid_pet['species'], $valid_pet['size'], $valid_pet['weight']]);

    // Check if any packages are available
    if (pg_num_rows($packages_result) === 0) {
        error_log("No packages found for: {$valid_pet['species']}, {$valid_pet['size']}, {$valid_pet['weight']}kg");
        $_SESSION['error'] = "⚠️ No packages available for a {$valid_pet['size']} {$valid_pet['species']} weighing {$valid_pet['weight']} kg. Please contact support.";
        header("Location: ../pets/pet-profile.php");
        exit;
    }
    
    error_log("Found " . pg_num_rows($packages_result) . " matching packages");
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>PAWsig City | Book</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="../homepage/images/pawsig2.png">

  <style>
    * {
      box-sizing: border-box;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    body {
      padding-top: 80px;
      background-color: #f5f7fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .header-wrapper {
      margin: 0;
      padding: 0;
    }

    .form-wrapper {
      margin-top: 80px;
      padding: 20px;
      min-height: calc(100vh - 100px);
    }

    .page-content {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0;
      background-color: transparent;
      box-shadow: none;
    }

    .content-grid {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 24px;
      align-items: start;
    }

    /* Pet Profile Card - Left Side */
    .pet-profile-card {
      background: #fff;
      border-radius: 24px;
      padding: 32px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 100px;
    }

    .pet-image-container {
      width: 100%;
      height: 280px;
      border-radius: 20px;
      overflow: hidden;
      margin-bottom: 24px;
      background: linear-gradient(135deg, #A8E6CF 0%, #87d7b7 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 24px rgba(168, 230, 207, 0.3);
    }

    .pet-image-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .pet-placeholder {
      font-size: 120px;
      color: rgba(255, 255, 255, 0.7);
    }

    .pet-info {
      text-align: center;
    }

    .pet-name {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin: 0 0 8px 0;
    }

    .pet-breed {
      font-size: 1.1rem;
      color: #7f8c8d;
      margin: 0 0 20px 0;
      font-weight: 500;
    }

    .pet-details {
      display: flex;
      gap: 16px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 2px solid #f0f0f0;
    }

    .pet-detail-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
    }

    .pet-detail-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: linear-gradient(135deg, #A8E6CF 0%, #87d7b7 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 20px;
    }

    .pet-detail-label {
      font-size: 0.75rem;
      color: #95a5a6;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .pet-detail-value {
      font-size: 1rem;
      font-weight: 600;
      color: #2c3e50;
    }

    .size-info-badge {
      display: inline-block;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
      margin-top: 12px;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* Booking Form - Right Side */
    .booking-section {
      background: #fff;
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      min-height: 600px;
    }

    h2 {
      color: #2c3e50;
      margin: 0 0 32px 0;
      font-weight: 700;
      font-size: 2rem;
    }

    .booking-form {
      display: flex;
      flex-direction: column;
      gap: 28px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    label {
      font-weight: 600;
      color: #34495e;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1rem;
    }

    label i {
      color: #A8E6CF;
      font-size: 1.1rem;
    }

    select,
    input[type="text"],
    textarea {
      width: 100%;
      padding: 14px 18px;
      font-size: 1rem;
      font-family: inherit;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      background-color: #f8f9fa;
      color: #333;
      transition: all 0.3s ease;
    }

    select:focus,
    input[type="text"]:focus,
    textarea:focus {
      border-color: #A8E6CF;
      box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.2);
      outline: none;
      background-color: #fff;
    }

    textarea {
      resize: vertical;
      min-height: 120px;
    }

    /* Calendar Styles */
    .calendar-container {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
      border: 2px solid #e0e0e0;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .calendar-header h3 {
      margin: 0;
      color: #2c3e50;
      font-size: 1.1rem;
      font-weight: 700;
    }

    .calendar-nav {
      display: flex;
      gap: 8px;
    }

    .calendar-nav button {
      background: white;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      padding: 6px 12px;
      cursor: pointer;
      font-weight: 600;
      color: #34495e;
      transition: all 0.2s ease;
      font-family: inherit;
      font-size: 0.85rem;
    }

    .calendar-nav button:hover {
      background: #A8E6CF;
      border-color: #A8E6CF;
      color: white;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 4px;
      margin-bottom: 12px;
    }

    .calendar-day-header {
      text-align: center;
      font-weight: 600;
      color: #7f8c8d;
      padding: 8px 0;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .calendar-day {
      aspect-ratio: 1;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      background: white;
      position: relative;
      padding: 4px;
      min-height: 50px;
    }

    .calendar-day:hover:not(.disabled):not(.other-month) {
      border-color: #A8E6CF;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .calendar-day.disabled {
      background: #f0f0f0;
      cursor: not-allowed;
      opacity: 0.4;
    }

    .calendar-day.other-month {
      opacity: 0.25;
      cursor: not-allowed;
      background: #fafafa;
    }

    .calendar-day.selected {
      background: linear-gradient(135deg, #A8E6CF 0%, #87d7b7 100%);
      border-color: #A8E6CF;
      color: white;
      font-weight: 700;
      box-shadow: 0 3px 10px rgba(168, 230, 207, 0.4);
    }

    .calendar-day.today {
      border-color: #3498db;
      border-width: 2px;
    }

    .day-number {
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 2px;
    }

    .day-indicator {
      font-size: 0.6rem;
      font-weight: 600;
      padding: 2px 6px;
      border-radius: 6px;
      white-space: nowrap;
    }

    .day-indicator.available {
      background: #d4edda;
      color: #155724;
    }

    .day-indicator.busy {
      background: #fff3cd;
      color: #856404;
    }

    .day-indicator.full {
      background: #f8d7da;
      color: #721c24;
    }

    /* Time Slot Selection */
    .time-slots-container {
      margin-top: 16px;
      display: none;
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .time-slots-container.show {
      display: block;
    }

    .time-slots-header {
      font-weight: 600;
      color: #34495e;
      margin-bottom: 12px;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 12px;
      background: linear-gradient(135deg, #e8fff3 0%, #d4f5e5 100%);
      border-radius: 8px;
      border-left: 3px solid #A8E6CF;
    }

    .time-slots-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 8px;
    }

    .time-slot {
      padding: 10px 12px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease;
      background: white;
      font-weight: 600;
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      font-size: 0.9rem;
    }

    .time-slot:hover:not(.disabled) {
      border-color: #A8E6CF;
      box-shadow: 0 2px 6px rgba(168, 230, 207, 0.3);
    }

    .time-slot.selected {
      background: linear-gradient(135deg, #A8E6CF 0%, #87d7b7 100%);
      border-color: #A8E6CF;
      color: white;
      box-shadow: 0 3px 10px rgba(168, 230, 207, 0.4);
    }

    .time-slot.disabled {
      background: #f0f0f0;
      cursor: not-allowed;
      opacity: 0.5;
    }

    .time-slot-badge {
      font-size: 0.65rem;
      padding: 2px 6px;
      border-radius: 6px;
      display: inline-block;
      font-weight: 700;
    }

    .time-slot-badge.full {
      background: #f8d7da;
      color: #721c24;
    }

    .time-slot-badge.available {
      background: #d4edda;
      color: #155724;
    }

    .recommendation-box {
      background: linear-gradient(135deg, #e8fff3 0%, #d4f5e5 100%);
      border-left: 5px solid #A8E6CF;
      padding: 20px;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 500;
      color: #2c3e50;
      box-shadow: 0 4px 12px rgba(168, 230, 207, 0.2);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .recommend {
      color: #16a085;
      font-weight: bold;
      font-size: 1.1rem;
    }

    .locked-notice {
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
      border-left: 5px solid #ff9800;
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      color: #e65100;
      font-weight: 500;
    }

    .locked-notice i {
      font-size: 1.5rem;
    }

    .submit-btn {
      background: linear-gradient(135deg, #A8E6CF 0%, #87d7b7 100%);
      border: none;
      padding: 16px 32px;
      border-radius: 12px;
      font-weight: 600;
      color: #252525;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1.1rem;
      margin-top: 12px;
      box-shadow: 0 4px 12px rgba(168, 230, 207, 0.4);
      font-family: inherit;
    }

    .submit-btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(168, 230, 207, 0.5);
    }

    .submit-btn:disabled {
      background: #ccc !important;
      color: #666 !important;
      cursor: not-allowed !important;
      opacity: 0.6 !important;
      transform: none !important;
      box-shadow: none !important;
    }

   /* Enhanced Alert Container */
.alert-container {
  position: fixed;
  top: 100px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  width: 90%;
  max-width: 600px;
  animation: slideDown 0.4s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.alert-success,
.alert-error,
.alert-warning {
  padding: 18px 24px;
  border-radius: 12px;
  font-weight: 500;
  margin-bottom: 16px;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  border: 2px solid;
  position: relative;
  animation: slideIn 0.3s ease;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border-color: #c3e6cb;
}

.alert-error {
  background-color: #f8d7da;
  color: #721c24;
  border-color: #f5c6cb;
}

.alert-warning {
  background-color: #fff3cd;
  color: #856404;
  border-color: #ffeaa7;
}

.alert-icon {
  font-size: 24px;
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-content {
  flex: 1;
}

.alert-title {
  font-weight: 700;
  font-size: 16px;
  margin-bottom: 4px;
}

.alert-message {
  font-size: 14px;
  line-height: 1.5;
}

.alert-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  opacity: 0.6;
  transition: opacity 0.3s ease;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: inherit;
}

.alert-close:hover {
  opacity: 1;
}
/* Missing Pet Info Warning */
.pet-info-required {
  background: linear-gradient(135deg, #fff3cd 0%, #ffe8a3 100%);
  border-left: 5px solid #ff9800;
  padding: 20px 24px;
  border-radius: 12px;
  margin-bottom: 24px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.2);
}

.pet-info-required i {
  font-size: 28px;
  color: #ff9800;
  margin-top: 2px;
}

.pet-info-required-content h4 {
  margin: 0 0 8px 0;
  color: #e65100;
  font-size: 18px;
  font-weight: 700;
}

.pet-info-required-content p {
  margin: 0 0 12px 0;
  color: #856404;
  line-height: 1.6;
}

.pet-info-required-content ul {
  margin: 8px 0;
  padding-left: 20px;
  color: #856404;
}

.pet-info-required-content ul li {
  margin-bottom: 6px;
}

.update-pet-btn {
  background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  margin-top: 8px;
}

.update-pet-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
}

/* No Packages Available Warning */
.no-packages-warning {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  border-left: 5px solid #dc3545;
  padding: 20px 24px;
  border-radius: 12px;
  margin-bottom: 24px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
}

.no-packages-warning i {
  font-size: 28px;
  color: #dc3545;
  margin-top: 2px;
}

/* No Groomers Available Warning */
.no-groomers-warning {
  background: linear-gradient(135deg, #fff3cd 0%, #ffe8a3 100%);
  border-left: 5px solid #ff9800;
  padding: 16px 20px;
  border-radius: 10px;
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  color: #856404;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.15);
}

.no-groomers-warning i {
  font-size: 20px;
  color: #ff9800;
}

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 2px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 2px solid #f5c6cb;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #3498db;
      font-weight: 600;
      margin-bottom: 24px;
      transition: all 0.3s ease;
      text-decoration: none;
      padding: 10px 16px;
      border-radius: 8px;
      background: #f0f8ff;
    }

    .back-link:hover {
      background: #3498db;
      color: white;
      transform: translateX(-4px);
    }

    /* Pet Selection Cards */
    .pets-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
      margin-top: 24px;
    }

    .pet-card {
      background: #fff;
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .pet-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    }

    .pet-card-image {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      overflow: hidden;
      margin-bottom: 16px;
      background: linear-gradient(135deg, #A8E6CF 0%, #87d7b7 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(168, 230, 207, 0.3);
    }

    .pet-card-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .pet-card-name {
      font-size: 1.4rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 4px;
    }

    .pet-card-breed {
      font-size: 1rem;
      color: #7f8c8d;
      margin-bottom: 16px;
    }

    .pet-card .btn {
      width: 100%;
      background: linear-gradient(135deg, #A8E6CF 0%, #87d7b7 100%);
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      color: #252525;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1rem;
    }

    .pet-card .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(168, 230, 207, 0.4);
    }

    select option:disabled {
      color: #999;
      background-color: #f5f5f5;
      font-style: italic;
    }

    select option:disabled:hover {
      background-color: #f5f5f5;
      cursor: not-allowed;
    }

    /* Hidden input for appointment date */
    #appointment_date_hidden {
      display: none;
    }

    /* Hamburger Menu Styles */
    .hamburger {
      display: none;
      flex-direction: column;
      justify-content: space-around;
      cursor: pointer;
      background: none;
      border: none;
      padding: 8px;
      z-index: 1001;
      width: 40px;
      height: 40px;
      position: relative;
    }

    .hamburger span {
      width: 28px;
      height: 3px;
      background-color: #2c3e50;
      transition: all 0.3s ease;
      border-radius: 3px;
      display: block;
      position: relative;
    }

    .hamburger.active span:nth-child(1) {
      transform: rotate(45deg);
      position: absolute;
      top: 50%;
      margin-top: -1.5px;
    }

    .hamburger.active span:nth-child(2) {
      opacity: 0;
      transform: scale(0);
    }

    .hamburger.active span:nth-child(3) {
      transform: rotate(-45deg);
      position: absolute;
      top: 50%;
      margin-top: -1.5px;
    }

    /* Base navbar styles */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 30px;
      position: relative;
      z-index: 100;
    }

    .nav-menu {
      display: flex;
      align-items: center;
      list-style: none;
      margin: 0;
      padding: 0;
      gap: 5px;
    }

    .nav-item {
      position: relative;
      list-style: none;
    }

    .nav-link {
      text-decoration: none;
      padding: 8px 16px;
      display: flex;
      align-items: center;
      color: #2c3e50;
      font-weight: 500;
      transition: all 0.3s ease;
      border-radius: 8px;
    }

    .nav-link:hover {
      background-color: rgba(168, 230, 207, 0.1);
      color: #16a085;
    }

    .nav-link.active {
      background-color: rgba(168, 230, 207, 0.15);
      color: #16a085;
    }

    @media (min-width: 1025px) {
  /* Dropdown container */
  .dropdown {
    position: relative;
  }

  /* Dropdown menu - hidden by default */
  .dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 220px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
    transition-delay: 0s, 0s, 0s;
    margin-top: 8px;
    padding: 8px 0;
    z-index: 1000;
    list-style: none;
    pointer-events: none;
  }

  /* Show dropdown on hover - appears immediately */
  .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto;
    transition-delay: 0s, 0s, 0s;
  }

  /* Keep dropdown visible when hovering over menu items */
  .dropdown-menu:hover {
    opacity: 1;
    visibility: visible;
  }
  
  /* Add delay when mouse leaves dropdown - waits 300ms before hiding */
  .dropdown:not(:hover) .dropdown-menu {
    transition-delay: 0.3s, 0.3s, 0.3s;
  }

  /* Dropdown menu items */
  .dropdown-menu li {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .dropdown-menu a {
    display: block;
    padding: 12px 20px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    white-space: nowrap;
    text-align: left;
  }

  .dropdown-menu a:hover {
    background: linear-gradient(90deg, rgba(168, 230, 207, 0.1) 0%, transparent 100%);
    border-left-color: #A8E6CF;
    padding-left: 24px;
    color: #16a085;
  }

  /* Profile icon styling */
  .profile-icon {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    position: relative;
  }

  /* Arrow indicator */
  .profile-icon::after {
    content: '\f078';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 0.7rem;
    margin-left: 4px;
    transition: transform 0.3s ease;
  }

  .dropdown:hover .profile-icon::after {
    transform: rotate(180deg);
  }
}

    /* Mobile Menu Styles */
    @media (max-width: 1024px) {
      .hamburger {
        display: flex !important;
      }

      .nav-menu {
        position: fixed;
        right: -100%;
        top: 0;
        flex-direction: column;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        width: 320px;
        text-align: left;
        transition: right 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
        padding: 100px 0 30px 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 999;
        align-items: stretch;
        gap: 0;
      }

      .nav-menu.active {
        right: 0;
      }

      .nav-item {
        margin: 0;
        padding: 0;
        border-bottom: 1px solid #e9ecef;
        position: relative;
      }

      .nav-link {
        font-size: 1.1rem;
        padding: 18px 30px;
        display: block;
        color: #2c3e50;
        transition: all 0.3s ease;
        font-weight: 500;
        width: 100%;
      }

      .nav-link:hover {
        background: linear-gradient(90deg, #A8E6CF 0%, transparent 100%);
        padding-left: 40px;
        color: #16a085;
      }

      .nav-link i {
        margin-right: 12px;
        font-size: 1.2rem;
        color: #A8E6CF;
      }

      .profile-icon {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
      }

      .profile-icon::after {
        content: 'Profile Menu';
        font-family: 'Segoe UI', sans-serif;
        font-size: 1rem;
      }

      .dropdown-menu {
        position: relative;
        display: none;
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        box-shadow: none;
        background-color: #f1f3f5;
        margin: 0;
        border-radius: 0;
        padding: 8px 0;
        list-style: none;
      }

      .dropdown.active .dropdown-menu {
        display: block;
      }

      .dropdown-menu li {
        margin: 0;
        border-bottom: none;
        list-style: none;
      }

      .dropdown-menu a {
        padding: 14px 30px 14px 50px;
        font-size: 0.95rem;
        color: #495057;
        display: block;
        transition: all 0.3s ease;
        position: relative;
      }

      .dropdown-menu a::before {
        content: '•';
        position: absolute;
        left: 35px;
        color: #A8E6CF;
        font-size: 1.2rem;
      }

      .dropdown-menu a:hover {
        padding-left: 55px;
        color: #16a085;
        background-color: #e9ecef;
      }

      .nav-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 998;
        opacity: 0;
        transition: opacity 0.3s ease;
      }

      .nav-overlay.active {
        display: block;
        opacity: 1;
      }
    }

    /* Hide hamburger on desktop */
    @media (min-width: 1025px) {
      .hamburger {
        display: none !important;
      }
      
      .nav-overlay {
        display: none !important;
      }
      
      .nav-menu {
        display: flex !important;
        position: static !important;
        flex-direction: row !important;
        width: auto !important;
        height: auto !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
        overflow: visible !important;
      }
      
      .nav-item {
        border-bottom: none !important;
      }
    }

    @media (max-width: 1200px) {
      .content-grid {
        grid-template-columns: 1fr;
      }

      .pet-profile-card {
        position: relative;
        top: 0;
      }
    }

    @media (max-width: 768px) {
      body {
        padding-top: 70px;
      }

      .form-wrapper {
        padding: 12px;
        margin-top: 20px;
      }

      .booking-section {
        padding: 24px;
      }

      .pet-profile-card {
        padding: 24px;
      }

      .pets-grid {
        grid-template-columns: 1fr;
      }

      .calendar-grid {
        gap: 4px;
      }

      .calendar-day {
        padding: 4px;
        min-height: 45px;
      }

      .day-number {
        font-size: 0.85rem;
      }

      .day-indicator {
        font-size: 0.55rem;
        padding: 2px 4px;
      }

      .time-slots-grid {
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 6px;
      }

      .calendar-header h3 {
        font-size: 1rem;
      }

      .calendar-nav button {
        padding: 5px 10px;
        font-size: 0.8rem;
      }

      h2 {
        font-size: 1.6rem;
      }
    }
    /* Add these styles to your book-appointment.php style section */

/* Closed date styling */
.calendar-day.closed {
  background: linear-gradient(135deg, #ffe0e0 0%, #ffcccc 100%);
  border-color: #ff6b6b;
  opacity: 0.7;
  cursor: not-allowed;
  position: relative;
}

.calendar-day.closed::before {
  content: '✖';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 2rem;
  color: #ff6b6b;
  opacity: 0.3;
  z-index: 0;
}

.calendar-day.closed .day-number {
  position: relative;
  z-index: 1;
}

.calendar-day.closed .day-indicator {
  position: relative;
  z-index: 1;
  background: #ff6b6b !important;
  color: white !important;
  font-weight: 700;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.calendar-day.closed:hover {
  transform: none;
  box-shadow: none;
  border-color: #ff6b6b;
}

/* Legend update - add to your existing legend */
.legend .legend-item.closed-legend {
  background: #ffe0e0;
  border: 2px solid #ff6b6b;
  padding: 6px 12px;
  border-radius: 8px;
}

.legend .legend-item.closed-legend i {
  color: #ff6b6b;
}
/* Floating Chat Button - Bottom Right */
.floating-chat-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 65px;
  height: 65px;
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(168, 230, 207, 0.4);
  z-index: 999;
  transition: all 0.3s ease;
  border: 3px solid #ffffff;
  animation: pulse-chat 2s infinite;
}

.floating-chat-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 12px 32px rgba(168, 230, 207, 0.6);
}

.floating-chat-btn i {
  font-size: 28px;
  color: #252525;
  animation: bounce-icon 2s ease-in-out infinite;
}

@keyframes pulse-chat {
  0%, 100% {
    box-shadow: 0 8px 24px rgba(168, 230, 207, 0.4);
  }
  50% {
    box-shadow: 0 8px 24px rgba(168, 230, 207, 0.6), 0 0 0 10px rgba(168, 230, 207, 0.1);
  }
}

@keyframes bounce-icon {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

/* Chat Modal */
.chat-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  z-index: 10000;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(5px);
}

.chat-modal.active {
  display: flex;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.chat-modal-content {
  background: #ffffff;
  border-radius: 24px;
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Chat Header */
.chat-modal-header {
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  padding: 20px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 3px solid rgba(255, 255, 255, 0.5);
}

.chat-modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: #252525;
  display: flex;
  align-items: center;
  gap: 12px;
}

.chat-modal-header h3 i {
  font-size: 24px;
  animation: float 3s ease-in-out infinite;
}

.close-chat-modal {
  background: rgba(255, 255, 255, 0.3);
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #252525;
  font-size: 20px;
}

.close-chat-modal:hover {
  background: rgba(255, 255, 255, 0.5);
  transform: rotate(90deg);
}

/* Chat Body */
.chat-modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
  background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
}

/* Messages */
.chat-messages {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 20px;
}

.message-item {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  animation: messageSlide 0.3s ease;
}

@keyframes messageSlide {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.bot-avatar {
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  color: #252525;
}

.user-avatar {
  background: linear-gradient(135deg, #252525 0%, #3a3a3a 100%);
  color: white;
}

.message-bubble {
  padding: 12px 16px;
  border-radius: 16px;
  max-width: 80%;
  font-size: 14px;
  line-height: 1.5;
  word-wrap: break-word;
}

.bot-message {
  background: #ffffff;
  color: #252525;
  border: 2px solid #A8E6CF;
  border-bottom-left-radius: 4px;
}

.user-message {
  background: linear-gradient(135deg, #252525 0%, #3a3a3a 100%);
  color: white;
  border-bottom-right-radius: 4px;
  margin-left: auto;
}

.message-item.user {
  flex-direction: row-reverse;
}

/* Welcome Message */
.welcome-message {
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
  border: 2px solid rgba(255, 255, 255, 0.5);
  position: relative;
  overflow: hidden;
}

.welcome-message::before {
  content: '🐾';
  position: absolute;
  font-size: 60px;
  right: -10px;
  bottom: -10px;
  opacity: 0.2;
}

.welcome-message h4 {
  margin: 0 0 10px 0;
  font-size: 18px;
  font-weight: 700;
  color: #252525;
}

.welcome-message p {
  margin: 0;
  font-size: 14px;
  color: #252525;
  line-height: 1.6;
}

/* Quick Questions */
.quick-questions-section {
  margin-top: 20px;
}

.questions-header {
  font-size: 14px;
  font-weight: 700;
  color: #252525;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.questions-header i {
  color: #A8E6CF;
  font-size: 16px;
}

.question-category {
  margin-bottom: 16px;
}

.category-label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.category-label i {
  color: #A8E6CF;
  font-size: 12px;
}

.question-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.question-btn {
  background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
  color: #252525;
  font-size: 13px;
  font-weight: 500;
  padding: 10px 16px;
  border-radius: 20px;
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: inherit;
}

.question-btn:hover {
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  border-color: #A8E6CF;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(168, 230, 207, 0.4);
}

.question-btn:active {
  transform: translateY(0);
}

/* Typing Indicator */
.typing-indicator {
  display: none;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #ffffff;
  border: 2px solid #A8E6CF;
  border-radius: 16px;
  border-bottom-left-radius: 4px;
  max-width: 80%;
}

.typing-indicator.active {
  display: flex;
}

.typing-dots {
  display: flex;
  gap: 4px;
}

.typing-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #A8E6CF;
  animation: typing 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) {
  animation-delay: -0.32s;
}

.typing-dots span:nth-child(2) {
  animation-delay: -0.16s;
}

@keyframes typing {
  0%, 80%, 100% {
    transform: scale(0);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Scrollbar */
.chat-modal-body::-webkit-scrollbar {
  width: 6px;
}

.chat-modal-body::-webkit-scrollbar-track {
  background: transparent;
}

.chat-modal-body::-webkit-scrollbar-thumb {
  background: #A8E6CF;
  border-radius: 3px;
}

.chat-modal-body::-webkit-scrollbar-thumb:hover {
  background: #7ed6ad;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .chat-modal-content {
    max-width: 95%;
    max-height: 85vh;
  }

  .chat-modal-header {
    padding: 16px 20px;
  }

  .chat-modal-header h3 {
    font-size: 18px;
  }

  .chat-modal-body {
    padding: 20px;
  }

  .message-bubble {
    font-size: 13px;
    max-width: 85%;
  }

  .floating-chat-btn {
    width: 60px;
    height: 60px;
    bottom: 20px;
    right: 20px;
  }

  .floating-chat-btn i {
    font-size: 26px;
  }

  .question-btn {
    font-size: 12px;
    padding: 8px 14px;
  }
}

@media (max-width: 480px) {
  .chat-modal-content {
    border-radius: 20px;
  }

  .welcome-message {
    padding: 16px;
  }

  .welcome-message h4 {
    font-size: 16px;
  }

  .message-avatar {
    width: 32px;
    height: 32px;
  }
}
  </style>
</head>
<body>
  <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/pawsig2.png" alt="Logo" class="icon" />
      </a>
      
      <!-- Hamburger Menu Button -->
      <button class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <!-- Overlay for mobile -->
      <div class="nav-overlay" id="nav-overlay"></div>

      <ul class="nav-menu" id="nav-menu">
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-info-circle"></i> About</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-concierge-bell"></i> Services</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-images"></i> Gallery</a></li>
        <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a></li>
        <li class="nav-item dropdown" id="profile-dropdown">
          <a href="#" class="nav-link profile-icon active">
            <i class="fas fa-user"></i>
          </a>
          <ul class="dropdown-menu">
            <li><a href="../pets/pet-profile.php">Pet Profiles</a></li>
            <li><a href="../pets/add-pet.php">Add Pet</a></li>
            <li><a href="../appointment/book-appointment.php">Book</a></li>
            <li><a href="../homepage/appointments.php">Appointments</a></li>
            <li><a href="../homepage/logout/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <div class="form-wrapper">
    <div class="page-content">
      
     <!-- Enhanced Notification Container -->
<?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
<div class="alert-container">
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert-success">
      <i class="fas fa-check-circle alert-icon"></i>
      <div class="alert-content">
        <div class="alert-title">Success!</div>
        <div class="alert-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
      </div>
      <button class="alert-close" onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert-error">
      <i class="fas fa-exclamation-circle alert-icon"></i>
      <div class="alert-content">
        <div class="alert-title">Error</div>
        <div class="alert-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
      </div>
      <button class="alert-close" onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
</div>

<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
  const alertContainer = document.querySelector('.alert-container');
  if (alertContainer) {
    alertContainer.style.opacity = '0';
    alertContainer.style.transform = 'translateX(-50%) translateY(-30px)';
    setTimeout(() => alertContainer.remove(), 300);
  }
}, 5000);
</script>
<?php endif; ?>
      <?php
// Check if pet is missing required information
if ($selected_pet_id && $valid_pet) {
    $missing_fields = [];
    
    if (empty($valid_pet['species'])) $missing_fields[] = 'Species';
    if (empty($valid_pet['size'])) $missing_fields[] = 'Size';
    if (empty($valid_pet['weight'])) $missing_fields[] = 'Weight';
    
    if (!empty($missing_fields)) {
        $pet_name = htmlspecialchars($valid_pet['name']);
        ?>
        <div class="pet-info-required">
          <i class="fas fa-exclamation-triangle"></i>
          <div class="pet-info-required-content">
            <h4><i class="fas fa-paw"></i> <?= $pet_name ?> Needs Profile Update</h4>
            <p>Before booking an appointment, please complete your pet's profile with the following information:</p>
            <ul>
              <?php foreach ($missing_fields as $field): ?>
                <li><strong><?= $field ?></strong> is required</li>
              <?php endforeach; ?>
            </ul>
            <p style="margin-top: 12px; font-size: 13px; color: #666;">
              <i class="fas fa-info-circle"></i> This information helps us provide the best grooming service for your pet.
            </p>
            <a href="../pets/pet-profile.php" class="update-pet-btn">
              <i class="fas fa-edit"></i> Update Pet Profile
            </a>
          </div>
        </div>
        <?php
        // Exit early to prevent showing the booking form
        echo '</div></div></body></html>';
        exit;
    }
}

// Check if no packages are available
if ($selected_pet_id && $valid_pet && isset($packages_result) && pg_num_rows($packages_result) === 0) {
    ?>
    <div class="no-packages-warning">
      <i class="fas fa-box-open"></i>
      <div>
        <h4 style="margin: 0 0 8px 0; color: #721c24; font-size: 18px; font-weight: 700;">
          <i class="fas fa-times-circle"></i> No Packages Available
        </h4>
        <p style="margin: 0 0 8px 0; color: #721c24; line-height: 1.6;">
          Unfortunately, we don't have grooming packages available for:
        </p>
        <ul style="margin: 8px 0; padding-left: 20px; color: #721c24;">
          <li><strong>Species:</strong> <?= htmlspecialchars($valid_pet['species']) ?></li>
          <li><strong>Size:</strong> <?= htmlspecialchars($valid_pet['size']) ?></li>
          <li><strong>Weight:</strong> <?= htmlspecialchars($valid_pet['weight']) ?> kg</li>
        </ul>
        <p style="margin: 12px 0 0 0; font-size: 14px; color: #721c24;">
          <i class="fas fa-phone"></i> Please contact us at <strong>0954 476 0085</strong> or message our Facebook page for assistance.
        </p>
        <a href="book-appointment.php" class="update-pet-btn" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
          <i class="fas fa-arrow-left"></i> Choose Another Pet
        </a>
      </div>
    </div>
    <?php
    echo '</div></div></body></html>';
    exit;
}
?>
      <?php if (!$selected_pet_id): ?>
        <div class="booking-section">
          <h2>Choose Your Pet</h2>
          <div class="pets-grid">
            <?php while ($pet = pg_fetch_assoc($pets_result)): ?>
              <div class="pet-card">
                <div class="pet-card-image">
                  <?php if (!empty($pet['photo_url'])): ?>
                    <img src="<?= htmlspecialchars($pet['photo_url']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
                  <?php else: ?>
                    <i class="fas fa-paw pet-placeholder"></i>
                  <?php endif; ?>
                </div>
                <div class="pet-card-name"><?= htmlspecialchars($pet['name']) ?></div>
                <div class="pet-card-breed"><?= htmlspecialchars($pet['breed']) ?></div>
                <form method="GET" action="book-appointment.php">
                  <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">
                  <button class="btn" type="submit">Book for <?= htmlspecialchars($pet['name']) ?></button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="content-grid">
          <div class="pet-profile-card">
            <div class="pet-image-container">
              <?php if (!empty($valid_pet['photo_url'])): ?>
                <img src="<?= htmlspecialchars($valid_pet['photo_url']) ?>" alt="<?= htmlspecialchars($valid_pet['name']) ?>">
              <?php else: ?>
                <i class="fas fa-paw pet-placeholder"></i>
              <?php endif; ?>
            </div>
            
            <div class="pet-info">
              <h3 class="pet-name"><?= htmlspecialchars($valid_pet['name']) ?></h3>
              <p class="pet-breed"><?= htmlspecialchars($valid_pet['breed']) ?></p>
              
              <div class="pet-details">
                <div class="pet-detail-item">
                  <div class="pet-detail-icon">
                    <i class="fas fa-birthday-cake"></i>
                  </div>
                  <div class="pet-detail-label">Age</div>
                  <div class="pet-detail-value"><?= htmlspecialchars($valid_pet['age']) ?> years</div>
                </div>
                
                <div class="pet-detail-item">
                  <div class="pet-detail-icon">
                    <i class="fas fa-venus-mars"></i>
                  </div>
                  <div class="pet-detail-label">Gender</div>
                  <div class="pet-detail-value"><?= htmlspecialchars($valid_pet['gender']) ?></div>
                </div>
                
                <div class="pet-detail-item">
                  <div class="pet-detail-icon">
                    <i class="fas fa-palette"></i>
                  </div>
                  <div class="pet-detail-label">Color</div>
                  <div class="pet-detail-value"><?= htmlspecialchars($valid_pet['color']) ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="booking-section">
            <a href="book-appointment.php" class="back-link">
              <i class="fas fa-arrow-left"></i> Choose Another Pet
            </a>
            
            <h2>Book Grooming Appointment</h2>

            <form method="POST" action="appointment-handler.php" class="booking-form" id="bookingForm">
              <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">
              <input type="hidden" name="appointment_date" id="appointment_date_hidden">

              <?php if ($recommended_package): ?>
                <input type="hidden" name="recommended_package" value="<?= htmlspecialchars($recommended_package) ?>">
                <div class="recommendation-box">
                  <i class="fas fa-star"></i> 
                  <span>Recommended Package for <strong><?= htmlspecialchars($valid_pet['name']) ?></strong>:
                  <span class="recommend"><?= htmlspecialchars($recommended_package) ?></span></span>
                </div>
              <?php endif; ?>
              
              <!-- Service/Package Selection -->
              <div class="form-group">
                <label for="package_id">
                  <i class="fas fa-box"></i> Select Service Package
                </label>
                <select name="package_id" id="package_id" required>
                  <option value="">-- Select Package --</option>
                  <?php while ($package = pg_fetch_assoc($packages_result)): ?>
                    <option 
                      value="<?= $package['package_id'] ?>" 
                      data-price="<?= $package['price_id'] ?>"
                      <?= ($recommended_package && stripos($package['name'], $recommended_package) !== false) ? 'selected' : '' ?>
                    >
                      <?= htmlspecialchars($package['name']) ?> 
                      (<?= htmlspecialchars($package['species']) ?> - <?= htmlspecialchars($package['size']) ?>) 
                      - ₱ <?= number_format($package['price'], 2) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Groomer Selection -->
              <div class="form-group">
                <label for="groomer_id">
                   <i class="fas fa-user-md"></i> Select Groomer
                </label>
                <select name="groomer_id" id="groomer_id" required>
                  <option value="">-- Select Groomer --</option>
                  <?php foreach ($groomers_array as $groomer): ?>
                    <?php 
                    $is_active = ($groomer['is_active'] === 't' || $groomer['is_active'] === true || $groomer['is_active'] == 1);
                    ?>
                    <option 
                      value="<?= $groomer['groomer_id'] ?>"
                      <?= !$is_active ? 'disabled' : '' ?>
                    >
                      <?= htmlspecialchars($groomer['groomer_name']) ?>
                      <?= !$is_active ? ' (Offline)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                
                <?php 
                $has_active_groomer = false;
                foreach ($groomers_array as $groomer) {
                  if ($groomer['is_active'] === 't') {
                    $has_active_groomer = true;
                    break;
                  }
                }
                ?>
                
                <?php if (!$has_active_groomer && count($groomers_array) > 0): ?>
                  <div class="no-groomers-warning">
                    <i class="fas fa-user-times"></i>
                    <div>
                      <strong>All Groomers Offline</strong><br>
                      <span style="font-size: 13px;">All our groomers are currently unavailable. Please try again later or contact us at <strong>0954 476 0085</strong>.</span>
                    </div>
                  </div>
                <?php elseif (count($groomers_array) === 0): ?>
                  <div style="margin-top: 10px; padding: 12px; background: #f8d7da; border-left: 4px solid #dc3545; border-radius: 8px; color: #721c24;">
                    <i class="fas fa-exclamation-triangle"></i>
                    No groomers are currently available. Please try again later or contact support.
                  </div>
                <?php endif; ?>
              </div>

              <!-- Calendar for Date Selection -->
              <div class="form-group">
                <label>
                  <i class="fas fa-calendar-alt"></i> Select Appointment Date & Time
                </label>
                
                <div class="calendar-container">
                  <div class="calendar-header">
                    <h3 id="currentMonth"></h3>
                    <div class="calendar-nav">
                      <button type="button" id="prevMonth"><i class="fas fa-chevron-left"></i> Prev</button>
                      <button type="button" id="nextMonth">Next <i class="fas fa-chevron-right"></i></button>
                    </div>
                  </div>
                  
                  <div class="calendar-grid" id="calendarGrid">
                    <!-- Calendar will be generated by JavaScript -->
                  </div>
                </div>

                <!-- Time Slots Selection -->
                <div class="time-slots-container" id="timeSlotsContainer">
                  <div class="time-slots-header">
                    <i class="fas fa-clock"></i>
                    <span>Select Time Slot for <span id="selectedDateDisplay"></span></span>
                  </div>
                  <div class="time-slots-grid" id="timeSlotsGrid">
                    <!-- Time slots will be generated by JavaScript -->
                  </div>
                </div>
              </div>

              <!-- Special Instructions -->
              <div class="form-group">
                <label for="notes">
                  <i class="fas fa-sticky-note"></i> Special Instructions (optional)
                </label>
                <textarea name="notes" id="notes" rows="3" placeholder="Any special care instructions for your pet..."></textarea>
              </div>

              <?php
              $has_active_groomer_for_submit = false;
              if (count($groomers_array) > 0) {
                foreach ($groomers_array as $g) {
                  if ($g['is_active'] === 't' || $g['is_active'] === true || $g['is_active'] == 1) {
                    $has_active_groomer_for_submit = true;
                    break;
                  }
                }
              }
              ?>
              <button type="submit" class="submit-btn" id="submitBtn" <?= !$has_active_groomer_for_submit ? 'disabled' : '' ?>>
                <i class="fas fa-calendar-times"></i> Please Select Date & Time
              </button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Appointment data from PHP
    const appointmentStats = <?= json_encode($appointment_stats) ?>;
    const appointmentCounts = <?= json_encode($appointment_counts) ?>;
    const MAX_APPOINTMENTS_PER_SLOT = 5;

    // Calendar variables
    let currentDate = new Date();
    let selectedDate = null;
    let selectedTime = null;
    let closedDates = [];

    // Helper function to format date without timezone issues
    function formatDateString(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    // Load closed dates on page initialization
    async function loadClosedDates() {
      try {
        const response = await fetch('get_closed_dates.php');
        const data = await response.json();
        
        if (data.success) {
          closedDates = data.dates.map(cd => cd.closed_date);
          console.log('Loaded closed dates:', closedDates);
        } else {
          console.error('Error loading closed dates:', data.message);
        }
      } catch (error) {
        console.error('Failed to load closed dates:', error);
      }
    }

    // Initialize calendar on page load
    document.addEventListener('DOMContentLoaded', async function() {
      console.log('Page loaded, fetching data...');
      
      // Load closed dates first
      await loadClosedDates();
      
      // Then generate calendar
      generateCalendar();
      setupEventListeners();
      setupMobileMenu();
      
      console.log('Dashboard initialized');
    });

    function setupEventListeners() {
      document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        generateCalendar();
      });

      document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        generateCalendar();
      });

      // Enhanced form submission validation
document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
  // Package validation
  const packageSelect = document.getElementById('package_id');
  if (packageSelect && !packageSelect.value) {
    e.preventDefault();
    showValidationError('Please select a grooming package', 'package_id');
    return false;
  }
  
  // Groomer validation
  const groomerSelect = document.getElementById('groomer_id');
  if (groomerSelect) {
    if (!groomerSelect.value) {
      e.preventDefault();
      showValidationError('Please select a groomer', 'groomer_id');
      return false;
    }
    
    const selectedOption = groomerSelect.options[groomerSelect.selectedIndex];
    if (selectedOption && selectedOption.disabled) {
      e.preventDefault();
      showValidationError('The selected groomer is currently offline. Please choose another groomer.', 'groomer_id');
      return false;
    }
  }
  
  // Date and time validation
  const appointmentDate = document.getElementById('appointment_date_hidden');
  if (!appointmentDate || !appointmentDate.value) {
    e.preventDefault();
    showValidationError('Please select a date and time for your appointment', 'calendarGrid');
    return false;
  }
});

// Validation error display function
function showValidationError(message, fieldId) {
  // Create alert if it doesn't exist
  let alertContainer = document.querySelector('.alert-container');
  if (!alertContainer) {
    alertContainer = document.createElement('div');
    alertContainer.className = 'alert-container';
    document.body.appendChild(alertContainer);
  }
  
  // Clear existing alerts
  alertContainer.innerHTML = '';
  
  // Create new alert
  const alert = document.createElement('div');
  alert.className = 'alert-warning';
  alert.innerHTML = `
    <i class="fas fa-exclamation-triangle alert-icon"></i>
    <div class="alert-content">
      <div class="alert-title">Validation Error</div>
      <div class="alert-message">${message}</div>
    </div>
    <button class="alert-close" onclick="this.parentElement.remove()">
      <i class="fas fa-times"></i>
    </button>
  `;
  
  alertContainer.appendChild(alert);
  
  // Scroll to the field
  const field = document.getElementById(fieldId);
  if (field) {
    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Add highlight effect
    if (field.tagName === 'SELECT' || field.tagName === 'INPUT') {
      field.style.border = '2px solid #ff9800';
      field.focus();
      setTimeout(() => {
        field.style.border = '';
      }, 2000);
    }
  }
  
  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-30px)';
    setTimeout(() => alert.remove(), 300);
  }, 5000);
}

// Real-time validation feedback
document.getElementById('package_id')?.addEventListener('change', function() {
  if (this.value) {
    this.style.borderColor = '#A8E6CF';
    setTimeout(() => { this.style.borderColor = ''; }, 1000);
  }
});

document.getElementById('groomer_id')?.addEventListener('change', function() {
  const selectedOption = this.options[this.selectedIndex];
  if (this.value && !selectedOption.disabled) {
    this.style.borderColor = '#A8E6CF';
    setTimeout(() => { this.style.borderColor = ''; }, 1000);
  } else if (selectedOption.disabled) {
    this.style.borderColor = '#ff9800';
    showValidationError('This groomer is currently offline. Please select an available groomer.', 'groomer_id');
  }
});

      // Update submit button when package or groomer changes
      document.getElementById('package_id')?.addEventListener('change', updateSubmitButton);
      document.getElementById('groomer_id')?.addEventListener('change', updateSubmitButton);
    }

    function setupMobileMenu() {
      const hamburger = document.getElementById('hamburger');
      const navMenu = document.getElementById('nav-menu');
      const navOverlay = document.getElementById('nav-overlay');
      const profileDropdown = document.getElementById('profile-dropdown');

      if (hamburger) {
        hamburger.addEventListener('click', function() {
          hamburger.classList.toggle('active');
          navMenu.classList.toggle('active');
          navOverlay.classList.toggle('active');
          document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
        });
      }

      if (navOverlay) {
        navOverlay.addEventListener('click', function() {
          hamburger.classList.remove('active');
          navMenu.classList.remove('active');
          navOverlay.classList.remove('active');
          profileDropdown.classList.remove('active');
          document.body.style.overflow = '';
        });
      }

      document.querySelectorAll('.nav-link:not(.profile-icon)').forEach(link => {
        link.addEventListener('click', function() {
          if (window.innerWidth <= 1024) {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            navOverlay.classList.remove('active');
            document.body.style.overflow = '';
          }
        });
      });

      if (profileDropdown) {
        profileDropdown.addEventListener('click', function(e) {
          if (window.innerWidth <= 1024) {
            if (e.target.closest('.profile-icon')) {
              e.preventDefault();
              this.classList.toggle('active');
            }
          }
        });
      }

      document.querySelectorAll('.dropdown-menu a').forEach(link => {
        link.addEventListener('click', function() {
          if (window.innerWidth <= 1024) {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            navOverlay.classList.remove('active');
            profileDropdown.classList.remove('active');
            document.body.style.overflow = '';
          }
        });
      });

      window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
          hamburger.classList.remove('active');
          navMenu.classList.remove('active');
          navOverlay.classList.remove('active');
          profileDropdown.classList.remove('active');
          document.body.style.overflow = '';
        }
      });
    }

    function generateCalendar() {
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();
      
      // Update month display
      const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];
      document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
      
      const grid = document.getElementById('calendarGrid');
      grid.innerHTML = '';
      
      // Add day headers (Sunday to Saturday)
      const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      days.forEach(day => {
        const header = document.createElement('div');
        header.className = 'calendar-day-header';
        header.textContent = day;
        grid.appendChild(header);
      });
      
      // Get first day of month (0 = Sunday, 6 = Saturday)
      const firstDay = new Date(year, month, 1).getDay();
      
      // Get number of days in current month
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      
      // Get number of days in previous month
      const prevMonth = month === 0 ? 11 : month - 1;
      const prevYear = month === 0 ? year - 1 : year;
      const daysInPrevMonth = new Date(prevYear, prevMonth + 1, 0).getDate();
      
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      // Add previous month's trailing days
      for (let i = firstDay - 1; i >= 0; i--) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day other-month';
        const dayNum = document.createElement('div');
        dayNum.className = 'day-number';
        dayNum.textContent = daysInPrevMonth - i;
        emptyDay.appendChild(dayNum);
        grid.appendChild(emptyDay);
      }
      
      // Add current month's days
      for (let day = 1; day <= daysInMonth; day++) {
        const dayDate = new Date(year, month, day);
        dayDate.setHours(0, 0, 0, 0);
        
        // Format date without timezone conversion (FIXED)
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        // Check if date is closed
        const isClosed = closedDates.includes(dateStr);
        
        // Check if date is in the past
        if (dayDate < today) {
          dayElement.classList.add('disabled');
        } else if (isClosed) {
          // Mark as closed (disabled with special styling)
          dayElement.classList.add('disabled');
          dayElement.classList.add('closed');
          dayElement.title = 'This date is closed for bookings';
        }
        
        // Check if it's today
        if (dayDate.getTime() === today.getTime() && !isClosed) {
          dayElement.classList.add('today');
        }
        
        // Check if this date is selected
        if (selectedDate && dayDate.getTime() === selectedDate.getTime() && !isClosed) {
          dayElement.classList.add('selected');
        }
        
        // Day number
        const dayNumber = document.createElement('div');
        dayNumber.className = 'day-number';
        dayNumber.textContent = day;
        dayElement.appendChild(dayNumber);
        
        if (isClosed) {
          // Add closed indicator
          const closedBadge = document.createElement('div');
          closedBadge.className = 'day-indicator';
          closedBadge.style.background = '#ff6b6b';
          closedBadge.style.color = 'white';
          closedBadge.textContent = 'CLOSED';
          dayElement.appendChild(closedBadge);
        } else {
          // Get appointment count for this date
          const stats = appointmentStats[dateStr];
          
          if (stats && dayDate >= today) {
            const indicator = document.createElement('div');
            indicator.className = 'day-indicator';
            
            if (stats.total <= 5) {
              indicator.classList.add('available');
              indicator.textContent = `${stats.total}`;
            } else if (stats.total <= 10) {
              indicator.classList.add('busy');
              indicator.textContent = `${stats.total}`;
            } else {
              indicator.classList.add('full');
              indicator.textContent = `${stats.total}`;
            }
            
            dayElement.appendChild(indicator);
          } else if (dayDate >= today) {
            const indicator = document.createElement('div');
            indicator.className = 'day-indicator available';
            indicator.textContent = '0';
            dayElement.appendChild(indicator);
          }
        }
        
        // Add click handler only if not disabled or closed
        if (!dayElement.classList.contains('disabled') && !isClosed) {
          dayElement.addEventListener('click', () => selectDate(dayDate, dayElement));
        }
        
        grid.appendChild(dayElement);
      }
      
      // Calculate how many cells we've filled so far
      const totalCellsFilled = firstDay + daysInMonth;
      
      // Calculate remaining cells needed to complete 6 rows (42 cells total)
      const totalCellsNeeded = 42;
      const remainingCells = totalCellsNeeded - totalCellsFilled;
      
      // Add next month's days to fill remaining cells
      for (let day = 1; day <= remainingCells; day++) {
        const nextDay = document.createElement('div');
        nextDay.className = 'calendar-day other-month';
        const dayNum = document.createElement('div');
        dayNum.className = 'day-number';
        dayNum.textContent = day;
        nextDay.appendChild(dayNum);
        grid.appendChild(nextDay);
      }
    }

    function selectDate(date, element) {
      selectedDate = date;
      selectedTime = null;
      
      // Remove previous selection
      document.querySelectorAll('.calendar-day.selected').forEach(el => {
        el.classList.remove('selected');
      });
      
      // Add selection to clicked element
      element.classList.add('selected');
      
      // Show time slots
      showTimeSlots(date);
      
      // Update submit button
      updateSubmitButton();
    }

    function showTimeSlots(date) {
      const container = document.getElementById('timeSlotsContainer');
      const grid = document.getElementById('timeSlotsGrid');
      const dateDisplay = document.getElementById('selectedDateDisplay');
      
      container.classList.add('show');
      
      // Format date for display
      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      dateDisplay.textContent = date.toLocaleDateString('en-US', options);
      
      // Clear previous time slots
      grid.innerHTML = '';
      
      // Format date without timezone conversion (FIXED)
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const dateStr = `${year}-${month}-${day}`;
      const hourCounts = appointmentCounts[dateStr] || {};
      
      // Generate time slots from 9 AM to 6 PM (30-minute intervals)
      for (let hour = 9; hour <= 18; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
          // Skip 6:30 PM and later
          if (hour === 18 && minute > 0) continue;
          
          const timeSlot = document.createElement('div');
          timeSlot.className = 'time-slot';
          
          const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
          const displayTime = formatTime(hour, minute);
          
          const timeDiv = document.createElement('div');
          timeDiv.textContent = displayTime;
          timeSlot.appendChild(timeDiv);
          
          // Check appointment count for this hour
          const count = hourCounts[hour] || 0;
          
          if (count >= MAX_APPOINTMENTS_PER_SLOT) {
            timeSlot.classList.add('disabled');
            const badge = document.createElement('div');
            badge.className = 'time-slot-badge full';
            badge.textContent = 'Full';
            timeSlot.appendChild(badge);
          } else {
            const badge = document.createElement('div');
            badge.className = 'time-slot-badge available';
            badge.textContent = `${MAX_APPOINTMENTS_PER_SLOT - count} left`;
            timeSlot.appendChild(badge);
            
            // Add click handler
            timeSlot.addEventListener('click', () => selectTimeSlot(date, timeString, timeSlot));
          }
          
          grid.appendChild(timeSlot);
        }
      }
      
      // Scroll to time slots smoothly
      setTimeout(() => {
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }, 100);
    }

    function selectTimeSlot(date, time, element) {
      selectedTime = time;
      
      // Remove previous selection
      document.querySelectorAll('.time-slot.selected').forEach(el => {
        el.classList.remove('selected');
      });
      
      // Add selection to clicked element
      element.classList.add('selected');
      
      // Create full datetime string without timezone conversion (FIXED)
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const dateStr = `${year}-${month}-${day}`;
      const fullDateTime = `${dateStr} ${time}:00`;
      
      // Update hidden input
      document.getElementById('appointment_date_hidden').value = fullDateTime;
      
      // Enable submit button
      updateSubmitButton();
    }

    function formatTime(hour, minute) {
      const period = hour >= 12 ? 'PM' : 'AM';
      const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
      return `${displayHour}:${minute.toString().padStart(2, '0')} ${period}`;
    }

    function updateSubmitButton() {
      const submitBtn = document.getElementById('submitBtn');
      const appointmentDate = document.getElementById('appointment_date_hidden').value;
      const packageSelect = document.getElementById('package_id').value;
      const groomerSelect = document.getElementById('groomer_id').value;
      
      if (appointmentDate && packageSelect && groomerSelect) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Appointment';
      } else {
        submitBtn.disabled = true;
        if (!packageSelect) {
          submitBtn.innerHTML = '<i class="fas fa-box-open"></i> Please Select Package';
        } else if (!groomerSelect) {
          submitBtn.innerHTML = '<i class="fas fa-user-times"></i> Please Select Groomer';
        } else if (!appointmentDate) {
          submitBtn.innerHTML = '<i class="fas fa-calendar-times"></i> Please Select Date & Time';
        }
      }
    }
</script>
<!-- Add this HTML before the closing </body> tag in main.php -->

<!-- Floating Chat Button -->
<div class="floating-chat-btn" onclick="toggleChatModal()">
  <i class="fas fa-comments"></i>
</div>

<!-- Chat Modal -->
<div class="chat-modal" id="chatModal" onclick="closeChatModalOnOverlay(event)">
  <div class="chat-modal-content" onclick="event.stopPropagation()">
    <!-- Header -->
    <div class="chat-modal-header">
      <h3><i class="fas fa-paw"></i> HelpPAWL</h3>
      <button class="close-chat-modal" onclick="toggleChatModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Body -->
    <div class="chat-modal-body" id="chatModalBody">
      <!-- Welcome Message -->
      <div class="welcome-message">
        <h4>👋 Welcome to PAWsig City!</h4>
        <p>I'm HelpPAWL, your friendly assistant. Click any question below to get instant answers!</p>
      </div>

      <!-- Chat Messages -->
      <div class="chat-messages" id="chatMessages">
        <!-- Messages will be added here -->
      </div>

      <!-- Typing Indicator -->
      <div class="typing-indicator" id="typingIndicator">
        <div class="message-avatar bot-avatar">
          <i class="fas fa-paw"></i>
        </div>
        <div>
          <div style="font-size: 11px; color: #9ca3af; font-weight: 500; margin-bottom: 4px;">Assistant is typing...</div>
          <div class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
      </div>

      <!-- Quick Questions -->
      <div class="quick-questions-section">
        <div class="questions-header">
          <i class="fas fa-magic"></i>
          Quick Questions
        </div>


        <div class="question-category">
          <div class="category-label">
            <i class="fas fa-map-marker-alt"></i>
            Location & Contact
          </div>
          <div class="question-buttons">
            <button class="question-btn" onclick="sendQuickQuestion('where are you located')">Where are you located?</button>
            <button class="question-btn" onclick="sendQuickQuestion('what are your contact')">Contact info?</button>
            <button class="question-btn" onclick="sendQuickQuestion('when are you open')">When are you open?</button>
          </div>
        </div>

        <div class="question-category">
          <div class="category-label">
            <i class="fas fa-calendar-alt"></i>
            Booking & Appointments
          </div>
          <div class="question-buttons">
            <button class="question-btn" onclick="sendQuickQuestion('how can i book an appointment')">How to book?</button>
            <button class="question-btn" onclick="sendQuickQuestion('do you accept walk-ins')">Walk-ins accepted?</button>
          </div>
        </div>

        <div class="question-category">
          <div class="category-label">
            <i class="fas fa-cut"></i>
            Services & Pricing
          </div>
          <div class="question-buttons">
            <button class="question-btn" onclick="sendQuickQuestion('what services do you offer')">All services?</button>
            <button class="question-btn" onclick="sendQuickQuestion('do you offer grooming services')">Grooming services?</button>
            <button class="question-btn" onclick="sendQuickQuestion('how much is grooming')">Grooming cost?</button>
          </div>
        </div>

        <div class="question-category">
          <div class="category-label">
            <i class="fas fa-credit-card"></i>
            Payment
          </div>
          <div class="question-buttons">
            <button class="question-btn" onclick="sendQuickQuestion('what payment methods do you accept')">Payment methods?</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
// Q&A Database
const qaDatabase = {
  "hi": "Hello there! 👋",
  "hello": "Hi! How can I assist you today? 😊",
  "where are you located": "Hello! PAWsig City is located at 2F Hampton Gardens Arcade, C. Raymundo, Maybunga, Pasig, Philippines. 📍",
  "what are your contact": "You can message us on our Facebook page or send a message at 0954 476 0085. 📱",
  "when are you open": "We're open daily from 9:00 AM to 8:00 PM, Monday to Sunday. 🕐",
  "what is your name": "Hi! I'm HelpPAWL, your friendly assistant at PAWsig City. 🐾",
  "how can i book an appointment": "You can book an appointment online through our website or contact us directly via call, text, and Facebook messenger. 📅",
  "do you offer grooming services": "Yes! We offer pet grooming services including Full Grooming, Bath and Dry, and Spa Bath. ✨",
  "how much is grooming": "Grooming prices start at ₱499 depending on the size and breed of your pet. 💰",
  "do you accept walk-ins": "We highly recommend appointments, but we do accept walk-ins when available. 🚶‍♂️",
  "what services do you offer": "We offer Full Grooming, Bath and Dry, and Spa Bath. 🛁",
  "what payment methods do you accept": "We accept cash and GCash for walk-ins. 💳",
  "thank you": "You're welcome! Let me know if there's anything else I can help with. 😊",
  "bye": "Goodbye! Hope to see you and your pet soon! 🐾"
};

// Toggle Chat Modal
function toggleChatModal() {
  const modal = document.getElementById('chatModal');
  modal.classList.toggle('active');
  
  if (modal.classList.contains('active')) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
}

// Close modal when clicking overlay
function closeChatModalOnOverlay(event) {
  if (event.target.id === 'chatModal') {
    toggleChatModal();
  }
}

// Get bot response
function getResponse(userMessage) {
  const normalizedMessage = userMessage.toLowerCase().trim();
  
  if (qaDatabase[normalizedMessage]) {
    return qaDatabase[normalizedMessage];
  }
  
  for (const [question, answer] of Object.entries(qaDatabase)) {
    if (normalizedMessage.includes(question) || question.includes(normalizedMessage)) {
      return answer;
    }
  }
  
  return "I'm sorry, I didn't quite understand that. 🤔 Try clicking one of the quick questions below!";
}

// Send Quick Question
function sendQuickQuestion(question) {
  const chatMessages = document.getElementById('chatMessages');
  const typingIndicator = document.getElementById('typingIndicator');
  const chatBody = document.getElementById('chatModalBody');
  
  // Add user message
  const userMessageDiv = document.createElement('div');
  userMessageDiv.className = 'message-item user';
  userMessageDiv.innerHTML = `
    <div class="message-avatar user-avatar">
      <i class="fas fa-user"></i>
    </div>
    <div class="message-bubble user-message">${escapeHtml(question)}</div>
  `;
  chatMessages.appendChild(userMessageDiv);
  
  // Scroll to bottom
  chatBody.scrollTop = chatBody.scrollHeight;
  
  // Show typing indicator
  typingIndicator.classList.add('active');
  chatBody.scrollTop = chatBody.scrollHeight;
  
  // Get bot response
  const botResponse = getResponse(question);
  
  // Simulate typing delay
  setTimeout(() => {
    typingIndicator.classList.remove('active');
    
    const botMessageDiv = document.createElement('div');
    botMessageDiv.className = 'message-item';
    botMessageDiv.innerHTML = `
      <div class="message-avatar bot-avatar">
        <i class="fas fa-paw"></i>
      </div>
      <div class="message-bubble bot-message">${botResponse}</div>
    `;
    chatMessages.appendChild(botMessageDiv);
    
    // Scroll to bottom
    chatBody.scrollTop = chatBody.scrollHeight;
  }, Math.random() * 800 + 600);
}

// Escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const modal = document.getElementById('chatModal');
    if (modal.classList.contains('active')) {
      toggleChatModal();
    }
  }
});
</script>
</body>
</html>