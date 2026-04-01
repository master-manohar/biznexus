<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

$user_id = 1;
$new_balance = 999;

echo "Attempting UPDATE voocoin_balances SET balance = $new_balance WHERE user_id = $user_id...\n";

$upd = $pdo->prepare("UPDATE voocoin_balances SET balance = ? WHERE user_id = ?");
$res = $upd->execute([$new_balance, (int)$user_id]);

if ($res) {
    echo "SUCCESS: Rows affected: " . $upd->rowCount() . "\n";
} else {
    echo "FAILURE: Error: ";
    print_r($upd->errorInfo());
}

echo "Final check of table:\n";
$stmt = $pdo->query("SELECT * FROM voocoin_balances WHERE user_id = 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
