<?php
require_once '../../conn.php';
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) exit("Invalid ID");

$stmt = $conn->prepare("SELECT image_path FROM gallery WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$image = "../gallery_images/" . $result['image_path'];

if (file_exists($image)) unlink($image);

$stmt = $conn->prepare("DELETE FROM gallery WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo "🗑️ Image deleted successfully.";
