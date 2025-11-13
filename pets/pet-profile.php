<?php
session_start();
require '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../homepage/login/loginform.php');
    exit;
}

$user_id = intval($_SESSION['user_id']); // sanitize

// Fetch the logged-in user's info
$user_result = pg_query_params($conn, "SELECT * FROM users WHERE user_id = $1", [$user_id]);
if ($user_result && pg_num_rows($user_result) > 0) {
    $user = pg_fetch_assoc($user_result);
} else {
    $user = null;
}

// Query to get user's pets
$pets = pg_query_params($conn, "SELECT * FROM pets WHERE user_id = $1", [$user_id]);
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
  <title>PAWsig City | Pet Profile</title>
  <link rel="stylesheet" href="../homepage/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../homepage/images/pawsig2.png">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
      margin: 0;
      padding: 0;
      min-height: 100vh;
      padding-top: 140px;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
    }

    .container {
      max-width: 1600px;
      margin: 0 auto;
      padding: 20px 20px;
    }

    .main-grid {
      display: grid;
      grid-template-columns: 480px 1fr;
      gap: 20px;
      align-items: start;
    }

    /* Sidebar - User Account */
    .sidebar {
      position: sticky;
      top: 95px;
    }

    .user-card {
      background: white;
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
      border-top: 4px solid #A8E6CF;
    }

    .user-card h2 {
      font-size: 18px;
      margin: 0 0 18px 0;
      color: #2a2a2a;
      font-weight: 600;
      text-align: center;
      padding-bottom: 12px;
      border-bottom: 2px solid #f0f0f0;
    }

    .user-info h3 {
      font-size: 20px;
      margin: 0 0 12px 0;
      color: #333;
      font-weight: 600;
      text-align: center;
    }

    .user-info p {
      margin: 8px 0;
      color: #666;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-info p i {
      color: #A8E6CF;
      width: 18px;
    }

    .edit-btn {
      display: block;
      width: 100%;
      text-align: center;
      background: #A8E6CF;
      color: #333;
      padding: 10px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      margin-top: 15px;
      transition: all 0.2s;
      font-size: 14px;
      border: none;
      cursor: pointer;
    }

    .edit-btn:hover {
      background: #91dbc3;
      transform: translateY(-1px);
    }

    /* Main Content - Pets */
    .main-content h1 {
      font-size: 20px;
      margin: 0 0 15px 0;
      color: #2a2a2a;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background: white;
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
      border-top: 4px solid #A8E6CF;
    }

    .main-content h1 i {
      color: #2a2a2a;
      font-size: 22px;
    }

    .pet-card {
      background: white;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 15px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
      border-left: 4px solid #FFE29D;
      transition: all 0.3s;
    }

    .pet-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 18px rgba(0,0,0,0.12);
    }

    .pet-header {
      display: grid;
      grid-template-columns: 90px 1fr auto;
      gap: 18px;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f8f9fa;
    }

    .pet-avatar {
      width: 90px;
      height: 90px;
      border-radius: 12px;
      object-fit: cover;
      border: 3px solid #f0f0f0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .pet-info h3 {
      margin: 0 0 8px 0;
      font-size: 22px;
      color: #333;
      font-weight: 600;
    }

    .pet-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      color: #666;
      font-size: 13px;
    }

    .pet-meta span {
      display: flex;
      align-items: center;
      gap: 5px;
      background: #f8f9fa;
      padding: 4px 10px;
      border-radius: 6px;
    }

    .pet-meta i {
      color: #A8E6CF;
      font-size: 12px;
    }

    .pet-actions {
      display: flex;
      gap: 8px;
      flex-direction: column;
    }

    .btn-edit {
      background: #ffd166;
      color: #333;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      font-size: 13px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      white-space: nowrap;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-edit:hover {
      background: #ffbe3d;
      transform: translateY(-1px);
    }

    .btn-delete {
      background: #ff6b6b;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 13px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-delete:hover {
      background: #ee5a52;
      transform: translateY(-1px);
    }

    /* Tabs */
    .tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
      padding-bottom: 8px;
    }

    .tab {
      padding: 8px 16px;
      background: transparent;
      border: none;
      color: #666;
      font-weight: 500;
      cursor: pointer;
      border-radius: 6px;
      transition: all 0.2s;
      font-size: 13px;
    }

    .tab:hover {
      background: #f8f9fa;
      color: #333;
    }

    .tab.active {
      background: #A8E6CF;
      color: #333;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .info-row {
      padding: 10px 0;
      border-bottom: 1px solid #f0f0f0;
      display: grid;
      grid-template-columns: 140px 1fr;
      gap: 15px;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-row strong {
      color: #555;
      font-weight: 500;
      font-size: 13px;
    }

    .info-row span {
      color: #333;
      font-size: 13px;
    }

    /* Pet Edit Form - Same styling as User Edit Form */
    .pet-card .edit-form {
      display: none;
      background: #f8f9fa;
      padding: 20px;
      border-radius: 12px;
      margin-top: 18px;
      border: 2px solid #e0e0e0;
    }

    .pet-card .edit-form.show {
      display: block;
    }

    .pet-card .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .pet-card .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .pet-card .form-group.full-width {
      grid-column: 1 / -1;
    }

    .pet-card .form-group label {
      font-size: 13px;
      font-weight: 500;
      color: #555;
    }

    .pet-card .form-group input,
    .pet-card .form-group select {
      padding: 10px 12px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      transition: border 0.2s;
    }

    .pet-card .form-group input:focus,
    .pet-card .form-group select:focus {
      outline: none;
      border-color: #A8E6CF;
    }

    .pet-card .form-actions {
      grid-column: 1 / -1;
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .pet-card .btn-save {
      flex: 1;
      background: #A8E6CF;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .pet-card .btn-save:hover {
      background: #91dbc3;
    }

    .pet-card .btn-cancel {
      flex: 1;
      background: #e0e0e0;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .pet-card .btn-cancel:hover {
      background: #d0d0d0;
    }

    /* Edit Form */
    .edit-form {
      display: none;
      background: #f8f9fa;
      padding: 20px;
      border-radius: 12px;
      margin-top: 18px;
      border: 2px solid #e0e0e0;
    }

    .edit-form.show {
      display: block;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-group label {
      font-size: 13px;
      font-weight: 500;
      color: #555;
    }

    .form-group input,
    .form-group select {
      padding: 10px 12px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      transition: border 0.2s;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #A8E6CF;
    }

    .form-actions {
      grid-column: 1 / -1;
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .btn-save {
      flex: 1;
      background: #A8E6CF;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .btn-save:hover {
      background: #91dbc3;
    }

    .btn-cancel {
      flex: 1;
      background: #e0e0e0;
      color: #333;
      padding: 11px 20px;
      border-radius: 8px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 14px;
    }

    .btn-cancel:hover {
      background: #d0d0d0;
    }

    .empty-state {
      text-align: center;
      padding: 50px 20px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    }

    .empty-state i {
      font-size: 60px;
      color: #A8E6CF;
      opacity: 0.5;
      margin-bottom: 15px;
    }

    .empty-state p {
      color: #666;
      margin-bottom: 18px;
      font-size: 15px;
    }

    .empty-state a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #A8E6CF;
      color: #333;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s;
    }

    .empty-state a:hover {
      background: #91dbc3;
      transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .main-grid {
        grid-template-columns: 1fr;
      }

      .sidebar {
        position: static;
      }

      .user-card {
        margin-bottom: 20px;
      }
    }

    @media (max-width: 768px) {
      .container {
        padding: 10px 15px;
      }

      .pet-header {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 12px;
      }

      .pet-avatar {
        margin: 0 auto;
      }

      .pet-actions {
        flex-direction: row;
        width: 100%;
      }

      .info-row {
        grid-template-columns: 1fr;
        gap: 5px;
      }

      .pet-actions form {
        flex: 1;
      }

      .btn-edit,
      .btn-delete {
        width: 100%;
      }

      .form-grid {
        grid-template-columns: 1fr;
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

/* ============================================
   RESPONSIVE DESIGN - ALL DEVICES
   ============================================ */

/* Large Desktop (1400px+) */
@media (min-width: 1400px) {
  .container {
    max-width: 1800px;
  }
  
  .main-grid {
    grid-template-columns: 500px 1fr;
    gap: 30px;
  }
}

/* Standard Desktop (1025px - 1399px) */
@media (min-width: 1025px) and (max-width: 1399px) {
  .container {
    max-width: 1400px;
  }
  
  .main-grid {
    grid-template-columns: 420px 1fr;
    gap: 20px;
  }
}

/* Tablet Landscape (769px - 1024px) */
@media (min-width: 769px) and (max-width: 1024px) {
  body {
    padding-top: 120px;
  }

  .container {
    padding: 15px 20px;
  }

  .main-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .sidebar {
    position: static;
  }

  .user-card {
    margin-bottom: 20px;
  }

  .pet-header {
    grid-template-columns: 90px 1fr auto;
    gap: 15px;
  }

  .pet-meta {
    flex-wrap: wrap;
    gap: 8px;
  }

  .pet-meta span {
    font-size: 12px;
    padding: 3px 8px;
  }

  .info-row {
    grid-template-columns: 160px 1fr;
    gap: 12px;
  }

  .form-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
}

/* Tablet Portrait (601px - 768px) */
@media (min-width: 601px) and (max-width: 768px) {
  body {
    padding-top: 110px;
  }

  .container {
    padding: 12px 18px;
  }

  .main-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .sidebar {
    position: static;
  }

  .user-card {
    margin-bottom: 18px;
  }

  .pet-header {
    grid-template-columns: 80px 1fr;
    gap: 15px;
  }

  .pet-avatar {
    width: 80px;
    height: 80px;
  }

  .pet-info h3 {
    font-size: 20px;
  }

  .pet-meta {
    gap: 8px;
  }

  .pet-meta span {
    font-size: 12px;
    padding: 3px 8px;
  }

  .pet-actions {
    grid-column: 1 / -1;
    flex-direction: row;
    gap: 10px;
    margin-top: 10px;
  }

  .pet-actions form {
    flex: 1;
  }

  .btn-edit,
  .btn-delete {
    width: 100%;
    padding: 10px 12px;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 6px;
    padding: 12px 0;
  }

  .info-row strong {
    color: #A8E6CF;
    font-weight: 600;
  }

  .tabs {
    gap: 6px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .tab {
    padding: 10px 14px;
    font-size: 12px;
    white-space: nowrap;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .edit-form {
    padding: 18px;
  }
}

/* Mobile Landscape (481px - 600px) */
@media (min-width: 481px) and (max-width: 600px) {
  body {
    padding-top: 100px;
  }

  .container {
    padding: 10px 15px;
  }

  .main-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .sidebar {
    position: static;
  }

  .user-card {
    padding: 20px;
    margin-bottom: 15px;
  }

  .user-card h2 {
    font-size: 17px;
  }

  .user-info h3 {
    font-size: 18px;
  }

  .user-info p {
    font-size: 13px;
  }

  .main-content h1 {
    font-size: 18px;
    padding: 18px;
  }

  .pet-card {
    padding: 18px;
    margin-bottom: 15px;
  }

  .pet-header {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 12px;
  }

  .pet-avatar {
    width: 90px;
    height: 90px;
    margin: 0 auto;
  }

  .pet-info h3 {
    font-size: 19px;
    margin-bottom: 10px;
  }

  .pet-meta {
    justify-content: center;
    gap: 8px;
  }

  .pet-meta span {
    font-size: 11px;
    padding: 3px 8px;
  }

  .pet-actions {
    flex-direction: row;
    gap: 10px;
    width: 100%;
  }

  .pet-actions form {
    flex: 1;
  }

  .btn-edit,
  .btn-delete {
    width: 100%;
    padding: 10px 12px;
    font-size: 12px;
  }

  .tabs {
    gap: 5px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 5px;
  }

  .tab {
    padding: 10px 12px;
    font-size: 12px;
    white-space: nowrap;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 5px;
    padding: 10px 0;
  }

  .info-row strong {
    color: #A8E6CF;
    font-weight: 600;
    font-size: 12px;
  }

  .info-row span {
    font-size: 12px;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .form-group label {
    font-size: 12px;
  }

  .form-group input,
  .form-group select {
    padding: 9px 11px;
    font-size: 12px;
  }

  .edit-form {
    padding: 16px;
  }

  .btn-save,
  .btn-cancel {
    padding: 10px 16px;
    font-size: 13px;
  }

  .empty-state {
    padding: 40px 15px;
  }

  .empty-state i {
    font-size: 50px;
  }

  .empty-state p {
    font-size: 14px;
  }
}

/* Mobile Portrait (320px - 480px) */
@media (max-width: 480px) {
  body {
    padding-top: 90px;
  }

  .container {
    padding: 8px 12px;
  }

  .main-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .sidebar {
    position: static;
  }

  .user-card {
    padding: 18px;
    margin-bottom: 12px;
    border-radius: 14px;
  }

  .user-card h2 {
    font-size: 16px;
    margin-bottom: 15px;
  }

  .user-info h3 {
    font-size: 17px;
    margin-bottom: 10px;
  }

  .user-info p {
    font-size: 12px;
    margin: 6px 0;
  }

  .user-info p i {
    width: 16px;
    font-size: 12px;
  }

  .edit-btn {
    padding: 9px 16px;
    font-size: 13px;
    margin-top: 12px;
  }

  .main-content h1 {
    font-size: 17px;
    padding: 16px;
    margin-bottom: 12px;
  }

  .main-content h1 i {
    font-size: 20px;
  }

  .pet-card {
    padding: 16px;
    margin-bottom: 12px;
    border-radius: 14px;
  }

  .pet-header {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 12px;
    padding-bottom: 12px;
  }

  .pet-avatar {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    border-radius: 10px;
  }

  .pet-info h3 {
    font-size: 18px;
    margin-bottom: 8px;
  }

  .pet-meta {
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
  }

  .pet-meta span {
    font-size: 11px;
    padding: 3px 7px;
  }

  .pet-meta i {
    font-size: 10px;
  }

  .pet-actions {
    flex-direction: row;
    gap: 8px;
    width: 100%;
  }

  .pet-actions form {
    flex: 1;
  }

  .btn-edit,
  .btn-delete {
    width: 100%;
    padding: 9px 10px;
    font-size: 12px;
    border-radius: 6px;
  }

  .btn-edit i,
  .btn-delete i {
    font-size: 11px;
  }

  .tabs {
    gap: 5px;
    margin-bottom: 12px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }

  .tabs::-webkit-scrollbar {
    display: none;
  }

  .tab {
    padding: 9px 12px;
    font-size: 11px;
    white-space: nowrap;
    border-radius: 5px;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 4px;
    padding: 9px 0;
  }

  .info-row strong {
    color: #A8E6CF;
    font-weight: 600;
    font-size: 11px;
  }

  .info-row span {
    font-size: 12px;
    line-height: 1.5;
  }

  .edit-form {
    padding: 14px;
    border-radius: 10px;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .form-group {
    gap: 5px;
  }

  .form-group label {
    font-size: 11px;
  }

  .form-group input,
  .form-group select {
    padding: 8px 10px;
    font-size: 12px;
    border-radius: 6px;
  }

  .form-actions {
    gap: 8px;
    margin-top: 8px;
  }

  .btn-save,
  .btn-cancel {
    padding: 9px 14px;
    font-size: 12px;
    border-radius: 6px;
  }

  .empty-state {
    padding: 35px 12px;
    border-radius: 14px;
  }

  .empty-state i {
    font-size: 45px;
    margin-bottom: 12px;
  }

  .empty-state p {
    font-size: 13px;
    margin-bottom: 15px;
  }

  .empty-state a {
    padding: 10px 20px;
    font-size: 13px;
    border-radius: 6px;
  }
}

/* Extra Small Mobile (320px) */
@media (max-width: 360px) {
  .pet-meta span {
    font-size: 10px;
    padding: 2px 6px;
  }

  .btn-edit,
  .btn-delete {
    padding: 8px 8px;
    font-size: 11px;
  }

  .tab {
    padding: 8px 10px;
    font-size: 10px;
  }

  .info-row strong {
    font-size: 10px;
  }

  .info-row span {
    font-size: 11px;
  }
}

/* Landscape Orientation Optimization */
@media (max-height: 600px) and (orientation: landscape) {
  body {
    padding-top: 80px;
  }

  .sidebar {
    position: static;
  }

  .user-card {
    margin-bottom: 15px;
  }

  .pet-card {
    margin-bottom: 15px;
  }

  .edit-form {
    max-height: 400px;
    overflow-y: auto;
  }
}

/* Touch Device Improvements */
@media (hover: none) and (pointer: coarse) {
  .btn-edit,
  .btn-delete,
  .edit-btn,
  .tab {
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .pet-actions {
    gap: 12px;
  }

  .tabs {
    padding-bottom: 8px;
  }
}

/* Print Styles */
@media print {
  body {
    background: white;
    padding-top: 0;
  }

  header,
  .hamburger,
  .nav-overlay,
  .pet-actions,
  .edit-btn,
  .edit-form {
    display: none !important;
  }

  .container {
    max-width: 100%;
    padding: 0;
  }

  .main-grid {
    grid-template-columns: 1fr;
  }

  .pet-card {
    page-break-inside: avoid;
    box-shadow: none;
    border: 1px solid #ddd;
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
          <li><a href="../ai/templates/index.html">Help Center</a></li>
          <li><a href="../homepage/logout/logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </nav>
</header>

  <div class="container">
    <div class="main-grid">
      <!-- Sidebar - User Account -->
      <aside class="sidebar">
        <div class="user-card">
          <h2></i> My Account</h2>
          <?php if (!empty($user)): ?>
            <div class="user-info">
              <h3><?= htmlspecialchars($user['first_name'] ?? '') ?> <?= htmlspecialchars($user['last_name'] ?? '') ?></h3>
              <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email'] ?? '') ?></p>
              <p><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone'] ?? '') ?></p>
              <p><i class="fas fa-id-badge"></i> <?= htmlspecialchars($user['role'] ?? '') ?></p>
              <button class="edit-btn" onclick="toggleUserEdit()">
                <i class="fas fa-edit"></i> Edit Account
              </button>
            </div>

            <!-- User Edit Form -->
            <div id="user-edit-form" class="edit-form">
              <form action="user-edit-handler.php" method="POST">
                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                <div class="form-grid">
                  <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name']) ?>">
                  </div>
                  <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                  </div>
                  <div class="form-group full-width">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                  </div>
                  <div class="form-group full-width">
                    <label>New Password (optional)</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current">
                  </div>
                  <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="button" class="btn-cancel" onclick="toggleUserEdit()">Cancel</button>
                  </div>
                </div>
              </form>
            </div>
          <?php else: ?>
            <p>User information not found.</p>
          <?php endif; ?>
        </div>
      </aside>

      <!-- Main Content - Pets -->
      <main class="main-content">

        <?php if (pg_num_rows($pets) > 0): ?>
          <?php while ($pet = pg_fetch_assoc($pets)):
              $pet_id = $pet['pet_id'];
              
              // Get health info
              $health_result = pg_query_params($conn, "SELECT * FROM health_info WHERE pet_id = $1", [$pet_id]);
              $health = pg_fetch_assoc($health_result);

              // Get behavior preferences
              $behavior_result = pg_query_params($conn, "SELECT * FROM behavior_preferences WHERE pet_id = $1", [$pet_id]);
              $behavior = pg_fetch_assoc($behavior_result);
          ?>

 <div class="pet-card">
  <div class="pet-header">
    <img src="<?= htmlspecialchars($pet['photo_url']) ?>" 
        alt="<?= htmlspecialchars($pet['name']) ?>"
        class="pet-avatar"
        onerror="this.onerror=null;this.src='../uploads/default.jpg';">
    <div class="pet-info">
      <h3><?= htmlspecialchars($pet['name']) ?></h3>
      <div class="pet-meta">
        <span><i class="fas fa-dog"></i> <?= htmlspecialchars($pet['breed']) ?></span>
        <span><i class="fas fa-calendar"></i> <?= htmlspecialchars($pet['age']) ?> years</span>
        <span><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($pet['gender']) ?></span>
        <span><i class="fas fa-palette"></i> <?= htmlspecialchars($pet['color']) ?></span>
        <span><i class="fas fa-birthday-cake"></i> <?= htmlspecialchars($pet['birthday']) ?></span>
        <?php if (!empty($pet['size'])): ?>
          <span><i class="fas fa-ruler-combined"></i> <?= htmlspecialchars($pet['size']) ?></span>
        <?php endif; ?>
        <?php if (!empty($pet['weight'])): ?>
          <span><i class="fas fa-weight"></i> <?= htmlspecialchars($pet['weight']) ?> kg</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="pet-actions">
      <button class="btn-edit" onclick="togglePetEdit(<?= $pet_id ?>)">
        <i class="fas fa-edit"></i> Edit
      </button>
      <form action="delete-pet.php" method="POST" onsubmit="return confirm('Delete this pet?');">
        <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
        <button type="submit" class="btn-delete">
          <i class="fas fa-trash"></i> Delete
        </button>
      </form>
    </div>
  </div>

  <!-- Pet Edit Form -->
  <div id="pet-edit-<?= $pet_id ?>" class="edit-form">
    <form action="pet-edit-handler.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="pet_id" value="<?= $pet_id ?>">
      
      <!-- Basic Information Section -->
      <h4 style="margin: 0 0 15px 0; color: #2a2a2a; font-size: 15px; font-weight: 600; border-bottom: 2px solid #A8E6CF; padding-bottom: 8px;">
        <i class="fas fa-paw"></i> Basic Information
      </h4>
      <div class="form-grid">
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Breed</label>
          <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>" required>
        </div>
        <div class="form-group">
          <label>Age</label>
          <input type="number" step="0.1" name="age" value="<?= htmlspecialchars($pet['age']) ?>" required>
        </div>
        <div class="form-group">
          <label>Gender</label>
          <select name="gender" required>
            <option value="Male" <?= $pet['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $pet['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="text" name="color" value="<?= htmlspecialchars($pet['color']) ?>" required>
        </div>
        <div class="form-group">
          <label>Birthday</label>
          <input type="date" name="birthday" value="<?= htmlspecialchars($pet['birthday']) ?>" required>
        </div>
        <div class="form-group">
          <label>Size</label>
          <select name="size">
            <option value="">Select size</option>
            <option value="Small" <?= ($pet['size'] ?? '') == 'Small' ? 'selected' : '' ?>>Small</option>
            <option value="Medium" <?= ($pet['size'] ?? '') == 'Medium' ? 'selected' : '' ?>>Medium</option>
            <option value="Large" <?= ($pet['size'] ?? '') == 'Large' ? 'selected' : '' ?>>Large</option>
            <option value="Extra Large" <?= ($pet['size'] ?? '') == 'Extra Large' ? 'selected' : '' ?>>Extra Large</option>
            <option value="XX-Large" <?= ($pet['size'] ?? '') == 'XX-Large' ? 'selected' : '' ?>>XX-Large</option>
            <option value="XXX-Large" <?= ($pet['size'] ?? '') == 'XXX-Large' ? 'selected' : '' ?>>XXX-Large</option>
          </select>
        </div>
        <div class="form-group">
          <label>Weight (kg)</label>
          <input type="number" step="0.1" name="weight" value="<?= htmlspecialchars($pet['weight'] ?? '') ?>" placeholder="e.g., 5.5">
        </div>
        <div class="form-group full-width">
          <label>Photo</label>
          <input type="file" name="photo_url">
        </div>
      </div>

      <!-- Health Information Section -->
      <h4 style="margin: 20px 0 15px 0; color: #2a2a2a; font-size: 15px; font-weight: 600; border-bottom: 2px solid #FFE29D; padding-bottom: 8px;">
        <i class="fas fa-heartbeat"></i> Health Information
      </h4>
      <div class="form-grid">
        <div class="form-group full-width">
          <label>Allergies</label>
          <textarea name="allergies" rows="3" style="padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: 'Poppins', sans-serif; resize: vertical;" placeholder="List any allergies (e.g., chicken, dairy, pollen)"><?= htmlspecialchars($health['allergies'] ?? '') ?></textarea>
        </div>
        <div class="form-group full-width">
          <label>Medications</label>
          <textarea name="medications" rows="3" style="padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: 'Poppins', sans-serif; resize: vertical;" placeholder="Current medications and dosages"><?= htmlspecialchars($health['medications'] ?? '') ?></textarea>
        </div>
        <div class="form-group full-width">
          <label>Medical Conditions</label>
          <textarea name="medical_conditions" rows="3" style="padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: 'Poppins', sans-serif; resize: vertical;" placeholder="Any medical conditions or health concerns"><?= htmlspecialchars($health['medical_conditions'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Behavior & Preferences Section -->
      <h4 style="margin: 20px 0 15px 0; color: #2a2a2a; font-size: 15px; font-weight: 600; border-bottom: 2px solid #B4A7D6; padding-bottom: 8px;">
        <i class="fas fa-heart"></i> Behavior & Preferences
      </h4>
      <div class="form-grid">
        <div class="form-group full-width">
          <label>Behavior Notes</label>
          <textarea name="behavior_notes" rows="3" style="padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; font-family: 'Poppins', sans-serif; resize: vertical;" placeholder="Temperament, likes, dislikes, special handling instructions"><?= htmlspecialchars($behavior['behavior_notes'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Nail Trimming</label>
          <select name="nail_trimming">
            <option value="">Select preference</option>
            <option value="Yes" <?= ($behavior['nail_trimming'] ?? '') == 'Yes' ? 'selected' : '' ?>>Yes</option>
            <option value="No" <?= ($behavior['nail_trimming'] ?? '') == 'No' ? 'selected' : '' ?>>No</option>
          </select>
        </div>
        <div class="form-group">
          <label>Haircut Style</label>
          <input type="text" name="haircut_style" value="<?= htmlspecialchars($behavior['haircut_style'] ?? '') ?>" placeholder="e.g., Puppy cut, Lion cut">
        </div>
      </div>

      <!-- ONLY ONE form-actions section -->
      <div class="form-actions">
        <button type="submit" class="btn-save">Save Changes</button>
        <button type="button" class="btn-cancel" onclick="togglePetEdit(<?= $pet_id ?>)">Cancel</button>
      </div>
    </form>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab active" onclick="switchTab(<?= $pet_id ?>, 'health')">Health Info</button>
    <button class="tab" onclick="switchTab(<?= $pet_id ?>, 'behavior')">Behavior & Preferences</button>
  </div>

  <!-- Health Tab -->
  <div id="health-<?= $pet_id ?>" class="tab-content active">
    <div class="info-row">
      <strong>Allergies</strong>
      <span><?= htmlspecialchars($health['allergies'] ?? 'None') ?></span>
    </div>
    <div class="info-row">
      <strong>Medications</strong>
      <span><?= htmlspecialchars($health['medications'] ?? 'None') ?></span>
    </div>
    <div class="info-row">
      <strong>Medical Conditions</strong>
      <span><?= htmlspecialchars($health['medical_conditions'] ?? 'None') ?></span>
    </div>
  </div>

  <!-- Behavior Tab -->
  <div id="behavior-<?= $pet_id ?>" class="tab-content">
    <div class="info-row">
      <strong>Behavior Notes</strong>
      <span><?= htmlspecialchars($behavior['behavior_notes'] ?? 'None') ?></span>
    </div>
    <div class="info-row">
      <strong>Nail Trimming</strong>
      <span><?= htmlspecialchars($behavior['nail_trimming'] ?? 'Not specified') ?></span>
    </div>
    <div class="info-row">
      <strong>Haircut Style</strong>
      <span><?= htmlspecialchars($behavior['haircut_style'] ?? 'None') ?></span>
    </div>
  </div>
</div>

<?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-paw"></i>
            <p>You haven't added any pets yet.</p>
            <a href="add-pet.php">
              <i class="fas fa-plus-circle"></i> Add Your First Pet
            </a>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

<script>
function switchTab(petId, tabName) {
  // Find the specific pet card by looking for the tab content with the petId
  const healthTab = document.getElementById(`health-${petId}`);
  if (!healthTab) {
    console.error(`Could not find pet card for pet ID: ${petId}`);
    return;
  }
  
  const petCard = healthTab.closest('.pet-card');
  if (!petCard) {
    console.error(`Could not find parent pet-card for pet ID: ${petId}`);
    return;
  }
  
  const tabs = petCard.querySelectorAll('.tab');
  const contents = petCard.querySelectorAll('.tab-content');
  
  // Remove active class from all tabs and contents
  tabs.forEach(tab => tab.classList.remove('active'));
  contents.forEach(content => content.classList.remove('active'));
  
  // Add active class to clicked tab
  event.target.classList.add('active');
  
  // Show the corresponding content
  const targetContent = document.getElementById(`${tabName}-${petId}`);
  if (targetContent) {
    targetContent.classList.add('active');
  }
}

function togglePetEdit(petId) {
  const form = document.getElementById(`pet-edit-${petId}`);
  if (form) {
    form.classList.toggle('show');
  } else {
    console.error(`Could not find edit form for pet ID: ${petId}`);
  }
}

function toggleUserEdit() {
  const form = document.getElementById('user-edit-form');
  if (form) {
    form.classList.toggle('show');
  } else {
    console.error('Could not find user edit form');
  }
}

// Hamburger Menu Toggle
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

// Close menu when clicking overlay
if (navOverlay) {
  navOverlay.addEventListener('click', function() {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
    navOverlay.classList.remove('active');
    if (profileDropdown) profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  });
}

// Close menu when clicking on regular nav links
document.querySelectorAll('.nav-link:not(.profile-icon)').forEach(link => {
  link.addEventListener('click', function() {
    if (hamburger) hamburger.classList.remove('active');
    if (navMenu) navMenu.classList.remove('active');
    if (navOverlay) navOverlay.classList.remove('active');
    document.body.style.overflow = '';
  });
});

// Handle profile dropdown - ONLY for mobile (click to toggle)
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

// Close menu when clicking dropdown items
document.querySelectorAll('.dropdown-menu a').forEach(link => {
  link.addEventListener('click', function() {
    if (hamburger) hamburger.classList.remove('active');
    if (navMenu) navMenu.classList.remove('active');
    if (navOverlay) navOverlay.classList.remove('active');
    if (profileDropdown) profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  });
});

// Reset on window resize
window.addEventListener('resize', function() {
  if (window.innerWidth > 1024) {
    if (hamburger) hamburger.classList.remove('active');
    if (navMenu) navMenu.classList.remove('active');
    if (navOverlay) navOverlay.classList.remove('active');
    if (profileDropdown) profileDropdown.classList.remove('active');
    document.body.style.overflow = '';
  }
});
</script>
</body>
</html>