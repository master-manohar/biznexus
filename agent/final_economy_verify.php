<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

echo "FINAL VOOCOIN ECONOMY VERIFICATION\n";

$test_uid = 1; // Assuming demo user

// 1. Get initial balances
$stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
$stmt->execute([$test_uid]);
$u_start = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT balance FROM voocoin_balances WHERE user_id = ?");
$stmt->execute([$test_uid]);
$v_start = (int)$stmt->fetchColumn();

echo "Initial Balances: Users:$u_start, VooCoinBalances:$v_start\n";

if ($u_start !== $v_start) {
    echo "WARNING: Initial sync mismatch. Fixing...\n";
    $pdo->prepare("UPDATE voocoin_balances SET balance = ? WHERE user_id = ?")->execute([$u_start, $test_uid]);
}

// 2. Test +100 Credit
echo "Testing +100 Credit...\n";
awardCoins($pdo, $test_uid, 100, "Verification Test Credit");

$stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
$stmt->execute([$test_uid]);
$u_after = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT balance FROM voocoin_balances WHERE user_id = ?");
$stmt->execute([$test_uid]);
$v_after = (int)$stmt->fetchColumn();

echo "After Credit: Users:$u_after, VooCoinBalances:$v_after\n";

// 3. Test -50 Debit
echo "Testing -50 Debit...\n";
awardCoins($pdo, $test_uid, -50, "Verification Test Debit");

$stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
$stmt->execute([$test_uid]);
$u_final = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT balance FROM voocoin_balances WHERE user_id = ?");
$stmt->execute([$test_uid]);
$v_final = (int)$stmt->fetchColumn();

echo "Final Balances: Users:$u_final, VooCoinBalances:$v_final\n";

// 4. Verify Log
$stmt = $pdo->prepare("SELECT * FROM coin_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 2");
$stmt->execute([$test_uid]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Last 2 Logs:\n";
foreach($logs as $l) {
    echo "ID:{$l['id']} Amt:{$l['amount']} BalAfter:{$l['balance_after']} Desc:{$l['description']}\n";
}

if ($u_final === $v_final && $u_final === ($u_start + 50)) {
    echo "SUCCESS: VooCoin Economy is synchronized and accurate.\n";
} else {
    echo "FAILURE: Sync or calculation error.\n";
}
