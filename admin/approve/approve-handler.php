<?php
require '../../db.php';

if (isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $stmt = $mysqli->prepare("UPDATE appointments SET is_approved = 1 WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
}

header("Location: view-appointments.php");
exit;
