<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];
$pet_id = $_GET['pet_id'] ?? null;

echo "<h2>Session Info</h2>";
echo "<pre>";
echo "Session User ID: '" . $user_id . "'\n";
echo "Length: " . strlen($user_id) . "\n";
echo "Hex: " . bin2hex($user_id) . "\n";
echo "</pre>";

echo "<h2>All Pets for This User</h2>";
$result = pg_query_params($conn, "SELECT pet_id, user_id, name, breed FROM pets WHERE user_id = $1", [$user_id]);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Name</th><th>Breed</th><th>Pet ID</th><th>User ID</th><th>Action</th></tr>";

while ($pet = pg_fetch_assoc($result)) {
    $match = ($pet['user_id'] === $user_id) ? "✓" : "✗";
    echo "<tr>";
    echo "<td>{$pet['name']}</td>";
    echo "<td>{$pet['breed']}</td>";
    echo "<td title='Length: " . strlen($pet['pet_id']) . "'>{$pet['pet_id']}</td>";
    echo "<td title='Length: " . strlen($pet['user_id']) . "'>{$pet['user_id']}</td>";
    echo "<td>
        <a href='book-appointment.php?pet_id=" . urlencode($pet['pet_id']) . "'>Book (Standard)</a><br>
        <a href='debug-pets.php?pet_id=" . urlencode($pet['pet_id']) . "'>Test Here</a>
    </td>";
    echo "</tr>";
}
echo "</table>";

if ($pet_id) {
    echo "<h2>Testing Pet ID: {$pet_id}</h2>";
    
    // Test 1: Exact match
    echo "<h3>Test 1: Exact Match</h3>";
    $test1 = pg_query_params($conn, "SELECT * FROM pets WHERE pet_id = $1 AND user_id = $2", [$pet_id, $user_id]);
    echo pg_num_rows($test1) > 0 ? "✓ PASS" : "✗ FAIL";
    
    // Test 2: Case insensitive
    echo "<h3>Test 2: Case Insensitive</h3>";
    $test2 = pg_query_params($conn, "SELECT * FROM pets WHERE LOWER(pet_id) = LOWER($1) AND LOWER(user_id) = LOWER($2)", [$pet_id, $user_id]);
    echo pg_num_rows($test2) > 0 ? "✓ PASS" : "✗ FAIL";
    
    // Test 3: With TRIM
    echo "<h3>Test 3: With TRIM</h3>";
    $test3 = pg_query_params($conn, "SELECT * FROM pets WHERE TRIM(pet_id) = TRIM($1) AND TRIM(user_id) = TRIM($2)", [$pet_id, $user_id]);
    echo pg_num_rows($test3) > 0 ? "✓ PASS" : "✗ FAIL";
    
    // Test 4: Does pet exist at all?
    echo "<h3>Test 4: Pet Exists?</h3>";
    $test4 = pg_query_params($conn, "SELECT * FROM pets WHERE pet_id = $1", [$pet_id]);
    if ($row = pg_fetch_assoc($test4)) {
        echo "✓ Pet exists<br>";
        echo "Pet's user_id: '{$row['user_id']}'<br>";
        echo "Session user_id: '{$user_id}'<br>";
        echo "Match: " . ($row['user_id'] === $user_id ? "YES" : "NO") . "<br>";
        echo "Hex comparison:<br>";
        echo "  DB: " . bin2hex($row['user_id']) . "<br>";
        echo "  Session: " . bin2hex($user_id) . "<br>";
    } else {
        echo "✗ Pet doesn't exist";
    }
}
?>