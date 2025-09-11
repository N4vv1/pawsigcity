<?php
require_once '../../db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  die("No valid image ID provided.");
}

// Fetch current image
$result = pg_query_params($conn, "SELECT image_path FROM gallery WHERE id = $1", [$id]);
if (!$result || pg_num_rows($result) === 0) {
  die("Image not found.");
}
$row = pg_fetch_assoc($result);
$currentImage = $row['image_path'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"])) {
  $folder = "gallery_images/";
  $newImageName = basename($_FILES["image"]["name"]);
  $targetPath = $folder . $newImageName;

  // Create folder if it doesn't exist
  if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
  }

  $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
  $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

  if (!in_array($fileType, $allowedTypes)) {
    $error = "❌ Only JPG, JPEG, PNG, or GIF files are allowed.";
  } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
    $oldImagePath = $folder . $currentImage;
    if ($currentImage !== $newImageName && file_exists($oldImagePath)) {
      unlink($oldImagePath);
    }

    pg_query_params(
      $conn,
      "UPDATE gallery SET image_path = $1 WHERE id = $2",
      [$newImageName, $id]
    );

    header("Location: ../gallery_dashboard/gallery.php");
    exit;
  } else {
    $error = "❌ Failed to upload new image.";
  }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Image</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary-color: #A8E6CF;
      --secondary-color: #FFE29D;
      --light-pink: #faf4f5;
      --dark: #252525;
      --gray: #ccc;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Montserrat', sans-serif;
    }

    body {
      background-color: var(--light-pink);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }

    .form-wrapper {
      background: #fff;
      padding: 40px 30px;
      border-radius: 20px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      max-width: 460px;
      width: 100%;
      text-align: center;
    }

    .form-wrapper h2 {
      color: var(--dark);
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .form-wrapper img {
      max-width: 100%;
      max-height: 300px;
      margin: 10px 0 20px;
      border-radius: 12px;
      border: 2px solid var(--primary-color);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    }

    .custom-file-upload {
      display: inline-block;
      padding: 15px 20px;
      background-color: var(--primary-color);
      border: 2px dashed var(--gray);
      border-radius: 12px;
      color: var(--dark);
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
      margin-bottom: 20px;
    }

    .custom-file-upload:hover {
      background-color: #91d7bd;
    }

    input[type="file"] {
      display: none;
    }

    button {
      padding: 12px 24px;
      background-color: var(--secondary-color);
      color: var(--dark);
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #fbd876;
    }

    .back-link {
      display: inline-block;
      margin-top: 20px;
      color: #777;
      font-size: 0.95rem;
      text-decoration: none;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    .error {
      color: red;
      font-weight: 500;
      margin-bottom: 15px;
    }

    @media (max-width: 480px) {
      .form-wrapper {
        padding: 30px 20px;
      }
    }
  </style>
  
</head>
<body>
  <div class="form-wrapper">
    <h2>🖼️ Edit Image</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <p><strong>Current Image:</strong></p>
    <img id="currentImage" src="gallery_images/<?php echo htmlspecialchars($currentImage); ?>" alt="Current Image" />


    <form method="post" enctype="multipart/form-data">
      <label for="imageUpload" class="custom-file-upload">
        📁 Choose New Image
      </label>
      <input type="file" name="image" id="imageUpload" required />
      <br>
      <button type="submit">Update Image</button>
    </form>

    <a href="../gallery_dashboard/gallery.php" class="back-link">← Back to Dashboard</a>
  </div>

  <script>
  const imageUpload = document.getElementById("imageUpload");
  const currentImage = document.getElementById("currentImage");

  imageUpload.addEventListener("change", function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        currentImage.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
</script>

</body>
</html>
