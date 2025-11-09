<?php
// Script to fix image paths in database
require '../../db.php';

echo "<h2>Fix Image Paths - Change pet-images to gallery-images</h2>";

// First, show current paths
echo "<h3>Current Image Paths:</h3>";
$query = "SELECT id, image_path FROM gallery ORDER BY id ASC";
$result = pg_query($conn, $query);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Current Path</th></tr>";
while ($row = pg_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td style='font-size: 11px; color: " . (strpos($row['image_path'], 'pet-images') !== false ? 'red' : 'green') . ";'>" . htmlspecialchars($row['image_path']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Update the paths
echo "<h3>Updating Paths...</h3>";

$updateQuery = "UPDATE gallery SET image_path = REPLACE(image_path, '/pet-images/', '/gallery-images/')";
$updateResult = pg_query($conn, $updateQuery);

if ($updateResult) {
    $affectedRows = pg_affected_rows($updateResult);
    echo "<p style='color: green; font-weight: bold;'>✅ Successfully updated {$affectedRows} image path(s)!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Error updating paths: " . pg_last_error($conn) . "</p>";
}

// Show updated paths
echo "<h3>Updated Image Paths:</h3>";
$result = pg_query($conn, $query);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Updated Path</th><th>Test Image</th></tr>";
while ($row = pg_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td style='font-size: 11px; color: " . (strpos($row['image_path'], 'gallery-images') !== false ? 'green' : 'red') . ";'>" . htmlspecialchars($row['image_path']) . "</td>";
    echo "<td><img src='" . htmlspecialchars($row['image_path']) . "' width='100' onerror=\"this.parentElement.innerHTML='❌ FAILED';\"></td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If images show above with ✅, your paths are fixed!</li>";
echo "<li>If images still show ❌, check your Supabase bucket permissions</li>";
echo "<li>Go back to <a href='gallery.php'>Gallery Page</a> to verify</li>";
echo "<li><strong>DELETE THIS FILE (fix_image_paths.php) after fixing!</strong></li>";
echo "</ol>";

pg_close($conn);
?>