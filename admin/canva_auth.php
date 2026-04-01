<?php
/**
 * admin/canva_auth.php
 * Canva OAuth 2.0 with PKCE (required by Canva Connect API).
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/canva_config.php';

$msg = '';
$redirect_uri = 'https://biznexus.in/admin/canva_auth.php';

// ─── Helpers ─────────────────────────────────────────────────────────────────
function base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function generatePKCE(): array {
    $verifier  = base64url(random_bytes(64));
    $challenge = base64url(hash('sha256', $verifier, true));
    return ['verifier' => $verifier, 'challenge' => $challenge];
}

// ─── Step 2: Canva redirected back with ?code= ────────────────────────────────
if (isset($_GET['code'])) {
    $code     = $_GET['code'];
    $verifier = $_SESSION['canva_verifier'] ?? '';

    if (empty($verifier)) {
        $msg = '<div class="alert alert-danger">❌ Session expired — please click "Authorize Canva" again.</div>';
    } else {
        $credentials = base64_encode(CANVA_CLIENT_ID . ':' . CANVA_CLIENT_SECRET);
        $ch = curl_init(CANVA_TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $redirect_uri,
                'code_verifier' => $verifier,
            ])
        ]);
        $raw  = curl_exec($ch);
        $code_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($raw, true);

        if (!empty($data['access_token'])) {
            // Store permanently in DB
            $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (name VARCHAR(100) PRIMARY KEY, value TEXT)");
            $stmt = $pdo->prepare("INSERT INTO site_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
            $stmt->execute(['canva_access_token',  $data['access_token']]);
            $stmt->execute(['canva_refresh_token', $data['refresh_token'] ?? '']);
            $stmt->execute(['canva_token_expiry',  time() + ($data['expires_in'] ?? 3600)]);
            unset($_SESSION['canva_verifier']);
            $msg = '<div class="alert alert-success" style="background:rgba(0,232,122,.1);border-color:#00e87a;color:#00e87a;">✅ Canva connected successfully! Videos will now be generated automatically for Instagram posts.</div>';
        } else {
            $msg = '<div class="alert alert-danger" style="background:rgba(255,77,109,.1);border-color:#ff4d6d;color:#ff4d6d;">❌ Token exchange failed (HTTP '.$code_http.'): ' . htmlspecialchars($raw) . '</div>';
        }
    }
}

// ─── Step 1: Generate PKCE and build auth URL ─────────────────────────────────
$pkce = generatePKCE();
$_SESSION['canva_verifier'] = $pkce['verifier'];

$auth_url = 'https://www.canva.com/api/oauth/authorize?' . http_build_query([
    'client_id'             => CANVA_CLIENT_ID,
    'response_type'         => 'code',
    'redirect_uri'          => $redirect_uri,
    'scope'                 => 'design:content:read design:content:write asset:read asset:write',
    'code_challenge'        => $pkce['challenge'],
    'code_challenge_method' => 'S256',
]);

$page_title = 'Connect Canva — BizNexus Admin';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<div class="container py-5" style="max-width:620px;">
    <h2 style="color:#FFD700; font-weight:800; margin-bottom:8px;">🎨 Connect Canva to BizNexus</h2>
    <p style="color:#888; margin-bottom:24px;">One-time setup. After this, the Instagram agent auto-generates branded videos using Canva.</p>

    <?= $msg ?>

    <?php if (empty($msg) || strpos($msg, '❌') !== false): ?>
    <p style="color:#aaa; margin-bottom:12px;">Click the button below — Canva will open in a <strong>new tab</strong>. Approve the access and you'll be redirected back automatically.</p>
    
    <a href="<?= htmlspecialchars($auth_url) ?>"
       target="_blank"
       rel="noopener noreferrer"
       class="d-block text-center py-3 text-decoration-none"
       style="background:linear-gradient(135deg,#7D2AE8,#a259ff); color:#fff; font-weight:800; font-size:1.1rem; border-radius:14px; letter-spacing:.5px;">
        🔗 Open Canva Authorization (New Tab)
    </a>

    <div class="mt-4 p-3" style="background:#0d0d16; border:1px solid #2a2a3a; border-radius:10px;">
        <p class="small text-warning mb-1">If the button doesn't work — copy and paste this URL directly into your browser:</p>
        <textarea onclick="this.select()" style="width:100%; background:#080810; color:#00e87a; border:none; font-size:.7rem; resize:none; border-radius:6px; padding:10px;" rows="4"><?= htmlspecialchars($auth_url) ?></textarea>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
