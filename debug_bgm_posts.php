<?php
// debug_bgm_posts.php — Check recent posts and BGM status
require_once __DIR__ . '/includes/db.php';
$posts = $pdo->query("SELECT id, media_type, media_url, status, error_log, created_at FROM social_posts ORDER BY id DESC LIMIT 5")->fetchAll();
foreach ($posts as $p) {
    echo "ID: {$p['id']} | Type: {$p['media_type']} | Status: {$p['status']}\n";
    echo "URL: " . substr($p['media_url'], 0, 80) . "\n";
    echo "Error: " . ($p['error_log'] ?? 'none') . "\n";
    echo "Created: {$p['created_at']}\n\n";
}
