<?php
require_once __DIR__ . '/../includes/db.php';

echo "--- TEST USERS ---\n";
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE name LIKE '%Test%' OR name LIKE '%Simulated%' OR email LIKE '%test%'");
$test_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($test_users);

echo "\n--- TEST REFERRALS ---\n";
$stmt = $pdo->query("SELECT id, referred_name, notes FROM referrals WHERE referred_name LIKE '%Test%' OR notes LIKE '%test%'");
$test_refs = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($test_refs);

echo "\n--- TEST LEADS ---\n";
$stmt = $pdo->query("SELECT id, name, query FROM public_leads WHERE name LIKE '%Test%' OR query LIKE '%test%'");
$test_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($test_leads);

echo "\n--- TEST TRANSACTIONS ---\n";
$stmt = $pdo->query("SELECT id, description FROM coin_transactions WHERE description LIKE '%Test%' OR description LIKE '%Simulated%'");
$test_txs = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($test_txs);
?>
