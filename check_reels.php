<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->prepare("SELECT id, status, error_log FROM social_posts WHERE id >= 15 ORDER BY id DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($posts, JSON_PRETTY_PRINT);
