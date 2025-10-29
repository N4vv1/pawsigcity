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
  <!-- Navbar Header with Burger Menu -->
  <header>
    <nav class="navbar section-content">
      <div class="nav-logo">
        <img src="./homepage/images/pawsig.png" alt="Logo" class="icon" />
        <span class="logo-text">PAWsig City</span>
      </div>

      <!-- Burger Menu Icon -->
      <div class="burger-menu" id="burger-menu">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <!-- Navigation Menu -->
      <ul class="nav-menu" id="nav-menu">
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
            <li><a href="./homepage/login/loginform.php">Login</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <!-- Hero Section -->
  <main>
    <section class="hero-section" id="home">
      <div class="section-content">
        <div class="hero-details">
          <h1 class="title">Welcome to PAWsig City</h1>
          <h3 class="subtitle">Where Grooming Meets Love & Care</h3>
          <p class="description">
            From Paw-scheduling to Tail-wagging — We've Got It Covered.  
            Treat your pets with the best grooming experience in Pasig City.
          </p>
          <div class="buttons">
            <a href="./homepage/login/loginform.php" class="button">Book Now</a>
            <a href="#contact" class="contact-us">Contact Us</a>
          </div>
        </div>
        <div class="hero-image-wrapper">
          <img src="./homepage/images/asd.png" alt="Happy Pet" />
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
            <a href="./homepage/login/loginform.php" style="text-decoration: none; color: inherit; display: contents;">
              <img src="./homepage/images/fullgroom.png" alt="Full Grooming" class="service-image" />
              <h3 class="name">FULL GROOMING</h3>
              <p class="text">
                Our Full Grooming package includes a warm bath, blow dry, nail trim, tooth brushing, ear cleaning, and a stylish haircut — everything your pet needs to look and feel their best.
              </p>
            </a>
          </li>

          <li class="service-item">
            <a href="./homepage/login/loginform.php" style="text-decoration: none; color: inherit; display: contents;">
              <img src="./homepage/images/bathdry.png" alt="Spa Bath" class="service-image" />
              <h3 class="name">SPA BATH</h3>
              <p class="text">
                Pamper your pet with our luxurious Spa Bath, which features a nano bubble bath, gentle massage, blow dry, nail trim, tooth brushing, and ear cleaning. This package also includes a stylish haircut, odor eliminator, and paw moisturizer for a complete spa experience.
              </p>
            </a>
          </li>

          <li class="service-item">
            <a href="./homepage/login/loginform.php" style="text-decoration: none; color: inherit; display: contents;">
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

    <!-- Gallery Section with PHP -->
    <section class="gallery-section" id="gallery">
      <h2 class="section-title">Gallery</h2>
      <div class="section-content">
        <ul class="gallery-list" id="gallery-list">
          <?php if ($result && pg_num_rows($result) > 0): ?>
            <?php while ($row = pg_fetch_assoc($result)): ?>
              <li class="gallery-item">
                <img src="../pawsigcity/dashboard/gallery_images/<?php echo htmlspecialchars($row['image_path']); ?>"
                     alt="Gallery Image" 
                     class="gallery-image" />
              </li>
            <?php endwhile; ?>
          <?php else: ?>
            <li class="gallery-item" style="grid-column: 1/-1; text-align: center; padding: 40px;">
              <p>No images found in the gallery.</p>
            </li>
          <?php endif; ?>
        </ul>

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
    </section>

    <!-- Contact Us Section -->
    <section class="contact-section" id="contact">
      <h2 class="section-title">Contact Us</h2>
      <div class="section-content">
        <div class="contact-info-list">
          <div class="contact-info">
            <i class="fa-solid fa-location-crosshairs"></i>
            <p>324 DR. SIXTO ANTONIO AVENUE., CANIOGAN, PASIG CITY</p>
          </div>
          <div class="contact-info">
            <i class="fa-regular fa-envelope"></i>
            <p>pawsigcity@gmail.com</p>
          </div>
          <div class="contact-info">
            <i class="fa-solid fa-phone"></i>
            <p>CP num</p>
          </div>
          <div class="contact-info">
            <i class="fa-solid fa-clock"></i>
            <p>9AM - 8PM ONLY</p>
          </div>
          <div class="contact-info">
            <i class="fa-solid fa-calendar-check"></i>
            <p>MONDAY TO SUNDAY</p>
          </div>
          <div class="contact-info">
            <i class="fa-solid fa-globe"></i>
            <p>PAWsig City</p>
          </div>
        </div>

        <form action="loginform.php" class="contact-form">
          <input type="text" placeholder="Your Name" class="form-input" required />
          <input type="email" placeholder="Email" class="form-input" required />
          <textarea placeholder="Your Message" class="form-input" required></textarea>
          <button type="submit" class="submit-button">Submit</button>
        </form>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="footer-section">
    <div class="footer-container section-content">
      <div class="footer-logo">
        <img src="./homepage/images/pawsig.png" alt="PAWsig City Logo" />
        <h3>PAWsig City</h3>
      </div>

      <div class="footer-links">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="#home">Home</a></li>
          <li><a href="#about">About Us</a></li>
          <li><a href="#service">Services</a></li>
          <li><a href="#gallery">Gallery</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </div>

      <div class="footer-contact">
        <h4>Contact Info</h4>
        <p>324 DR. SIXTO ANTONIO AVENUE</p>
        <p>CANIOGAN, PASIG CITY</p>
        <p>pawsigcity@gmail.com</p>
      </div>

      <div class="footer-socials">
        <h4>Follow Us</h4>
        <div class="social-icons">
          <a href="#"><i class="fa-brands fa-facebook"></i></a>
          <a href="#"><i class="fa-brands fa-instagram"></i></a>
          <a href="#"><i class="fa-brands fa-twitter"></i></a>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>&copy; 2025 PAWsig City. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    // Burger Menu Toggle
    const burger = document.getElementById('burger-menu');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    burger.addEventListener('click', () => {
      burger.classList.toggle('active');
      navMenu.classList.toggle('active');
    });

    // Close menu when clicking a link
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        burger.classList.remove('active');
        navMenu.classList.remove('active');
      });
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!burger.contains(e.target) && !navMenu.contains(e.target)) {
        burger.classList.remove('active');
        navMenu.classList.remove('active');
      }
    });

    // Active Nav Link on Scroll
    const sections = document.querySelectorAll("section[id]");

    window.addEventListener("scroll", () => {
      let scrollY = window.pageYOffset + 150;

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

    // Gallery Page Loading with fade transition
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
            galleryList.classList.remove('fade-out');
          });
      }, 300); // Match this with CSS transition duration
    }

    // Smooth Scroll for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  </script>
</body>
</html>