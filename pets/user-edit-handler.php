<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../homepage/login/loginform.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];

// If password entered, hash it
if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $query = "UPDATE users SET first_name=$1, middle_name=$2, last_name=$3, email=$4, phone=$5, password=$6 WHERE user_id=$7";
    $params = [$first_name, $middle_name, $last_name, $email, $phone, $hashed, $user_id];
} else {
    $query = "UPDATE users SET first_name=$1, middle_name=$2, last_name=$3, email=$4, phone=$5 WHERE user_id=$6";
    $params = [$first_name, $middle_name, $last_name, $email, $phone, $user_id];
}

$result = pg_query_params($conn, $query, $params);

if ($result) {
    $_SESSION['notification'] = "Account updated successfully!";
    $_SESSION['notification_type'] = 'success';
    header("Location: pet-profile.php");
    exit;
} else {
    $_SESSION['notification'] = "Error updating account: " . pg_last_error($conn);
    $_SESSION['notification_type'] = 'error';
    header("Location: pet-profile.php");
    exit;
}