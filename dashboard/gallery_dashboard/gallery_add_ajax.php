<?php
require_once '../../db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"])) {
    $folder = "../gallery_images/";
    $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $filename = uniqid("img_", true) . "." . $extension;
    $targetPath = $folder . $filename;

    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 2 * 1024 * 1024;

    if (!in_array(strtolower($extension), $allowedTypes)) {
        exit("❌ Only JPG, JPEG, PNG, or GIF files allowed.");
    }

    if ($_FILES["image"]["size"] > $maxSize) {
        exit("❌ Image must be less than 2MB.");
    }

    if (getimagesize($_FILES["image"]["tmp_name"]) === false) {
        exit("❌ File is not a valid image.");
    }

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
        $basename = basename($filename);

        $result = pg_query_params(
            $conn,
            "INSERT INTO gallery (image_path) VALUES ($1)",
            [$basename]
        );

        if ($result) {
            exit("✅ Image uploaded successfully!");
        } else {
            exit("❌ Failed to save the image path to the database.");
        }
    } else {
        exit("❌ Failed to upload the image.");
    }
}

exit("❌ No image received.");
