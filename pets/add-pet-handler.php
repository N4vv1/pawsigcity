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
    $species = trim($_POST['species'] ?? ''); // ✅ NEW - REQUIRED
    $gender = trim($_POST['gender'] ?? '');
    $age = !empty($_POST['age']) ? floatval($_POST['age']) : null;
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
    $color = trim($_POST['color'] ?? '');
    $size = trim($_POST['size'] ?? ''); // ✅ NEW - REQUIRED
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null; // ✅ NEW - REQUIRED

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

    // Handle photo upload
    $photo_url = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['photo']['type'];

        if (in_array($file_type, $allowed_types)) {
            // ✅ Use existing uploads folder (one level up from pets folder)
            $upload_dir = dirname(__DIR__) . '/../uploads';
            
            // ✅ Check if uploads folder exists
            if (!is_dir($upload_dir)) {
                $_SESSION['error'] = "⚠️ Upload directory does not exist. Please contact administrator.";
                header('Location: add-pet.php');
                exit;
            }
            
            // ✅ Check if writable
            if (!is_writable($upload_dir)) {
                $_SESSION['error'] = "⚠️ Upload directory is not writable. Please contact administrator.";
                header('Location: add-pet.php');
                exit;
            }

            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('pet_', true) . '.' . $file_extension;
            $target_path = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                // ✅ Store relative path for database (for displaying in browser)
                $photo_url = '/../uploads' . $unique_filename;
            } else {
                $_SESSION['error'] = "⚠️ Failed to upload photo. Please try again or contact administrator.";
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
        $species,   // ✅ NEW
        $gender, 
        $age, 
        $birthday, 
        $color, 
        $size,      // ✅ NEW
        $weight,    // ✅ NEW
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