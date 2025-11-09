<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Initialize Supabase Storage
$supabaseUrl = getenv('https://pgapbbukmyitwuvfbgho.supabase.co');
$supabaseKey = getenv('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = intval($_POST['image_id']);
    
    try {
        // Get image path before deleting
        pg_prepare($conn, "get_image", "SELECT image_path FROM gallery WHERE id = $1");
        $result = pg_execute($conn, "get_image", [$image_id]);
        
        if ($result && pg_num_rows($result) > 0) {
            $image = pg_fetch_assoc($result);
            $image_path = $image['image_path'];
            
            // Delete from database first
            pg_prepare($conn, "delete_gallery", "DELETE FROM gallery WHERE id = $1");
            $delete_result = pg_execute($conn, "delete_gallery", [$image_id]);
            
            if ($delete_result && pg_affected_rows($delete_result) > 0) {
                // Delete from Supabase Storage
                // Check if it's a Supabase URL
                if (strpos($image_path, '/storage/v1/object/public/pet-images/') !== false) {
                    $filename = basename($image_path);
                    
                    $deleteUrl = "{$supabaseUrl}/storage/v1/object/pet-images/{$filename}";
                    
                    $ch = curl_init($deleteUrl);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer {$supabaseKey}"
                    ]);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    // Log if deletion fails but don't throw error
                    if ($httpCode !== 200 && $httpCode !== 204) {
                        error_log("Failed to delete file from storage: " . $response);
                    }
                } else {
                    // Old local file path - try to delete from local filesystem
                    $filename = basename($image_path);
                    $file_path = __DIR__ . '/uploads/' . $filename;
                    
                    if (file_exists($file_path)) {
                        @unlink($file_path);
                    }
                }
                
                $_SESSION['success'] = "Image deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete image from database.";
            }
        } else {
            $_SESSION['error'] = "Image not found.";
        }
    } catch (Exception $e) {
        error_log("Delete Image Error: " . $e->getMessage());
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: gallery.php");
exit;
?>