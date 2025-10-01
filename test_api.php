<?php
$api_url = "https://pawsigcity.onrender.com/recommend";

$payload = json_encode([
    "breed" => "Shih Tzu",
    "gender" => "Male",
    "age" => 2
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "<h3>Raw Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";

    $response_data = json_decode($response, true);

    echo "<h3>Decoded Response:</h3>";
    echo "<pre>";
    print_r($response_data);
    echo "</pre>";
}

curl_close($ch);
