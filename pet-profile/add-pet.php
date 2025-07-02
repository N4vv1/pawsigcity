<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = 1; // Replace this with $_SESSION['user_id'] later
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birthday = $_POST['birthday'];
    $color = $_POST['color'];

    // Handle image upload
    $photo_url = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $filename = time() . '_' . basename($_FILES['photo']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $photo_url = 'uploads/' . $filename;
        }
    }

    $stmt = $mysqli->prepare("INSERT INTO pets (user_id, name, breed, gender, age, birthday, color, photo_url)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssss', $user_id, $name, $breed, $gender, $age, $birthday, $color, $photo_url);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Pet added successfully!";
        header("Location: pet-profile.php");
        exit;
    } else {
        echo "<p style='color:red;'>Error adding pet: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add New Pet</title>
</head>
<body>
  <h2>Add a Pet</h2>
  <form method="POST" action="add-pet-handler.php" enctype="multipart/form-data">
    <label>Name: <input type="text" name="name" required></label><br><br>
    <label>Breed: <input type="text" name="breed" required></label><br><br>
    <label>Gender:
      <select name="gender">
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
    </label><br><br>
    <label>Age: <input type="text" name="age"></label><br><br>
    <label>Birthday: <input type="date" name="birthday"></label><br><br>
    <label>Color: <input type="text" name="color"></label><br><br>
    <label>Photo: <input type="file" name="photo" accept="image/*"></label><br><br>
    <button type="submit">Add Pet</button>
  </form>
</body>
</html>
