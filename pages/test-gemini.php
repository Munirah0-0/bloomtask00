
<?php

// Load the Gemini configuration.
define("BLOOMTASK_BOOTSTRAP", true);

$config = require "../config/gemini.php";

// Check whether PHP cURL is available.
if (!function_exists("curl_init")) {
    exit("ERROR: PHP cURL is not enabled in Laragon.");
}

// Prepare a simple test prompt.
$payload = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => "Hello Bloom AI! Introduce yourself in one short sentence."
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 150
    ]
];

// Connect to Gemini.
$ch = curl_init($config["endpoint"]);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-goog-api-key: " . $config["api_key"]
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

// Display connection result.
header("Content-Type: text/html; charset=UTF-8");

echo "<h1>Bloom AI Connection Test</h1>";

if ($response === false) {
    echo "<p>Connection failed: " .
         htmlspecialchars($curlError) . "</p>";
    exit;
}

$data = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300) {

    $reply = $data["candidates"][0]["content"]["parts"][0]["text"]
        ?? null;

    if ($reply !== null) {
        echo "<h2>Connection successful!</h2>";
        echo "<p>Gemini responded:</p>";
        echo "<p>" . nl2br(htmlspecialchars($reply)) . "</p>";
    } else {
        echo "<p>Gemini responded, but no text was found.</p>";
    }

} else {

    $message = $data["error"]["message"]
        ?? "Unknown API error.";

    echo "<h2>Connection unsuccessful</h2>";
    echo "<p>HTTP Status: " . (int) $httpCode . "</p>";
    echo "<p>" . htmlspecialchars($message) . "</p>";

}
