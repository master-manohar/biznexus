<?php
// test_canva.php — Debug Canva API connection
require_once __DIR__ . '/includes/canva_config.php';

echo "=== Canva API Connection Test v2 ===\n\n";

// Raw token request with full debug
$credentials = base64_encode(CANVA_CLIENT_ID . ':' . CANVA_CLIENT_SECRET);
echo "Client ID: " . CANVA_CLIENT_ID . "\n";
echo "Auth Header: Basic " . substr($credentials, 0, 20) . "...\n\n";

$ch = curl_init(CANVA_TOKEN_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/x-www-form-urlencoded',
    ],
    CURLOPT_POSTFIELDS => http_build_query([
        'grant_type' => 'client_credentials',
        'scope'      => 'design:content:read design:content:write asset:read asset:write',
    ])
]);
$raw = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Raw Response:\n$raw\n\n";

$response = json_decode($raw, true);
if (!empty($response['access_token'])) {
    echo "✅ Token received! Length: " . strlen($response['access_token']) . "\n";
    echo "Expires in: " . ($response['expires_in'] ?? 'N/A') . " seconds\n";
} else {
    echo "❌ No token. Error: " . json_encode($response) . "\n";
}
