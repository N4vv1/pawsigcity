<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin | Analytics</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../homepage/images/pawsig.png">

  <style>
        :root {
        --white-color: #fff;
        --dark-color: #252525;
        --primary-color: #A8E6CF;
        --secondary-color: #FFE29D;
        --light-pink-color: #faf4f5;
        --medium-gray-color: #ccc;
        --font-size-s: 0.9rem;
        --font-size-n: 1rem;
        --font-size-l: 1.5rem;
        --font-size-xl: 2rem;
        --font-weight-semi-bold: 600;
        --font-weight-bold: 700;
        --border-radius-s: 8px;
        --border-radius-circle: 50%;
        --site-max-width: 1300px;
        --sidebar-width: 260px;
        --transition-speed: 0.3s;  /* ADD THIS LINE */
        --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);  /* ADD THIS LINE */
      }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Montserrat", sans-serif;
    }

    body {
      background: var(--light-pink-color);
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 260px;
      height: 100vh;
      background-color: var(--primary-color);
      padding: 30px 20px;
      position: fixed;
      left: 0;
      top: 0;
      display: flex;
      flex-direction: column;
      gap: 20px;
      overflow-y: auto;
      transition: transform var(--transition-speed);  /* ADD THIS */
      z-index: 999;
    }

    .sidebar .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .sidebar .logo img {
      width: 80px;
      height: 80px;
      border-radius: var(--border-radius-circle);
    }

    .menu {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .menu a {
      display: flex;
      align-items: center;
      padding: 10px 12px;
      text-decoration: none;
      color: var(--dark-color);
      border-radius: var(--border-radius-s);
      transition: background 0.3s, color 0.3s;
      font-weight: var(--font-weight-semi-bold);
    }

    .menu a i {
      margin-right: 10px;
      font-size: 20px;
    }

    .menu a:hover,
    .menu a.active {
      background-color: var(--secondary-color);
      color: var(--dark-color);
    }

    .menu hr {
      border: none;
      border-top: 1px solid var(--secondary-color);
      margin: 9px 0;
    }

    .dropdown {
      position: relative;
    }

    .dropdown-toggle {
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
    }

    .dropdown-menu {
      display: none;
      flex-direction: column;
      gap: 5px;
      padding-left: 20px;
      margin-top: 5px;
    }

    .dropdown-menu.show {
      display: flex;
    }

    .content {
      margin-left: 260px;
      padding: 20px;
      width: calc(100% - 260px);
      min-height: 100vh;
      overflow-x: hidden;
      transition: margin-left var(--transition-speed), width var(--transition-speed);
    }

    .header {
      margin-bottom: 20px;
    }

    .header h1 {
      color: var(--dark-color);
      font-size: 1.8rem;
      margin-bottom: 5px;
    }

    .header p {
      color: #666;
      font-size: 0.9rem;
    }

    .model-status {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      padding: 15px 20px;
      margin-bottom: 20px;
      border-radius: var(--border-radius-s);
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .model-status.error {
      background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    }

    .model-status i {
      font-size: 28px;
    }

    .model-info {
      flex: 1;
    }

    .model-info h4 {
      margin: 0 0 5px 0;
      font-size: 1rem;
      color: var(--dark-color);
    }

    .model-info p {
      margin: 0;
      font-size: 0.8rem;
      color: #666;
    }

    .retrain-btn {
      background: var(--primary-color);
      color: var(--dark-color);
      border: none;
      padding: 10px 18px;
      border-radius: var(--border-radius-s);
      font-weight: var(--font-weight-semi-bold);
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .retrain-btn:hover {
      background: var(--secondary-color);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 20px;
    }

    .card {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .card h3 {
      color: var(--dark-color);
      font-size: 1rem;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .card-icon {
      width: 35px;
      height: 35px;
      background: var(--primary-color);
      border-radius: var(--border-radius-s);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: var(--font-weight-bold);
      color: var(--primary-color);
      margin: 8px 0;
    }

    .stat-label {
      color: #666;
      font-size: 0.85rem;
    }

    .calendar-container {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--light-pink-color);
    }

    .calendar-header h2 {
      color: var(--dark-color);
      font-size: 1.3rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .calendar-nav {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .calendar-nav button {
      background: var(--primary-color);
      border: none;
      padding: 10px 18px;
      border-radius: var(--border-radius-s);
      cursor: pointer;
      font-weight: var(--font-weight-semi-bold);
      transition: background 0.3s;
      font-size: 0.9rem;
    }

    .calendar-nav button:hover {
      background: var(--secondary-color);
    }

    .calendar-nav select {
      padding: 10px 15px;
      border-radius: var(--border-radius-s);
      border: 1px solid var(--medium-gray-color);
      background: white;
      cursor: pointer;
      font-weight: var(--font-weight-semi-bold);
      font-size: 0.9rem;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
      margin-bottom: 20px;
    }

    .calendar-day-header {
      text-align: center;
      font-weight: var(--font-weight-bold);
      padding: 12px 8px;
      color: var(--white-color);
      background: linear-gradient(135deg, var(--primary-color), #8DD9B4);
      border-radius: var(--border-radius-s);
      font-size: 0.9rem;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .calendar-day {
      border: 2px solid #e8e8e8;
      border-radius: var(--border-radius-s);
      padding: 10px;
      min-height: 100px;
      position: relative;
      cursor: pointer;
      transition: all 0.3s;
      background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
      display: flex;
      flex-direction: column;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .calendar-day:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.15);
      border-color: var(--primary-color);
    }

    .calendar-day.empty {
      background: #f9f9f9;
      cursor: default;
      border-color: #f0f0f0;
      box-shadow: none;
    }

    .calendar-day.empty:hover {
      transform: none;
      box-shadow: none;
      border-color: #f0f0f0;
    }

    .calendar-day.today {
      border-color: var(--secondary-color);
      border-width: 3px;
      background: linear-gradient(135deg, #fffbf0 0%, #fff9e6 100%);
      box-shadow: 0 3px 10px rgba(255, 228, 157, 0.4);
    }

    .day-number {
      font-size: 1.1rem;
      font-weight: var(--font-weight-bold);
      color: var(--dark-color);
      margin-bottom: 8px;
    }

    .day-stats {
      font-size: 0.75rem;
      margin-top: 5px;
    }

    .appointments-count {
      display: inline-block;
      background: linear-gradient(135deg, var(--primary-color), #95DCBE);
      padding: 3px 8px;
      border-radius: 12px;
      font-weight: var(--font-weight-semi-bold);
      margin-bottom: 4px;
      font-size: 0.75rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      color: var(--dark-color);
    }

    .noshow-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 12px;
      font-weight: var(--font-weight-semi-bold);
      font-size: 0.7rem;
      margin-top: 3px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .peak-level {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 14px;
      height: 14px;
      border-radius: 50%;
      border: 2px solid white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .peak-level.high {
      background: #ff6b6b;
      box-shadow: 0 0 10px #ff6b6b;
    }

    .peak-level.medium {
      background: #ffd43b;
      box-shadow: 0 0 10px #ffd43b;
    }

    .peak-level.low {
      background: #51cf66;
      box-shadow: 0 0 10px #51cf66;
    }

    .noshow-high {
      background: #ffe0e0;
      color: #c92a2a;
    }

    .noshow-medium {
      background: #fff3bf;
      color: #e67700;
    }

    .noshow-low {
      background: #d3f9d8;
      color: #2b8a3e;
    }

    .legend {
      display: flex;
      gap: 15px;
      padding: 15px;
      flex-wrap: wrap;
      font-size: 0.85rem;
      background: var(--light-pink-color);
      border-radius: var(--border-radius-s);
      align-items: center;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.85rem;
      padding: 5px 10px;
      background: var(--white-color);
      border-radius: 15px;
    }

    .legend-color {
      width: 15px;
      height: 15px;
      border-radius: 50%;
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal.show {
      display: flex;
    }

    .modal-content {
      background: var(--white-color);
      border-radius: var(--border-radius-s);
      padding: 30px;
      max-width: 600px;
      width: 90%;
      max-height: 85vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--light-pink-color);
    }

    .modal-header h3 {
      margin: 0;
      color: var(--dark-color);
      font-size: 1.3rem;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 28px;
      cursor: pointer;
      color: #999;
      line-height: 1;
    }

    .close-modal:hover {
      color: var(--dark-color);
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 14px;
      margin: 10px 0;
      background: var(--light-pink-color);
      border-radius: var(--border-radius-s);
    }

    .detail-label {
      font-weight: var(--font-weight-semi-bold);
      color: var(--dark-color);
    }

    .detail-value {
      color: #666;
    }

    .hourly-breakdown {
      margin-top: 20px;
    }

    .hourly-breakdown h4 {
      margin-bottom: 12px;
      color: var(--dark-color);
    }

    .hour-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      margin: 6px 0;
      background: #f9f9f9;
      border-radius: var(--border-radius-s);
      border-left: 4px solid var(--primary-color);
    }

    .algorithm-info {
      background: #e3f2fd;
      padding: 14px;
      border-radius: var(--border-radius-s);
      margin-top: 15px;
      font-size: 0.85rem;
      line-height: 1.6;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 250px;
      }
      .content {
        margin-left: 200px;
        width: calc(100% - 200px);
      }
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
      .calendar-grid {
        gap: 5px;
      }
      .calendar-day {
        min-height: 80px;
        padding: 6px;
      }
    }
        /* MOBILE MENU BUTTON */
      .mobile-menu-btn {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: var(--primary-color);
        border: none;
        border-radius: 8px;
        padding: 12px;
        cursor: pointer;
        box-shadow: var(--shadow-light);
        transition: var(--transition-speed);
      }

    .mobile-menu-btn i {
      font-size: 24px;
      color: var(--dark-color);
    }

    .mobile-menu-btn:hover {
      background: var(--secondary-color);
    }
        /* SIDEBAR OVERLAY */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 998;
      opacity: 0;
      transition: opacity var(--transition-speed);
    }

    .sidebar-overlay.active {
      display: block;
      opacity: 1;
    }
    /* RESPONSIVE DESIGN */
@media screen and (max-width: 1024px) {
  .dashboard {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
  }

  .card {
    padding: 25px;
    min-height: 200px;
  }
}

/* REPLACE your existing media queries with these improved responsive styles */

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}

/* Prevent iOS zoom on inputs */
input, select, button {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  font-size: 16px;
}

/* TABLET RESPONSIVE (769px - 1024px) */
@media screen and (min-width: 769px) and (max-width: 1024px) {
  .content {
    padding: 30px 25px;
  }

  .dashboard-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
  }

  .card {
    padding: 18px;
  }

  .stat-value {
    font-size: 1.8rem;
  }

  .calendar-grid {
    gap: 8px;
  }

  .calendar-day {
    min-height: 90px;
    padding: 8px;
  }
}

/* MOBILE RESPONSIVE (up to 768px) */
@media screen and (max-width: 768px) {
  /* Show mobile menu button */
  .mobile-menu-btn {
    display: block;
  }

  /* Hide sidebar by default */
  .sidebar {
    transform: translateX(-100%);
  }

  /* Show sidebar when active */
  .sidebar.active {
    transform: translateX(0);
  }

  /* Adjust content area */
  .content {
    margin-left: 0;
    width: 100%;
    padding: 80px 20px 40px;
  }

  .header h1 {
    font-size: 1.5rem;
  }

  .header p {
    font-size: 0.85rem;
  }

  /* Model status responsive */
  .model-status {
    flex-direction: column;
    text-align: center;
    padding: 12px 15px;
  }

  .model-info h4 {
    font-size: 0.95rem;
  }

  .retrain-btn {
    width: 100%;
    justify-content: center;
  }

  /* Dashboard grid - stack vertically */
  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .dashboard-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(150px, 200px)); /* Smaller, fixed-width boxes */
  gap: 15px;
  margin-bottom: 20px;
  justify-content: start; /* Align to the left/top */
}

.card {
  background: var(--white-color);
  border-radius: var(--border-radius-s);
  padding: 15px; /* Reduced padding */
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  min-height: auto; /* Remove min-height to make it compact */
}

.card h3 {
  color: var(--dark-color);
  font-size: 0.85rem; /* Smaller heading */
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.card-icon {
  width: 28px; /* Smaller icon container */
  height: 28px;
  background: var(--primary-color);
  border-radius: var(--border-radius-s);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0; /* Prevent icon from shrinking */
}

.stat-value {
  font-size: 1.5rem; /* Smaller stat number */
  font-weight: var(--font-weight-bold);
  color: var(--primary-color);
  margin: 5px 0;
  line-height: 1;
}

.stat-label {
  color: #666;
  font-size: 0.75rem; /* Smaller label */
  margin: 0;
}

  /* Calendar responsive */
  .calendar-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .calendar-header h2 {
    font-size: 1.2rem;
  }

  .calendar-nav {
    width: 100%;
    flex-wrap: wrap;
    justify-content: center;
  }

  .calendar-nav button {
    padding: 8px 14px;
    font-size: 0.85rem;
  }

  .calendar-nav select {
    padding: 8px 12px;
    font-size: 0.85rem;
  }

  .calendar-grid {
    gap: 5px;
  }

  .calendar-day-header {
    padding: 8px 4px;
    font-size: 0.75rem;
  }

  .calendar-day {
    min-height: 80px;
    padding: 6px;
  }

  .day-number {
    font-size: 0.95rem;
  }

  .appointments-count,
  .noshow-badge {
    font-size: 0.65rem;
    padding: 2px 6px;
  }

  .peak-level {
    width: 12px;
    height: 12px;
  }

  /* Legend responsive */
  .legend {
    justify-content: center;
    font-size: 0.75rem;
  }

  .legend-item {
    font-size: 0.75rem;
    padding: 4px 8px;
  }

  .legend-color {
    width: 12px;
    height: 12px;
  }

  /* Modal responsive */
  .modal-content {
    width: 95%;
    padding: 20px;
    max-height: 85vh;
  }

  .modal-header h3 {
    font-size: 1.1rem;
  }

  .detail-row {
    padding: 10px;
    font-size: 0.85rem;
    flex-direction: column;
    gap: 5px;
    text-align: left;
  }

  .hour-item {
    padding: 10px;
    font-size: 0.85rem;
  }

  .algorithm-info {
    font-size: 0.75rem;
    padding: 12px;
  }
}

/* SMALL MOBILE (up to 480px) */
@media screen and (max-width: 480px) {
  .content {
    padding: 70px 15px 30px;
  }

  .header h1 {
    font-size: 1.3rem;
  }

  .sidebar .logo img {
    width: 60px;
    height: 60px;
  }

  .menu a {
    padding: 8px 10px;
    font-size: 0.9rem;
  }

  .menu a i {
    font-size: 18px;
  }

  /* Model status */
  .model-status i {
    font-size: 24px;
  }

  .model-info h4 {
    font-size: 0.9rem;
  }

  .model-info p {
    font-size: 0.75rem;
  }

  .retrain-btn {
    padding: 8px 14px;
    font-size: 0.85rem;
  }

  /* Dashboard cards */
  .card {
    padding: 15px;
  }

  .card h3 {
    font-size: 0.85rem;
  }

  .card-icon {
    width: 30px;
    height: 30px;
    font-size: 16px;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .stat-label {
    font-size: 0.75rem;
  }

  /* Calendar */
  .calendar-container {
    padding: 15px;
  }

  .calendar-header h2 {
    font-size: 1.1rem;
  }

  .calendar-nav {
    gap: 5px;
  }

  .calendar-nav button {
    padding: 6px 10px;
    font-size: 0.8rem;
  }

  .calendar-nav select {
    padding: 6px 10px;
    font-size: 0.8rem;
  }

  .calendar-day-header {
    padding: 6px 2px;
    font-size: 0.7rem;
  }

  .calendar-day {
    min-height: 70px;
    padding: 5px;
  }

  .day-number {
    font-size: 0.85rem;
  }

  .appointments-count,
  .noshow-badge {
    font-size: 0.6rem;
    padding: 2px 5px;
  }

  .peak-level {
    width: 10px;
    height: 10px;
    top: 4px;
    right: 4px;
  }

  /* Legend */
  .legend {
    padding: 10px;
    gap: 8px;
  }

  .legend-item {
    padding: 3px 6px;
  }

  /* Modal */
  .modal-content {
    padding: 15px;
  }

  .modal-header {
    padding-bottom: 10px;
  }

  .modal-header h3 {
    font-size: 1rem;
  }

  .close-modal {
    font-size: 24px;
  }

  .detail-row {
    padding: 8px;
    font-size: 0.8rem;
  }

  .hourly-breakdown h4 {
    font-size: 0.9rem;
  }

  .hour-item {
    padding: 8px;
    font-size: 0.8rem;
  }

  .algorithm-info {
    padding: 10px;
    font-size: 0.7rem;
  }
}

/* EXTRA SMALL DEVICES (up to 360px) */
@media screen and (max-width: 360px) {
  .content {
    padding: 70px 10px 30px;
  }

  .header h1 {
    font-size: 1.2rem;
  }

  .calendar-container {
    padding: 12px;
  }

  .calendar-grid {
    gap: 3px;
  }

  .calendar-day {
    min-height: 65px;
    padding: 4px;
  }

  .day-number {
    font-size: 0.8rem;
  }

  .appointments-count,
  .noshow-badge {
    font-size: 0.55rem;
    padding: 1px 4px;
  }

  .card {
    padding: 12px;
  }

  .stat-value {
    font-size: 1.3rem;
  }

  .menu a {
    font-size: 0.85rem;
    padding: 7px 8px;
  }
}

/* LANDSCAPE ORIENTATION (for phones in landscape) */
@media screen and (max-height: 600px) and (orientation: landscape) {
  .modal-content {
    max-height: 95vh;
    padding: 15px;
  }

  .sidebar {
    padding: 15px 10px;
  }

  .sidebar .logo img {
    width: 50px;
    height: 50px;
  }

  .menu a {
    padding: 6px 10px;
  }

  .calendar-day {
    min-height: 60px;
  }
}

/* TOUCH DEVICE OPTIMIZATIONS */
@media (hover: none) and (pointer: coarse) {
  /* Better touch targets */
  .menu a,
  .dropdown-toggle {
    min-height: 44px;
    display: flex;
    align-items: center;
  }

  .calendar-nav button,
  .retrain-btn {
    min-height: 44px;
  }

  .calendar-nav select {
    min-height: 44px;
  }

  .calendar-day {
    cursor: pointer;
  }
}

/* PRINT STYLES */
@media print {
  .sidebar,
  .mobile-menu-btn,
  .sidebar-overlay,
  .retrain-btn,
  .calendar-nav,
  .model-status {
    display: none !important;
  }

  .content {
    margin-left: 0;
    width: 100%;
    padding: 20px;
  }

  .calendar-container {
    page-break-inside: avoid;
  }

  .modal {
    display: none !important;
  }
}

/* DESKTOP - Keep original design (1025px and above) */
@media screen and (min-width: 1025px) {
  /* Desktop maintains all original styles */
  /* No changes needed - original design preserved */
}
  </style>
</head>
<body>

<?php
// Load trained models
$peakHourModel = null;
$noshowModel = null;
$modelStatus = 'not_trained';
$trainingMetadata = null;

$peakModelPath = __DIR__ . '/models/peak_hour_model.json';
$noshowModelPath = __DIR__ . '/models/noshow_model.json';
$metadataPath = __DIR__ . '/models/training_metadata.json';

if (file_exists($peakModelPath) && file_exists($noshowModelPath)) {
    $peakHourModel = json_decode(file_get_contents($peakModelPath), true);
    $noshowModel = json_decode(file_get_contents($noshowModelPath), true);
    $modelStatus = 'trained';
    
    if (file_exists($metadataPath)) {
        $trainingMetadata = json_decode(file_get_contents($metadataPath), true);
    }
}
?>
<button class="mobile-menu-btn" onclick="toggleSidebar()">
  <i class='bx bx-menu'></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside class="sidebar">
  <div class="logo">
    <img src="../../homepage/images/pawsig.png" alt="Pawsig City Logo" />
  </div>
  <nav class="menu">
    <a href="../admin/admin.php"><i class='bx bx-home'></i>Overview</a>
    <hr>
    <div class="dropdown">
      <a href="javascript:void(0)" class="dropdown-toggle" onclick="toggleDropdown(event)">
        <span><i class='bx bx-user'></i> Users</span>
        <i class='bx bx-chevron-down'></i>
      </a>
      <div class="dropdown-menu">
        <a href="../manage_accounts/accounts.php"><i class='bx bx-user-circle'></i> All Users</a>
        <a href="../groomer_management/groomer_accounts.php"><i class='bx bx-scissors'></i> Groomers</a>
      </div>
    </div>
    <hr>
    <a href="notes.php" class="active"><i class='bx bx-note'></i>Analytics</a>
    <hr>
    <a href="../gallery_dashboard/gallery.php"><i class='bx bx-camera'></i>Pet Gallery</a>
    <hr>
    <a href="../feedback_reports/feedback-reports.php"><i class='bx bx-comment-detail'></i>Feedback Reports</a>
    <hr>
    <a href="../../homepage/logout/logout.php"><i class='bx bx-log-out'></i>Logout</a>
  </nav>
</aside>

<main class="content">


  <div class="dashboard-grid">
    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-calendar'></i></div>
        Total Appointments
      </h3>
      <div class="stat-value" id="totalAppointments">0</div>
      <div class="stat-label">All time bookings</div>
    </div>

    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-error'></i></div>
        No-Show Rate
      </h3>
      <div class="stat-value" id="noshowRate">0%</div>
      <div class="stat-label"><span id="noshowCount">0</span> no-shows detected</div>
    </div>

    <div class="card">
      <h3>
        <div class="card-icon"><i class='bx bx-time'></i></div>
        Peak Hour
      </h3>
      <div class="stat-value" id="peakHour">--:00</div>
      <div class="stat-label" id="peakHourCount">0 appointments</div>
    </div>
  </div>

  <div class="calendar-container">
    <div class="calendar-header">
      <h2><i class='bx bx-calendar-week'></i> Booking Calendar</h2>
      <div class="calendar-nav">
        <button onclick="changeMonth(-1)">← Prev</button>
        <select id="monthSelect" onchange="updateCalendar()">
          <option value="1">January</option>
          <option value="2">February</option>
          <option value="3">March</option>
          <option value="4">April</option>
          <option value="5">May</option>
          <option value="6">June</option>
          <option value="7">July</option>
          <option value="8">August</option>
          <option value="9">September</option>
          <option value="10">October</option>
          <option value="11">November</option>
          <option value="12">December</option>
        </select>
        <select id="yearSelect" onchange="updateCalendar()">
          <option value="2024">2024</option>
          <option value="2025">2025</option>
          <option value="2026">2026</option>
          <option value="2027">2027</option>
          <option value="2028">2028</option>
          <option value="2029">2029</option>
          <option value="2030">2030</option>
        </select>
        <button onclick="changeMonth(1)">Next →</button>
      </div>
    </div>

    <div class="calendar-grid" id="calendarGrid"></div>

    <div class="legend">
      <div style="font-weight: bold;">Peak Level:</div>
      <div class="legend-item">
        <span class="legend-color" style="background: #ff6b6b;"></span>
        <span>High</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #ffd43b;"></span>
        <span>Medium</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #51cf66;"></span>
        <span>Low</span>
      </div>
      <div style="font-weight: bold; margin-left: 20px;">No-Show Risk:</div>
      <div class="legend-item">
        <span style="background: #ffe0e0; padding: 3px 8px; border-radius: 8px; font-size: 0.75rem;">High</span>
      </div>
      <div class="legend-item">
        <span style="background: #fff3bf; padding: 3px 8px; border-radius: 8px; font-size: 0.75rem;">Medium</span>
      </div>
      <div class="legend-item">
        <span style="background: #d3f9d8; padding: 3px 8px; border-radius: 8px; font-size: 0.75rem;">Low</span>
      </div>
    </div>
  </div>

  <div id="dayModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Daily Analytics</h3>
        <button class="close-modal" onclick="closeModal()">×</button>
      </div>
      <div id="modalBody"></div>
    </div>
  </div>
</main>

<script>
  // Load trained models from PHP
  const MODELS_LOADED = <?php echo $modelStatus === 'trained' ? 'true' : 'false'; ?>;
  const peakHourModel = <?php echo $peakHourModel ? json_encode($peakHourModel) : 'null'; ?>;
  const noshowModel = <?php echo $noshowModel ? json_encode($noshowModel) : 'null'; ?>;

  console.log('Models loaded:', MODELS_LOADED);

  let appointments = [];
  let currentYear = new Date().getFullYear();
  let currentMonth = new Date().getMonth() + 1;

  function predictWithTree(tree, features) {
    if (!tree) return 0;
    let node = tree;
    while (!node.leaf) {
      if (features[node.feature] <= node.threshold) {
        node = node.left;
      } else {
        node = node.right;
      }
    }
    return node.value;
  }

  function showTrainingInstructions() {
    alert('📚 Training Instructions:\n\n' +
          '1. Open your terminal/command prompt\n' +
          '2. Navigate to: ' + window.location.pathname.replace('/notes.php', '') + '\n' +
          '3. Run: python train_analytics.py\n' +
          '4. Wait for training visualization to complete\n' +
          '5. Refresh this page\n\n' +
          'Models will be saved in the models/ folder.');
  }

  function showRetrainInstructions() {
    if (confirm('Retrain ML models with latest data?\n\nThis will:\n• Fetch latest appointments\n• Train new models\n• Show training visualizations\n\nContinue?')) {
      showTrainingInstructions();
    }
  }

  function updateDashboard() {
    const total = appointments.length;
    const noshows = appointments.filter(apt => apt.status === 'no_show').length;
    const noshowPct = total > 0 ? (noshows / total) * 100 : 0;
    
    document.getElementById('totalAppointments').textContent = total;
    document.getElementById('noshowRate').textContent = noshowPct.toFixed(1) + '%';
    document.getElementById('noshowCount').textContent = noshows;

    const hourCounts = {};
    appointments.forEach(apt => {
      const hour = new Date(apt.appointment_date).getHours();
      hourCounts[hour] = (hourCounts[hour] || 0) + 1;
    });
    
    if (Object.keys(hourCounts).length > 0) {
      const peakHour = Object.keys(hourCounts).reduce((a, b) => hourCounts[a] > hourCounts[b] ? a : b);
      document.getElementById('peakHour').textContent = peakHour + ':00';
      document.getElementById('peakHourCount').textContent = hourCounts[peakHour] + ' appointments';
    }
  }

  function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';
    
    const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    dayNames.forEach(day => {
      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.textContent = day;
      grid.appendChild(header);
    });

    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
    const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
    const adjustedFirstDay = firstDay === 0 ? 6 : firstDay - 1;

    const monthAppointments = appointments.filter(apt => {
      const aptDate = new Date(apt.appointment_date);
      return aptDate.getMonth() + 1 === currentMonth && aptDate.getFullYear() === currentYear;
    });
    const avgPerDay = monthAppointments.length > 0 ? monthAppointments.length / daysInMonth : 0;

    for (let i = 0; i < adjustedFirstDay; i++) {
      const empty = document.createElement('div');
      empty.className = 'calendar-day empty';
      grid.appendChild(empty);
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const currentDate = new Date(currentYear, currentMonth - 1, day);
      const dayEl = document.createElement('div');
      const isToday = dateStr === new Date().toISOString().split('T')[0];
      dayEl.className = `calendar-day ${isToday ? 'today' : ''}`;

      const dayAppointments = appointments.filter(apt => apt.appointment_date.startsWith(dateStr));
      const total = dayAppointments.length;
      const actualNoshows = dayAppointments.filter(apt => apt.status === 'no_show').length;
      
      const isPastDate = currentDate < today;
      
      if (total > 0) {
        let peakClass = '';
        
        if (avgPerDay > 0) {
          const ratio = total / avgPerDay;
          if (ratio >= 1.5) peakClass = 'high';
          else if (ratio >= 1.0) peakClass = 'medium';
          else peakClass = 'low';
        } else {
          if (total >= 3) peakClass = 'high';
          else if (total >= 2) peakClass = 'medium';
          else peakClass = 'low';
        }

        const noshowPct = Math.round((actualNoshows / total) * 100);
        let noshowClass = 'noshow-low';
        if (noshowPct >= 50) noshowClass = 'noshow-high';
        else if (noshowPct >= 25) noshowClass = 'noshow-medium';

        let content = `<div class="day-number">${day}</div>`;
        content += `<span class="peak-level ${peakClass}"></span>`;
        content += `<div class="day-stats">`;
        content += `<span class="appointments-count">${total} apt${total > 1 ? 's' : ''}</span><br>`;
        content += `<span class="noshow-badge ${noshowClass}">${noshowPct}% actual</span>`;
        content += `</div>`;
        
        dayEl.innerHTML = content;
        dayEl.onclick = () => showDayDetail(dateStr, total, actualNoshows, dayAppointments);
      } else if (!isPastDate && appointments.length >= 5 && MODELS_LOADED) {
        const dayOfWeek = (currentDate.getDay() + 6) % 7;
        
        const sameDayAppointments = appointments.filter(apt => {
          const aptDate = new Date(apt.appointment_date);
          return ((aptDate.getDay() + 6) % 7) === dayOfWeek;
        });
        
        const features = [dayOfWeek, day, currentMonth];
        const predictedHour = predictWithTree(peakHourModel, features);
        
        const noshowFeatures = [dayOfWeek, predictedHour, day];
        const noshowPrediction = predictWithTree(noshowModel, noshowFeatures);
        
        const avgForThisDay = sameDayAppointments.length > 0 ? 
          sameDayAppointments.length / (appointments.length / 30) : avgPerDay;
        
        let peakClass = 'medium';
        if (avgPerDay > 0) {
          if (avgForThisDay > avgPerDay * 1.5) peakClass = 'high';
          else if (avgForThisDay > avgPerDay * 0.8) peakClass = 'medium';
          else peakClass = 'low';
        }
        
        const sameDayNoshows = sameDayAppointments.filter(apt => apt.status === 'no_show').length;
        const historicalNoshowRate = sameDayAppointments.length > 0 ? 
          (sameDayNoshows / sameDayAppointments.length) * 100 : 30;
        
        const noshowPct = Math.round(historicalNoshowRate);
        let noshowClass = 'noshow-medium';
        
        if (historicalNoshowRate >= 40 || noshowPrediction === 1) noshowClass = 'noshow-high';
        else if (historicalNoshowRate >= 20) noshowClass = 'noshow-medium';
        else noshowClass = 'noshow-low';

        let content = `<div class="day-number">${day}</div>`;
        content += `<span class="peak-level ${peakClass}" style="opacity: 0.8;"></span>`;
        content += `<div class="day-stats">`;
        const expectedCount = Math.max(1, Math.round(avgForThisDay));
        content += `<span class="appointments-count" style="opacity: 0.7; font-style: italic;">~${expectedCount} expected</span><br>`;
        content += `<span class="noshow-badge ${noshowClass}" style="opacity: 0.7;">${noshowPct}% risk</span>`;
        content += `</div>`;
        
        dayEl.innerHTML = content;
        dayEl.onclick = () => showPredictionDetail(dateStr, dayOfWeek, predictedHour, noshowPct, peakClass, expectedCount, sameDayAppointments.length);
      } else {
        dayEl.innerHTML = `<div class="day-number">${day}</div>`;
        if (!isPastDate && !MODELS_LOADED) {
          dayEl.style.opacity = '0.5';
          dayEl.title = 'Run train_analytics.py to enable predictions';
        }
      }

      grid.appendChild(dayEl);
    }
  }

  function showDayDetail(dateStr, total, noshows, dayAppointments) {
    const modal = document.getElementById('dayModal');
    const modalBody = document.getElementById('modalBody');
    
    const date = new Date(dateStr);
    const formattedDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    const noshowPct = total > 0 ? Math.round((noshows / total) * 100) : 0;
    const confirmed = total - noshows;
    
    let riskLevel = 'Low';
    let riskColor = '#51cf66';
    if (noshowPct >= 50) {
      riskLevel = 'High';
      riskColor = '#ff6b6b';
    } else if (noshowPct >= 25) {
      riskLevel = 'Medium';
      riskColor = '#ffd43b';
    }

    const hours = {};
    let peakHour = '--';
    let peakHourCount = 0;
    
    dayAppointments.forEach(apt => {
      const hour = new Date(apt.appointment_date).getHours();
      hours[hour] = (hours[hour] || 0) + 1;
      if (hours[hour] > peakHourCount) {
        peakHourCount = hours[hour];
        peakHour = hour;
      }
    });

    let hourlyBreakdown = '<div class="hourly-breakdown"><h4>Hourly Distribution:</h4>';
    const sortedHours = Object.entries(hours).sort((a, b) => b[1] - a[1]);
    
    if (sortedHours.length > 0) {
      sortedHours.forEach(([hour, count]) => {
        const isPeak = hour == peakHour;
        hourlyBreakdown += `
          <div class="hour-item" style="${isPeak ? 'border-left: 4px solid #FFD43B; background: #fffbf0;' : ''}">
            <span><strong>${hour}:00</strong> ${isPeak ? '⭐ Peak' : ''}</span>
            <span>${count} appointment${count > 1 ? 's' : ''}</span>
          </div>
        `;
      });
    } else {
      hourlyBreakdown += '<p style="color: #999; font-style: italic;">No hourly data available</p>';
    }
    hourlyBreakdown += '</div>';

    const recommendation = noshowPct >= 50 
      ? 'High no-show rate detected. Send SMS reminders 24h before and consider requiring deposits.'
      : noshowPct >= 25 
      ? 'Moderate no-show rate. Send email reminders and follow up with clients.'
      : noshowPct === 0 && total > 0
      ? 'Perfect attendance! Continue with current confirmation process.'
      : 'Low no-show rate. Standard confirmation process is working well.';

    modalBody.innerHTML = `
      <div class="detail-row">
        <span class="detail-label">Date:</span>
        <span class="detail-value">${formattedDate}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Total Appointments:</span>
        <span class="detail-value"><strong>${total}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Confirmed/Completed:</span>
        <span class="detail-value" style="color: #51cf66;"><strong>${confirmed}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Shows:</span>
        <span class="detail-value" style="color: #ff6b6b;"><strong>${noshows}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Show Rate:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${noshowPct}%</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Risk Assessment:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${riskLevel} Risk</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Peak Hour:</span>
        <span class="detail-value"><strong>${peakHour}:00</strong> (${peakHourCount} apt${peakHourCount > 1 ? 's' : ''})</span>
      </div>
      ${hourlyBreakdown}
      <div class="algorithm-info">
        <strong>📊 Actual Data:</strong> All statistics are based on real bookings for this day.
      </div>
      <div class="algorithm-info">
        <strong>💡 Recommendation:</strong> ${recommendation}
      </div>
    `;

    modal.classList.add('show');
  }

  function showPredictionDetail(dateStr, dayOfWeek, predictedHour, noshowPct, peakClass, expectedCount, historicalCount) {
    const modal = document.getElementById('dayModal');
    const modalBody = document.getElementById('modalBody');
    
    const date = new Date(dateStr);
    const formattedDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    let riskLevel = 'Low';
    let riskColor = '#51cf66';
    if (noshowPct >= 40) {
      riskLevel = 'High';
      riskColor = '#ff6b6b';
    } else if (noshowPct >= 20) {
      riskLevel = 'Medium';
      riskColor = '#ffd43b';
    }
    
    let peakLevel = peakClass === 'high' ? 'High' : peakClass === 'medium' ? 'Medium' : 'Low';
    let peakColor = peakClass === 'high' ? '#ff6b6b' : peakClass === 'medium' ? '#ffd43b' : '#51cf66';

    const recommendation = noshowPct >= 40 
      ? 'High risk day. Send multiple reminders and consider overbooking slightly.'
      : noshowPct >= 20 
      ? 'Moderate risk. Send reminders 24-48h in advance.'
      : 'Low risk day. Standard procedures recommended.';

    modalBody.innerHTML = `
      <div class="detail-row">
        <span class="detail-label">Date:</span>
        <span class="detail-value">${formattedDate}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Expected Appointments:</span>
        <span class="detail-value"><strong>~${expectedCount}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Predicted Peak Hour:</span>
        <span class="detail-value"><strong>${predictedHour}:00</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Expected Demand:</span>
        <span class="detail-value"><strong style="color: ${peakColor};">${peakLevel}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">No-Show Risk:</span>
        <span class="detail-value"><strong style="color: ${riskColor};">${noshowPct}% (${riskLevel})</strong></span>
      </div>
    `;

    modal.classList.add('show');
  }

  function closeModal() {
    document.getElementById('dayModal').classList.remove('show');
  }

  document.getElementById('dayModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });

  function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth > 12) {
      currentMonth = 1;
      currentYear++;
    } else if (currentMonth < 1) {
      currentMonth = 12;
      currentYear--;
    }
    document.getElementById('monthSelect').value = currentMonth;
    document.getElementById('yearSelect').value = currentYear;
    renderCalendar();
  }

  function updateCalendar() {
    currentMonth = parseInt(document.getElementById('monthSelect').value);
    currentYear = parseInt(document.getElementById('yearSelect').value);
    renderCalendar();
  }

  function toggleDropdown(event) {
  event.preventDefault();
  event.stopPropagation(); // ADD THIS LINE
  const dropdown = event.currentTarget.parentElement;
  const menu = dropdown.querySelector('.dropdown-menu');
  menu.classList.toggle('show');
  }
  // Close dropdown if clicked outside
document.addEventListener('click', function(event) {
  if (!event.target.closest('.dropdown')) {
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(menu => menu.classList.remove('show'));
  }
});

  // Fetch appointments and initialize
  window.addEventListener('DOMContentLoaded', async () => {
    console.log('Page loaded, fetching appointments...');
    
    try {
      const SUPABASE_URL = 'https://pgapbbukmyitwuvfbgho.supabase.co';
      const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A';
      
      const response = await fetch(`${SUPABASE_URL}/rest/v1/appointments?select=*`, {
        headers: {
          'apikey': SUPABASE_KEY,
          'Authorization': `Bearer ${SUPABASE_KEY}`,
        }
      });
      
      if (response.ok) {
        appointments = await response.json();
        console.log('Appointments loaded:', appointments.length);
      } else {
        console.error('Error fetching appointments:', response.status);
        appointments = [];
      }
    } catch (error) {
      console.error('Error:', error);
      appointments = [];
    }

    document.getElementById('monthSelect').value = currentMonth;
    document.getElementById('yearSelect').value = currentYear;
    
    updateDashboard();
    renderCalendar();
    
    console.log('Dashboard initialized');
  });

  window.toggleSidebar = function() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }
};
document.addEventListener('DOMContentLoaded', function() {
  // ...your existing code...

  // Close sidebar when clicking a link on mobile
  const menuLinks = document.querySelectorAll('.menu a:not(.dropdown-toggle)');
  menuLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      }
    });
  });
});
</script>

</body>
</html>