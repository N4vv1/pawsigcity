<?php
// Script to clean up invalid gallery entries and check Supabase access
require '../../db.php';

$supabaseUrl = 'https://pgapbbukmyitwuvfbgho.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A';

echo "<h2>Gallery Cleanup & Diagnostics</h2>";

// Check Supabase bucket access
echo "<h3>Step 1: Testing Supabase Bucket Access</h3>";

$testUrl = "{$supabaseUrl}/storage/v1/object/list/gallery-images";
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$supabaseKey}",
    "apikey: {$supabaseKey}"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>List Files API Response (HTTP {$httpCode}):</strong></p>";

if ($httpCode == 200) {
    echo "<p style='color: green;'>✅ Can connect to Supabase storage!</p>";
    $files = json_decode($response, true);
    if (is_array($files) && count($files) > 0) {
        echo "<p>Found " . count($files) . " file(s) in gallery-images bucket:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . htmlspecialchars($file['name']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Bucket is empty or response format unexpected</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot access bucket (HTTP {$httpCode})</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Test public access to an existing file
echo "<h3>Step 2: Testing Public Access to Files</h3>";

$query = "SELECT id, image_path FROM gallery WHERE image_path LIKE '%gallery-images%' LIMIT 1";
$result = pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    $testImage = pg_fetch_assoc($result);
    $testImageUrl = $testImage['image_path'];
    
    echo "<p>Testing: <code>" . htmlspecialchars($testImageUrl) . "</code></p>";
    
    $ch = curl_init($testImageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<p style='color: green;'>✅ Public access works! (HTTP 200)</p>";
        echo "<p><img src='" . htmlspecialchars($testImageUrl) . "' width='200'></p>";
    } else if ($httpCode == 403) {
        echo "<p style='color: red;'>❌ <strong>BUCKET IS NOT PUBLIC!</strong> (HTTP 403 Forbidden)</p>";
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;'>";
        echo "<h4>How to Fix:</h4>";
        echo "<ol>";
        echo "<li>Go to <strong>Supabase Dashboard</strong> → <strong>Storage</strong></li>";
        echo "<li>Click on <strong>gallery-images</strong> bucket</li>";
        echo "<li>Click <strong>Policies</strong> tab</li>";
        echo "<li>Click <strong>New Policy</strong></li>";
        echo "<li>Select <strong>Custom Policy</strong></li>";
        echo "<li>Use this policy:</li>";
        echo "</ol>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "Name: Public Access\n";
        echo "Policy Definition:\n";
        echo "CREATE POLICY \"Public Access\" ON storage.objects\n";
        echo "FOR SELECT\n";
        echo "USING (bucket_id = 'gallery-images');\n";
        echo "</pre>";
        echo "</div>";
    } else if ($httpCode == 404) {
        echo "<p style='color: red;'>❌ File not found in storage (HTTP 404)</p>";
        echo "<p>The file may have been deleted from Supabase storage.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error accessing file (HTTP {$httpCode})</p>";
    }
}

// Show all gallery entries
echo "<h3>Step 3: Current Gallery Entries</h3>";

$query = "SELECT id, image_path, uploaded_at FROM gallery ORDER BY id ASC";
$result = pg_query($conn, $query);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Path Type</th><th>Path</th><th>Action</th></tr>";

$localPathIds = [];
$supabasePathIds = [];

while ($row = pg_fetch_assoc($result)) {
    $isLocal = strpos($row['image_path'], 'gallery_dashboard/uploads/') !== false;
    $isSupabase = strpos($row['image_path'], 'supabase.co') !== false;
    
    if ($isLocal) {
        $localPathIds[] = $row['id'];
    } else if ($isSupabase) {
        $supabasePathIds[] = $row['id'];
    }
    
    $pathType = $isLocal ? 'Local (Invalid)' : ($isSupabase ? 'Supabase' : 'Unknown');
    $color = $isLocal ? 'red' : ($isSupabase ? 'green' : 'orange');
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td style='color: {$color}; font-weight: bold;'>" . $pathType . "</td>";
    echo "<td style='font-size: 10px;'>" . htmlspecialchars($row['image_path']) . "</td>";
    echo "<td>" . ($isLocal ? "Will delete" : "Keep") . "</td>";
    echo "</tr>";
}
echo "</table>";

// Delete local path entries
if (count($localPathIds) > 0) {
    echo "<h3>Step 4: Cleaning Up Invalid Local Path Entries</h3>";
    echo "<p>Found " . count($localPathIds) . " entries with local paths. Deleting...</p>";
    
    $idsString = implode(',', $localPathIds);
    $deleteQuery = "DELETE FROM gallery WHERE id IN ({$idsString})";
    $deleteResult = pg_query($conn, $deleteQuery);
    
    if ($deleteResult) {
        $deleted = pg_affected_rows($deleteResult);
        echo "<p style='color: green;'>✅ Deleted {$deleted} invalid entries (IDs: " . implode(', ', $localPathIds) . ")</p>";
    } else {
        echo "<p style='color: red;'>❌ Error deleting entries: " . pg_last_error($conn) . "</p>";
    }
} else {
    echo "<h3>Step 4: No Invalid Entries to Clean</h3>";
    echo "<p>✅ All entries have valid Supabase paths!</p>";
}

// Summary
echo "<hr>";
echo "<h3>Summary & Next Steps:</h3>";
echo "<ol>";

if ($httpCode == 403) {
    echo "<li style='color: red; font-weight: bold;'>❌ <strong>CRITICAL:</strong> Your bucket is NOT PUBLIC. Follow the instructions above to make it public.</li>";
} else if ($httpCode == 200) {
    echo "<li style='color: green;'>✅ Bucket is accessible</li>";
}

if (count($localPathIds) > 0) {
    echo "<li style='color: orange;'>⚠️ Deleted " . count($localPathIds) . " old entries with local paths</li>";
}

if (count($supabasePathIds) > 0) {
    echo "<li style='color: green;'>✅ You have " . count($supabasePathIds) . " valid Supabase entries</li>";
}

echo "<li>Go back to <a href='gallery.php' style='font-weight: bold;'>Gallery Page</a> to verify</li>";
echo "<li>Upload new images to test</li>";
echo "<li><strong style='color: red;'>DELETE THIS FILE (cleanup_gallery.php) after fixing!</strong></li>";
echo "</ol>";

pg_close($conn);
?>