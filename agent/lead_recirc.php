<?php
// /agent/lead_recirc.php
// Updated: 10-minute rollover (was 2 hours), @example.com excluded, BCC admin on re-dispatch

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';
require_once __DIR__ . '/../includes/email_config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) die("Unauthorized.");

$BCC_ADMIN = 'manohar.nch@gmail.com';
$ROLLOVER_MINUTES = 10; // Roll lead to next person after this many minutes

echo "Lead Recirculation Agent - Running at " . date('Y-m-d H:i:s') . "\n";
echo "Rollover Timeout: $ROLLOVER_MINUTES minutes\n";

// 1. Find lead_dispatches that are still 'pending' after $ROLLOVER_MINUTES
$dispatch_stmt = $pdo->prepare("
    SELECT ld.id as dispatch_id, ld.lead_id, ld.member_id as old_member_id, ld.dispatch_rank,
           pl.category, pl.query, pl.name as lead_name, pl.phone as lead_phone
    FROM lead_dispatches ld
    JOIN public_leads pl ON ld.lead_id = pl.id
    WHERE ld.status = 'pending'
      AND ld.notified_at < DATE_SUB(NOW(), INTERVAL $ROLLOVER_MINUTES MINUTE)
      AND pl.status IN ('new', 'open')
      AND pl.status NOT IN ('claimed', 'accepted', 'closed', 'won')
");
$dispatch_stmt->execute();
$expired_dispatches = $dispatch_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($expired_dispatches) . " expired dispatches to roll.\n";

foreach ($expired_dispatches as $d) {
    $dispatchId = $d['dispatch_id'];
    $leadId     = $d['lead_id'];
    $category   = $d['category'];
    $oldMember  = $d['old_member_id'];
    $currentRank = (int)$d['dispatch_rank'];

    // Mark this dispatch as expired
    $pdo->prepare("UPDATE lead_dispatches SET status = 'expired' WHERE id = ?")->execute([$dispatchId]);

    // Find the next eligible member in the same category (exclude all already dispatched + @example.com)
    $already_dispatched = $pdo->prepare("SELECT member_id FROM lead_dispatches WHERE lead_id = ?");
    $already_dispatched->execute([$leadId]);
    $excluded = $already_dispatched->fetchAll(PDO::FETCH_COLUMN);
    $excluded[] = 0;

    $excludePlaceholders = implode(',', array_fill(0, count($excluded), '?'));

    $nextStmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.plan, bp.business_name, bp.whatsapp, bp.category
        FROM users u
        JOIN business_profiles bp ON u.id = bp.user_id
        WHERE u.status = 'active'
          AND u.email NOT LIKE '%@example.com'
          AND bp.category = ?
          AND u.id NOT IN ($excludePlaceholders)
        ORDER BY FIELD(u.plan,'platinum','gold','silver','free'), u.id ASC
        LIMIT 1
    ");
    $nextStmt->execute(array_merge([$category], $excluded));
    $nextMember = $nextStmt->fetch(PDO::FETCH_ASSOC);

    if ($nextMember) {
        $newRank = $currentRank + 1;
        // Insert new dispatch row for next member
        $pdo->prepare("INSERT INTO lead_dispatches (lead_id, member_id, member_name, business_name, category, city, whatsapp, dispatch_rank, slot_number, status, notified_at) VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, 'pending', NOW())")
            ->execute([$leadId, $nextMember['id'], $nextMember['name'], $nextMember['business_name'], $category, $nextMember['whatsapp'] ?? '', $newRank, $newRank]);

        // Send notification
        sendNotification($pdo, $nextMember['id'], "⚡ Lead Passed to You", "A $category lead was not claimed. It's now yours — act fast!", 'lead');

        // Send email with BCC to admin
        if (file_exists(__DIR__ . '/../includes/emails/lead_notify.php')) {
            require_once __DIR__ . '/../includes/emails/lead_notify.php';
            sendLeadEmail($nextMember['email'], $nextMember['name'], $category, '', $d['query'], $d['lead_name'], $d['lead_phone'], $BCC_ADMIN);
        } else {
            $subject = "⚡ New Lead Assigned: $category";
            $body = "<p>Hi {$nextMember['name']},</p><p>A new <strong>$category</strong> lead has been rolled to you after {$ROLLOVER_MINUTES} minutes.</p><p>Query: {$d['query']}</p><p><a href='https://biznexus.in/auth/login.php'>Login to claim it now →</a></p>";
            sendEmail($nextMember['email'], $subject, $body, $BCC_ADMIN);
        }

        echo "Lead #$leadId rolled from User #$oldMember to User #{$nextMember['id']} ({$nextMember['name']})\n";
    } else {
        // No more members, put lead back to open pool
        $pdo->prepare("UPDATE public_leads SET status = 'open' WHERE id = ?")->execute([$leadId]);
        echo "Lead #$leadId has no more eligible members. Moved to open pool.\n";
    }
}

// 2. Auto-clean stuck agent tasks (running > 30 min)
$stuck = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE status = 'running' AND updated_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)")->fetchColumn();
if ($stuck > 0) {
    $pdo->exec("UPDATE agent_tasks SET status = 'failed', result = 'Stuck/Timeout (Auto-Reset)' WHERE status = 'running' AND updated_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    echo "Auto-cleaned $stuck stuck tasks.\n";
}

echo "Agent Finished.\n";
?>
