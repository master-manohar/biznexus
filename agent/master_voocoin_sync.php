<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

// 1. Fetch all active users and their current balances
$stmt = $pdo->query("SELECT id, name, coins FROM users WHERE status = 'active'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Processing " . count($users) . " users for Master Sync...\n";

$updates = 0;
$inserts = 0;

foreach ($users as $u) {
    $uid = $u['id'];
    $legacy_coins = (int)$u['coins'];
    
    // Check modern balance
    $bStmt = $pdo->prepare("SELECT balance FROM voocoin_balances WHERE user_id = ?");
    $bStmt->execute([$uid]);
    $modern_record = $bStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$modern_record) {
        // Record missing: Create it using legacy balance
        $pdo->prepare("INSERT INTO voocoin_balances (user_id, balance, total_earned, total_spent, updated_at) VALUES (?, ?, ?, 0, NOW())")
            ->execute([$uid, $legacy_coins, $legacy_coins]);
        
        // Log transaction
        $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, balance_after, description, created_at) VALUES (?, 'earn', ?, ?, 'Master Sync: Legacy Recovery', NOW())")
            ->execute([$uid, $legacy_coins, $legacy_coins]);
            
        echo "User $uid ({$u['name']}): Created missing balance record with $legacy_coins coins.\n";
        $inserts++;
    } else {
        $modern_balance = (int)$modern_record['balance'];
        
        if ($legacy_coins > $modern_balance) {
            // Mismatch: Legacy is higher, trust legacy for this sync
            $diff = $legacy_coins - $modern_balance;
            
            $pdo->prepare("UPDATE voocoin_balances SET balance = ?, total_earned = total_earned + ?, updated_at = NOW() WHERE user_id = ?")
                ->execute([$legacy_coins, $diff, $uid]);
                
            // Log transaction
            $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, balance_after, description, created_at) VALUES (?, 'earn', ?, ?, 'Master Sync: Audit Reconciliation', NOW())")
                ->execute([$uid, $diff, $legacy_coins]);
                
            echo "User $uid ({$u['name']}): Synced legacy balance ($legacy_coins) over modern ($modern_balance).\n";
            $updates++;
        }
    }
}

echo "\nSync Complete. Inserts: $inserts, Updates: $updates.\n";
?>
