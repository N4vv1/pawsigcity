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
  <title>PAWSig City</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="icon" type="image/png" href="./images/Logo.jpg">

  <style>
    .fade-out {
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
    }
  </style>
</head>
<body>
  <!-- Navbar Header -->
  <header>
    <nav class="navbar section-content">
      <a href="#" class="navbar-logo">
        <img src="../homepage/images/Logo.jpg" alt="Logo" class="icon" />
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
            <li><a href="../../Purrfect-paws/ai/chatbot/index.html">Help Center</a></li>
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
      <div class="section-content">
        <div class="hero-details">
          <h2 class="title">PAWSig City</h2>
          <h3 class="subtitle">Best Grooming in the Town of Caniogan</h3>
          <p class="description">From Paw-scheduling to Tail-wagging — We’ve Got It Covered.</p>
          <div class="buttons">
            <a href="../homepage/login/loginform.php" class="button book-now">Book Now</a>
            <a href="#contact" class="button contact-us">Contact Us</a>
          </div>
        </div>
        <div class="hero-image-wrapper">
          <img src="../../Purrfect-paws/homepage/images/paw.png" alt="Hero" class="image-hero" />
        </div>
      </div>
    </section>


    <!-- About Section -->
    <section class="about-section" id="about">
      <div class="section-content">
        <div class="about-image-wrapper">
          <img src="../homepage/images/about.jpg" alt="About Our Shop" class="about-image" />
        </div>
        <div class="about-details">
          <h2 class="section-title">About Us</h2>
          <p class="text">
            Purrfect Paws is a pet grooming shop committed to providing quality, gentle, and professional grooming services for pets of all shapes and sizes. We believe that every pet deserves to feel clean, happy, and loved — and every pet owner deserves a hassle-free booking experience.
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
              <img src="../homepage/images/fullgroom.png" alt="Full Grooming" class="service-image" />
              <h3 class="name">FULL GROOMING</h3>
              <p class="text">Includes bath, dry, haircut, nail trim, brushing, and more.</p>
            </a>
          </li>
          <li class="service-item">
            <a href="../appointment/book-appointment.php" class="service-link">
              <img src="../homepage/images/bathdry.png" alt="Spa Bath" class="service-image" />
              <h3 class="name">SPA BATH</h3>
              <p class="text">Pamper with nano bubble bath, odor eliminator, and paw moisturizer.</p>
            </a>
          </li>
          <li class="service-item">
            <a href="../appointment/book-appointment.php" class="service-link">
              <img src="../homepage/images/bnd.png" alt="Bath and Dry" class="service-image" />
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
                      <img src="../dashboard/gallery_images/<?php echo htmlspecialchars($row['image_path']); ?>" alt="Gallery Image" class="gallery-image" />
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
          <li class="contact-info"><i class="fa-regular fa-envelope"></i><p>purrfectpaws@gmail.com</p></li>
          <li class="contact-info"><i class="fa-solid fa-phone"></i><p>CP num</p></li>
          <li class="contact-info"><i class="fa-solid fa-clock"></i><p>9AM - 8PM ONLY</p></li>
          <li class="contact-info"><i class="fa-solid fa-calendar-check"></i><p>MONDAY TO SUNDAY</p></li>
          <li class="contact-info"><i class="fa-solid fa-globe"></i><p>Purrfect Paws</p></li>
        </ul>
        <form action="#" class="contact-form">
          <input type="text" placeholder="Your Name" class="form-input" required />
          <input type="email" placeholder="Email" class="form-input" required />
          <textarea placeholder="Your Message" class="form-input" required></textarea>
          <button type="submit" class="submit-button">Submit</button>
        </form>
      </div>
    </section>

     <!-- Footer Section -->
<footer class="footer-section">
  <div class="section-content footer-container">
    <div class="footer-logo">
      <img src="../homepage/images/Logo.jpg" alt="Purrfect Paws Logo" />
      <h3>Purrfect Paws</h3>
    </div>
    <div class="footer-links">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#service">Services</a></li>
        <li><a href="#gallery">Gallery</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
    </div>
    <div class="footer-contact">
      <h4>Contact Us</h4>
      <p>324 Dr. Sixto Antonio Ave., Caniogan, Pasig City</p>
      <p>Email: purrfectpaws@gmail.com</p>
      <p>Phone: 09XX-XXX-XXXX</p>
    </div>
    <div class="footer-socials">
      <h4>Follow Us</h4>
      <div class="social-icons">
        <a href="#"><i class="fa-brands fa-facebook"></i></a>
        <a href="#"><i class="fa-brands fa-instagram"></i></a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2025 Purrfect Paws. All rights reserved.</p>
  </div>
</footer>

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
