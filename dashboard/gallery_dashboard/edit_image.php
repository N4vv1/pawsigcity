<?php
// Start output buffering to prevent header issues
ob_start();

session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = intval($_POST['image_id']);
    $current_image_path = $_POST['current_image_path'];
    
    // Check if new image was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExt, $allowed)) {
            // Check file size (max 5MB)
            if ($fileSize < 5000000) {
                // Generate unique filename
                $newFileName = uniqid('gallery_', true) . '.' . $fileExt;
                
                // NEW PATH: uploads folder inside gallery_dashboard
                $uploadPath = __DIR__ . '/uploads/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $destination = $uploadPath . $newFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $destination)) {
                    // Delete old image file
                    // Extract just the filename from the path
                    $old_filename = basename($current_image_path);
                    $old_file = __DIR__ . '/uploads/' . $old_filename;
                    
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                    
                    // Update database with new image path
                    $new_image_path = 'gallery_dashboard/uploads/' . $newFileName;
                    
                    // Use pg_query_params instead of prepare/execute to avoid conflicts
                    $result = pg_query_params(
                        $conn,
                        "UPDATE gallery SET image_path=$1, uploaded_at=NOW() WHERE id=$2",
                        [$new_image_path, $image_id]
                    );
                    
                    if ($result) {
                        $_SESSION['success'] = "Image replaced successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to update image in database.";
                        // Delete new file if database update fails
                        if (file_exists($destination)) {
                            unlink($destination);
                        }
                    }
                } else {
                    $_SESSION['error'] = "Failed to upload new image.";
                }
            } else {
                $_SESSION['error'] = "File size too large. Maximum 5MB allowed.";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP allowed.";
        }
    } else {
        $_SESSION['error'] = "No file uploaded or upload error occurred.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

// Clear output buffer and redirect
ob_end_clean();
header("Location: gallery.php");
exit;
?>