<?php
require_once __DIR__ . '/includes/db.php';
$email = 'antigravity_admin@biznexus.in';
$password = 'Admin@999';
$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (name, email, password, status, email_verified, role, plan, created_at)
               VALUES ('Antigravity Admin', ?, ?, 'active', 1, 'admin', 'platinum', NOW()) ON DUPLICATE KEY UPDATE role='admin', status='active'")
    ->execute([$email, $hash]);
echo "✅ Admin account created/updated: $email / $password\n";
?>
