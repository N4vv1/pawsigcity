<?php
header('Content-Type: application/json');
session_start();
require '../../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $first_name  = trim($_POST['first_name']);
  $middle_name = trim($_POST['middle_name']);
  $last_name   = trim($_POST['last_name']);
  $email       = trim($_POST['email']);
  $password    = $_POST['password'];
  $phone       = trim($_POST['phone']);
  $role        = 'customer';

  // Validation
  if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
  }

  if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
  }

  $hashed_password = password_hash($password, PASSWORD_BCRYPT);

  // Check if email exists
  $check_query = "SELECT 1 FROM users WHERE email = $1";
  $check_result = pg_query_params($conn, $check_query, [$email]);

  if (!$check_result) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . pg_last_error($conn)]);
    exit;
  }

  if (pg_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
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
    echo json_encode(['success' => true, 'message' => 'Registration successful!']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . pg_last_error($conn)]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>