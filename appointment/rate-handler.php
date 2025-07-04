<?php
require '../db.php';

$id = $_POST['appointment_id'];
$rating = $_POST['rating'];
$feedback = $_POST['feedback'];

$mysqli->query("UPDATE appointments SET rating = $rating, feedback = '$feedback' WHERE appointment_id = $id");

session_start();
$_SESSION['success'] = "Thank you for your feedback!";
header("Location: rate.php");
exit;
