<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/loginform.php');
        exit;
    }

    $user_id = intval($_SESSION['user_id']); // sanitize

    // Collect pet info
    $name = $_POST['name'] ?? '';
    $breed = $_POST['breed'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
    $color = $_POST['color'] ?? '';

    // Collect health info
    $allergies = $_POST['allergies'] ?? '';
    $medications = $_POST['medications'] ?? '';
    $medical_conditions = $_POST['medical_conditions'] ?? '';

    // Collect behavior info
    $behavior_notes = $_POST['behavior_notes'] ?? '';
    $nail_trimming = $_POST['nail_trimming'] ?? 'Yes';
    $haircut_style = $_POST['haircut_style'] ?? '';

    // Handle photo upload
    $photo_url = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = time() . '_' . basename($_FILES['photo']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $photo_url = 'uploads/' . $filename;
        }
    }

    // Insert into pets table (returning pet_id)
    $query = "INSERT INTO pets (user_id, name, breed, gender, age, birthday, color, photo_url)
              VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING pet_id";
    $result = pg_query_params($conn, $query, [
        $user_id, $name, $breed, $gender, $age, $birthday, $color, $photo_url
    ]);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $pet_id = $row['pet_id'];

        // Insert into health_info
        $query_health = "INSERT INTO health_info (pet_id, allergies, medications, medical_conditions)
                         VALUES ($1, $2, $3, $4)";
        pg_query_params($conn, $query_health, [$pet_id, $allergies, $medications, $medical_conditions]);

        // Insert into behavior_preferences
        $query_behavior = "INSERT INTO behavior_preferences (pet_id, behavior_notes, nail_trimming, haircut_style)
                           VALUES ($1, $2, $3, $4)";
        pg_query_params($conn, $query_behavior, [$pet_id, $behavior_notes, $nail_trimming, $haircut_style]);

        $_SESSION['success'] = "Pet added successfully!";
        header("Location: pet-profile.php");
        exit;
    } else {
        echo "<p style='color:red;'>Error adding pet: " . pg_last_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:red;'>Invalid request.</p>";
}
?>
