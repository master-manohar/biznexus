<?php
require_once __DIR__ . '/../includes/secrets.php';
$secrets = require __DIR__ . '/../includes/secrets.php';
$api_key = $secrets['gemini_api_key'] ?? '';

header('Content-Type: text/plain');
echo "--- Gemini Model List ---\n";

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=$api_key";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
curl_close($ch);

echo $resp;
echo "\n--- End List ---\n";
