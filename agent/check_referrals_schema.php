<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

$table = 'referrals';
$stmt = $pdo->query("DESCRIBE $table");
echo "TABLE: $table\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

$stmt2 = $pdo->query("SHOW CREATE TABLE $table");
$row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "\nCREATE TABLE:\n" . $row2['Create Table'] . "\n";
