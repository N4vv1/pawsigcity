<?php
require '../../db.php'; // $conn from pg_connect

if (isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']); // sanitize input

    // Update using parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE appointments 
         SET is_approved = 1, status = 'confirmed' 
         WHERE appointment_id = $1",
        [$appointment_id]
    );

    if ($result) {
        header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php?approved=1&show=appointments");
        exit;
    } else {
        die("Error: " . pg_last_error($conn));
    }

} else {
    header("Location: http://localhost/purrfect-paws/dashboard/home_dashboard/home.php");
    exit;
}
