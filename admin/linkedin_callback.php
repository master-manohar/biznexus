<?php
/**
 * admin/linkedin_callback.php
 * OAuth callback — gets encoded member ID via /v2/userinfo (openid scope)
 * then saves token + member ID, tests a live post
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /auth/login.php'); exit;
}

$_li_secrets  = require __DIR__ . '/../includes/secrets.php';
$clientId     = $_li_secrets['linkedin_client_id']     ?? '';
$clientSecret = $_li_secrets['linkedin_client_secret'] ?? '';
$redirectUri  = 'https://biznexus.in/admin/linkedin_callback.php';
$configPath   = __DIR__ . '/../includes/social_config.php';

$error = $_GET['error'] ?? null;
$code  = $_GET['code'] ?? null;

if ($error) { echo "LinkedIn error: " . htmlspecialchars($error); exit; }
if (!$code)  { echo "No auth code received."; exit; }

// Step 1: Exchange code for token
$ch = curl_init('https://www.linkedin.com/oauth/v2/accessToken');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'grant_type' => 'authorization_code', 'code' => $code,
        'redirect_uri' => $redirectUri, 'client_id' => $clientId, 'client_secret' => $clientSecret,
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'], CURLOPT_TIMEOUT => 15,
]);
$tokenRes = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($tokenRes['access_token'])) {
    echo "Token exchange failed: " . json_encode($tokenRes); exit;
}
$newToken = $tokenRes['access_token'];

// Step 2: Get member ID via /v2/userinfo (works with openid scope)
$ch = curl_init('https://api.linkedin.com/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $newToken],
    CURLOPT_TIMEOUT => 10,
]);
$uiRes = json_decode(curl_exec($ch), true);
curl_close($ch);

// sub = encoded member ID (e.g. "aBcDeFgH1234")
$encodedId = $uiRes['sub'] ?? null;

// Also try /v2/me as backup
if (!$encodedId) {
    $ch = curl_init('https://api.linkedin.com/v2/me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $newToken, 'X-Restli-Protocol-Version: 2.0.0'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $meRes = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $encodedId = $meRes['id'] ?? null;
}

if (!$encodedId) {
    // Show debug info
    echo "<pre>Token: ...". substr($newToken,-20) ."\nuserinfo: ".json_encode($uiRes)."\nme: ".json_encode($meRes ?? [])."</pre>";
    exit;
}

// Step 3: Save token + encoded member ID to config
$configContent = file_get_contents($configPath);
$configContent = preg_replace("/define\('LI_ACCESS_TOKEN',\s*'[^']*'\)/",
    "define('LI_ACCESS_TOKEN', '" . addslashes($newToken) . "')", $configContent);

if (strpos($configContent, 'LI_MEMBER_ID') !== false) {
    $configContent = preg_replace("/define\('LI_MEMBER_ID',\s*'[^']*'\)/",
        "define('LI_MEMBER_ID', '$encodedId')", $configContent);
} else {
    $configContent .= "\ndefine('LI_MEMBER_ID', '$encodedId');\n";
}
$configContent = preg_replace("/define\('LI_SCOPE',\s*'[^']*'\)/",
    "define('LI_SCOPE', 'w_member_social')", $configContent);

file_put_contents($configPath, $configContent);

// Step 4: Immediately test a live post
$caption = "🚀 BizNexus is live — India's AI-powered B2B Network for SMBs!\n\nConnect with verified Indian businesses, get real leads, and grow faster.\nJoin FREE 👉 https://biznexus.in\n\n#BizNexus #IndianBusiness #SMBIndia #B2BNetworking #MakeInIndia";
$author  = "urn:li:member:$encodedId";

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
                'description' => ['text' => 'Connect, grow, and scale your Indian SMB with AI']]],
        ]],
        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
    ]),
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$newToken,'Content-Type: application/json','X-Restli-Protocol-Version: 2.0.0'],
    CURLOPT_TIMEOUT => 20,
]);
$resPost = curl_exec($ch);
$httpPost = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$postData = json_decode($resPost, true);
$success = ($httpPost === 201 && isset($postData['id']));
?>
<!DOCTYPE html>
<html><head><title>LinkedIn Connect</title></head>
<body style="font-family:sans-serif;max-width:580px;margin:60px auto;text-align:center;">
<div style="background:#fff;border-radius:16px;padding:40px;box-shadow:0 4px 24px rgba(0,0,0,.1);">
<?php if ($success): ?>
  <div style="font-size:56px;">🎉</div>
  <h2 style="color:#0a66c2;">LinkedIn Connected & Posted!</h2>
  <p style="color:#555;">Token + Member ID (<strong><?= htmlspecialchars($encodedId) ?></strong>) saved.<br>
  Test post published successfully! LinkedIn will now auto-post every cycle.</p>
<?php else: ?>
  <div style="font-size:48px;">⚠️</div>
  <h2 style="color:#d97706;">Token Saved</h2>
  <p style="color:#555;">Member ID: <strong><?= htmlspecialchars($encodedId) ?></strong><br>
  Token saved ✅ but test post returned HTTP <strong><?= $httpPost ?></strong>:<br>
  <code style="font-size:11px;color:#c00;word-break:break-all;"><?= htmlspecialchars($resPost) ?></code></p>
<?php endif; ?>
  <a href="/admin/superadmin.php" style="display:inline-block;margin-top:20px;background:#f5a623;color:#000;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;">Dashboard →</a>
</div>
</body></html>
