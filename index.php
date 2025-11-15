<?php 
require_once 'db.php'; 

// Pagination settings for gallery
$images_per_page = 6; // 3x2 grid
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
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
$result = pg_query($conn, "SELECT * FROM gallery ORDER BY id ASC LIMIT $images_per_page OFFSET $offset");
if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}
// Pagination settings for services
$services_per_page = 6;
$current_service_page = isset($_GET['service_page']) ? max(1, intval($_GET['service_page'])) : 1;
$service_offset = ($current_service_page - 1) * $services_per_page;

// Get total number of active services
$total_services_result = pg_query($conn, "SELECT COUNT(*) as total FROM packages WHERE is_active = true");
if (!$total_services_result) {
    die("Query failed: " . pg_last_error($conn));
}
$total_services_row = pg_fetch_assoc($total_services_result);
$total_services = $total_services_row['total'];
$total_service_pages = ceil($total_services / $services_per_page);

// Get services for current page with price range
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

// Map static images based on service name
function getServiceImage($serviceName) {
    $imageMap = [
        'Basic Groom' => './homepage/images/bnd.png',
        'Full Groom' => './homepage/images/fullgroom.png',
        'Spa Bath' => './homepage/images/bathdry.png',
    ];
    return isset($imageMap[$serviceName]) ? $imageMap[$serviceName] : './homepage/images/default-service.png';
}
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAWsig City</title>
  <link rel="stylesheet" href="./homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="./homepage/images/pawsig2.png">
</head>
<body>
  <style>
     /* Add this near the top of your <style> section */
    html {
      scroll-behavior: smooth;
      scroll-padding-top: 100px;
    }

    section[id] {
      scroll-margin-top: 30px;
    }

    .hero-section {
      scroll-margin-top: 0 !important;
    }
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
<!-- Add this CSS to replace your existing gallery styles -->
<style>
/* ===== COMPACT GALLERY - FITS IN ONE VIEWPORT ===== */

/* Gallery Section */
.gallery-section {
  padding: 40px 0px 40px 0px;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  width: 100%;
  overflow: hidden;
  min-height: 85vh;
  display: flex;
  flex-direction: column;
}

.gallery-section .section-title {
  text-align: center;
  font-size: 2.5rem;
  color: #2c3e50;
  margin-bottom: 20px;
  font-weight: 800;
  letter-spacing: -1px;
  text-transform: uppercase;
  position: relative;
  display: block;
  width: 100%;
  padding: 0 15px;
}

.gallery-section .section-title::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 100px;
  height: 4px;
  background: linear-gradient(90deg, #A8E6CF 0%, #7ed6ad 100%);
  border-radius: 10px;
}

/* Container - CENTERED WITH MAX WIDTH */
.gallery-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  width: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Grid - FLEXBOX for perfect control */
.gallery-grid {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
  margin: 0 auto;
  padding: 0;
  flex: 1;
  justify-content: center;
}

/* SMALLER Gallery Items - 3 per row on desktop */
.gallery-item {
  position: relative;
  width: 33.333333%;
  padding-bottom: 18%; /* REDUCED from 22% - makes images shorter/more compact */
  overflow: hidden;
  cursor: pointer;
  background: #fff;
  border: none;
  margin: 0;
  transition: all 0.3s ease;
}

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

.gallery-item:hover {
  z-index: 10;
}

.gallery-item:hover::before {
  opacity: 1;
}

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

.gallery-item:hover img {
  transform: scale(1.1);
}

/* Overlay with Icon */
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

.gallery-overlay i {
  font-size: 2.5rem;
  color: #ffffff;
  animation: zoomPulse 1.5s ease-in-out infinite;
}

@keyframes zoomPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.15); }
}

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

/* ===== COMPACT PAGINATION ===== */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
  margin-bottom: 15px;
  flex-wrap: wrap;
  padding: 0 15px;
  width: 100%;
}

.pagination a,
.pagination span {
  min-width: 45px;
  height: 45px;
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
  gap: 10px;
}

/* ===== RESPONSIVE - 3x2 on tablets, 2x3 on mobile ===== */

/* Tablets - KEEP 3 columns */
@media (max-width: 968px) {
  .gallery-section {
    padding: 40px 0px;
    min-height: auto;
  }

  .gallery-section .section-title {
    font-size: 2rem;
    margin-bottom: 20px;
  }

  .gallery-item {
    width: 33.333333%; /* KEEP 3 columns */
    padding-bottom: 22%; /* Slightly taller for tablets */
  }

  .pagination a,
  .pagination span {
    min-width: 42px;
    height: 42px;
    font-size: 0.95rem;
  }

  .gallery-overlay i {
    font-size: 2rem;
  }

  .gallery-overlay-text {
    font-size: 0.9rem;
  }
}

/* Mobile - 2 columns for better fit */
@media (max-width: 480px) {
  .gallery-section {
    padding: 30px 0px;
  }

  .gallery-section .section-title {
    font-size: 1.6rem;
    margin-bottom: 15px;
  }

  .gallery-item {
    width: 50%; /* 2 columns on mobile */
    padding-bottom: 50%; /* Square-ish ratio */
  }

  .gallery-overlay i {
    font-size: 1.8rem;
  }

  .gallery-overlay-text {
    font-size: 0.8rem;
    letter-spacing: 1px;
  }

  .pagination {
    gap: 8px;
    margin-top: 20px;
  }

  .pagination a,
  .pagination span {
    min-width: 40px;
    height: 40px;
    font-size: 0.85rem;
  }

  .gallery-empty {
    padding: 60px 20px;
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
  animation: fadeIn 0.3s ease;
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

@keyframes fadeIn {
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
/* Service pricing styles */
.service-price {
  font-size: 1.5rem;
  font-weight: 800;
  color: #A8E6CF;
  margin: 15px 0 8px 0;
  text-align: center;
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

/* Responsive adjustments */
@media (max-width: 968px) {
  .service-price {
    font-size: 1.3rem;
  }
}

@media (max-width: 480px) {
  .service-price {
    font-size: 1.1rem;
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
</style>
  </style>
  <!-- Navbar Header -->
  <header>
  <nav class="navbar section-content">
    <a href="#" class="navbar-logo">
      <img src="./homepage/images/pawsig2.png" alt="Logo" class="icon" />
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
      <li class="nav-item"><a href="https://pawsigcity.onrender.com/homepage/login/loginform.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a></li>
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
  background-image: url('./uploads/pawsigbg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  background-attachment: fixed;
  overflow: hidden;
}

/* Animated particles floating */
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
  z-index: 1;
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
  -webkit-text-stroke: 2px rgba(0, 0, 0, 0.5);
  text-stroke: 2px rgba(0, 0, 0, 0.5);
  text-shadow: 
    4px 4px 0px rgba(0, 0, 0, 0.3),
    6px 6px 12px rgba(0, 0, 0, 0.4),
    0 0 40px rgba(0, 0, 0, 0.2);
  paint-order: stroke fill;
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
  background-image: url('../pawsigcity/homepage/images/pawsig2.png');
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

@media (max-width: 968px) {
  .hero-section {
    background-attachment: scroll;
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
.service-section {
  padding: 80px 20px 80px 20px;
  background: linear-gradient(135deg, #A8E6CF 0%, #7ed6ad 100%);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  scroll-margin-top: 100px;
}

.service-section .section-title {
  text-align: center;
  font-size: 2.5rem;
  color: #2c3e50;
  margin-bottom: 60px;
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

</style>


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
        <a href="../homepage/login/loginform.php" class="button book-now"> Book Now</a>
        <a href="#contact" class="button contact-us"> Contact Us</a>
      </div>
    </div>
  </div>
</section>


    <!-- About Section -->
     <section class="about-section" id="about">
      <div class="section-content">
        <div class="about-image-wrapper">
          <img src="./homepage/images/pawsig2.png" alt="About Our Shop" class="about-image" />
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



    <!-- Contact Us Section -->
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
        <form action="./homepage/login/loginform.php" class="contact-form">
          <input type="text" placeholder="Your Name" class="form-input" required />
          <input type="email" placeholder="Email" class="form-input" required />
          <textarea placeholder="Your Message" class="form-input" required></textarea>
          <button type="submit" class="submit-button">Submit</button>
        </form>
      </div>
    </section>

  

  </main>

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
      if (profileDropdown) profileDropdown.classList.remove('active');
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
    if (profileDropdown) {
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
    }

    // Close menu when clicking dropdown items
    document.querySelectorAll('.dropdown-menu a').forEach(link => {
      link.addEventListener('click', function() {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        navOverlay.classList.remove('active');
        if (profileDropdown) profileDropdown.classList.remove('active');
        document.body.style.overflow = '';
      });
    });

    // Reset on window resize
    window.addEventListener('resize', function() {
      if (window.innerWidth > 1024) {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        navOverlay.classList.remove('active');
        if (profileDropdown) profileDropdown.classList.remove('active');
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
  </script>
</body>
</html>