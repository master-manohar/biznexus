<?php
// /agent/lead_recirc.php
// Automation Agent: Enforces 2-hour claim policy for Referrals and Public Leads

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

echo "Lead Recirculation Agent - Running at " . date('Y-m-d H:i:s') . "\n";

// 1. Process UNCLAIMED REFERRALS
$refStmt = $pdo->prepare("SELECT id, sender_id, receiver_id, category, recirc_count FROM referrals WHERE status = 'new' AND assigned_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
$refStmt->execute();
$unclaimedRefs = $refStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($unclaimedRefs as $r) {
    $rid = $r['id'];
    $cat = $r['category'];
    $oldRec = $r['receiver_id'];
    $count = (int)$r['recirc_count'];
    
    echo "Processing Unclaimed Referral #$rid ($cat)...\n";

    // Find next available member in same category (exclude old receiver and sender)
    $nextStmt = $pdo->prepare("SELECT id FROM users WHERE category = ? AND id NOT IN (?, ?) AND status = 'active' ORDER BY id ASC LIMIT 1 OFFSET ?");
    $nextStmt->execute([$cat, $oldRec, $r['sender_id'], $count + 1]);
    $next = $nextStmt->fetchColumn();

    if ($next) {
        $pdo->prepare("UPDATE referrals SET receiver_id = ?, assigned_at = NOW(), recirc_count = recirc_count + 1 WHERE id = ?")->execute([$next, $rid]);
        sendNotification($pdo, $next, "Urgent Lead Re-assigned", "A $cat lead has been re-assigned to you. Claim it fast!", 'crm');
        echo "Re-assigned to User #$next.\n";
    } else {
        // No more members found, move to Open Pool (0) for admin
        $pdo->prepare("UPDATE referrals SET receiver_id = 0, assigned_at = NOW() WHERE id = ?")->execute([$rid]);
        echo "Moved to Open Pool (No more matches and candidates found).\n";
        // Notify admin about stalled lead
        $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        while ($aid = $adminStmt->fetchColumn()) {
            sendNotification($pdo, $aid, "Stalled Lead: #$rid", "A $cat lead has no more available experts and needs manual assignment.", 'superadmin');
        }
    }
}

// 2. Process UNCLAIMED PUBLIC LEADS (AI Captured)
$leadStmt = $pdo->prepare("SELECT id, category, claimed_by_member_id as receiver_id, recirc_count FROM public_leads WHERE status IN ('new', 'open') AND (claimed_by_member_id IS NOT NULL AND claimed_by_member_id != 0) AND assigned_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
$leadStmt->execute();
$unclaimedPublic = $leadStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($unclaimedPublic as $l) {
    $lid = $l['id'];
    $cat = $l['category'];
    $oldRec = $l['receiver_id'];
    $count = (int)$l['recirc_count'];

    echo "Processing Unclaimed Public Lead #$lid ($cat)...\n";

    $nextStmt = $pdo->prepare("SELECT id FROM users WHERE category = ? AND id != ? AND status = 'active' ORDER BY id ASC LIMIT 1 OFFSET ?");
    $nextStmt->execute([$cat, $oldRec, $count + 1]);
    $next = $nextStmt->fetchColumn();

    if ($next) {
        $pdo->prepare("UPDATE public_leads SET claimed_by_member_id = ?, assigned_at = NOW(), recirc_count = recirc_count + 1 WHERE id = ?")->execute([$next, $lid]);
        sendNotification($pdo, $next, "AI Lead Re-assigned", "A new potential customer in $cat has been assigned to you.", 'crm');
        echo "Re-assigned to User #$next.\n";
    } else {
        $pdo->prepare("UPDATE public_leads SET claimed_by_member_id = 0, assigned_at = NOW() WHERE id = ?")->execute([$lid]);
        echo "Moved to Open Pool.\n";
    }
}

echo "Agent Finished.\n";
?>
