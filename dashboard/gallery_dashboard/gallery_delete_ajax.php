<?php
require_once '../../db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) exit("Invalid ID");

// Get the image path
$result = pg_query_params($conn, "SELECT image_path FROM gallery WHERE id = $1", [$id]);
if (!$result || pg_num_rows($result) === 0) {
    exit("Image not found.");
}

$row = pg_fetch_assoc($result);
$image = "../gallery_images/" . $row['image_path'];

// Delete the image file if it exists
if (file_exists($image)) {
    unlink($image);
}

// Delete the record from the database
pg_query_params($conn, "DELETE FROM gallery WHERE id = $1", [$id]);

echo "🗑️ Image deleted successfully.";
?>
