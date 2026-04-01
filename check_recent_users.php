<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT id, name, email, category, created_at FROM users WHERE status = 'active' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY created_at DESC");
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($recent, JSON_PRETTY_PRINT);
