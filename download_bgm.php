<?php
// download_bgm.php — Download royalty-free BGM tracks to server
$music_dir = __DIR__ . '/assets/music/';
if (!is_dir($music_dir)) mkdir($music_dir, 0755, true);

// Royalty-free BGM tracks from various public sources
$tracks = [
    'bgm_corporate.mp3'    => 'https://cdn.pixabay.com/audio/2024/01/16/audio_5b4ca30b02.mp3',
    'bgm_motivational.mp3' => 'https://cdn.pixabay.com/audio/2023/10/30/audio_98741ab6a3.mp3',
    'bgm_upbeat.mp3'       => 'https://cdn.pixabay.com/audio/2023/06/08/audio_f9e24cc09d.mp3',
    'bgm_ambient.mp3'      => 'https://cdn.pixabay.com/audio/2022/05/27/audio_1808fbf07a.mp3',
    'bgm_inspiring.mp3'    => 'https://cdn.pixabay.com/audio/2023/12/11/audio_29b38be699.mp3',
];

foreach ($tracks as $filename => $url) {
    $dest = $music_dir . $filename;
    if (file_exists($dest) && filesize($dest) > 10000) {
        echo "✅ Already exists: $filename (" . round(filesize($dest)/1024) . " KB)\n";
        continue;
    }
    echo "⬇️ Downloading $filename...\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; BizNexus/1.0)',
    ]);
    $data = curl_exec($ch);
    $size = strlen($data);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($size > 10000) {
        file_put_contents($dest, $data);
        echo "   ✅ Saved! Size: " . round($size/1024) . " KB\n";
    } else {
        echo "   ❌ Failed (HTTP $code, size: $size bytes). Trying fallback...\n";
        // Try archive.org fallback
        $fallback = "https://archive.org/download/kevin-mac-leod-royalty-free-music/Monkeys%20Spinning%20Monkeys.mp3";
        $ch2 = curl_init($fallback);
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30]);
        $data2 = curl_exec($ch2);
        curl_close($ch2);
        if (strlen($data2) > 10000) {
            file_put_contents($dest, $data2);
            echo "   ✅ Fallback saved! Size: " . round(strlen($data2)/1024) . " KB\n";
        } else {
            echo "   ❌ Both sources failed for $filename\n";
        }
    }
}

echo "\n=== Available music files ===\n";
foreach (glob($music_dir . '*.mp3') as $f) {
    echo basename($f) . " → https://biznexus.in/assets/music/" . basename($f) . " (" . round(filesize($f)/1024) . " KB)\n";
}
