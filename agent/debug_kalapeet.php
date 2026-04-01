<?php
require_once __DIR__ . '/../includes/db.php';

$email = 'kalapeet.com@gmail.com';
$stmt = $pdo->prepare("SELECT id, name, coins FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found: $email");
}

echo "--- USER DATA ---\n";
print_r($user);

$uid = $user['id'];
$stmt = $pdo->prepare("SELECT * FROM voocoin_balances WHERE user_id = ?");
$stmt->execute([$uid]);
$balance = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n--- VOOCOIN BALANCE TABLE ---\n";
print_r($balance);

$stmt = $pdo->prepare("SELECT * FROM coin_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$uid]);
$txs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n--- RECENT TRANSACTIONS ---\n";
print_r($txs);
?>
