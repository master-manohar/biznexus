<?php
require_once __DIR__ . '/../includes/db.php';

echo "Starting Production Cleanup...\n";

// 1. Identify Test Users
$stmt = $pdo->query("SELECT id FROM users WHERE name LIKE '%Test%' OR name LIKE '%Simulated%' OR email LIKE '%test%'");
$uids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($uids)) {
    $uid_list = implode(',', $uids);
    echo "Found " . count($uids) . " test users. Deleting dependencies...\n";
    
    // Delete dependencies (in case no cascading keys)
    $pdo->exec("DELETE FROM business_profiles WHERE user_id IN ($uid_list)");
    $pdo->exec("DELETE FROM voocoin_balances WHERE user_id IN ($uid_list)");
    $pdo->exec("DELETE FROM coin_transactions WHERE user_id IN ($uid_list)");
    $pdo->exec("DELETE FROM notifications WHERE user_id IN ($uid_list)");
    $pdo->exec("DELETE FROM referrals WHERE sender_id IN ($uid_list) OR receiver_id IN ($uid_list)");
    $pdo->exec("DELETE FROM lead_dispatches WHERE member_id IN ($uid_list)");
    
    // Finally delete users
    $pdo->exec("DELETE FROM users WHERE id IN ($uid_list)");
    echo "Deleted " . count($uids) . " users and all related profile/transaction data.\n";
}

// 2. Identify and Delete Test Referrals (standalone/remaining)
$stmt = $pdo->query("SELECT id FROM referrals WHERE referred_name LIKE '%test%' OR notes LIKE '%test%'");
$rids = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!empty($rids)) {
    $rid_list = implode(',', $rids);
    $pdo->exec("DELETE FROM referrals WHERE id IN ($rid_list)");
    echo "Deleted " . count($rids) . " test referrals.\n";
}

// 3. Identify and Delete Test Public Leads
$stmt = $pdo->query("SELECT id FROM public_leads WHERE name LIKE '%test%' OR query LIKE '%test%'");
$plids = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!empty($plids)) {
    $pl_list = implode(',', $plids);
    $pdo->exec("DELETE FROM public_leads WHERE id IN ($pl_list)");
    echo "Deleted " . count($plids) . " test leads.\n";
}

// 4. Identify and Delete orphaned test transactions
$pdo->exec("DELETE FROM coin_transactions WHERE description LIKE '%Test%' OR description LIKE '%Simulated%'");
echo "Cleaned up test transaction entries.\n";

echo "Cleanup complete. System ready for marketing.\n";
?>
