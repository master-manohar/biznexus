<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

echo "BULK SYNCING VOOCOINS...\n";

$users = $pdo->query("SELECT id, coins FROM users")->fetchAll(PDO::FETCH_ASSOC);
$count = 0;

foreach ($users as $u) {
    // We can use awardCoins with amount 0 to force a sync
    awardCoins($pdo, $u['id'], 0, "Economy Stabilization Sync");
    $count++;
}

echo "SUCCESS: Synced $count users.\n";
