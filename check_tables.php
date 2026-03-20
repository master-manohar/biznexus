<?php
require_once 'includes/db.php';
global $pdo;

$tables = ['voocoin_balances', 'notifications', 'member_badges', 'groups', 'coin_transactions'];
foreach ($tables as $t) {
    echo "Table $t: ";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        echo "OK (" . $stmt->rowCount() . " columns)<br>";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "<br>";
    }
}
?>
