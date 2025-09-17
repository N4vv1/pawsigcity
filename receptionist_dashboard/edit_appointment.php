<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['appointment_id']);
    $date = pg_escape_string($conn, $_POST['appointment_date']);
    $package_id = intval($_POST['package_id']);
    $status = pg_escape_string($conn, $_POST['status']);

    $query = "
        UPDATE appointments 
        SET appointment_date = '$date', package_id = $package_id, status = '$status'
        WHERE appointment_id = $id
    ";
    pg_query($conn, $query);

    header("Location: receptionist_home.php");
    exit;
}
?>
