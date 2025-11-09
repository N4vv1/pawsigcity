<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Initialize Supabase Storage Client
$supabaseUrl = getenv('pgapbbukmyitwuvfbgho'); // e.g., https://xxxxx.supabase.co
$supabaseKey = getenv('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A'); // Your service_role or anon key

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                
                try {
                    // Read file content
                    $fileContent = file_get_contents($fileTmpName);
                    
                    // Get MIME type
                    $mimeType = mime_content_type($fileTmpName);
                    
                    // Upload to Supabase Storage using REST API
                    $uploadUrl = "{$supabaseUrl}/storage/v1/object/gallery-images/{$newFileName}";
                    
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
                    curl_close($ch);
                    
                    if ($httpCode === 200 || $httpCode === 201) {
                        // Get public URL
                        $imagePath = "{$supabaseUrl}/storage/v1/object/public/gallery-images/{$newFileName}";
                        
                        // Insert into database
                        pg_prepare($conn, "insert_gallery", "INSERT INTO gallery (image_path) VALUES ($1)");
                        $result = pg_execute($conn, "insert_gallery", [$imagePath]);
                        
                        if ($result) {
                            $_SESSION['success'] = "Image uploaded successfully!";
                        } else {
                            // Delete uploaded file if database insert fails
                            $deleteUrl = "{$supabaseUrl}/storage/v1/object/gallery-images/{$newFileName}";
                            $ch = curl_init($deleteUrl);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                "Authorization: Bearer {$supabaseKey}"
                            ]);
                            curl_exec($ch);
                            curl_close($ch);
                            
                            $_SESSION['error'] = "Failed to save image to database.";
                        }
                    } else {
                        $_SESSION['error'] = "Failed to upload image to storage: " . $response;
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Upload error: " . $e->getMessage();
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