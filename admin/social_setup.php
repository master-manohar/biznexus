<?php
/**
 * admin/social_setup.php
 * Secure Setup for Instagram API Credentials.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

// Simple Auth Check (Replace with standard admin check)
// if(!isset($_SESSION['admin'])) { die('Unauthorized'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $ig_id = $_POST['ig_id'] ?? '';
    $pexels = $_POST['pexels'] ?? '';
    
    // Save to a config file for simplicity (Secure it with .htaccess)
    $config_content = "<?php\ndefine('IG_ACCESS_TOKEN', '$token');\ndefine('IG_BUSINESS_ID', '$ig_id');\ndefine('PEXELS_API_KEY', '$pexels');\n";
    file_put_contents(__DIR__ . '/../includes/social_config.php', $config_content);
    $msg = "✅ Credentials saved successfully! The AI Reels agent is now ready.";
}

// Load existing
$token = "";
$ig_id = "";
if(file_exists(__DIR__ . '/../includes/social_config.php')) {
    include __DIR__ . '/../includes/social_config.php';
    $token = defined('IG_ACCESS_TOKEN') ? IG_ACCESS_TOKEN : '';
    $ig_id = defined('IG_BUSINESS_ID') ? IG_BUSINESS_ID : '';
    $pexels = defined('PEXELS_API_KEY') ? PEXELS_API_KEY : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Social Media Setup | BizNexus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@800&family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #06060a; color: #fff; font-family: 'DM Sans', sans-serif; }
        .setup-card { background: #0e0e16; border: 1px solid #1e1e2e; border-radius: 20px; padding: 40px; margin-top: 50px; }
        h1 { font-family: 'Syne', sans-serif; color: #FFD700; }
        .btn-gold { background: #FFD700; color: #000; font-weight: 800; border: none; }
        .form-control { background: #13131a; border: 1px solid #2a2a3a; color: #fff; }
        .form-control:focus { background: #1a1a24; border-color: #FFD700; box-shadow: none; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="setup-card shadow-lg">
                    <h1>📸 Instagram API Setup</h1>
                    <p class="text-muted mb-4">Link your BizNexus Instagram account for 24/7 automated posting.</p>
                    
                    <?php if($msg): ?>
                        <div class="alert alert-success"><?= $msg ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Instagram Business ID</label>
                            <input type="text" name="ig_id" class="form-control" placeholder="e.g. 178414..." value="<?= htmlspecialchars($ig_id) ?>" required>
                            <div class="form-text text-muted">Found in your Facebook Page Settings -> Instagram.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Page Access Token (Long-Lived)</label>
                            <textarea name="token" class="form-control" rows="3" placeholder="Paste your Facebook Graph API token here..." required><?= htmlspecialchars($token) ?></textarea>
                            <div class="form-text text-muted">Generate this in the Facebook Developer Portal.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Pexels API Key (For Realistic Reels)</label>
                            <input type="text" name="pexels" class="form-control" placeholder="e.g. 563492..." value="<?= htmlspecialchars($pexels) ?>">
                            <div class="form-text text-muted">Get your free key at <a href="https://www.pexels.com/api/" target="_blank">pexels.com/api/</a>.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gold py-3">Link Instagram Account & Start Posting 🚀</button>
                        </div>
                    </form>
                </div>

                <div class="mt-5 p-4 rounded" style="background: rgba(255,215,0,0.05); border: 1px dashed #FFD700;">
                    <h5>💡 Need help getting the keys?</h5>
                    <ol class="small text-muted mt-3">
                        <li>Go to <a href="https://developers.facebook.com/" target="_blank" class="text-warning">developers.facebook.com</a> and create a new App.</li>
                        <li>Add <strong>"Instagram Graph API"</strong> to your app.</li>
                        <li>Link your Instagram Business Account to your Facebook Page.</li>
                        <li>Use the "Graph API Explorer" to generate a **Page Access Token** with <code>instagram_basic</code> and <code>instagram_content_publish</code> permissions.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
