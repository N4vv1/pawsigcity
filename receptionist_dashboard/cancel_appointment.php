<?php
include '../db.php';

if (!isset($_GET['id'])) {
    die("Appointment ID is missing");
}

$id = intval($_GET['id']);

// Set status to cancelled
$update_query = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = $id";
pg_query($conn, $update_query);

header("Location: receptionist_home.php");
exit;
?>
