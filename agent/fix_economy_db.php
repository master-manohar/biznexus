<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

echo "FIXING COIN ECONOMY DB...\n";

try {
    // 1. Add balance_after to coin_transactions if not exists
    $cols = $pdo->query("DESCRIBE coin_transactions")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('balance_after', $cols)) {
        $pdo->exec("ALTER TABLE coin_transactions ADD COLUMN balance_after INT AFTER amount");
        echo "Added balance_after to coin_transactions.\n";
    }

    // 2. Add referred_by to users
    $colsUser = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('referred_by', $colsUser)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN referred_by INT NULL AFTER role");
        echo "Added referred_by to users.\n";
    }

    // 3. Ensure voocoin_balances exists and is correct
    // Already verified in audit.

    echo "SUCCESS: Database schema updated.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
