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
    $size = trim($_POST['size'] ?? '');
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    
    // Health Info
    $allergies = trim($_POST['allergies'] ?? '');
    $medications = trim($_POST['medications'] ?? '');
    $medical_conditions = trim($_POST['medical_conditions'] ?? '');
    
    // Behavior & Preferences
    $behavior_notes = trim($_POST['behavior_notes'] ?? '');
    $nail_trimming = trim($_POST['nail_trimming'] ?? '');
    $haircut_style = trim($_POST['haircut_style'] ?? '');

    // ✅ Handle photo upload to Supabase Storage (if provided)
    $photo_url = null;
    
    if (isset($_FILES['photo_url']) && $_FILES['photo_url']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['photo_url']['type'];

        if (in_array($file_type, $allowed_types)) {
            $supabase_url = getenv('SUPABASE_URL');
            $supabase_key = getenv('SUPABASE_KEY');

            if (!$supabase_url || !$supabase_key) {
                $_SESSION['error'] = "❌ Supabase configuration missing.";
                header('Location: pet-profile.php');
                exit;
            }

            $user_query = "SELECT user_id FROM pets WHERE pet_id = $1";
            $user_result = pg_query_params($conn, $user_query, [$pet_id]);
            $user_row = pg_fetch_assoc($user_result);
            $user_id = $user_row['user_id'] ?? 0;

            $file_extension = pathinfo($_FILES['photo_url']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('pet_' . $user_id . '_', true) . '.' . $file_extension;
            $file_content = file_get_contents($_FILES['photo_url']['tmp_name']);

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
                $photo_url = $supabase_url . '/storage/v1/object/public/pet-images/' . $unique_filename;
                
                $old_photo_query = "SELECT photo_url FROM pets WHERE pet_id = $1";
                $old_photo_result = pg_query_params($conn, $old_photo_query, [$pet_id]);
                $old_photo_row = pg_fetch_assoc($old_photo_result);
                
                if (!empty($old_photo_row['photo_url'])) {
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

    // ✅ Update pet info
    if ($photo_url !== null) {
        $query = "
            UPDATE pets 
            SET name = $1, breed = $2, age = $3, birthday = $4, color = $5, gender = $6, photo_url = $7, size = $8, weight = $9
            WHERE pet_id = $10
        ";
        $result = pg_query_params($conn, $query, [
            $name, $breed, $age, $birthday, $color, $gender, $photo_url, $size, $weight, $pet_id
        ]);
    } else {
        $query = "
            UPDATE pets 
            SET name = $1, breed = $2, age = $3, birthday = $4, color = $5, gender = $6, size = $7, weight = $8
            WHERE pet_id = $9
        ";
        $result = pg_query_params($conn, $query, [
            $name, $breed, $age, $birthday, $color, $gender, $size, $weight, $pet_id
        ]);
    }

    if (!$result) {
        $_SESSION['error'] = "❌ Failed to update pet profile: " . pg_last_error($conn);
        header("Location: pet-profile.php");
        exit;
    }

    // ✅ Update or Insert Health Info
    $health_check = pg_query_params($conn, "SELECT health_id FROM health_info WHERE pet_id = $1", [$pet_id]);
    
    if (pg_num_rows($health_check) > 0) {
        // Update existing
        $health_query = "
            UPDATE health_info 
            SET allergies = $1, medications = $2, medical_conditions = $3
            WHERE pet_id = $4
        ";
        pg_query_params($conn, $health_query, [$allergies, $medications, $medical_conditions, $pet_id]);
    } else {
        // Insert new
        $health_query = "
            INSERT INTO health_info (pet_id, allergies, medications, medical_conditions)
            VALUES ($1, $2, $3, $4)
        ";
        pg_query_params($conn, $health_query, [$pet_id, $allergies, $medications, $medical_conditions]);
    }

    // ✅ Update or Insert Behavior Preferences
    $behavior_check = pg_query_params($conn, "SELECT preference_id FROM behavior_preferences WHERE pet_id = $1", [$pet_id]);
    
    if (pg_num_rows($behavior_check) > 0) {
        // Update existing
        $behavior_query = "
            UPDATE behavior_preferences 
            SET behavior_notes = $1, nail_trimming = $2, haircut_style = $3
            WHERE pet_id = $4
        ";
        pg_query_params($conn, $behavior_query, [$behavior_notes, $nail_trimming, $haircut_style, $pet_id]);
    } else {
        // Insert new
        $behavior_query = "
            INSERT INTO behavior_preferences (pet_id, behavior_notes, nail_trimming, haircut_style)
            VALUES ($1, $2, $3, $4)
        ";
        pg_query_params($conn, $behavior_query, [$pet_id, $behavior_notes, $nail_trimming, $haircut_style]);
    }

    $_SESSION['success'] = "✅ Pet profile updated successfully!";
    header("Location: pet-profile.php");
    exit;
}
?>