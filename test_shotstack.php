<?php
// test_shotstack.php — Debug Shotstack API
require_once __DIR__ . '/includes/video_bgm.php';

echo "=== Shotstack API Test ===\n";
echo "API Key: " . substr(SHOTSTACK_API_KEY, 0, 15) . "...\n";
echo "API URL: " . SHOTSTACK_API_URL . "\n\n";

$videoUrl = 'https://videos.pexels.com/video-files/8479283/8479283-hd_720_1280_25fps.mp4';
$bgmUrl   = 'https://biznexus.in/assets/music/bgm_corporate.mp3'; // Hosted on our server

$renderPayload = [
    'timeline' => [
        'background' => '#000000',
        'tracks' => [
            ['clips' => [['asset' => ['type' => 'video', 'src' => $videoUrl, 'volume' => 0], 'start' => 0, 'length' => 15, 'fit' => 'crop']]],
            ['clips' => [['asset' => ['type' => 'audio', 'src' => $bgmUrl, 'volume' => 0.7], 'start' => 0, 'length' => 15]]],
        ]
    ],
    'output' => ['format' => 'mp4', 'resolution' => 'hd', 'aspectRatio' => '9:16']
];

$ch = curl_init(SHOTSTACK_API_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . SHOTSTACK_API_KEY,
    ],
    CURLOPT_POSTFIELDS => json_encode($renderPayload),
]);
$raw      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Raw Response:\n$raw\n\n";

$res = json_decode($raw, true);
$renderId = $res['response']['id'] ?? null;

if ($renderId) {
    echo "✅ Render job submitted! ID: $renderId\n";
    echo "Polling status (up to 60s)...\n";
    for ($i = 0; $i < 12; $i++) {
        sleep(5);
        $ch = curl_init("https://api.shotstack.io/v1/render/$renderId");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['x-api-key: ' . SHOTSTACK_API_KEY]]);
        $statusRaw = curl_exec($ch);
        curl_close($ch);
        $status = json_decode($statusRaw, true);
        $state = $status['response']['status'] ?? 'unknown';
        echo "[$i] Status: $state\n";
        if ($state === 'done') {
            echo "🎬 DONE! Video URL: " . ($status['response']['url'] ?? 'N/A') . "\n";
            break;
        }
        if ($state === 'failed') { echo "❌ Failed!\nDetails: $statusRaw\n"; break; }
    }
} else {
    echo "❌ No render ID returned. Full response: $raw\n";
}
