<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT name FROM categories");
$cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "CATEGORIES (" . count($cats) . "):\n";
print_r($cats);
