<?php
session_start();
require '../db.php'; // $conn = pg_connect(...);

// ✅ If you want admin-only access, uncomment this
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//   header('Location: ../login/loginform.php');
//   exit;
// }

// Fetch all appointments with joins
$query = "
  SELECT a.*, 
         u.full_name AS client_name,
         u.user_id,
         p.name AS pet_name,
         p.breed AS pet_breed,
         pk.name AS package_name
  FROM appointments a
  JOIN users u ON a.user_id = u.user_id
  JOIN pets p ON a.pet_id = p.pet_id
  JOIN packages pk ON a.package_id = pk.id
  ORDER BY a.appointment_date DESC
";

$appointments = pg_query($conn, $query);

// Count pending approvals and cancellation requests
$today = date('Y-m-d');

// Use parameterized queries to avoid SQL injection
$pendingApprovals = pg_query_params(
    $conn,
    "SELECT * FROM appointments WHERE DATE(appointment_date) = $1 AND is_approved = 0",
    [$today]
);

$pendingCancellations = pg_query(
    $conn,
    "SELECT * FROM appointments WHERE cancel_requested = 1"
);
?>
