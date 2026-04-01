<?php
/**
 * includes/video_bgm.php
 * Uses Shotstack API to merge a Pexels video with royalty-free BGM.
 * Shotstack free tier: 50 renders/month — plenty for automated posting.
 */

// Load Shotstack key from secrets
$_shotstack_secrets = require __DIR__ . '/secrets.php';
define('SHOTSTACK_API_KEY', $_shotstack_secrets['shotstack_api_key'] ?? ''); // Set in includes/secrets.php
define('SHOTSTACK_API_URL', 'https://api.shotstack.io/v1/render');

// Royalty-free BGM tracks hosted on BizNexus server (Shotstack-accessible)
$BGM_TRACKS = [
    'corporate'    => 'https://biznexus.in/assets/music/bgm_corporate.mp3',
    'motivational' => 'https://biznexus.in/assets/music/bgm_motivational.mp3',
    'upbeat'       => 'https://biznexus.in/assets/music/bgm_upbeat.mp3',
    'ambient'      => 'https://biznexus.in/assets/music/bgm_ambient.mp3',
    'inspiring'    => 'https://biznexus.in/assets/music/bgm_inspiring.mp3',
];

/**
 * Merge video + BGM using Shotstack and return the final MP4 URL.
 * @param string $videoUrl  Pexels video URL
 * @param string $mood      corporate|motivational|upbeat|ambient|inspiring
 * @return string|null      Final video URL or null on failure
 */
function mergeVideoWithBGM(string $videoUrl, string $mood = 'corporate'): ?string {
    global $BGM_TRACKS;
    
    if (empty(SHOTSTACK_API_KEY)) {
        // No Shotstack key — return original video (no BGM)
        return $videoUrl;
    }
    
    $bgmUrl = $BGM_TRACKS[$mood] ?? $BGM_TRACKS['corporate'];
    
    // Step 1: Submit render job
    $renderPayload = [
        'timeline' => [
            'background' => '#000000',
            'tracks'     => [
                // Video track
                [
                    'clips' => [[
                        'asset'  => ['type' => 'video', 'src' => $videoUrl, 'volume' => 0],
                        'start'  => 0,
                        'length' => 30,
                        'fit'    => 'crop',
                    ]]
                ],
                // BGM Audio track (loops if video longer than BGM)
                [
                    'clips' => [[
                        'asset'  => ['type' => 'audio', 'src' => $bgmUrl, 'volume' => 0.7],
                        'start'  => 0,
                        'length' => 30,
                    ]]
                ],
            ]
        ],
        'output' => [
            'format'      => 'mp4',
            'resolution'  => 'hd',
            'aspectRatio' => '9:16',
        ]
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
    $res  = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $renderId = $res['response']['id'] ?? null;
    if (!$renderId) return null;

    // Step 2: Poll for completion (max 2 minutes)
    for ($i = 0; $i < 24; $i++) {
        sleep(5);
        $ch = curl_init("https://api.shotstack.io/v1/render/$renderId");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['x-api-key: ' . SHOTSTACK_API_KEY],
        ]);
        $status = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        $state = $status['response']['status'] ?? '';
        if ($state === 'done')   return $status['response']['url'] ?? null;
        if ($state === 'failed') return null;
    }
    return null;
}
