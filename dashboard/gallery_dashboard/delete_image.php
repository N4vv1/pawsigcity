<?php
session_start();
require '../../db.php';
require_once '../admin/check_admin.php';

// Supabase configuration
$supabaseUrl = 'https://pgapbbukmyitwuvfbgho.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A';
$bucketName = 'gallery-images'; // Using GALLERY-IMAGES bucket (uppercase)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = intval($_POST['image_id']);
    $current_image_path = $_POST['current_image_path'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExt, $allowed)) {
            if ($fileSize < 5000000) {
                $newFileName = uniqid('gallery_', true) . '.' . $fileExt;
                
                try {
                    // Read file content
                    $fileContent = file_get_contents($fileTmpName);
                    $mimeType = mime_content_type($fileTmpName);
                    
                    // Upload new image to Supabase
                    $uploadUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$newFileName}";
                    
                    $ch = curl_init($uploadUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $fileContent,
                        CURLOPT_HTTPHEADER => [
                            "Authorization: Bearer {$supabaseKey}",
                            "Content-Type: {$mimeType}",
                            "x-upsert: false"
                        ],
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_TIMEOUT => 30
                    ]);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    
                    // Log for debugging
                    error_log("=== EDIT UPLOAD DEBUG ===");
                    error_log("Upload URL: {$uploadUrl}");
                    error_log("HTTP Code: {$httpCode}");
                    error_log("Response: {$response}");
                    
                    if ($httpCode === 200 || $httpCode === 201) {
                        // Construct new public URL
                        $newImagePath = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$newFileName}";
                        
                        // Update database
                        $query = "UPDATE gallery SET image_path = $1 WHERE id = $2";
                        $result = pg_query_params($conn, $query, [$newImagePath, $image_id]);
                        
                        if ($result && pg_affected_rows($result) > 0) {
                            // Delete old image from Supabase Storage
                            if (strpos($current_image_path, '/storage/v1/object/public/GALLERY-IMAGES/') !== false) {
                                $oldFilename = basename($current_image_path);
                                $deleteUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$oldFilename}";
                                
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
                                    error_log("Failed to delete old file from storage: " . $deleteResponse);
                                }
                            }
                            
                            $_SESSION['success'] = "Image replaced successfully!";
                        } else {
                            // Delete newly uploaded file if database update fails
                            $deleteUrl = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$newFileName}";
                            $ch = curl_init($deleteUrl);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                "Authorization: Bearer {$supabaseKey}"
                            ]);
                            curl_exec($ch);
                            curl_close($ch);
                            
                            $_SESSION['error'] = "Failed to update database.";
                        }
                    } else {
                        // Parse error response
                        $errorDetail = json_decode($response, true);
                        $errorMsg = isset($errorDetail['message']) ? $errorDetail['message'] : $response;
                        $_SESSION['error'] = "Upload failed (HTTP {$httpCode}): {$errorMsg}";
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Upload error: " . $e->getMessage();
                    error_log("Exception: " . $e->getMessage());
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