<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/loginform.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Collect pet info
    $name = $_POST['name'] ?? '';
    $breed = $_POST['breed'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $birthday = $_POST['birthday'] ?? null;
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

    // Insert into pets table
    $stmt = $mysqli->prepare("INSERT INTO pets (user_id, name, breed, gender, age, birthday, color, photo_url)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $name, $breed, $gender, $age, $birthday, $color, $photo_url);

    if ($stmt->execute()) {
        $pet_id = $stmt->insert_id;

        // Insert into health_info
        $stmt_health = $mysqli->prepare("INSERT INTO health_info (pet_id, allergies, medications, medical_conditions)
                                         VALUES (?, ?, ?, ?)");
        $stmt_health->bind_param("isss", $pet_id, $allergies, $medications, $medical_conditions);
        $stmt_health->execute();

        // Insert into behavior_preferences
        $stmt_behavior = $mysqli->prepare("INSERT INTO behavior_preferences (pet_id, behavior_notes, nail_trimming, haircut_style)
                                           VALUES (?, ?, ?, ?)");
        $stmt_behavior->bind_param("isss", $pet_id, $behavior_notes, $nail_trimming, $haircut_style);
        $stmt_behavior->execute();

        $_SESSION['success'] = "Pet added successfully!";
        header("Location: pet-profile.php");
        exit;
    } else {
        echo "<p style='color:red;'>Error adding pet: " . $stmt->error . "</p>";
    }
} else {
    echo "<p style='color:red;'>Invalid request.</p>";
}
?>
