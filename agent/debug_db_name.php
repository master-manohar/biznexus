<?php
require_once __DIR__ . '/../includes/db.php';
$stmt = $pdo->query("SELECT DATABASE()");
echo "CURRENT_DB: " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->query("DESCRIBE social_posts");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
