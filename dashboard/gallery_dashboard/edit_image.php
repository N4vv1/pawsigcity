<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

@ini_set('upload_max_filesize', '10M');
@ini_set('post_max_size', '10M');
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '300');

session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Supabase configuration
$supabaseUrl = 'https://pgapbbukmyitwuvfbgho.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A';
$bucketName = 'gallery-images';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['image_id']) || !isset($_POST['current_image_path'])) {
        throw new Exception('Missing required data');
    }

    $image_id = intval($_POST['image_id']);
    $current_image_path = trim($_POST['current_image_path']);

    if ($image_id <= 0) {
        throw new Exception('Invalid image ID');
    }

    if (!isset($_FILES['image'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['image'];

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

    if (!file_exists($fileTmpName) || !is_uploaded_file($fileTmpName)) {
        throw new Exception('Uploaded file not found');
    }

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($fileExt, $allowed)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP allowed');
    }

    if ($fileSize > 5000000) {
        throw new Exception('File size too large. Maximum 5MB allowed');
    }

    $imageInfo = @getimagesize($fileTmpName);
    if ($imageInfo === false) {
        throw new Exception('File is not a valid image');
    }

    // Generate unique filename
    $newFileName = uniqid('gallery_', true) . '.' . $fileExt;
    
    // Read file content
    $fileContent = file_get_contents($fileTmpName);
    if ($fileContent === false) {
        throw new Exception('Failed to read uploaded file');
    }
    
    $mimeType = mime_content_type($fileTmpName);
    
    // Upload to Supabase Storage
    $uploadUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$newFileName}";
    
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$supabaseKey}",
        "Content-Type: {$mimeType}",
        "x-upsert: false"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        throw new Exception('Failed to upload to storage (HTTP ' . $httpCode . '): ' . $response);
    }
    
    // Construct public URL for the new image
    $new_image_path = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$newFileName}";
    
    // Update database
    $update_query = "UPDATE gallery SET image_path = $1, uploaded_at = NOW() WHERE id = $2";
    $result = @pg_query_params($conn, $update_query, [$new_image_path, $image_id]);
    
    if (!$result) {
        // Database update failed - delete the newly uploaded file
        $deleteUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$newFileName}";
        $ch = curl_init($deleteUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$supabaseKey}"
        ]);
        curl_exec($ch);
        curl_close($ch);
        
        throw new Exception('Failed to update database: ' . pg_last_error($conn));
    }
    
    $affected_rows = pg_affected_rows($result);
    if ($affected_rows === 0) {
        // No row was updated - delete the newly uploaded file
        $deleteUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$newFileName}";
        $ch = curl_init($deleteUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$supabaseKey}"
        ]);
        curl_exec($ch);
        curl_close($ch);
        
        throw new Exception('No image found with the given ID');
    }
    
    // Database updated successfully - now delete old image from Supabase Storage
    if (strpos($current_image_path, '/storage/v1/object/public/pet-images/') !== false) {
        $old_filename = basename($current_image_path);
        
        $deleteUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$old_filename}";
        $ch = curl_init($deleteUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$supabaseKey}"
        ]);
        $deleteResponse = curl_exec($ch);
        $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($deleteHttpCode !== 200 && $deleteHttpCode !== 204) {
            error_log("Warning: Failed to delete old file from storage: " . $deleteResponse);
        }
    }
    
    $_SESSION['success'] = "Image replaced successfully!";

} catch (Exception $e) {
    error_log("Edit Image Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

if (isset($conn)) {
    pg_close($conn);
}

while (ob_get_level()) {
    ob_end_clean();
}

header("Location: gallery.php", true, 303);
exit();