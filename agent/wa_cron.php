<?php
// /agent/wa_cron.php
require_once dirname(__DIR__) . '/includes/db.php';

// Detect new leads not yet pushed to WA
$stmt = $pdo->query("
    SELECT * FROM public_leads 
    WHERE status != 'locked' AND id NOT IN (SELECT lead_id FROM lead_whatsapp_queue)
    ORDER BY created_at DESC LIMIT 50
");
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
$messagesQueued = 0;

foreach($leads as $lead) {
    // Find matching members by Trust Score / Trust Badge
    $matchSql = "
        SELECT u.id, u.name, bp.whatsapp, bp.business_name, u.trust_badge
        FROM users u
        JOIN business_profiles bp ON u.id = bp.user_id
        WHERE u.status = 'active' AND bp.category = ?
        ORDER BY 
            CASE u.trust_badge 
                WHEN 'diamond' THEN 3 
                WHEN 'gold' THEN 2 
                WHEN 'blue' THEN 1 
                ELSE 0 END DESC
        LIMIT 5
    ";
    
    $mStmt = $pdo->prepare($matchSql);
    $mStmt->execute([$lead['category']]);
    $members = $mStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($members as $m) {
        $wa = preg_replace('/[^0-9]/', '', $m['whatsapp'] ?? '');
        if(strlen($wa) == 10) $wa = "91" . $wa;
        
        if (empty($wa)) continue;
        
        $buyerName = "A verified buyer"; // Masked inherently
        
        $msg = "⚡ *BizNexus Proactive Lead*\n\n";
        $msg .= "Hello " . $m['name'] . ",\n";
        $msg .= "{$buyerName} in *" . $lead['city'] . "* is looking for *" . $lead['category'] . "*.\n\n";
        $msg .= "📋 *Requirement:* " . $lead['query'] . "\n\n";
        $msg .= "⏳ *Claim Countdown: 2 Hours*\n";
        $msg .= "Click below to secure this deal before others do:\n";
        $msg .= "https://biznexus.in/dashboard/leads.php\n";
        
        $link = "https://wa.me/" . $wa . "?text=" . urlencode($msg);
        
        $iStmt = $pdo->prepare("INSERT INTO lead_whatsapp_queue (lead_id, member_id, member_phone, message_text, wa_link, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $iStmt->execute([$lead['id'], $m['id'], $wa, $msg, $link]);
        $messagesQueued++;
    }
    $count++;
}

echo "Proactive Lead Pusher executed successfully.\n";
echo "Processed $count unnotified leads.\n";
echo "Queued $messagesQueued WhatsApp actionable deep-links for members.";
?>
