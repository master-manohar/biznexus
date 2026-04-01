<?php
// agent/linkedin_cache_clear.php — clear opcode cache and test LinkedIn post
if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') die('Unauthorized');

// Clear opcode cache so social_config.php reloads fresh
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared\n";
} else {
    echo "ℹ️ OPcache not available\n";
}

// Force fresh load of config
clearstatcache(true);
require_once __DIR__ . '/../includes/social_config.php';

echo "<pre>";
echo "Token ends: ..." . substr(LI_ACCESS_TOKEN, -25) . "\n";
echo "Member ID: " . LI_MEMBER_ID . "\n";
echo "Scope: " . LI_SCOPE . "\n\n";

// Test LinkedIn post with current config
$author  = 'urn:li:member:' . LI_MEMBER_ID;
$caption = "🚀 BizNexus — India's AI-powered B2B Network for SMBs!\n\nConnect, grow & scale your business. Join FREE 👉 https://biznexus.in\n\n#BizNexus #IndianBusiness #SMBIndia #B2BNetworking #MakeInIndia";

echo "Attempting post as: $author\n\n";

$ch = curl_init('https://api.linkedin.com/v2/ugcPosts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'author' => $author,
        'lifecycleState' => 'PUBLISHED',
        'specificContent' => ['com.linkedin.ugc.ShareContent' => [
            'shareCommentary' => ['text' => $caption],
            'shareMediaCategory' => 'ARTICLE',
            'media' => [['status' => 'READY', 'originalUrl' => 'https://biznexus.in',
                'title' => ['text' => 'BizNexus — India\'s AI Business Network'],
                'description' => ['text' => 'AI-powered B2B networking for Indian SMBs']]],
        ]],
        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . LI_ACCESS_TOKEN,
        'Content-Type: application/json',
        'X-Restli-Protocol-Version: 2.0.0',
    ],
    CURLOPT_TIMEOUT => 20,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($res, true);

echo "HTTP: $code\n";
echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
if ($code === 201) echo "\n🎉 SUCCESS! LinkedIn post published: " . ($data['id'] ?? '') . "\n";
else echo "\n❌ Failed — " . ($data['message'] ?? $res) . "\n";
echo "</pre>";
?>
