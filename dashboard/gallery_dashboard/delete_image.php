<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Supabase configuration
$supabaseUrl = 'https://pgapbbukmyitwuvfbgho.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A';
$bucketName = 'gallery-images';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required POST data exists
    if (!isset($_POST['image_id']) || !isset($_POST['image_path'])) {
        $_SESSION['error'] = "Missing required data for deletion.";
        header("Location: gallery.php");
        exit;
    }

    $image_id = intval($_POST['image_id']);
    $image_path = $_POST['image_path'];
    
    try {
        // Delete from database first
        $query = "DELETE FROM gallery WHERE id = $1";
        $result = pg_query_params($conn, $query, [$image_id]);
        
        if ($result && pg_affected_rows($result) > 0) {
            // Database deletion successful - now delete from Supabase Storage
            // Extract filename from the full URL
            $filename = basename($image_path);
            
            $deleteUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$filename}";
            
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$supabaseKey}"
            ]);
            
            $deleteResponse = curl_exec($ch);
            $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log storage deletion result
            error_log("Storage deletion - HTTP Code: {$deleteHttpCode}, Response: {$deleteResponse}");
            
            $_SESSION['success'] = "Image deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete image from database or image not found.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Delete error: " . $e->getMessage();
        error_log("Exception during deletion: " . $e->getMessage());
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: gallery.php");
exit;
?>