<?php 
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../homepage/login/loginform.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if the user has pets
$petCheck = pg_query_params($conn, "SELECT COUNT(*) AS count FROM pets WHERE user_id = $1", [$user_id]);
if (!$petCheck) {
    die("Query failed: " . pg_last_error($conn));
}
$petCount = pg_fetch_assoc($petCheck)['count'];

// DEBUG - Remove after testing
echo "<!-- DEBUG: Session user_id = " . htmlspecialchars($_SESSION['user_id']) . " -->";
echo "<!-- DEBUG: Pet count = " . $petCount . " -->";

// Pagination settings
$images_per_page = 6;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Sanitized
$offset = ($current_page - 1) * $images_per_page;

// Get total number of images
$total_result = pg_query($conn, "SELECT COUNT(*) as total FROM gallery");
if (!$total_result) {
    die("Query failed: " . pg_last_error($conn));
}
$total_row = pg_fetch_assoc($total_result);
$total_images = $total_row['total'];
$total_pages = ceil($total_images / $images_per_page); 

// Get images for current page
$result = pg_query($conn, "SELECT * FROM gallery ORDER BY uploaded_at DESC LIMIT $images_per_page OFFSET $offset");
if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

// DEBUG: Check what paths are stored (remove this after fixing)
$debug_result = pg_query($conn, "SELECT image_path FROM gallery LIMIT 1");
if ($debug_result && pg_num_rows($debug_result) > 0) {
    $debug_row = pg_fetch_assoc($debug_result);
    // This will show in HTML comment - check browser source
    echo "<!-- DEBUG - Database stores: " . htmlspecialchars($debug_row['image_path']) . " -->";
    echo "<!-- DEBUG - Current file location: " . __FILE__ . " -->";
    echo "<!-- DEBUG - Document root: " . $_SERVER['DOCUMENT_ROOT'] . " -->";
}
// Pagination settings for services
$services_per_page = 6;
$current_service_page = isset($_GET['service_page']) ? max(1, intval($_GET['service_page'])) : 1;
$service_offset = ($current_service_page - 1) * $services_per_page;

// Get total number of active services (from your packages table)
$total_services_result = pg_query($conn, "SELECT COUNT(*) as total FROM packages WHERE is_active = true");
if (!$total_services_result) {
    die("Query failed: " . pg_last_error($conn));
}
$total_services_row = pg_fetch_assoc($total_services_result);
$total_services = $total_services_row['total'];
$total_service_pages = ceil($total_services / $services_per_page);

// Get services for current page (from your packages table)
$services_query = "
    SELECT 
        p.package_id,
        p.name as service_name,
        p.description,
        MIN(pp.price) as min_price,
        MAX(pp.price) as max_price
    FROM packages p
    LEFT JOIN package_prices pp ON p.package_id = pp.package_id
    WHERE p.is_active = true 
    GROUP BY p.package_id, p.name, p.description
    ORDER BY p.package_id ASC 
    LIMIT $services_per_page 
    OFFSET $service_offset
";
$services_result = pg_query($conn, $services_query);
if (!$services_result) {
    die("Query failed: " . pg_last_error($conn));
}
$services_result = pg_query($conn, $services_query);
if (!$services_result) {
    die("Query failed: " . pg_last_error($conn));
}

// Map static images based on service name
function getServiceImage($serviceName) {
    $imageMap = [
        'Basic Groom' => './images/bnd.png',
        'Full Groom' => './images/fullgroom.png',
        'Spa Bath' => './images/bathdry.png',
    ];
    
    // Return mapped image or default
    return isset($imageMap[$serviceName]) ? $imageMap[$serviceName] : './images/default-service.png';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAWsig City | Homepage</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="./images/pawsig2.png">

  <style>
       /* Base navbar styles */
    .navbar, header {
  background: #ffffff !important;
  background-color: #ffffff !important;
  backdrop-filter: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}
/* Add this near the top of your <style> section */
html {
  scroll-behavior: smooth;
  scroll-padding-top: 100px; /* Adjust this value based on your navbar height */
}

section[id] {
  scroll-margin-top: 30px;
}

/* Don't add extra padding to hero section */
.hero-section {
  scroll-margin-top: 0 !important;
}

/* Alternative: If you want a green navbar to match */
.navbar, header {
  background: #A8E6CF !important;
  background-color: #A8E6CF !important;
  backdrop-filter: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}

/* overlay effect */
.hero-overlay {
  position: absolute;
  inset: 0;
  background: #A8E6CF;
  backdrop-filter: blur(2px);
}

.hero-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 40px;
  flex-wrap: wrap;
  z-index: 1;
  max-width: 1200px;
  margin: auto;
  padding: 40px 0;  /* optional spacing for balance */
}

.hero-text {
  flex: 1;
  max-width: 600px;
}

.hero-title {
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.2;
  color: #333;
}

.hero-title span {
  color: #FFE4A3;
}

.hero-subtitle {
  font-size: 1.5rem;
  margin: 15px 0;
  color: #444;
  font-weight: 600;
}

.hero-description {
  font-size: 1.1rem;
  margin-bottom: 25px;
  color: #555;
  line-height: 1.6;
}

.hero-buttons {
  display: flex;
  justify-content: center;  /* centers buttons */
  gap: 15px;
  margin-top: 20px;         /* space from description */
}

.hero-buttons .button {
  padding: 12px 25px;
  border-radius: 50px;
  font-weight: bold;
  font-size: 1rem;
  transition: all 0.3s ease;
  text-decoration: none;
}

.book-now {
  background: #A8E6CF;
  color: #fff;
    border: 2px solid #252525;

}

.book-now:hover {
  background: #FFE4A3;
  transform: translateY(-2px);
}

.contact-us {
  background: #FFE4A3;
  border: 2px solid #252525;
  color: #252525;
}

.contact-us:hover {
  background: #A8E6CF;
  color: #fff;
}

/* Hero Image */
.hero-image-wrapper {
  flex: 1;
  text-align: center;
}

.hero-image {
  width: 550px;
  max-width: 100%;
  animation: float 4s ease-in-out infinite;
}

/* Floating animation */
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-15px); }
}
/* ========================================
   COMPLETE WORKING DROPDOWN SOLUTION
   Replace your existing dropdown CSS with this
   ======================================== */

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
/* Desktop Dropdown (min-width: 1025px) */
@media (min-width: 1025px) {
  .dropdown {
    position: relative;
  }

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
    /* INCREASED TRANSITION TIME - Makes it stay longer */
    transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
    transition-delay: 0s; /* Show immediately on hover */
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
    transition-delay: 0s; /* Show immediately */
  }

  /* IMPORTANT: Keep dropdown visible when hovering over menu items */
  .dropdown-menu:hover {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  /* ADD DELAY BEFORE HIDING - This prevents quick close */
  .dropdown-menu {
    transition: opacity 0.3s ease 0.3s, /* 0.3s delay before hiding */
                visibility 0.3s ease 0.3s,
                transform 0.3s ease 0.3s;
  }

  /* Remove delay when showing */
  .dropdown:hover .dropdown-menu,
  .dropdown-menu:hover {
    transition-delay: 0s;
  }

  /* ALTERNATIVE: Create invisible bridge between nav and dropdown */
  .dropdown::before {
    content: '';
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    height: 15px; /* Invisible bridge area */
    background: transparent;
    z-index: 999;
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
    box-sizing: border-box;
  }

  .dropdown-menu a:hover {
    background: linear-gradient(90deg, rgba(168, 230, 207, 0.15) 0%, transparent 100%);
    border-left-color: #A8E6CF;
    padding-left: 24px;
    color: #16a085;
  }

  .profile-icon {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    position: relative;
  }

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
  .hamburger {
    display: flex;
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
    text-align: left;
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
    display: none;
  }
  
  .nav-overlay {
    display: none;
  }
}
</style>
</head>
<body>
  <!-- Navbar Header -->
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
      <li class="nav-item"><a href="#home" class="nav-link active"><i class="fas fa-home"></i> Home</a></li>
      <li class="nav-item"><a href="#about" class="nav-link"><i class="fas fa-info-circle"></i> About</a></li>
      <li class="nav-item"><a href="#service" class="nav-link"><i class="fas fa-concierge-bell"></i> Services</a></li>
      <li class="nav-item"><a href="#gallery" class="nav-link"><i class="fas fa-images"></i> Gallery</a></li>
      <li class="nav-item"><a href="#contact" class="nav-link"><i class="fas fa-envelope"></i> Contact</a></li>
      <li class="nav-item dropdown" id="profile-dropdown">
        <a href="#" class="nav-link profile-icon">
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

<style>
 /* ===== Enhanced Hero Section - Centered with Green Background ===== */
.hero-section {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 80px 20px;
  background-image: url('../uploads/pawsigbg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
  overflow: hidden;
}

.hero-section::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(0, 77, 64, 0.80) 0%, rgba(0, 105, 92, 0.85) 50%, rgba(0, 121, 107, 0.88) 100%);
  z-index: 1;
  animation: pulse 8s ease-in-out infinite;
}

.hero-section::after {
  content: "";
  position: absolute;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
  border-radius: 50%;
  bottom: -150px;
  left: -150px;
  animation: pulse 6s ease-in-out infinite reverse;
  z-index: 2;
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.8;
  }
  50% {
    opacity: 1;
  }
}

/* Floating paw prints */
.paw-print {
  position: absolute;
  font-size: 3rem;
  color: rgba(255, 255, 255, 0.2);
  animation: float-paw 12s ease-in-out infinite;
  z-index: 3;
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
}

.paw-print:nth-child(1) {
  top: 15%;
  left: 10%;
  animation-delay: 0s;
  font-size: 2.5rem;
}

.paw-print:nth-child(2) {
  top: 65%;
  left: 12%;
  animation-delay: 2s;
  font-size: 2rem;
}

.paw-print:nth-child(3) {
  top: 25%;
  right: 15%;
  animation-delay: 4s;
  font-size: 3rem;
}

.paw-print:nth-child(4) {
  top: 75%;
  right: 10%;
  animation-delay: 6s;
  font-size: 2.2rem;
}

.paw-print:nth-child(5) {
  top: 45%;
  left: 5%;
  animation-delay: 8s;
  font-size: 1.8rem;
}

.paw-print:nth-child(6) {
  top: 55%;
  right: 8%;
  animation-delay: 10s;
  font-size: 2.8rem;
}

@keyframes float-paw {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
    opacity: 0.3;
  }
  25% {
    transform: translateY(-40px) rotate(90deg);
    opacity: 0.6;
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
    opacity: 0.8;
  }
  75% {
    transform: translateY(-35px) rotate(270deg);
    opacity: 0.5;
  }
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: transparent;
  backdrop-filter: blur(0px);
}

.hero-content {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 40px;
  z-index: 10;
  max-width: 900px;
  margin: auto;
  padding: 60px 20px;
  animation: fadeInUp 1.2s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hero-text {
  flex: 1;
  max-width: 100%;
  animation: zoomIn 1s ease-out;
}

@keyframes zoomIn {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.hero-title {
  font-size: 4rem;
  font-weight: 900;
  line-height: 1.2;
  color: #ffffff;
  margin-bottom: 25px;
  text-shadow: 4px 4px 12px rgba(0, 0, 0, 0.3);
  letter-spacing: -2px;
  animation: titlePulse 3s ease-in-out infinite;
}

@keyframes titlePulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
}

.hero-title span {
  color: #FFE4A3;
  display: inline-block;
  animation: glow 2s ease-in-out infinite;
  position: relative;
  text-shadow: 0 0 30px rgba(255, 228, 163, 0.8),
               0 0 60px rgba(255, 228, 163, 0.5),
               4px 4px 12px rgba(0, 0, 0, 0.3);
}

@keyframes glow {
  0%, 100% {
    text-shadow: 0 0 20px rgba(255, 228, 163, 0.6),
                 0 0 40px rgba(255, 228, 163, 0.4),
                 4px 4px 12px rgba(0, 0, 0, 0.3);
  }
  50% {
    text-shadow: 0 0 40px rgba(255, 228, 163, 1),
                 0 0 80px rgba(255, 228, 163, 0.7),
                 4px 4px 12px rgba(0, 0, 0, 0.3);
  }
}

.hero-subtitle {
  font-size: 2rem;
  margin: 25px 0;
  color: #ffffff;
  font-weight: 700;
  text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.3);
  animation: slideIn 1.2s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.hero-description {
  font-size: 1.3rem;
  margin-bottom: 40px;
  color: #ffffff;
  line-height: 1.9;
  text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
  animation: fadeIn 1.5s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.hero-buttons {
  display: flex;
  gap: 25px;
  margin-top: 35px;
  flex-wrap: wrap;
  justify-content: center;
}

.hero-buttons .button {
  padding: 18px 45px;
  border-radius: 50px;
  font-weight: 800;
  font-size: 1.15rem;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  position: relative;
  overflow: hidden;
  z-index: 1;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.hero-buttons .button::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.4);
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
  z-index: -1;
}

.hero-buttons .button:hover::before {
  width: 350px;
  height: 350px;
}

.hero-buttons .button i {
  font-size: 1.3rem;
  transition: transform 0.3s ease;
}

.hero-buttons .button:hover i {
  transform: scale(1.2) rotate(10deg);
}

.book-now {
  background: linear-gradient(135deg, #FFE4A3 0%, #ffd97d 100%);
  color: #2c3e50;
  border: 4px solid #ffffff;
  animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-8px);
  }
}

.book-now:hover {
  background: linear-gradient(135deg, #ffd97d 0%, #ffcc5c 100%);
  transform: translateY(-8px) scale(1.1);
  box-shadow: 0 15px 40px rgba(255, 228, 163, 0.6);
  border-color: #FFE4A3;
}

.contact-us {
  background: transparent;
  border: 4px solid #ffffff;
  color: #ffffff;
  animation: pulse-border 2s ease-in-out infinite;
}

@keyframes pulse-border {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
  }
}

.contact-us:hover {
  background: #ffffff;
  color: #16a085;
  transform: translateY(-8px) scale(1.1);
  box-shadow: 0 15px 40px rgba(255, 255, 255, 0.5);
}

/* Hero Image - Hidden for centered layout */
.hero-image-wrapper {
  display: none;
}

/* Feature badges with impact */
.feature-badges {
  display: flex;
  gap: 20px;
  margin-top: 40px;
  flex-wrap: wrap;
  justify-content: center;
  animation: fadeInUp 1.8s ease-out;
}

.badge {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  padding: 12px 25px;
  border-radius: 30px;
  color: #16a085;
  font-size: 1rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 3px solid #ffffff;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  cursor: pointer;
}

.badge:hover {
  background: #FFE4A3;
  transform: translateY(-8px) scale(1.15);
  box-shadow: 0 12px 30px rgba(255, 228, 163, 0.5);
  border-color: #FFE4A3;
}

.badge i {
  color: #16a085;
  font-size: 1.2rem;
  animation: rotate 3s linear infinite;
}

@keyframes rotate {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.badge:hover i {
  animation: bounce-icon 0.5s ease;
}

@keyframes bounce-icon {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.3);
  }
}

.hero-content::after {
  content: '';
  position: absolute;
  width: 50px;
  height: 50px;
  background-image: url('../pawsigcity/homepage/images/pawsig.png');
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  opacity: 0;
  animation: sparkle 4s ease-in-out infinite;
  pointer-events: none;
}

@keyframes sparkle {
  0%, 100% {
    opacity: 0;
    transform: translate(0, 0) scale(0);
  }
  25% {
    opacity: 1;
    transform: translate(100px, -50px) scale(1);
  }
  50% {
    opacity: 0;
    transform: translate(200px, -100px) scale(0.5);
  }
  75% {
    opacity: 1;
    transform: translate(-100px, -80px) scale(1.2);
  }
}

/* Responsive Design */
@media (max-width: 968px) {
  .hero-section {
    background-attachment: scroll;
  }
  .hero-title {
    font-size: 3rem;
  }

  .hero-subtitle {
    font-size: 1.6rem;
  }

  .hero-description {
    font-size: 1.15rem;
  }

  .hero-buttons .button {
    padding: 16px 35px;
    font-size: 1rem;
  }

  .badge {
    padding: 10px 20px;
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .hero-section {
    background-attachment: scroll;
  }
  .hero-title {
    font-size: 2.2rem;
    letter-spacing: -1px;
  }

  .hero-subtitle {
    font-size: 1.3rem;
  }

  .hero-description {
    font-size: 1rem;
  }

  .hero-buttons {
    flex-direction: column;
    gap: 15px;
  }

  .hero-buttons .button {
    padding: 14px 30px;
    font-size: 0.95rem;
    width: 100%;
    max-width: 280px;
  }

  .feature-badges {
    gap: 12px;
  }

  .badge {
    padding: 8px 16px;
    font-size: 0.85rem;
  }

  .paw-print {
    font-size: 1.5rem !important;
  }
}
/* ===== COMPLETE GALLERY SECTION CSS ===== */

/* Gallery Section Container */
.gallery-section {
  padding: 80px 0px;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  width: 100%;
  overflow: hidden;
}

/* Gallery Title */
.gallery-section .section-title {
  text-align: center;
  font-size: 2.8rem;
  color: #2c3e50;
  margin-bottom: 50px;
  font-weight: 800;
  letter-spacing: -1px;
  text-transform: uppercase;
  position: relative;
  display: block;
  width: 100%;
  padding: 0 15px;
}

/* Title Underline Decoration */
.gallery-section .section-title::after {
  content: '';
  position: absolute;
  bottom: -15px;
  left: 50%;
  transform: translateX(-50%);
  width: 100px;
  height: 4px;
  background: linear-gradient(90deg, #A8E6CF 0%, #7ed6ad 100%);
  border-radius: 10px;
}

/* Gallery Container - Centered with Max Width */
.gallery-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  width: 100%;
}

/* Gallery Grid - 3 Columns Layout */
.gallery-grid {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
  margin: 0 auto 30px auto;
  padding: 0;
  justify-content: center;
  gap: 0;
}

/* Individual Gallery Item */
.gallery-item {
  position: relative;
  width: 33.333333%; /* 3 columns on desktop */
  padding-bottom: 25%; /* Height ratio */
  overflow: hidden;
  cursor: pointer;
  background: #fff;
  border: 2px solid #f0f0f0;
  margin: 0;
  transition: all 0.3s ease;
}

/* Gallery Item Hover Overlay Background */
.gallery-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(168, 230, 207, 0.2) 0%, rgba(126, 214, 173, 0.2) 100%);
  opacity: 0;
  transition: opacity 0.4s ease;
  z-index: 1;
}

/* Gallery Item Hover State */
.gallery-item:hover {
  z-index: 10;
  border-color: #A8E6CF;
}

.gallery-item:hover::before {
  opacity: 1;
}

/* Gallery Images */
.gallery-item img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
  transition: transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Image Zoom on Hover */
.gallery-item:hover img {
  transform: scale(1.1);
}

/* Gallery Overlay with Icon and Text */
.gallery-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(168, 230, 207, 0.95) 0%, rgba(126, 214, 173, 0.95) 100%);
  opacity: 0;
  transition: opacity 0.4s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 10px;
  z-index: 3;
}

.gallery-item:hover .gallery-overlay {
  opacity: 1;
}

/* Overlay Icon */
.gallery-overlay i {
  font-size: 2.5rem;
  color: #ffffff;
  animation: zoomPulse 1.5s ease-in-out infinite;
}

@keyframes zoomPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.15); }
}

/* Overlay Text */
.gallery-overlay-text {
  color: #ffffff;
  font-size: 1rem;
  font-weight: 700;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
}

/* Empty Gallery State */
.gallery-empty {
  text-align: center;
  padding: 80px 20px;
  color: #666;
  animation: fadeIn 1s ease-out;
  width: 100%;
}

.gallery-empty i {
  font-size: 4rem;
  color: #A8E6CF;
  margin-bottom: 20px;
  opacity: 0.4;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

.gallery-empty h3 {
  font-size: 1.8rem;
  color: #2c3e50;
  margin-bottom: 10px;
  font-weight: 700;
}

.gallery-empty p {
  font-size: 1rem;
  color: #666;
  max-width: 500px;
  margin: 0 auto;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* ===== PAGINATION STYLES ===== */

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-top: 40px;
  flex-wrap: wrap;
  padding: 0 15px;
}

.pagination a,
.pagination span {
  min-width: 50px;
  height: 50px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  text-decoration: none;
  font-weight: 700;
  font-size: 1rem;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  background: #ffffff;
  color: #2c3e50;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
  border: 3px solid transparent;
}

.pagination a:hover {
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  color: #ffffff;
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 6px 20px rgba(168, 230, 207, 0.5);
  border-color: #A8E6CF;
}

.pagination .active {
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  color: #ffffff;
  box-shadow: 0 6px 20px rgba(168, 230, 207, 0.5);
  transform: scale(1.1);
  border-color: #7ed6ad;
}

.pagination .disabled {
  opacity: 0.3;
  cursor: not-allowed;
  pointer-events: none;
  background: #f0f0f0;
  box-shadow: none;
}

.pagination a i {
  font-size: 0.9rem;
  font-weight: 900;
}

.page-numbers {
  display: flex;
  gap: 12px;
}

/* ===== LIGHTBOX MODAL ===== */

.lightbox {
  display: none;
  position: fixed;
  z-index: 10000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.97);
  justify-content: center;
  align-items: center;
  animation: fadeInLightbox 0.3s ease;
  backdrop-filter: blur(10px);
}

.lightbox.active {
  display: flex;
}

.lightbox-content {
  max-width: 95%;
  max-height: 95vh;
  position: relative;
  animation: zoomIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.lightbox-content img {
  width: 100%;
  height: auto;
  max-height: 90vh;
  object-fit: contain;
  border-radius: 15px;
  box-shadow: 0 20px 80px rgba(0, 0, 0, 0.8);
}

.lightbox-close {
  position: absolute;
  top: -60px;
  right: 0;
  color: #ffffff;
  font-size: 3rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  background: rgba(168, 230, 207, 0.9);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
}

.lightbox-close:hover {
  background: #A8E6CF;
  transform: rotate(90deg) scale(1.1);
  box-shadow: 0 8px 30px rgba(168, 230, 207, 0.6);
}

@keyframes fadeInLightbox {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes zoomIn {
  from { 
    transform: scale(0.7); 
    opacity: 0; 
  }
  to { 
    transform: scale(1); 
    opacity: 1; 
  }
}

/* ===== RESPONSIVE DESIGN ===== */

/* Tablets (481px - 968px) - Keep 3 columns */
@media (max-width: 968px) {
  .gallery-section {
    padding: 60px 0px;
  }

  .gallery-section .section-title {
    font-size: 2.2rem;
    margin-bottom: 40px;
  }

  .gallery-item {
    width: 33.333333%; /* Keep 3 columns */
    padding-bottom: 25%;
  }

  .gallery-overlay i {
    font-size: 2rem;
  }

  .gallery-overlay-text {
    font-size: 0.9rem;
  }

  .pagination {
    margin-top: 30px;
    gap: 10px;
  }

  .pagination a,
  .pagination span {
    min-width: 45px;
    height: 45px;
    font-size: 0.95rem;
  }

  .lightbox-close {
    top: -50px;
    width: 50px;
    height: 50px;
    font-size: 2.5rem;
  }

  .gallery-empty {
    padding: 60px 20px;
  }

  .gallery-empty i {
    font-size: 3.5rem;
  }

  .gallery-empty h3 {
    font-size: 1.6rem;
  }
}

/* Mobile (<= 480px) - 2 columns */
@media (max-width: 480px) {
  .gallery-section {
    padding: 50px 0px;
  }

  .gallery-section .section-title {
    font-size: 1.8rem;
    margin-bottom: 30px;
  }

  .gallery-item {
    width: 50%; /* 2 columns on mobile */
    padding-bottom: 50%; /* Square ratio */
  }

  .gallery-grid {
    margin-bottom: 20px;
  }

  .gallery-overlay i {
    font-size: 1.8rem;
  }

  .gallery-overlay-text {
    font-size: 0.8rem;
    letter-spacing: 1px;
  }

  .pagination {
    margin-top: 25px;
    gap: 8px;
  }

  .pagination a,
  .pagination span {
    min-width: 42px;
    height: 42px;
    font-size: 0.9rem;
  }

  .lightbox-close {
    top: -50px;
    width: 50px;
    height: 50px;
    font-size: 2rem;
  }

  .gallery-empty {
    padding: 50px 15px;
  }

  .gallery-empty i {
    font-size: 3rem;
  }
  
  .gallery-empty h3 {
    font-size: 1.4rem;
  }

  .gallery-empty p {
    font-size: 0.9rem;
  }
}
.service-price {
  font-size: 1.5rem;
  font-weight: 800;
  color: #A8E6CF;
  margin: 15px 0 8px 0;
  text-align: center;
}

.service-duration {
  font-size: 0.9rem;
  color: #666;
  text-align: center;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.service-duration i {
  color: #A8E6CF;
  font-size: 0.85rem;
}

.service-empty {
  text-align: center;
  padding: 80px 20px;
  color: #666;
  animation: fadeIn 1s ease-out;
  width: 100%;
}

.service-empty i {
  font-size: 4rem;
  color: #A8E6CF;
  margin-bottom: 20px;
  opacity: 0.4;
  animation: float 3s ease-in-out infinite;
}

.service-empty h3 {
  font-size: 1.8rem;
  color: #2c3e50;
  margin-bottom: 10px;
  font-weight: 700;
}

.service-empty p {
  font-size: 1rem;
  color: #666;
  max-width: 500px;
  margin: 0 auto;
}

.service-pagination {
  margin-top: 40px;
}

/* Enhance service items */
.service-item {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.service-item:hover {
  transform: translateY(-12px) scale(1.05);
}

.service-link {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Responsive adjustments */
@media (max-width: 968px) {
  .service-price {
    font-size: 1.3rem;
  }
  
  .service-duration {
    font-size: 0.85rem;
  }
}

@media (max-width: 480px) {
  .service-price {
    font-size: 1.1rem;
  }
  
  .service-duration {
    font-size: 0.8rem;
  }
  
  .service-empty {
    padding: 60px 15px;
  }
  
  .service-empty i {
    font-size: 3rem;
  }
  
  .service-empty h3 {
    font-size: 1.5rem;
  }
}
.service-section {
  padding: 80px 20px;
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
}

.service-section .section-title {
  text-align: center;
  font-size: 2.5rem;
  color: #2c3e50;
  margin-bottom: 40px;
  font-weight: 700;
}

/* Service Grid Layout */
.service-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0;
  list-style: none;
}

/* Service Card */
.service-item {
  background: #ffffff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  border: 3px solid transparent;
}

.service-item:hover {
  transform: translateY(-12px);
  box-shadow: 0 15px 40px rgba(168, 230, 207, 0.4);
  border-color: #A8E6CF;
}

.service-link {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 25px;
  text-decoration: none;
  height: 100%;
  text-align: center;
}

/* Service Icon Styling */
.service-icon {
  width: 90px;
  height: 90px;
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 25px;
  transition: all 0.4s ease;
  box-shadow: 0 8px 20px rgba(168, 230, 207, 0.3);
}

.service-icon i {
  font-size: 2.5rem;
  color: #ffffff;
  animation: iconFloat 3s ease-in-out infinite;
}

@keyframes iconFloat {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-8px);
  }
}

.service-item:hover .service-icon {
  transform: scale(1.15) rotate(10deg);
  box-shadow: 0 12px 30px rgba(168, 230, 207, 0.5);
}

/* Service Title */
.service-link .name {
  font-size: 1.5rem;
  font-weight: 800;
  color: #2c3e50;
  margin-bottom: 15px;
  transition: color 0.3s ease;
}
 
.service-item:hover .name {
  color: #16a085;
}

/* Service Description */
.service-link .text {
  font-size: 1rem;
  color: #666;
  line-height: 1.7;
  margin-bottom: 20px;
  flex-grow: 1;
}

/* Service Price */
.service-price {
  font-size: 1.5rem;
  font-weight: 800;
  color: #A8E6CF;
  margin: 15px 0 0 0;
  text-align: center;
}

/* Empty State */
.service-empty {
  text-align: center;
  padding: 80px 20px;
  color: #666;
  animation: fadeIn 1s ease-out;
  width: 100%;
}

.service-empty i {
  font-size: 4rem;
  color: #A8E6CF;
  margin-bottom: 20px;
  opacity: 0.4;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

.service-empty h3 {
  font-size: 1.8rem;
  color: #2c3e50;
  margin-bottom: 10px;
  font-weight: 700;
}

.service-empty p {
  font-size: 1rem;
  color: #666;
  max-width: 500px;
  margin: 0 auto;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.service-pagination {
  margin-top: 40px;
}

/* ===== RESPONSIVE DESIGN ===== */

/* Tablets */
@media (max-width: 968px) {
  .service-section {
    padding: 60px 15px;
  }
  
  .service-list {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
  }
  
  .service-icon {
    width: 80px;
    height: 80px;
  }
  
  .service-icon i {
    font-size: 2.2rem;
  }
  
  .service-link .name {
    font-size: 1.3rem;
  }
  
  .service-price {
    font-size: 1.3rem;
  }
}

/* Mobile */
@media (max-width: 480px) {
  .service-section {
    padding: 50px 15px;
  }
  
  .service-list {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .service-icon {
    width: 70px;
    height: 70px;
  }
  
  .service-icon i {
    font-size: 2rem;
  }
  
  .service-link {
    padding: 30px 20px;
  }
  
  .service-link .name {
    font-size: 1.2rem;
  }
  
  .service-link .text {
    font-size: 0.95rem;
  }
  
  .service-price {
    font-size: 1.2rem;
  }
  
  .service-empty {
    padding: 60px 15px;
  }
  
  .service-empty i {
    font-size: 3rem;
  }
  
  .service-empty h3 {
    font-size: 1.5rem;
  }
}
/* ===== ABOUT & CONTACT SECTION TITLE STYLES ===== */
.about-section .section-title,
.contact-section .section-title {
  text-align: center;
  font-size: 2.5rem;
  color: #2c3e50;
  margin-bottom: 40px;
  font-weight: 700;
}
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
     <?php if ($petCount == 0): ?>
  <div id="petToast" class="pet-toast">
    <div class="pet-toast-content">
      🐶 You haven’t added any pets yet. 
      <a href="../pets/add-pet.php" class="toast-link">Add one now</a> to book a grooming appointment!
      <button class="toast-close" onclick="document.getElementById('petToast').style.display='none'">&times;</button>
    </div>
  </div>
  <script>
    // Show toast on load
    window.addEventListener("DOMContentLoaded", () => {
      const toast = document.getElementById("petToast");
      if (toast) {
        toast.style.display = "block";
        // Optional: auto-hide after 7 seconds
        setTimeout(() => toast.style.display = "none", 200000);
      }
    });
  </script>
<?php endif; ?>



  <!-- Hero Section -->
  <main>
   <!-- Hero Section -->
<section class="hero-section" id="home">
  <div class="hero-overlay"></div>
  <div class="section-content hero-content">
    <div class="hero-text">
      <h1 class="hero-title">Welcome to <span>PAWsig City</span></h1>
      <h3 class="hero-subtitle">Where Grooming Meets Love & Care</h3>
      <p class="hero-description">
        From Paw-scheduling to Tail-wagging — We’ve Got It Covered.  
        Treat your pets with the best grooming experience in Pasig City.
      </p>
      <div class="hero-buttons">
        <a href="../appointment/book-appointment.php" class="button book-now"> Book Now</a>
        <a href="#contact" class="button contact-us"> Contact Us</a>
      </div>
    </div>
  </div>
</section>

    <!-- About Section -->
    <section class="about-section" id="about">
      <div class="section-content">
        <div class="about-image-wrapper">
          <img src="./images/pawsig2.png" alt="About Our Shop" class="about-image" />
        </div>
        <div class="about-details">
          <h2 class="section-title">About Us</h2>
        <p class="text">
  PAWsig City is a dedicated pet grooming shop that focuses on giving pets a comfortable, gentle, and high-quality grooming experience. We understand that every pet has its own personality and needs, which is why our team takes the time to get to know each one. From energetic pups to anxious or senior pets, we ensure every grooming session is handled with care, patience, and professionalism. Whether it’s a simple bath, a complete grooming makeover, or a specialized treatment, we work to make pets look their best while keeping their health, hygiene, and comfort as our top priorities. Our goal is to make every visit a positive experience where pets feel safe, relaxed, and genuinely cared for.
</p>

<p class="text">
  We also want to make things easier for pet owners by offering a smooth and stress-free booking experience. Our online appointment system lets you schedule your pet’s grooming session anytime, anywhere—without the hassle of long waiting lines or complicated processes. At PAWsig City, we believe that great grooming is built on trust, both with pets and the people who love them. That’s why we strive to create a warm and welcoming environment, provide transparent services, and ensure every pet receives the love, attention, and respect they deserve. Your pet’s happiness and well-being are at the heart of everything we do.
</p>


          <div class="social-link-list">
            <a href="https://www.facebook.com/pawsigcity" class="social-link"><i class="fa-brands fa-facebook"></i></a>
            <a href="https://www.instagram.com/pawsig_city/" class="social-link"><i class="fa-brands fa-instagram"></i></a>
          </div>
        </div>
      </div>
    </section>

    <!-- Services Section -->
<section class="service-section" id="service">
  <h2 class="section-title">Our Services</h2>
  
  <div class="section-content">
    <?php if (pg_num_rows($services_result) > 0): ?>
      <ul class="service-list">
          <?php while ($service = pg_fetch_assoc($services_result)): ?>
      <li class="service-item">
        <a href="../appointment/book-appointment.php?service=<?= urlencode($service['service_name']) ?>" class="service-link">
          <!-- NEW: Service Icon -->
          <div class="service-icon">
            <i class="fas fa-cut"></i>
          </div>
          <h3 class="name"><?= htmlspecialchars($service['service_name']) ?></h3>
          <p class="text"><?= htmlspecialchars($service['description']) ?></p>
          <?php if ($service['min_price']): ?>
            <p class="service-price">
              <?php if ($service['min_price'] == $service['max_price']): ?>
                ₱<?= number_format($service['min_price'], 2) ?>
              <?php else: ?>
                ₱<?= number_format($service['min_price'], 2) ?> - ₱<?= number_format($service['max_price'], 2) ?>
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </a>
      </li>
    <?php endwhile; ?>
      </ul>

      <!-- Pagination (only show if more than one page) -->
      <?php if ($total_service_pages > 1): ?>
        <div class="pagination service-pagination">
          <!-- Previous Button -->
          <?php if ($current_service_page > 1): ?>
            <a href="?service_page=<?= $current_service_page - 1 ?>#service" title="Previous Page">
              <i class="fas fa-chevron-left"></i>
            </a>
          <?php else: ?>
            <span class="disabled">
              <i class="fas fa-chevron-left"></i>
            </span>
          <?php endif; ?>

          <!-- Page Numbers -->
          <div class="page-numbers">
            <?php for ($i = 1; $i <= $total_service_pages; $i++): ?>
              <?php if ($i == $current_service_page): ?>
                <span class="active"><?= $i ?></span>
              <?php else: ?>
                <a href="?service_page=<?= $i ?>#service"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </div>

          <!-- Next Button -->
          <?php if ($current_service_page < $total_service_pages): ?>
            <a href="?service_page=<?= $current_service_page + 1 ?>#service" title="Next Page">
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php else: ?>
            <span class="disabled">
              <i class="fas fa-chevron-right"></i>
            </span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="service-empty">
        <i class="fas fa-spa"></i>
        <h3>No Services Available</h3>
        <p>We're updating our service offerings. Please check back soon!</p>
      </div>
    <?php endif; ?>
  </div>
</section>

  <!-- Gallery Section - FIXED -->
<section class="gallery-section" id="gallery">
  <h2 class="section-title">Pet Gallery</h2>
  
  <div class="gallery-container">
    <?php if (pg_num_rows($result) > 0): ?>
      <!-- 3x2 Gallery Grid -->
      <div class="gallery-grid">
        <?php while ($image = pg_fetch_assoc($result)): 
          // Use the full Supabase URL directly from the database
          $image_url = htmlspecialchars($image['image_path']);
        ?>
          <div class="gallery-item" onclick="openLightbox('<?= $image_url ?>')">
            <img src="<?= $image_url ?>" 
                 alt="Pet Gallery Image #<?= $image['id'] ?>"
                 onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;background:#f0f0f0;color:#999;\'>Image unavailable</div>';">
            <div class="gallery-overlay">
              <i class="fas fa-search-plus"></i>
              <div class="gallery-overlay-text">Click to View</div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <!-- Pagination: 1 2 3 -->
      <div class="pagination">
        <!-- Previous Button -->
        <?php if ($current_page > 1): ?>
          <a href="?page=<?= $current_page - 1 ?>#gallery" title="Previous Page">
            <i class="fas fa-chevron-left"></i>
          </a>
        <?php else: ?>
          <span class="disabled">
            <i class="fas fa-chevron-left"></i>
          </span>
        <?php endif; ?>

        <!-- Page Numbers -->
        <div class="page-numbers">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $current_page): ?>
              <span class="active"><?= $i ?></span>
            <?php else: ?>
              <a href="?page=<?= $i ?>#gallery"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
        </div>

        <!-- Next Button -->
        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?= $current_page + 1 ?>#gallery" title="Next Page">
            <i class="fas fa-chevron-right"></i>
          </a>
        <?php else: ?>
          <span class="disabled">
            <i class="fas fa-chevron-right"></i>
          </span>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <div class="gallery-empty">
        <i class="fas fa-images"></i>
        <h3>No Images Yet</h3>
        <p>Our gallery will be filled with adorable pet photos soon!</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
  <div class="lightbox-content">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <img id="lightbox-img" src="" alt="Full size image">
  </div>
</div>


<script>
// Lightbox functions - UPDATED
function openLightbox(imageSrc) {
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  console.log('Opening lightbox with image:', imageSrc); // Debug
  lightboxImg.src = imageSrc;
  lightbox.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeLightbox(event) {
  if (event) event.stopPropagation();
  const lightbox = document.getElementById('lightbox');
  lightbox.classList.remove('active');
  document.body.style.overflow = '';
}

// Close lightbox on ESC key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeLightbox();
  }
});
</script>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
      <h2 class="section-title">Contact Us</h2>
      <div class="section-content">
        <ul class="contact-info-list">
           <li class="contact-info"><i class="fa-solid fa-location-crosshairs"></i><p>2F Hampton Gardens Arcade, C. Raymundo, Maybunga, Pasig, Philippines</p></li>
          <li class="contact-info"><i class="fa-regular fa-envelope"></i><p>pawsigcity@gmail.com</p></li>
          <li class="contact-info"><i class="fa-solid fa-phone"></i><p>0954 476 0085</p></li>
          <li class="contact-info"><i class="fa-solid fa-clock"></i><p>9AM - 8PM ONLY</p></li>
          <li class="contact-info"><i class="fa-solid fa-calendar-check"></i><p>MONDAY TO SUNDAY</p></li>
          <li class="contact-info"><i class="fa-solid fa-globe"></i><p>PAWsig City</p></li>
        </ul>
        <form action="#" class="contact-form">
          <input type="text" placeholder="Your Name" class="form-input" required />
          <input type="email" placeholder="Email" class="form-input" required />
          <textarea placeholder="Your Message" class="form-input" required></textarea>
          <button type="submit" class="submit-button">Submit</button>
        </form>
      </div>
    </section>

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
  </main>

  <!-- JavaScript -->
  <script>
   // Navigation highlight on scroll
    const sections = document.querySelectorAll("section[id]");
    const navLinks = document.querySelectorAll(".nav-link");

    window.addEventListener("scroll", () => {
      let scrollY = window.pageYOffset + 130;

      sections.forEach((section) => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const sectionId = section.getAttribute("id");

        if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
          navLinks.forEach((link) => {
            link.classList.remove("active");
            if (link.getAttribute("href") === `#${sectionId}`) {
              link.classList.add("active");
            }
          });
        }
      });
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
 // Lightbox functions
    function openLightbox(imageSrc) {
      const lightbox = document.getElementById('lightbox');
      const lightboxImg = document.getElementById('lightbox-img');
      lightboxImg.src = imageSrc;
      lightbox.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      const lightbox = document.getElementById('lightbox');
      lightbox.classList.remove('active');
      document.body.style.overflow = '';
    }

    // Close lightbox on ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeLightbox();
      }
    });
    // Q&A Database
const qaDatabase = {
  "where are you located": "Hello! PAWsig City is located at 2F Hampton Gardens Arcade, C. Raymundo, Maybunga, Pasig, Philippines. 📍",
  "what are your contact": "You can message us on our Facebook page or send a message at 0954 476 0085. 📱",
  "when are you open": "We're open daily from 9:00 AM to 8:00 PM, Monday to Sunday. 🕐",

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
