<?php
require_once '../../db.php'; // $conn = pg_connect(...);

date_default_timezone_set('Asia/Manila'); // Ensure correct timezone

// ✅ Update all "confirmed" appointments where appointment_date + 15min < now
$result = pg_query($conn, "
    UPDATE appointments
    SET status = 'no_show'
    WHERE status = 'confirmed'
      AND (appointment_date + INTERVAL '15 minutes') < NOW()
    RETURNING appointment_id
");

// ✅ Count how many were updated
$affected = pg_num_rows($result);

header("Location: ../admin_dashboard/home.php?show=appointments&noshows=$affected");
exit;
?>
