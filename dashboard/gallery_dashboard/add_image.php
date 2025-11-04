<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
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
                    // Store relative path in database (relative to gallery_dashboard)
                    $imagePath = 'gallery_dashboard/uploads/' . $newFileName;
                    
                    // Insert into database
                    pg_prepare(
                        $conn,
                        "insert_gallery",
                        "INSERT INTO gallery (image_path) VALUES ($1)"
                    );
                    
                    $result = pg_execute($conn, "insert_gallery", [$imagePath]);
                    
                    if ($result) {
                        $_SESSION['success'] = "Image uploaded successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to save image to database.";
                        // Delete uploaded file if database insert fails
                        unlink($destination);
                    }
                } else {
                    $_SESSION['error'] = "Failed to upload image file.";
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

header("Location: gallery.php");
exit;
?>