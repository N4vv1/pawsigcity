<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = intval($_POST['pet_id']);
    $name = trim($_POST['name'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $age = !empty($_POST['age']) ? floatval($_POST['age']) : null;
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
    $color = trim($_POST['color'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    // ✅ Handle photo upload to Supabase Storage (if provided)
    $photo_url = null; // null means don't update photo
    
    if (isset($_FILES['photo_url']) && $_FILES['photo_url']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['_url']['type'];

        if (in_array($file_type, $allowed_types)) {
            // Get Supabase credentials
            $supabase_url = getenv('SUPABASE_URL');
            $supabase_key = getenv('SUPABASE_KEY');

            if (!$supabase_url || !$supabase_key) {
                $_SESSION['error'] = "❌ Supabase configuration missing.";
                header('Location: pet-profile.php');
                exit;
            }

            // Get user_id for filename
            $user_query = "SELECT user_id FROM pets WHERE pet_id = $1";
            $user_result = pg_query_params($conn, $user_query, [$pet_id]);
            $user_row = pg_fetch_assoc($user_result);
            $user_id = $user_row['user_id'] ?? 0;

            // Generate unique filename
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('pet_' . $user_id . '_', true) . '.' . $file_extension;

            // Read file content
            $file_content = file_get_contents($_FILES['photo']['tmp_name']);

            // Upload to Supabase Storage
            $upload_url = $supabase_url . '/storage/v1/object/pet-images/' . $unique_filename;

            $ch = curl_init($upload_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $supabase_key,
                'Content-Type: ' . $file_type,
                'apikey: ' . $supabase_key
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 || $http_code === 201) {
                // Success! Generate public URL
                $photo_url = $supabase_url . '/storage/v1/object/public/pet-images/' . $unique_filename;
                
                // Optional: Delete old photo from Supabase if it exists
                $old_photo_query = "SELECT photo_url FROM pets WHERE pet_id = $1";
                $old_photo_result = pg_query_params($conn, $old_photo_query, [$pet_id]);
                $old_photo_row = pg_fetch_assoc($old_photo_result);
                
                if (!empty($old_photo_row['photo_url'])) {
                    // Extract filename from old URL and delete it
                    $old_url = $old_photo_row['photo_url'];
                    $old_filename = basename(parse_url($old_url, PHP_URL_PATH));
                    
                    $delete_url = $supabase_url . '/storage/v1/object/pet-images/' . $old_filename;
                    $ch_delete = curl_init($delete_url);
                    curl_setopt($ch_delete, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch_delete, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_delete, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $supabase_key,
                        'apikey: ' . $supabase_key
                    ]);
                    curl_exec($ch_delete);
                    curl_close($ch_delete);
                }
            } else {
                error_log("Supabase upload failed. HTTP Code: $http_code, Response: $response");
                $_SESSION['error'] = "❌ Failed to upload photo. Please try again.";
                header('Location: pet-profile.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "⚠️ Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.";
            header('Location: pet-profile.php');
            exit;
        }
    }

    // ✅ Update pet info (include photo_url only if new photo was uploaded)
    if ($photo_url !== null) {
        // Update with new photo
        $query = "
            UPDATE pets 
            SET name = $1, breed = $2, age = $3, birthday = $4, color = $5, gender = $6, photo_url = $7
            WHERE pet_id = $8
        ";
        $result = pg_query_params($conn, $query, [
            $name, $breed, $age, $birthday, $color, $gender, $photo_url, $pet_id
        ]);
    } else {
        // Update without changing photo
        $query = "
            UPDATE pets 
            SET name = $1, breed = $2, age = $3, birthday = $4, color = $5, gender = $6
            WHERE pet_id = $7
        ";
        $result = pg_query_params($conn, $query, [
            $name, $breed, $age, $birthday, $color, $gender, $pet_id
        ]);
    }

    if ($result) {
        $_SESSION['success'] = "✅ Pet profile updated successfully!";
    } else {
        $_SESSION['error'] = "❌ Failed to update pet profile: " . pg_last_error($conn);
    }

    header("Location: pet-profile.php");
    exit;
}
?>