<?php 
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../homepage/login/loginform.php');
    exit;
}

$user_id = intval($_SESSION['user_id']); // Sanitized

// Check if the user has pets
$petCheck = pg_query_params($conn, "SELECT COUNT(*) AS count FROM pets WHERE user_id = $1", [$user_id]);
if (!$petCheck) {
    die("Query failed: " . pg_last_error($conn));
}
$petCount = pg_fetch_assoc($petCheck)['count'];

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
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAWsig City | Homepage</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="./images/pawsig.png">

  <style>
    .fade-out {
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
    }
  /* ===== Hero Section Enhanced ===== */
.hero-section {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;             /* full viewport height */
  min-height: unset;         /* remove forced extra height */
  padding: 0 20px;           /* keep only side padding */
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  overflow: hidden;
}

/* subtle paw background */
.hero-section::before {
  content: "";
  position: absolute;
  inset: 0;
  background: url('../homepage/images/paw-bg.png') repeat;
  opacity: 0.07;
  pointer-events: none;
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
</head>
<body>
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
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link active"><i class="fas fa-home"></i>Home</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-info-circle"></i>About</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-concierge-bell"></i>Services</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-images"></i>Gallery</a></li>
      <li class="nav-item"><a href="../homepage/main.php" class="nav-link"><i class="fas fa-envelope"></i>Contact</a></li>
      <li class="nav-item dropdown" id="profile-dropdown">
        <a href="#" class="nav-link profile-icon">
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
        <a href="../homepage/login/loginform.php" class="button book-now"> Book Now</a>
        <a href="#contact" class="button contact-us"> Contact Us</a>
      </div>
    </div>
    <div class="hero-image-wrapper">
      <img src="./images/asd.png" alt="Happy Pet" class="hero-image" />
    </div>
  </div>
</section>



    <!-- About Section -->
    <section class="about-section" id="about">
      <div class="section-content">
        <div class="about-image-wrapper">
          <img src="./images/about.jpg" alt="About Our Shop" class="about-image" />
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
            <a href="../appointment/book-appointment.php" class="service-link">
              <img src="./images/fullgroom.png" alt="Full Grooming" class="service-image" />
              <h3 class="name">FULL GROOMING</h3>
              <p class="text">Includes bath, dry, haircut, nail trim, brushing, and more.</p>
            </a>
          </li>
          <li class="service-item">
            <a href="../pawsigcity/appointment/book-appointment.php" class="service-link">
              <img src="./images/bathdry.png" alt="Spa Bath" class="service-image" />
              <h3 class="name">SPA BATH</h3>
              <p class="text">Pamper with nano bubble bath, odor eliminator, and paw moisturizer.</p>
            </a>
          </li>
          <li class="service-item">
            <a href="../appointment/book-appointment.php" class="service-link">
              <img src="./images/bnd.png" alt="Bath and Dry" class="service-image" />
              <h3 class="name">BATH AND DRY</h3>
              <p class="text">Quick cleaning — perfect between full grooming sessions.</p>
            </a>
          </li>
        </ul>
      </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-section" id="gallery">
      <h2 class="section-title">Gallery</h2>
      <div class="section-content">
        <div class="gallery-container">
          <div class="gallery-grid">
            <ul class="gallery-list" id="gallery-list">
              <?php if ($result && pg_num_rows($result) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                  <li class="gallery-item">
                    <div class="gallery-image-container">
                      <img src="../pawsigcity/dashboard/gallery_images/<?php echo htmlspecialchars($row['image_path']); ?>" alt="Gallery Image" class="gallery-image" />
                    </div>
                  </li>
                <?php endwhile; ?>
              <?php else: ?>
                <li class="gallery-item no-images"><p>No images found in the gallery.</p></li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- Pagination -->
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

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
      <h2 class="section-title">Contact Us</h2>
      <div class="section-content">
        <ul class="contact-info-list">
          <li class="contact-info"><i class="fa-solid fa-location-crosshairs"></i><p>324 DR. SIXTO ANTONIO AVENUE, CANIOGAN, PASIG CITY</p></li>
          <li class="contact-info"><i class="fa-regular fa-envelope"></i><p>pawsigcity@gmail.com</p></li>
          <li class="contact-info"><i class="fa-solid fa-phone"></i><p>CP num</p></li>
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


  </main>

  <!-- JavaScript -->
  <script>
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

    function loadGalleryPage(page) {
      const galleryList = document.getElementById('gallery-list');
      galleryList.classList.add('fade-out');

      setTimeout(() => {
        fetch(`../dashboard/gallery_dashboard/gallery_load.php?page=${page}`)
          .then(response => response.json())
          .then(data => {
            galleryList.innerHTML = data.html;
            document.querySelector('.gallery-pagination').innerHTML = data.pagination;
            const pageInfo = document.querySelector('.gallery-page-info');
            if (pageInfo) {
              pageInfo.innerHTML = `
                <span>Page ${data.current_page} of ${data.total_pages}</span>
                <span>(${data.total_images} total images)</span>
              `;
            }
            galleryList.classList.remove('fade-out');
          })
          .catch(error => console.error('Error loading gallery page:', error));
      }, 300);
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
