<?php
session_start();
require '../db.php'; // adjust path as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulated user ID (replace with session-based user later)
    $user_id = 1;

    // Collect input
    $name = $_POST['name'] ?? '';
    $breed = $_POST['breed'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $birthday = $_POST['birthday'] ?? null;
    $color = $_POST['color'] ?? '';

    // Handle image upload
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

    // Insert into database
    $stmt = $mysqli->prepare("INSERT INTO pets (user_id, name, breed, gender, age, birthday, color, photo_url)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $name, $breed, $gender, $age, $birthday, $color, $photo_url);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Pet added successfully!";
        header("Location: pet-profile.php");
        exit;
    } else {
        echo "<p style='color:red;'>Failed to add pet: " . $stmt->error . "</p>";
    }
} else {
    echo "<p style='color:red;'>Invalid request.</p>";
}
?>
