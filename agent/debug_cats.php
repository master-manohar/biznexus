<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT name FROM categories LIMIT 20");
foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
    echo "Name: [$name] | Hex: " . bin2hex($name) . "\n";
}
