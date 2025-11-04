<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = intval($_POST['image_id']);
    
    // Get image path before deleting
    pg_prepare($conn, "get_image", "SELECT image_path FROM gallery WHERE id = $1");
    $result = pg_execute($conn, "get_image", [$image_id]);
    
    if ($result && pg_num_rows($result) > 0) {
        $image = pg_fetch_assoc($result);
        $image_path = $image['image_path'];
        
        // Delete from database
        pg_prepare($conn, "delete_gallery", "DELETE FROM gallery WHERE id = $1");
        $delete_result = pg_execute($conn, "delete_gallery", [$image_id]);
        
        if ($delete_result) {
            // Delete physical file
            // Extract just the filename from the path
            $filename = basename($image_path);
            $file_path = __DIR__ . '/uploads/' . $filename;
            
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $_SESSION['success'] = "Image deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete image from database.";
        }
    } else {
        $_SESSION['error'] = "Image not found.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: gallery.php");
exit;
?>
