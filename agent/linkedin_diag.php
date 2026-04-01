<?php
// agent/linkedin_diag.php — Test NEW /rest/posts API
if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') die('Unauthorized');

require_once __DIR__ . '/../includes/social_config.php';

$token    = LI_ACCESS_TOKEN; // fresh token from OAuth flow
$memberId = '59080575';
$caption  = "🚀 BizNexus — India's AI-powered B2B Network for SMBs!\n\nConnect with verified Indian businesses, get real leads, and grow faster. Join FREE 👉 https://biznexus.in\n\n#BizNexus #IndianBusiness #SMBIndia #B2BNetworking #MakeInIndia";

echo "<pre>";
echo "=== LinkedIn REST Posts API Tests ===\n";
echo "Token ends: ..." . substr($token, -20) . "\n\n";

function liRestPost(string $url, string $token, array $body, string $version): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: ' . $version,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headers = curl_getinfo($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true), 'raw' => $res];
}

$newPostBody = [
    'author'         => "urn:li:person:$memberId",
    'lifecycleState' => 'PUBLISHED',
    'visibility'     => 'PUBLIC',
    'commentary'     => $caption,
    'distribution'   => [
        'feedDistribution'               => 'MAIN_FEED',
        'targetEntities'                 => [],
        'thirdPartyDistributionChannels' => [],
    ],
];

// Try multiple versions of the new /rest/posts API
$versions = ['202309', '202305', '202302', '202210'];
foreach ($versions as $ver) {
    echo "--- POST /rest/posts (version=$ver, author=urn:li:person:$memberId) ---\n";
    $r = liRestPost('https://api.linkedin.com/rest/posts', $token, $newPostBody, $ver);
    echo "HTTP: {$r['code']}\n";
    echo "Response: " . json_encode($r['body'], JSON_PRETTY_PRINT) . "\n";
    if ($r['code'] === 201) {
        echo "🎉 SUCCESS with version $ver!\n\n";
        break;
    }
    echo "❌ Failed\n\n";
    // If not version error, stop trying
    if ($r['code'] !== 426) break;
}

// Also test the old ugcPosts to compare
echo "--- POST /v2/ugcPosts (author=urn:li:member:$memberId) for comparison ---\n";
$ch = curl_init('https://api.linkedin.com/v2/ugcPosts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'author' => "urn:li:member:$memberId",
        'lifecycleState' => 'PUBLISHED',
        'specificContent' => ['com.linkedin.ugc.ShareContent' => [
            'shareCommentary' => ['text' => $caption],
            'shareMediaCategory' => 'NONE',
        ]],
        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
    ]),
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token,'Content-Type: application/json','X-Restli-Protocol-Version: 2.0.0'],
    CURLOPT_TIMEOUT => 15,
]);
$res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo "HTTP: $code\nResponse: " . json_encode(json_decode($res, true), JSON_PRETTY_PRINT) . "\n";
echo ($code === 201 ? "🎉 SUCCESS!\n" : "❌ Failed\n");
echo "</pre>";
?>
