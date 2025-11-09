<?php
session_start();
header('Content-Type: application/json');

$verified = isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true;

echo json_encode(['verified' => $verified]);
?>