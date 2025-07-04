<?php
require '../../db.php';

$appointment_id = $_POST['appointment_id'];
$method = $_POST['payment_method'];

// You can add GCash validation or screenshot proof logic here

$mysqli->query("UPDATE appointments SET payment_method = '$method', payment_status = 'paid' WHERE appointment_id = $appointment_id");

session_start();
$_SESSION['success'] = "Payment successful!";
header("Location: payment.php");
exit;
