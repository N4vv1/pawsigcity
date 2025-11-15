<?php
header('Content-Type: application/json');
require_once '../db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            // Fetch all closed dates
            $query = "SELECT closed_date_id, closed_date, reason, created_at, created_by 
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
            break;
            
        case 'add':
            $closed_date = $_POST['closed_date'] ?? '';
            $reason = $_POST['reason'] ?? '';
            $created_by = $_POST['created_by'] ?? 'admin';
            
            if (empty($closed_date)) {
                throw new Exception('Date is required');
            }
            
            // Check if date already exists
            $check = pg_query_params($conn, 
                "SELECT closed_date_id FROM closed_dates WHERE closed_date = $1",
                [$closed_date]
            );
            
            if (pg_num_rows($check) > 0) {
                throw new Exception('This date is already marked as closed');
            }
            
            // Insert new closed date
            $insert = pg_query_params($conn,
                "INSERT INTO closed_dates (closed_date, reason, created_by) 
                 VALUES ($1, $2, $3) 
                 RETURNING closed_date_id",
                [$closed_date, $reason, $created_by]
            );
            
            if (!$insert) {
                throw new Exception(pg_last_error($conn));
            }
            
            $row = pg_fetch_assoc($insert);
            
            echo json_encode([
                'success' => true,
                'message' => 'Closed date added successfully',
                'closed_date_id' => $row['closed_date_id']
            ]);
            break;
            
        case 'delete':
            $closed_date_id = $_POST['closed_date_id'] ?? '';
            
            if (empty($closed_date_id)) {
                throw new Exception('Date ID is required');
            }
            
            $delete = pg_query_params($conn,
                "DELETE FROM closed_dates WHERE closed_date_id = $1",
                [$closed_date_id]
            );
            
            if (!$delete) {
                throw new Exception(pg_last_error($conn));
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Closed date removed successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>