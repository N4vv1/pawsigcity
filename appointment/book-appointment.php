<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
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

    // ✅ CRITICAL: Verify pet has required size information
    if (empty($valid_pet['species']) || empty($valid_pet['size']) || empty($valid_pet['weight'])) {
        $_SESSION['error'] = "⚠️ Pet '{$valid_pet['name']}' is missing size information. Please update the pet profile first.";
        header("Location: ../pets/pet-profile.php");
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

    if ($curl_error) {
        error_log("API Error: " . $curl_error);
        $_SESSION['info'] = "ℹ️ Recommendation service unavailable. Showing all packages for your pet.";
        $recommended_package = null;
    } elseif ($http_code !== 200) {
        error_log("API HTTP Error: " . $http_code . " Response: " . $response);
        $_SESSION['info'] = "ℹ️ Recommendation service unavailable. Showing all packages for your pet.";
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
                $_SESSION['info'] = "ℹ️ Recommended package not available. Showing all packages for your pet.";
                $recommended_package = null;
            }
        } elseif (isset($response_data['error'])) {
            $_SESSION['info'] = "ℹ️ " . htmlspecialchars($response_data['error']);
            $recommended_package = null;
        }
    }

    // ✅ CRITICAL: Fetch ONLY packages matching pet's registered species, size, and weight
    $packages_result = pg_query_params($conn, "
        SELECT pp.price_id, p.name, pp.species, pp.size, pp.min_weight, pp.max_weight, pp.price
        FROM package_prices pp
        JOIN packages p ON pp.package_id = p.package_id
        WHERE pp.species = $1 
        AND pp.size = $2
        AND pp.min_weight <= $3 
        AND pp.max_weight >= $3
        ORDER BY p.name, pp.price
    ", [$valid_pet['species'], $valid_pet['size'], $valid_pet['weight']]);

    // ✅ Check if any packages are available
    if (pg_num_rows($packages_result) === 0) {
        $_SESSION['error'] = "⚠️ No packages available for a {$valid_pet['size']} {$valid_pet['species']} weighing {$valid_pet['weight']} kg. Please contact support.";
        header("Location: ../pets/pet-profile.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>PAWsig City | Book</title>
  <link rel="stylesheet" href="../homepage/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="../homepage/images/pawsig.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

 <style>
  * {
    box-sizing: border-box;
  }

  
  /* Add these styles to fix the navbar at the top */
  header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Optional: adds subtle shadow */
  }

  /* Adjust body padding to prevent content from hiding under navbar */
  body {
    padding-top: 80px; /* Adjust this value based on your navbar height */
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

    /* Size Info Badge */
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

  .recommendation-box {
    background: linear-gradient(135deg, #e8fff3 0%, #d4f5e5 100%);
    border-left: 5px solid #A8E6CF;
    padding: 20px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 500;
    color: #2c3e50;
    box-shadow: 0 4px 12px rgba(168, 230, 207, 0.2);
  }

  .recommend {
    color: #16a085;
    font-weight: bold;
    font-size: 1.1rem;
  }

    /* Locked Package Notice */
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
  }

  .submit-btn:hover {
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

  .alert-success,
  .alert-error {
    padding: 16px 20px;
    border-radius: 12px;
    font-weight: 500;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
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

  .availability-indicator {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #ccc;
    color: white;
    font-size: 0.75rem;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
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

  .legend-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin-top: 12px;
    border: 2px solid #e0e0e0;
  }

  .legend-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #34495e;
    margin-bottom: 12px;
  }

  .legend-items {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
  }

  .legend-color {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #ccc;
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
    .form-wrapper {
      padding: 12px;
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
  }

/* Burger Menu Styles */
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

/* Hamburger animation when active */
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

/* Mobile Menu Styles */
@media (max-width: 1024px) {
  .hamburger {
    display: flex;
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
  }

  .nav-menu.active {
    right: 0;
  }

  .nav-item {
    margin: 0;
    padding: 0;
    border-bottom: 1px solid #e9ecef;
  }

  .nav-link {
    font-size: 1.1rem;
    padding: 18px 30px;
    display: block;
    color: #2c3e50;
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
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
  }

  .profile-icon::after {
    content: 'Profile Menu';
    font-size: 1rem;
  }

  /* Dropdown adjustments for mobile */
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
  }

  .dropdown.active .dropdown-menu {
    display: block;
  }

  .dropdown-menu li {
    margin: 0;
    border-bottom: none;
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
    background-color: #e9ecef;
    padding-left: 55px;
    color: #16a085;
  }

  /* Overlay for mobile menu */
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
@media (min-width: 1025px) {
  .dropdown {
    position: relative;
  }

  .dropdown-menu {
    position: absolute;
    top: calc(100% + 5px); /* Position it below with small gap */
    right: 0;
    background: white;
    min-width: 220px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    padding: 8px 0;
    z-index: 1000;
    pointer-events: none; /* Prevents menu from blocking hover */
  }

  /* Show dropdown on hover */
  .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto; /* Enable interactions when visible */
  }

  /* Keep dropdown visible when hovering over it */
  .dropdown-menu:hover {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }

  .dropdown-menu li {
    margin: 0;
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
  }

  .dropdown-menu a:hover {
    background: linear-gradient(90deg, rgba(168, 230, 207, 0.1) 0%, transparent 100%);
    border-left-color: #A8E6CF;
    padding-left: 24px;
    color: #16a085;
  }

  /* Arrow indicator for dropdown */
  .profile-icon::after {
    content: '\f078'; /* FontAwesome down arrow */
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 0.7rem;
    margin-left: 6px;
    transition: transform 0.3s ease;
  }

  .dropdown:hover .profile-icon::after {
    transform: rotate(180deg);
  }

  /* Make sure profile icon doesn't block dropdown */
  .profile-icon {
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
  }
}

/* Mobile Dropdown Styles - Keep as click-based */
@media (max-width: 1024px) {
  .hamburger {
    display: flex;
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
  }

  .nav-menu.active {
    right: 0;
  }

  .nav-item {
    margin: 0;
    padding: 0;
    border-bottom: 1px solid #e9ecef;
  }

  .nav-link {
    font-size: 1.1rem;
    padding: 18px 30px;
    display: block;
    color: #2c3e50;
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
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

  /* Base navbar styles */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 30px;
  position: relative;
  z-index: 100;
}

/* Desktop nav menu - visible by default */
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

/* Hide hamburger by default (desktop) */
.hamburger {
  display: none;
}

/* ========================================
   DESKTOP DROPDOWN (Hover-based)
   ======================================== */
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
    transition: all 0.3s ease;
    margin-top: 8px;
    padding: 8px 0;
    z-index: 1000;
    list-style: none;
    pointer-events: none;
  }

  /* Show dropdown on hover */
  .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto;
  }

  /* Keep dropdown visible when hovering over menu items */
  .dropdown-menu:hover {
    opacity: 1;
    visibility: visible;
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

/* ========================================
   MOBILE STYLES (Click-based)
   ======================================== */
@media (max-width: 1024px) {
  /* Show hamburger on mobile */
  .hamburger {
    display: flex !important;
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

  /* Mobile dropdown */
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
  
  /* Ensure nav menu is visible on desktop */
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
}
</style>

</head>
<body>
  <header>
  <nav class="navbar section-content">
    <a href="#" class="navbar-logo">
      <img src="../homepage/images/pawsig.png" alt="Logo" class="icon" />
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
          <li><a href="../ai/templates/index.html">Help Center</a></li>
          <li><a href="../homepage/logout/logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </nav>
</header>


  <div class="form-wrapper">
    <div class="page-content">
      
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success">
          <i class="fas fa-check-circle"></i>
          <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

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
          <!-- Pet Profile Card - Left Side -->
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

          <!-- Booking Form - Right Side -->
          <div class="booking-section">
            <a href="book-appointment.php" class="back-link">
              <i class="fas fa-arrow-left"></i> Choose Another Pet
            </a>
            
            <h2>Book Grooming Appointment</h2>

            <form method="POST" action="appointment-handler.php" class="booking-form">
              <input type="hidden" name="pet_id" value="<?= htmlspecialchars($selected_pet_id) ?>">

              <?php if ($recommended_package): ?>
                <input type="hidden" name="recommended_package" value="<?= htmlspecialchars($recommended_package) ?>">
                <div class="recommendation-box">
                  <i class="fas fa-star"></i> Recommended Package for <strong><?= htmlspecialchars($valid_pet['name']) ?></strong>:
                  <span class="recommend"><?= htmlspecialchars($recommended_package) ?></span>
                </div>
              <?php endif; ?>

              <div class="form-group">
                <label for="package_id">
                  <i class="fas fa-box"></i> Select Grooming Package (Filtered for Your Pet)
                </label>
                <select name="package_id" id="package_id" required>
                  <option value="">-- Select Package --</option>
                  <?php while ($pkg = pg_fetch_assoc($packages_result)): ?>
                    <option value="<?= $pkg['price_id'] ?>" 
                      <?= ($pkg['name'] == $recommended_package) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($pkg['name']) ?> 
                      (<?= htmlspecialchars($pkg['species']) ?> - <?= htmlspecialchars($pkg['size']) ?>,
                      <?= $pkg['min_weight'] ?>-<?= $pkg['max_weight'] ?> kg) 
                      - ₱<?= number_format($pkg['price'], 2) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="appointment_date">
                  <i class="fas fa-calendar-alt"></i> Appointment Date and Time
                </label>
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
                      <span>Full (5+ bookings)</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <label for="notes">
                  <i class="fas fa-sticky-note"></i> Special Instructions (optional)
                </label>
                <textarea name="notes" id="notes" rows="3" placeholder="Any special care instructions for your pet..."></textarea>
              </div>

              <button type="submit" class="submit-btn">
                <i class="fas fa-check-circle"></i> Confirm Appointment
              </button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  const appointmentCounts = <?= json_encode($appointment_counts) ?>;
  const MAX_APPOINTMENTS = 5;

  function getAppointmentCount(date, hour) {
    const dateStr = date.toISOString().split('T')[0];
    if (appointmentCounts[dateStr] && appointmentCounts[dateStr][hour] !== undefined) {
      return appointmentCounts[dateStr][hour];
    }
    return 0;
  }

  function isSlotAvailable(date, hour) {
    const count = getAppointmentCount(date, hour);
    return count < MAX_APPOINTMENTS;
  }

  function updateAvailabilityIndicator() {
    const dateInput = document.getElementById('appointment_date');
    const indicator = document.getElementById('availabilityIndicator');
    const submitBtn = document.querySelector('.submit-btn');

    if (!dateInput.value) {
      indicator.className = 'availability-indicator';
      indicator.textContent = 'Select date/time';
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Appointment';
      return;
    }

    const selectedDate = new Date(dateInput.value);
    const hour = selectedDate.getHours();
    
    if (hour < 9 || hour > 18) {
      indicator.className = 'availability-indicator show full';
      indicator.innerHTML = '⛔ Outside Business Hours';
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-times-circle"></i> Outside Business Hours (9 AM - 6 PM Only)';
      return;
    }
    
    const appointmentCount = getAppointmentCount(selectedDate, hour);
    const available = isSlotAvailable(selectedDate, hour);

    if (available) {
      indicator.className = 'availability-indicator show available';
      indicator.innerHTML = `✓ Available (${appointmentCount}/${MAX_APPOINTMENTS} booked)`;
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Appointment';
    } else {
      indicator.className = 'availability-indicator show full';
      indicator.innerHTML = `✕ Full (${appointmentCount}/${MAX_APPOINTMENTS} booked)`;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-times-circle"></i> Time Slot Full - Choose Another';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('appointment_date');
    if (dateInput) {
      dateInput.addEventListener('change', updateAvailabilityIndicator);
      dateInput.addEventListener('input', updateAvailabilityIndicator);

      const now = new Date();
      const today = now.toISOString().split('T')[0];
      dateInput.setAttribute('min', today + 'T09:00');
      dateInput.setAttribute('max', '2025-12-31T18:00');
    }
  });

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
    disable: [
      function(date) {
        const hour = date.getHours();
        const minute = date.getMinutes();
        
        if (hour === 0 && minute === 0) {
          return false;
        }
        
        if (hour < 9 || hour > 18) {
          return true;
        }
        
        return !isSlotAvailable(date, hour);
      }
    ],
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      const date = dayElem.dateObj;
      if (date) {
        let totalAppointments = 0;
        let hoursChecked = 0;
        
        for (let h = 9; h <= 18; h++) {
          const testDate = new Date(date);
          testDate.setHours(h);
          totalAppointments += getAppointmentCount(testDate, h);
          hoursChecked++;
        }
        
        const avgAppointments = totalAppointments / hoursChecked;
        
        if (avgAppointments >= 4) {
          dayElem.style.backgroundColor = '#ffebee';
          dayElem.title = `Busy day - avg ${avgAppointments.toFixed(1)} bookings per hour`;
        } else if (avgAppointments >= 2) {
          dayElem.style.backgroundColor = '#fff3e0';
          dayElem.title = `Moderate day - avg ${avgAppointments.toFixed(1)} bookings per hour`;
        } else {
          dayElem.style.backgroundColor = '#e8f5e8';
          dayElem.title = `Available day - avg ${avgAppointments.toFixed(1)} bookings per hour`;
        }
      }
    }
  });

 // Hamburger Menu Toggle
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('nav-menu');
const navOverlay = document.getElementById('nav-overlay');
const profileDropdown = document.getElementById('profile-dropdown');

hamburger.addEventListener('click', function() {
  hamburger.classList.toggle('active');
  navMenu.classList.toggle('active');
  navOverlay.classList.toggle('active');
  document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
});

// Close menu when clicking overlay
navOverlay.addEventListener('click', function() {
  hamburger.classList.remove('active');
  navMenu.classList.remove('active');
  navOverlay.classList.remove('active');
  profileDropdown.classList.remove('active');
  document.body.style.overflow = '';
});

// Close menu when clicking on regular nav links
document.querySelectorAll('.nav-link:not(.profile-icon)').forEach(link => {
  link.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    document.body.style.overflow = '';
  });
});

// Handle profile dropdown - ONLY for mobile (click to toggle)
profileDropdown.addEventListener('click', function(e) {
  if (window.innerWidth <= 1024) {
    // Only prevent default and toggle on mobile
    if (e.target.closest('.profile-icon')) {
      e.preventDefault();
      this.classList.toggle('active');
    }
  }
  // On desktop, do nothing - CSS :hover handles it
});

// Close menu when clicking dropdown items
document.querySelectorAll('.dropdown-menu a').forEach(link => {
  link.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  });
});

// Reset on window resize
window.addEventListener('resize', function() {
  if (window.innerWidth > 1024) {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  }
});
  </script>
</body>
</html>