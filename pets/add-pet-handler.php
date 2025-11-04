<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/loginform.php');
        exit;
    }

    $user_id = intval($_SESSION['user_id']);

    // ✅ Collect basic pet info (with new required fields)
    $name = trim($_POST['name'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $age = !empty($_POST['age']) ? floatval($_POST['age']) : null;
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
    $color = trim($_POST['color'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;

    // ✅ Collect health info
    $allergies = trim($_POST['allergies'] ?? '');
    $medications = trim($_POST['medications'] ?? '');
    $medical_conditions = trim($_POST['medical_conditions'] ?? '');

    // ✅ Collect behavior info
    $behavior_notes = trim($_POST['behavior_notes'] ?? '');
    $nail_trimming = trim($_POST['nail_trimming'] ?? 'Yes');
    $haircut_style = trim($_POST['haircut_style'] ?? '');

    // ✅ VALIDATION: Required fields
    if (empty($name) || empty($breed) || empty($species) || empty($gender) || empty($size) || $weight === null || $weight <= 0) {
        $_SESSION['error'] = "⚠️ Please fill in all required fields: Name, Breed, Species, Gender, Size, and Weight.";
        header('Location: add-pet.php');
        exit;
    }

    // ✅ VALIDATION: Species must be Dog or Cat
    if (!in_array($species, ['Dog', 'Cat'])) {
        $_SESSION['error'] = "⚠️ Species must be either Dog or Cat.";
        header('Location: add-pet.php');
        exit;
    }

    // ✅ VALIDATION: Size must be Small, Medium, or Large
    if (!in_array($size, ['Small', 'Medium', 'Large'])) {
        $_SESSION['error'] = "⚠️ Size must be Small, Medium, or Large.";
        header('Location: add-pet.php');
        exit;
    }

    // ✅ VALIDATION: Weight should match size guidelines (soft warning)
    $weight_warnings = [];
    if ($size === 'Small' && $weight > 10) {
        $weight_warnings[] = "Weight seems high for a Small pet (typically under 10 kg).";
    }
    if ($size === 'Medium' && ($weight < 10 || $weight > 25)) {
        $weight_warnings[] = "Weight seems outside typical range for Medium pets (10-25 kg).";
    }
    if ($size === 'Large' && $weight < 25) {
        $weight_warnings[] = "Weight seems low for a Large pet (typically 25+ kg).";
    }

    // ✅ Handle photo upload to Supabase Storage with compression
    $photo_url = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['photo']['type'];

        if (in_array($file_type, $allowed_types)) {
            // Get Supabase credentials from environment variables
            $supabase_url = getenv('SUPABASE_URL');
            $supabase_key = getenv('SUPABASE_KEY');

            if (!$supabase_url || !$supabase_key) {
                $_SESSION['error'] = "❌ Supabase configuration missing. Please contact administrator.";
                header('Location: add-pet.php');
                exit;
            }

            // ✅ COMPRESS AND RESIZE IMAGE
            $max_width = 1920;
            $max_height = 1920;
            $quality = 85; // 85% quality - great balance between quality and file size
            
            list($width, $height) = getimagesize($_FILES['photo']['tmp_name']);
            
            // Determine if we need to resize
            $needs_resize = ($width > $max_width || $height > $max_height);
            
            if ($needs_resize) {
                // Calculate new dimensions
                $ratio = min($max_width / $width, $max_height / $height);
                $new_width = round($width * $ratio);
                $new_height = round($height * $ratio);
            } else {
                $new_width = $width;
                $new_height = $height;
            }
            
            // Create image resource based on type
            $source = null;
            switch ($file_type) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($_FILES['photo']['tmp_name']);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($_FILES['photo']['tmp_name']);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($_FILES['photo']['tmp_name']);
                    break;
                case 'image/webp':
                    $source = imagecreatefromwebp($_FILES['photo']['tmp_name']);
                    break;
            }
            
            if (!$source) {
                $_SESSION['error'] = "❌ Failed to process image. Please try a different file.";
                header('Location: add-pet.php');
                exit;
            }
            
            // Create new image
            $dest = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG/GIF
            if ($file_type === 'image/png' || $file_type === 'image/gif') {
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
                $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
                imagefilledrectangle($dest, 0, 0, $new_width, $new_height, $transparent);
            }
            
            // Resize
            imagecopyresampled($dest, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // Save to temp file
            $temp_file = tempnam(sys_get_temp_dir(), 'pet_img_');
            imagejpeg($dest, $temp_file, $quality); // Always save as JPEG for consistency
            
            // Get compressed file content
            $file_content = file_get_contents($temp_file);
            $compressed_size = strlen($file_content);
            
            // Clean up
            imagedestroy($source);
            imagedestroy($dest);
            unlink($temp_file);
            
            error_log("Image compressed: {$width}x{$height} -> {$new_width}x{$new_height}, Size: " . round($compressed_size/1024) . "KB");

            // Generate unique filename (always .jpg since we convert to JPEG)
            $unique_filename = uniqid('pet_' . $user_id . '_', true) . '.jpg';

            // Upload to Supabase Storage
            $upload_url = $supabase_url . '/storage/v1/object/pet-images/' . $unique_filename;

            $ch = curl_init($upload_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $supabase_key,
                'Content-Type: image/jpeg',
                'apikey: ' . $supabase_key
            ]);

            $start_time = microtime(true);
            $response = curl_exec($ch);
            $upload_time = round(microtime(true) - $start_time, 2);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            error_log("Upload took {$upload_time}s - HTTP: $http_code");

            if ($http_code === 200 || $http_code === 201) {
                // Success! Generate public URL
                $photo_url = $supabase_url . '/storage/v1/object/public/pet-images/' . $unique_filename;
            } else {
                error_log("Supabase upload failed. HTTP Code: $http_code, Response: $response");
                $_SESSION['error'] = "❌ Failed to upload photo. Please try again.";
                header('Location: add-pet.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "⚠️ Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.";
            header('Location: add-pet.php');
            exit;
        }
    }

    // ✅ Insert into pets table with NEW fields (species, size, weight)
    $query = "INSERT INTO pets (
        user_id, name, breed, species, gender, age, birthday, color, size, weight, photo_url
    ) VALUES (
        $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11
    ) RETURNING pet_id";
    
    $result = pg_query_params($conn, $query, [
        $user_id, 
        $name, 
        $breed, 
        $species,
        $gender, 
        $age, 
        $birthday, 
        $color, 
        $size,
        $weight,
        $photo_url
    ]);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $pet_id = $row['pet_id'];

        // ✅ Insert into health_info
        $query_health = "INSERT INTO health_info (pet_id, allergies, medications, medical_conditions)
                         VALUES ($1, $2, $3, $4)";
        pg_query_params($conn, $query_health, [$pet_id, $allergies, $medications, $medical_conditions]);

        // ✅ Insert into behavior_preferences
        $query_behavior = "INSERT INTO behavior_preferences (pet_id, behavior_notes, nail_trimming, haircut_style)
                           VALUES ($1, $2, $3, $4)";
        pg_query_params($conn, $query_behavior, [$pet_id, $behavior_notes, $nail_trimming, $haircut_style]);

        // ✅ Success message with optional weight warning
        $success_message = "✅ Pet '{$name}' added successfully!";
        
        if (!empty($weight_warnings)) {
            $success_message .= " Note: " . implode(' ', $weight_warnings);
        }
        
        $_SESSION['success'] = $success_message;
        header("Location: pet-profile.php");
        exit;
    } else {
        $_SESSION['error'] = "❌ Error adding pet: " . pg_last_error($conn);
        header('Location: add-pet.php');
        exit;
    }
} else {
    $_SESSION['error'] = "⚠️ Invalid request.";
    header('Location: add-pet.php');
    exit;
}
?>