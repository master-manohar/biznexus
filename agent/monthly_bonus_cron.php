<?php
/**
 * Cron Job: Monthly Active Bonus
 * Description: Awards +200 VooCoins to users active in the last 30 days (once per month).
 * Usage: Set this to run on the 1st of every month.
 */

// Handle CLI or Web usage
if (php_sapi_name() !== 'cli' && !isset($_GET['run_secret'])) {
    // Basic protection if run via web
    // Use ?run_secret=biznexus_bonus_2026
    // die("Unauthorized Access.");
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

echo "RUNNING MONTHLY ACTIVE BONUS CRON...\n";

// Find users active in the last 30 days
$month_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE last_active_at >= ? AND status = 'active'");
$stmt->execute([$month_ago]);
$active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($active_users as $u) {
    // Award 200 coins
    awardCoins($pdo, $u['id'], 200, "Monthly Active Bonus - " . date('F Y'));
    
    // Notify
    sendNotification($pdo, $u['id'], "Monthly Bonus! 🎁", "You earned 200 VooCoins for being an active member of BizNexus this month.", 'coins');
    
    $count++;
    if ($count % 50 === 0) echo "Processed $count users...\n";
}

echo "SUCCESS: Awarded Monthly Bonus to $count active members.\n";
