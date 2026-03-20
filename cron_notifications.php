<?php
/**
 * cron_notifications.php
 * Run this script via cron job (e.g. every hour) to trigger automated notifications.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes_functions.php';

// 1. Meeting Reminders (1 hour before)
try {
    // Find meetings starting in ~1 hour (between 50 and 70 minutes from now)
    $stmt = $pdo->prepare("SELECT m.id, m.user_id, m.meeting_time, u.name as other_party 
                           FROM meetings m 
                           JOIN users u ON m.participant_id = u.id
                           WHERE m.meeting_time BETWEEN DATE_ADD(NOW(), INTERVAL 50 MINUTE) AND DATE_ADD(NOW(), INTERVAL 70 MINUTE)");
    $stmt->execute();
    $meetings = $stmt->fetchAll();

    foreach ($meetings as $m) {
        $title = "📅 Meeting Reminder";
        $message = "Reminder: Your meeting with {$m['other_party']} starts at " . date('h:i A', strtotime($m['meeting_time'])) . ".";
        
        // Check if already notified to avoid duplicates (simplified)
        $check = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND title = ? AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)");
        $check->execute([$m['user_id'], $title]);
        if (!$check->fetch()) {
            sendNotification($pdo, $m['user_id'], $title, $message, 'meeting');
        }
    }
} catch (Exception $e) {
    error_log("Cron Meeting Reminder Error: " . $e->getMessage());
}

// 2. Monthly Points Summary (Run once on the 1st of the month)
if (date('d') === '01' && date('H') === '00') {
    try {
        $all_users = $pdo->query("SELECT id FROM users WHERE status='active'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($all_users as $uid) {
            $last_month = date('Y-m', strtotime('last month'));
            $pts = $pdo->prepare("SELECT SUM(amount) FROM coin_transactions WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $pts->execute([$uid, $last_month]);
            $total_pts = (int)$pts->fetchColumn();

            if ($total_pts > 0) {
                sendNotification($pdo, $uid, "🏆 Monthly Points Milestone", "You earned $total_pts BizCoins in " . date('F', strtotime('last month')) . "! Great job.", 'coin_milestone');
            }
        }
    } catch (Exception $e) {
        error_log("Cron Monthly Summary Error: " . $e->getMessage());
    }
}

// 3. Referral/Lead Weekly Digest (Run on Mondays)
if (date('D') === 'Mon' && date('H') === '09') {
    try {
        $all_users = $pdo->query("SELECT id FROM users WHERE status='active'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($all_users as $uid) {
            // New leads in last 7 days
            $leads_stmt = $pdo->prepare("SELECT COUNT(*) FROM public_leads WHERE claimed_by_member_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $leads_stmt->execute([$uid]);
            $leads_count = (int)$leads_stmt->fetchColumn();

            // Referrals in last 7 days
            $refs_stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $refs_stmt->execute([$uid]);
            $refs_count = (int)$refs_stmt->fetchColumn();

            if ($leads_count > 0 || $refs_count > 0) {
                $msg = "Weekly Stats: You had $leads_count new leads and $refs_count referrals this week.";
                sendNotification($pdo, $uid, "📊 Weekly Activity Digest", $msg, 'info');
            }
        }
    } catch (Exception $e) {
        error_log("Cron Weekly Digest Error: " . $e->getMessage());
    }
}

echo "Cron tasks processed successfully.\n";
