<?php
session_start();
require '../db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../homepage/login/loginform.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = $_POST['pet_id'] ?? null;
    
    if (!$pet_id) {
        header('Location: pet-profile.php?error=' . urlencode('Pet ID is required'));
        exit;
    }
    
    // Verify pet belongs to user
    $check = pg_query_params($conn, "SELECT user_id FROM pets WHERE pet_id = $1", [$pet_id]);
    if (!$check || pg_num_rows($check) === 0) {
        header('Location: pet-profile.php?error=' . urlencode('Pet not found'));
        exit;
    }
    
    $pet_owner = pg_fetch_assoc($check);
    if ($pet_owner['user_id'] != $user_id) {
        header('Location: pet-profile.php?error=' . urlencode('Unauthorized access'));
        exit;
    }
    
    // Get form data
    $name = $_POST['name'] ?? '';
    $breed = $_POST['breed'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $color = $_POST['color'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $size = $_POST['size'] ?? '';
    $weight = $_POST['weight'] ?? '';
    
    // Health info
    $allergies = $_POST['allergies'] ?? '';
    $medications = $_POST['medications'] ?? '';
    $medical_conditions = $_POST['medical_conditions'] ?? '';
    
    // Behavior info
    $behavior_notes = $_POST['behavior_notes'] ?? '';
    $nail_trimming = $_POST['nail_trimming'] ?? '';
    $haircut_style = $_POST['haircut_style'] ?? '';
    
    // Handle photo upload
    $photo_url = null;
    if (isset($_FILES['photo_url']) && $_FILES['photo_url']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['photo_url']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid('pet_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['photo_url']['tmp_name'], $upload_path)) {
                $photo_url = $upload_path;
            }
        }
    }
    
    // Start transaction
    pg_query($conn, "BEGIN");
    
    try {
        // Update pets table
        if ($photo_url) {
            $update_pet = pg_query_params($conn, 
                "UPDATE pets SET name = $1, breed = $2, age = $3, gender = $4, color = $5, 
                 birthday = $6, size = $7, weight = $8, photo_url = $9 
                 WHERE pet_id = $10",
                [$name, $breed, $age, $gender, $color, $birthday, $size, $weight, $photo_url, $pet_id]
            );
        } else {
            $update_pet = pg_query_params($conn, 
                "UPDATE pets SET name = $1, breed = $2, age = $3, gender = $4, color = $5, 
                 birthday = $6, size = $7, weight = $8 
                 WHERE pet_id = $9",
                [$name, $breed, $age, $gender, $color, $birthday, $size, $weight, $pet_id]
            );
        }
        
        if (!$update_pet) {
            throw new Exception("Failed to update pet information");
        }
        
        // Update or insert health info
        $health_check = pg_query_params($conn, "SELECT * FROM health_info WHERE pet_id = $1", [$pet_id]);
        
        if ($health_check && pg_num_rows($health_check) > 0) {
            $update_health = pg_query_params($conn,
                "UPDATE health_info SET allergies = $1, medications = $2, medical_conditions = $3 
                 WHERE pet_id = $4",
                [$allergies, $medications, $medical_conditions, $pet_id]
            );
        } else {
            $update_health = pg_query_params($conn,
                "INSERT INTO health_info (pet_id, allergies, medications, medical_conditions) 
                 VALUES ($1, $2, $3, $4)",
                [$pet_id, $allergies, $medications, $medical_conditions]
            );
        }
        
        if (!$update_health) {
            throw new Exception("Failed to update health information");
        }
        
        // Update or insert behavior preferences
        $behavior_check = pg_query_params($conn, "SELECT * FROM behavior_preferences WHERE pet_id = $1", [$pet_id]);
        
        if ($behavior_check && pg_num_rows($behavior_check) > 0) {
            $update_behavior = pg_query_params($conn,
                "UPDATE behavior_preferences SET behavior_notes = $1, nail_trimming = $2, haircut_style = $3 
                 WHERE pet_id = $4",
                [$behavior_notes, $nail_trimming, $haircut_style, $pet_id]
            );
        } else {
            $update_behavior = pg_query_params($conn,
                "INSERT INTO behavior_preferences (pet_id, behavior_notes, nail_trimming, haircut_style) 
                 VALUES ($1, $2, $3, $4)",
                [$pet_id, $behavior_notes, $nail_trimming, $haircut_style]
            );
        }
        
        if (!$update_behavior) {
            throw new Exception("Failed to update behavior preferences");
        }
        
        // Commit transaction
        pg_query($conn, "COMMIT");
        
        header('Location: pet-profile.php?pet_updated=1');
        exit;
        
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        header('Location: pet-profile.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: pet-profile.php');
    exit;
}
?>