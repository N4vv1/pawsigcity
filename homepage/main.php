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
  <title>PAWSig City - Homepage</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="../icons/pawsig.png">

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
  width: 350px;
  max-width: 100%;
  animation: float 4s ease-in-out infinite;
}

/* Floating animation */
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-15px); }
}
</style>
</head>
<body>
  <!-- Navbar Header -->
  <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../icons/pawsig.png" alt="Logo" class="icon" />
      </a>
      <ul class="nav-menu">
        <li class="nav-item"><a href="#home" class="nav-link active">Home</a></li>
        <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
        <li class="nav-item"><a href="#service" class="nav-link">Services</a></li>
        <li class="nav-item"><a href="#gallery" class="nav-link">Gallery</a></li>
        <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link profile-icon">
            <i class="fas fa-user-circle"></i>
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
      <img src="./icons/home.png" alt="Happy Pet" class="hero-image" />
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
  </script>
</body>
</html>
