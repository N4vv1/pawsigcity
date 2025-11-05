<?php
// Prevent any output before headers
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering immediately
ob_start();

// Increase resource limits
@ini_set('upload_max_filesize', '10M');
@ini_set('post_max_size', '10M');
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '300');

session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Log errors to file instead of displaying
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required POST data
    if (!isset($_POST['image_id']) || !isset($_POST['current_image_path'])) {
        throw new Exception('Missing required data');
    }

    $image_id = intval($_POST['image_id']);
    $current_image_path = trim($_POST['current_image_path']);

    if ($image_id <= 0) {
        throw new Exception('Invalid image ID');
    }

    // Check if new image was uploaded
    if (!isset($_FILES['image'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['image'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        $error_message = isset($upload_errors[$file['error']]) 
            ? $upload_errors[$file['error']] 
            : 'Unknown upload error';
        
        throw new Exception($error_message);
    }

    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];

    // Validate file exists in temp location
    if (!file_exists($fileTmpName) || !is_uploaded_file($fileTmpName)) {
        throw new Exception('Uploaded file not found');
    }

    // Get and validate file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($fileExt, $allowed)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP allowed');
    }

    // Check file size (max 5MB)
    if ($fileSize > 5000000) {
        throw new Exception('File size too large. Maximum 5MB allowed');
    }

    // Verify it's actually an image using getimagesize
    $imageInfo = @getimagesize($fileTmpName);
    if ($imageInfo === false) {
        throw new Exception('File is not a valid image');
    }

    // Generate unique filename
    $newFileName = uniqid('gallery_', true) . '.' . $fileExt;

    // Define upload path
    $uploadPath = __DIR__ . '/uploads/';

    // Create directory if it doesn't exist
    if (!file_exists($uploadPath)) {
        if (!mkdir($uploadPath, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Check if directory is writable
    if (!is_writable($uploadPath)) {
        throw new Exception('Upload directory is not writable');
    }

    $destination = $uploadPath . $newFileName;

    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $destination)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Set proper permissions on uploaded file
    @chmod($destination, 0644);

    // At this point, file upload successful. Now update database.
    // Store path in the same format as existing entries
    $new_image_path = 'gallery_dashboard/uploads/' . $newFileName;

    // Update database using parameterized query
    $update_query = "UPDATE gallery SET image_path = $1, uploaded_at = NOW() WHERE id = $2";
    $result = @pg_query_params($conn, $update_query, [$new_image_path, $image_id]);

    if (!$result) {
        // Database update failed - delete the new uploaded file
        if (file_exists($destination)) {
            @unlink($destination);
        }
        throw new Exception('Failed to update database: ' . pg_last_error($conn));
    }

    // Check if any row was actually updated
    $affected_rows = pg_affected_rows($result);
    if ($affected_rows === 0) {
        // No row was updated - delete the new file
        if (file_exists($destination)) {
            @unlink($destination);
        }
        throw new Exception('No image found with the given ID');
    }

    // Database updated successfully - now delete old image file
    // Extract just the filename from the database path
    $old_filename = basename($current_image_path);
    $old_file = __DIR__ . '/uploads/' . $old_filename;

    if (file_exists($old_file) && is_file($old_file)) {
        @unlink($old_file);
    }

    // Success!
    $_SESSION['success'] = "Image replaced successfully!";

} catch (Exception $e) {
    // Log the error
    error_log("Edit Image Error: " . $e->getMessage());
    
    // Set error message for user
    $_SESSION['error'] = $e->getMessage();
}

// Close database connection
if (isset($conn)) {
    pg_close($conn);
}

// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Redirect back to gallery
header("Location: gallery.php", true, 303);
exit();