<?php
require '../../db.php';

if (isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);

    $stmt = $mysqli->prepare("UPDATE appointments SET is_approved = 1, status = 'confirmed' WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

   header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php?approved=1&show=appointments");
    exit;
} else {
    header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php");
    exit;
}
