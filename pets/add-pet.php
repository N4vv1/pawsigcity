<?php
session_start();
require '../db.php';

// Display debug info
if (isset($_SESSION['debug'])) {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; font-family: monospace; font-size: 12px;'>";
    echo "<h3>🔍 DEBUG INFO:</h3>";
    echo "<pre>" . print_r($_SESSION['debug'], true) . "</pre>";
    echo "</div>";
    unset($_SESSION['debug']); // Clear after showing
}


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../homepage/login/loginform.php');
    exit;
}

$user_id = ($_SESSION['user_id']); // sanitize user_id

// Query to get user's pets
$query = "SELECT * FROM pets WHERE user_id = $1";
$pets = pg_query_params($conn, $query, [$user_id]);

if (!$pets) {
    echo "Query Error: " . pg_last_error($conn);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add New Pet</title>
  <link rel="stylesheet" href="../homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../homepage/images/pawsig2.png">
 <style>
  :root {
    --white: #ffffff;
    --dark: #252525;
    --primary: #A8E6CF;
    --primary-dark: #91dbc3;
    --secondary: #FFE29D;
    --accent: #FFB6B9;
    --gray: #ccc;
    --font: "Segoe UI", sans-serif;
    --radius: 14px;
    --transition: 0.3s ease;
  }

  body, html {
    margin: 0;
    padding: 0;
    min-height: 100%;
    overflow-y: auto;
    font-family: 'Poppins', sans-serif;
    background: #f5f5f5;
    color: var(--dark);
  }

  header {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 10;
    background-color: var(--primary);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
  }

  .back-button {
    position: absolute;
    top: 120px;
    left: 30px;
    background: none;
    color: var(--dark);
    padding: 6px;
    font-size: 20px;
    border: none;
    text-decoration: none;
    transition: color var(--transition);
  }

  .back-button:hover {
    color: var(--primary-dark);
  }

  .add-pet-container {
    width: 100%;
    padding: 160px 60px 40px;
    box-sizing: border-box;
  }

  .form-wrapper {
    width: 100%;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: 0 25px 45px rgba(0, 0, 0, 0.15);
    padding: 50px 60px;
    position: relative;
  }

  .form-wrapper::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 8px;
    width: 100%;
    border-radius: var(--radius) var(--radius) 0 0;
    background: linear-gradient(to right, var(--primary), var(--secondary), var(--accent));
  }

  .form-wrapper h2 {
    text-align: center;
    color: var(--dark);
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 30px;
    letter-spacing: 1px;
  }

  /* GRID FORM LAYOUT */
  .form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
  }

  .form-grid label {
    display: flex;
    flex-direction: column;
    font-weight: 600;
    font-size: 14px;
  }

  .form-grid input,
  .form-grid select,
  .form-grid textarea {
    width: 100%;
    padding: 10px 12px;
    margin-top: 6px;
    border: 2px solid var(--gray);
    border-radius: var(--radius);
    background: #fcfcfc;
    transition: all var(--transition);
    font-size: 14px;
    outline: none;
  }

  .form-grid input:hover,
  .form-grid select:hover,
  .form-grid textarea:hover {
    border-color: var(--primary);
    background-color: #f9fdfb;
    box-shadow: 0 2px 10px rgba(168, 230, 207, 0.2);
  }

  .form-grid input:focus,
  .form-grid select:focus,
  .form-grid textarea:focus {
    border-color: var(--primary-dark);
    box-shadow: 0 0 0 4px rgba(168, 230, 207, 0.3);
  }

  .form-grid input::placeholder,
  .form-grid textarea::placeholder {
    color: #aaa;
    font-style: italic;
  }

  /* TEXTAREAS SPAN FULL WIDTH */
  .form-grid textarea {
    grid-column: span 3;
    resize: vertical;
    min-height: 90px;
  }

  /* SECTION TITLE */
  .form-section-title {
    grid-column: 1 / -1;
    font-size: 18px;
    font-weight: bold;
    color: #00796B;
    background-color: #f0fdf9;
    padding: 8px 12px;
    border-left: 5px solid var(--primary);
    border-radius: 6px;
    margin-top: 20px;
  }

    /* REQUIRED FIELD INDICATOR */
  .required {
    color: #e74c3c;
    font-weight: bold;
    margin-left: 4px;
  } 

    /* SIZE GUIDE BOX */
  .size-guide {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #e8f5f9 0%, #f0f9ff 100%);
    border-left: 5px solid #2196F3;
    padding: 15px 20px;
    border-radius: 8px;
    margin: 10px 0;
  }

  .size-guide h4 {
    color: #1565C0;
    margin-bottom: 10px;
    font-size: 16px;
  }

  .size-guide ul {
    margin: 0;
    padding-left: 20px;
    color: #424242;
    font-size: 14px;
    line-height: 1.8;
  }

  /* SUBMIT BUTTON */
  .submit-button {
    grid-column: 1 / -1;
    padding: 14px;
    background: linear-gradient(135deg, var(--secondary), var(--accent));
    color: var(--dark);
    font-weight: bold;
    border: none;
    font-size: 16px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition);
    box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
    margin-top: 15px;
  }

  .submit-button:hover {
    transform: scale(1.03);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
  }

  /* FILE INPUT */
  input[type="file"]::file-selector-button {
    background-color: var(--primary);
    border: none;
    color: #333;
    padding: 8px 14px;
    border-radius: 6px;
    margin-right: 10px;
    cursor: pointer;
    transition: background var(--transition);
  }

  input[type="file"]::file-selector-button:hover {
    background-color: var(--primary-dark);
  }

  /* IMAGE PREVIEW */
  .preview-wrapper {
    margin-top: 8px;
  }

  .preview-wrapper img {
    display: none;
    max-width: 150px;
    max-height: 150px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  /* ALERT MESSAGES */
  .alert {
    grid-column: 1 / -1;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 5px solid #dc3545;
  }

  /* SUCCESS NOTIFICATION TOAST */
  .success-notification {
    position: fixed;
    top: 100px;
    right: 30px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 18px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(40, 167, 69, 0.3);
    font-weight: 600;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    animation: slideInRight 0.5s ease, slideOutRight 0.5s ease 4.5s;
    opacity: 0;
  }

  .success-notification i {
    font-size: 24px;
    animation: checkmark 0.6s ease;
  }

  @keyframes slideInRight {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  @keyframes slideOutRight {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }

  @keyframes checkmark {
    0% {
      transform: scale(0) rotate(-45deg);
    }
    50% {
      transform: scale(1.2) rotate(0deg);
    }
    100% {
      transform: scale(1) rotate(0deg);
    }
  }

  /* RESPONSIVE */
  @media (max-width: 1024px) {
    .form-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    .form-grid textarea,
    .form-section-title,
    .submit-button {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 768px) {
    .form-grid {
      grid-template-columns: 1fr;
    }
    .form-grid textarea,
    .form-section-title,
    .submit-button {
      grid-column: 1 / -1;
    }
    .back-button {
      top: 100px;
      left: 20px;
    }
    .form-wrapper {
      padding: 20px;
    }
    .add-pet-container {
      padding: 140px 20px 40px;
    }
    .success-notification {
      top: 80px;
      right: 20px;
      left: 20px;
      font-size: 14px;
      padding: 14px 18px;
    }
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
    
    <button class="hamburger" id="hamburger">
      <span></span>
      <span></span>
      <span></span>
    </button>

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

<div class="add-pet-container">
  
  <?php if (isset($_SESSION['success'])): ?>
    <div class="success-notification" id="successNotification">
      <i class="fas fa-check-circle"></i>
      <span><?= $_SESSION['success'] ?></span>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <div class="form-wrapper">
      <form method="POST" action="add-pet-handler.php" enctype="multipart/form-data">
        <div class="form-grid">
          
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
              <i class="fas fa-exclamation-circle"></i>
              <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
          <?php endif; ?>

          <span class="form-section-title"><i class="fas fa-paw"></i> Basic Information</span>

          <label>Name:<span class="required">*</span>
            <input type="text" name="name" placeholder="Enter pet name" required>
          </label>

          <label>Species:<span class="required">*</span>
            <select name="species" required>
              <option value="">Select Species</option>
              <option value="Dog">Dog</option>
              <option value="Cat">Cat</option>
            </select>
          </label>

          <label>Breed:<span class="required">*</span>
            <input type="text" name="breed" placeholder="Enter breed" required>
          </label>

          <label>Gender:<span class="required">*</span>
            <select name="gender" required>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </label>

          <label>Age:
            <input type="text" name="age" placeholder="Enter age (e.g., 2)">
          </label>

          <label>Birthday:
            <input type="date" name="birthday">
          </label>

          <label>Color:
            <input type="text" name="color" placeholder="Enter color">
          </label>

          <label>Photo:
            <input type="file" name="photo" accept="image/*" onchange="previewImage(event)">
            <div class="preview-wrapper">
              <img id="preview" src="#" alt="Selected Photo" />
            </div>
          </label>

          <span class="form-section-title"><i class="fas fa-ruler-combined"></i> Size & Weight</span>

          <div class="size-guide">
            <h4><i class="fas fa-info-circle"></i> Size Guidelines:</h4>
            <ul>
              <li><strong>Small:</strong> Under 10 kg (Chihuahua, Pomeranian, Shih Tzu, etc.)</li>
              <li><strong>Medium:</strong> 10-25 kg (Beagle, Cocker Spaniel, Bulldog, etc.)</li>
              <li><strong>Large:</strong> 25+ kg (Golden Retriever, German Shepherd, Labrador, etc.)</li>
            </ul>
          </div>

          <label>Size:<span class="required">*</span>
            <select name="size" id="size" required>
              <option value="">Select Size</option>
              <option value="Small">Small (Under 10 kg)</option>
              <option value="Medium">Medium (10-25 kg)</option>
              <option value="Large">Large (25+ kg)</option>
            </select>
          </label>

          <label>Weight (kg):<span class="required">*</span>
            <input type="number" name="weight" id="weight" step="0.1" min="0.1" placeholder="Enter weight in kg" required>
          </label>

          <span class="form-section-title"><i class="fas fa-heartbeat"></i> Health Information</span>

          <label>Allergies:
            <textarea name="allergies" placeholder="Any allergies?"></textarea>
          </label>

          <label>Medications:
            <textarea name="medications" placeholder="Current medications"></textarea>
          </label>

          <label>Medical Conditions:
            <textarea name="medical_conditions" placeholder="Ongoing conditions"></textarea>
          </label>

          <span class="form-section-title"><i class="fas fa-dog"></i> Behavior & Preferences</span>

          <label>Behavior Notes:
            <textarea name="behavior_notes" placeholder="Describe pet behavior"></textarea>
          </label>

          <label>Nail Trimming:
            <select name="nail_trimming">
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </label>

          <label>Haircut Style:
            <input type="text" name="haircut_style" placeholder="Preferred haircut">
          </label>

          <button type="submit" class="submit-button">
            <i class="fas fa-plus-circle"></i> Add Pet
          </button>
        </div>
      </form>
    </div>
  </div>

<script>
    function previewImage(event) {
      const input = event.target;
      const preview = document.getElementById('preview');

      if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);
      } else {
        preview.src = "#";
        preview.style.display = "none";
      }
    }

    // Weight validation based on size
    document.getElementById('size').addEventListener('change', function() {
      const weightInput = document.getElementById('weight');
      const size = this.value;
      
      if (size === 'Small') {
        weightInput.setAttribute('max', '10');
        weightInput.placeholder = 'Enter weight (under 10 kg)';
      } else if (size === 'Medium') {
        weightInput.setAttribute('min', '10');
        weightInput.setAttribute('max', '25');
        weightInput.placeholder = 'Enter weight (10-25 kg)';
      } else if (size === 'Large') {
        weightInput.setAttribute('min', '25');
        weightInput.removeAttribute('max');
        weightInput.placeholder = 'Enter weight (25+ kg)';
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

    navOverlay.addEventListener('click', function() {
      hamburger.classList.remove('active');
      navMenu.classList.remove('active');
      navOverlay.classList.remove('active');
      profileDropdown.classList.remove('active');
      document.body.style.overflow = '';
    });

    document.querySelectorAll('.nav-link:not(.profile-icon)').forEach(link => {
      link.addEventListener('click', function() {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        navOverlay.classList.remove('active');
        document.body.style.overflow = '';
      });
    });

    profileDropdown.addEventListener('click', function(e) {
      if (window.innerWidth <= 1024) {
        if (e.target.closest('.profile-icon')) {
          e.preventDefault();
          this.classList.toggle('active');
        }
      }
    });

    document.querySelectorAll('.dropdown-menu a').forEach(link => {
      link.addEventListener('click', function() {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
        navOverlay.classList.remove('active');
        profileDropdown.classList.remove('active');
        document.body.style.overflow = '';
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
  </script>

  <script>
    // Auto-hide success notification after 5 seconds
    const successNotification = document.getElementById('successNotification');
    if (successNotification) {
      setTimeout(() => {
        successNotification.remove();
      }, 5000);
    }
  </script>
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