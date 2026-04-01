<?php
/**
 * auth/verify.php
 * Handles email verification tokens.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

$token = $_GET['token'] ?? '';
$success = false;
$msg = "Invalid or expired verification link.";

if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE verify_token = ? AND email_verified = 0 LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $uid = $user['id'];
            // 1. Verify User
            $pdo->prepare("UPDATE users SET email_verified = 1, verify_token = NULL WHERE id = ?")->execute([$uid]);
            
            // 2. Recalculate Trust Score (Awards points)
            calculateTrustScore($pdo, $uid);
            
            // 3. Award Coins for verification
            awardCoins($pdo, $uid, 50, "Email Verified Bonus");
            
            $success = true;
            $msg = "Congratulations " . htmlspecialchars($user['name']) . "! Your email has been verified. You've earned +150 Security Points and 50 VooCoins.";
        } else {
            // Check if already verified
            $stmt2 = $pdo->prepare("SELECT id FROM users WHERE verify_token IS NULL AND email_verified = 1 AND id IN (SELECT id FROM users WHERE verify_token = ?)");
            // Note: This logic is slightly flawed for a 'null' token, but the token check at start protects it.
        }
    } catch (Exception $e) {
        $msg = "An error occurred during verification: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Email Verification | BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@800&family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #06060a; color: #fff; font-family: 'DM Sans', sans-serif; height: 100vh; display: grid; place-items: center; }
        .card { background: #0e0e16; border: 1px solid #1e1e2e; border-radius: 20px; padding: 40px; max-width: 500px; text-align: center; }
        h1 { font-family: 'Syne', sans-serif; color: #FFD700; margin-bottom: 20px; }
        .btn-gold { background: #FFD700; color: #000; font-weight: 800; border: none; border-radius: 10px; padding: 12px 30px; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card shadow-lg">
        <h1><?= $success ? '🎉 Verified!' : '⚠️ Oops!' ?></h1>
        <p class="lead mb-4"><?= $msg ?></p>
        <?php if ($success): ?>
            <a href="/auth/login.php" class="btn-gold">Proceed to Login →</a>
        <?php else: ?>
            <a href="/" class="text-muted text-decoration-none">Back to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>
