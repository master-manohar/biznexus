<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

function dumpTable($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "TABLE: $table\n";
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { print_r($row); }
    } catch (Exception $e) { echo "Table $table not found.\n"; }
}

dumpTable($pdo, 'users');
dumpTable($pdo, 'business_profiles');
dumpTable($pdo, 'voocoin_balances');
dumpTable($pdo, 'coin_transactions');
