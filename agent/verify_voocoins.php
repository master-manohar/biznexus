<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

echo "VERIFYING VOOCOIN ECONOMY...\n";

$uid = 1; // Assuming admin or test user exists

// 1. Check current balance
$stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
$stmt->execute([$uid]);
$old_balance = (int)$stmt->fetchColumn();
echo "Current Balance: $old_balance\n";

// 2. Test Credit (+50)
echo "Testing +50 Credit...\n";
awardCoins($pdo, $uid, 50, "TEST CREDIT: Referral Sync Fix");
$stmt->execute([$uid]);
$new_balance = (int)$stmt->fetchColumn();
echo "New Balance: $new_balance (Diff: " . ($new_balance - $old_balance) . ")\n";

// 3. Test Debit (-50)
echo "Testing -50 Debit...\n";
awardCoins($pdo, $uid, -50, "TEST DEBIT: Lead Claim Sync Fix");
$stmt->execute([$uid]);
$final_balance = (int)$stmt->fetchColumn();
echo "Final Balance: $final_balance (Diff from new: " . ($final_balance - $new_balance) . ")\n";

if (($new_balance - $old_balance) === 50 && ($final_balance - $new_balance) === -50) {
    echo "SUCCESS: Coin transactions working correctly.\n";
} else {
    echo "FAILURE: Increments mismatch.\n";
}
