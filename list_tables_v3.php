<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/db.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables Found:\n";
    foreach($tables as $t) echo "$t\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
助
