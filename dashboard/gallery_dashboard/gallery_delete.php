<?php
require_once '../../db.php';

// Validate image ID from query string
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  die("❌ No valid image ID provided.");
}

// Fetch image path for deletion
$result = pg_query_params($conn, "SELECT image_path FROM gallery WHERE id = $1", [$id]);
if (!$result || pg_num_rows($result) === 0) {
  die("❌ Image not found.");
}

$row = pg_fetch_assoc($result);
$imagePath = "gallery_images/" . $row['image_path'];

// Attempt to delete the image file from the server
if (file_exists($imagePath)) {
  unlink($imagePath);
}

// Delete the image record from the database
pg_query_params($conn, "DELETE FROM gallery WHERE id = $1", [$id]);

// Redirect to dashboard with a success flag
header("Location: ../gallery_dashboard/gallery.php?deleted=1");
exit;
?>
