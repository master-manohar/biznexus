<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT * FROM social_posts ORDER BY id DESC LIMIT 2");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($posts, JSON_PRETTY_PRINT);
