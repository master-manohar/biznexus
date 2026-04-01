<?php
/**
 * admin/linkedin_connect.php
 * LinkedIn OAuth Connector — Step 1: Redirect user to LinkedIn login
 * Saves member ID permanently to social_config.php after auth
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only admins
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /auth/login.php'); exit;
}

$clientId     = '86v8d0yl1xjugu';
$redirectUri  = 'https://biznexus.in/admin/linkedin_callback.php';
$state        = bin2hex(random_bytes(16));
$_SESSION['li_oauth_state'] = $state;

$scopes       = 'openid profile email w_member_social';
$authUrl      = 'https://www.linkedin.com/oauth/v2/authorization?'
    . http_build_query([
        'response_type' => 'code',
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'state'         => $state,
        'scope'         => $scopes,
    ]);

$title = "Connect LinkedIn — BizNexus Admin";
include __DIR__ . '/../includes/layout_start.php';
?>
<div style="max-width:500px;margin:80px auto;text-align:center;font-family:sans-serif;">
  <div style="background:#fff;border-radius:16px;padding:40px;box-shadow:0 4px 24px rgba(0,0,0,0.1);">
    <img src="https://upload.wikimedia.org/wikipedia/commons/8/81/LinkedIn_icon.svg" style="width:56px;margin-bottom:16px;">
    <h2 style="margin:0 0 8px;color:#0a66c2;">Connect LinkedIn to BizNexus</h2>
    <p style="color:#555;margin-bottom:24px;">
      This will allow BizNexus to automatically post business content to your LinkedIn profile on your behalf.
    </p>
    <a href="<?= htmlspecialchars($authUrl) ?>" 
       style="display:inline-block;background:#0a66c2;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:16px;">
      🔗 Connect LinkedIn Account
    </a>
    <p style="color:#888;font-size:13px;margin-top:20px;">
      You will be redirected to LinkedIn to authorize. Only posting permission is requested.
    </p>
  </div>
</div>
<?php include __DIR__ . '/../includes/layout_end.php'; ?>
