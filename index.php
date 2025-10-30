<?php 
require_once 'db.php'; 

// Pagination settings
$images_per_page = 6; // Number of images per page
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
$result = pg_query($conn, "SELECT * FROM gallery ORDER BY uploaded_at DESC LIMIT $images_per_page OFFSET $offset");
if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAWsig City</title>
  <link rel="stylesheet" href="./homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="./homepage/images/pawsig.png">
</head>
<body>
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
  </style>
  <!-- Navbar Header -->
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
          <li><a href="../homepage/login/loginform.php">Login</a></li>
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
  background: linear-gradient(135deg, #52de97 0%, #A8E6CF 50%, #7ed6ad 100%);
  overflow: hidden;
}

/* Animated particles floating */
.hero-section::before {
  content: "";
  position: absolute;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(255, 228, 163, 0.3) 0%, transparent 70%);
  border-radius: 50%;
  top: -200px;
  right: -200px;
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
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 0.6;
  }
  50% {
    transform: scale(1.3);
    opacity: 1;
  }
}

/* Floating paw prints */
.paw-print {
  position: absolute;
  font-size: 3rem;
  color: rgba(255, 255, 255, 0.2);
  animation: float-paw 12s ease-in-out infinite;
  z-index: 1;
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
          <img src="./homepage/images/about.jpg" alt="About" class="about-image" />
        </div>
        <div class="about-details">
          <h2 class="section-title">About Us</h2>
          <p class="text">
            PAWsig City is a pet grooming shop committed to providing quality, gentle, and professional grooming services for pets of all shapes and sizes. We believe that every pet deserves to feel clean, happy, and loved — and every pet owner deserves a hassle-free booking experience.
          </p>
          <div class="social-link-list">
            <a href="#" class="social-link"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" class="social-link"><i class="fa-brands fa-instagram"></i></a>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Services Section -->
<section class="service-section" id="service">
  <h2 class="section-title">Our Services</h2>
  <div class="section-content">
    <ul class="service-list">

      <li class="service-item">
        <a href="../homepage/login/loginform.php" class="service-link">
          <img src="./homepage/images/fullgroom.png" alt="Full Grooming" class="service-image" />
          <h3 class="name">FULL GROOMING</h3>
          <p class="text">
            Our Full Grooming package includes a warm bath, blow dry, nail trim, tooth brushing, ear cleaning, and a stylish haircut — everything your pet needs to look and feel their best.
          </p>
        </a>
      </li>

      <li class="service-item">
        <a href="./homepage/images/bathdry.png" class="service-link">
          <img src="./homepage/images/bathdry.png" alt="Spa Bath" class="service-image" />
          <h3 class="name">SPA BATH</h3>
          <p class="text">
            Pamper your pet with our luxurious Spa Bath, which features a nano bubble bath, gentle massage, blow dry, nail trim, tooth brushing, and ear cleaning. This package also includes a stylish haircut, odor eliminator, and paw moisturizer for a complete spa experience.
          </p>
        </a>
      </li>

      <li class="service-item">
        <a href="../homepage/login/loginform.php" class="service-link">
          <img src="./homepage/images/bnd.png" alt="Bath and Dry" class="service-image" />
          <h3 class="name">BATH AND DRY</h3>
          <p class="text">
            A quick and refreshing service that includes a full bath and gentle blow dry — ideal for keeping your pet clean between full grooming sessions.
          </p>
        </a>
      </li>

    </ul>
  </div>
</section>


    <!-- Gallery Section with Pagination -->
<section class="gallery-section" id="gallery">
  <h2 class="section-title">Gallery</h2>
  <div class="section-content">
    
    <!-- Gallery Container with Pagination Controls -->
    <div class="gallery-container">
      <!-- Gallery Grid -->
      <div class="gallery-grid">
        <ul class="gallery-list" id="gallery-list">
          <?php if ($result && pg_num_rows($result) > 0): ?>
          <?php while ($row = pg_fetch_assoc($result)): ?>
              <li class="gallery-item">
                <div class="gallery-image-container">
                  <img src="../pawsigcity/dashboard/gallery_images/<?php echo htmlspecialchars($row['image_path']); ?>"
                       alt="Gallery Image" 
                       class="gallery-image" />
                </div>
              </li>
            <?php endwhile; ?>
          <?php else: ?>
            <li class="gallery-item no-images">
              <p>No images found in the gallery.</p>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Numbered Pagination -->
      <div class="gallery-pagination">
        <?php if ($current_page > 1): ?>
          <a href="javascript:void(0)" onclick="loadGalleryPage(<?php echo $current_page - 1; ?>)" class="pagination-btn">&laquo;</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="javascript:void(0)"
             onclick="loadGalleryPage(<?php echo $i; ?>)"
             class="pagination-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
             <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
          <a href="javascript:void(0)" onclick="loadGalleryPage(<?php echo $current_page + 1; ?>)" class="pagination-btn">&raquo;</a>
        <?php endif; ?>
      </div>

    </div>


  </div>
</section>


    <!-- Contact Us Section -->
    <section class="contact-section" id="contact">
      <h2 class="section-title">Contact Us</h2>
      <div class="section-content">
        <ul class="contact-info-list">
          <li class="contact-info"><i class="fa-solid fa-location-crosshairs"></i><p>324 DR. SIXTO ANTONIO AVENUE., CANIOGAN, PASIG CITY</p></li>
          <li class="contact-info"><i class="fa-regular fa-envelope"></i><p>pawsigcity@gmail.com</p></li>
          <li class="contact-info"><i class="fa-solid fa-phone"></i><p>CP num</p></li>
          <li class="contact-info"><i class="fa-solid fa-clock"></i><p>9AM - 8PM ONLY</p></li>
          <li class="contact-info"><i class="fa-solid fa-calendar-check"></i><p>MONDAY TO SUNDAY</p></li>
          <li class="contact-info"><i class="fa-solid fa-globe"></i><p>PAWsig City</p></li>
        </ul>
        <form action="loginform.php" class="contact-form">
          <input type="text" placeholder="Your Name" class="form-input" required />
          <input type="email" placeholder="Email" class="form-input" required />
          <textarea placeholder="Your Message" class="form-input" required></textarea>
          <button type="submit" class="submit-button">Submit</button>
        </form>
      </div>
    </section>

  

  </main>

  <script>
  const sections = document.querySelectorAll("section[id]");
  const navLinks = document.querySelectorAll(".nav-link");

  // Highlight nav link based on scroll
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

  // Load gallery page with smooth fade transition
  function loadGalleryPage(page) {
    const galleryList = document.getElementById('gallery-list');
    galleryList.classList.add('fade-out');

    setTimeout(() => {
      fetch(`../dashboard/gallery_dashboard/gallery_load.php?page=${page}`)
        .then(response => response.json())
        .then(data => {
          // Update gallery content
          galleryList.innerHTML = data.html;

          // Update pagination controls
          document.querySelector('.gallery-pagination').innerHTML = data.pagination;

          // Update page info if exists
          const pageInfo = document.querySelector('.gallery-page-info');
          if (pageInfo) {
            pageInfo.innerHTML = `
              <span>Page ${data.current_page} of ${data.total_pages}</span>
              <span>(${data.total_images} total images)</span>
            `;
          }

          galleryList.classList.remove('fade-out');
        })
        .catch(error => {
          console.error('Error loading gallery page:', error);
        });
    }, 300); // Match this with CSS transition duration
  }

  // (Optional) Update next/prev arrows if you're using them
  function updateArrows(currentPage, totalPages) {
    const leftArrow = document.querySelector('.gallery-arrow-left');
    const rightArrow = document.querySelector('.gallery-arrow-right');

    if (leftArrow) {
      if (currentPage > 1) {
        leftArrow.onclick = () => loadGalleryPage(currentPage - 1);
        leftArrow.classList.remove('disabled');
      } else {
        leftArrow.onclick = null;
        leftArrow.classList.add('disabled');
      }
    }

    if (rightArrow) {
      if (currentPage < totalPages) {
        rightArrow.onclick = () => loadGalleryPage(currentPage + 1);
        rightArrow.classList.remove('disabled');
      } else {
        rightArrow.onclick = null;
        rightArrow.classList.add('disabled');
      }
    }
  }

  
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
