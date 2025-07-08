<?php
require '../db.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: manage-appointments.php");
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM appointments WHERE appointment_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: manage-appointments.php?deleted=1");
} else {
    header("Location: manage-appointments.php?deleted=0");
}
exit;
