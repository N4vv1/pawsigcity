<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    // Fetch all closed dates
    $query = "SELECT closed_date_id, closed_date, reason, created_at 
              FROM closed_dates 
              ORDER BY closed_date ASC";
    
    $result = pg_query($conn, $query);
    
    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }
    
    $dates = [];
    while ($row = pg_fetch_assoc($result)) {
        $dates[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'dates' => $dates
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>