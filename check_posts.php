<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT id, status, media_type, media_url, scheduled_at, published_at, error_log FROM social_posts ORDER BY id DESC LIMIT 10");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($posts, JSON_PRETTY_PRINT);
