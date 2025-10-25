<?php
session_start();
require '../../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get and sanitize input data
  $first_name  = trim($_POST['first_name']);
  $middle_name = trim($_POST['middle_name']);
  $last_name   = trim($_POST['last_name']);
  $email       = trim($_POST['email']);
  $password    = $_POST['password'];
  $phone       = trim($_POST['phone']);
  $role        = 'customer';

  // Basic validation
  if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    header("Location: loginform.php?error=" . urlencode("All required fields must be filled."));
    exit;
  }

  // Validate email format
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: loginform.php?error=" . urlencode("Invalid email format."));
    exit;
  }

  // Validate password length (minimum 6 characters)
  if (strlen($password) < 6) {
    header("Location: loginform.php?error=" . urlencode("Password must be at least 6 characters long."));
    exit;
  }

  // Hash the password
  $hashed_password = password_hash($password, PASSWORD_BCRYPT);

  // Check if email already exists
  $check_query = "SELECT 1 FROM users WHERE email = $1";
  $check_result = pg_query_params($conn, $check_query, [$email]);

  if (!$check_result) {
    header("Location: loginform.php?error=" . urlencode("Database error: " . pg_last_error($conn)));
    exit;
  }

  if (pg_num_rows($check_result) > 0) {
    header("Location: loginform.php?error=" . urlencode("Email is already registered."));
    exit;
  }

  // Insert new user
  $insert_query = "
    INSERT INTO users (first_name, middle_name, last_name, email, password, phone, role) 
    VALUES ($1, $2, $3, $4, $5, $6, $7)
  ";
  
  $insert_result = pg_query_params($conn, $insert_query, [
    $first_name, 
    $middle_name, 
    $last_name, 
    $email, 
    $hashed_password, 
    $phone, 
    $role
  ]);

  if ($insert_result) {
    // Registration successful
    $_SESSION['success'] = "Registration successful! Please login with your credentials.";
    header("Location: loginform.php");
    exit;
  } else {
    // Registration failed
    $error_message = pg_last_error($conn);
    header("Location: loginform.php?error=" . urlencode("Registration failed: " . $error_message));
    exit;
  }
} else {
  // If not POST request, redirect to login form
  header("Location: loginform.php");
  exit;
}
?>