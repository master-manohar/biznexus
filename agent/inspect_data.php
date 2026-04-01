<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

echo "--- USER 1 DATA ---\n";
$stmt = $pdo->query("SELECT id, name, coins FROM users WHERE id = 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "--- VOOCOIN BALANCE 1 DATA ---\n";
$stmt = $pdo->query("SELECT * FROM voocoin_balances WHERE user_id = 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "--- TRANSACTION LOGS 1 ---\n";
$stmt = $pdo->query("SELECT * FROM coin_transactions WHERE user_id = 1 ORDER BY id DESC LIMIT 5");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
