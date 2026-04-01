<?php
/**
 * agent/standalone_diag.php
 * Standalone diagnostic with NO dependencies.
 */
header('Content-Type: text/plain');
echo "--- STANDALONE GEMINI DIAGNOSTIC v4 ---\n";

$secrets = [
    'gemini_api_key' => 'AIzaSyAIDaA4qDWtrXGgLOgXbe-5ACUhtRzUGQU'
];
$api_key = $secrets['gemini_api_key'];

$payload = [
    'contents' => [[
        'role' => 'user',
        'parts' => [['text' => 'INSTRUCTIONS: You are a diagnostic bot. Reply with "GEMINI V4 ACTIVE".\n\n---\n\nHello!']]
    ]],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 100
    ]
];

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-flash-latest:generateContent?key=$api_key";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP CODE: $httpCode\n";
echo "RESPONSE: $response\n";
echo "--- END ---\n";
