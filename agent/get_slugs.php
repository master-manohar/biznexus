<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT slug FROM businesses WHERE website_generated = 1 LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach($rows as $r) echo $r . "\n";
