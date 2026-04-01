<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'bizfeed%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables found: " . implode(', ', $tables) . "\n";
?>
