<?php
// /agent/lead_mover_ai_agent.php
// Production Version: Proximity (100km), Fairness, and AI Enrichment.

ignore_user_abort(true);
set_time_limit(120);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes_functions.php';

echo "Lead Mover AI Agent Starting...\n";

// 1. AI ENRICHMENT (Only first 3 to avoid timeouts)
$leads = $pdo->query("SELECT id, query, category FROM public_leads WHERE ai_strategy IS NULL OR ai_strategy = '' LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

if (count($leads) > 0) {
    try {
        require_once dirname(__DIR__) . '/includes/ai_helper_v3.php';
        
        foreach ($leads as $l) {
            $strategy = "1. Connect within 5 mins\n2. Mention industry experience\n3. Schedule quick discovery call";
            
            echo "Requesting AI Strategy for Lead #{$l['id']}...\n";
            $prompt = "Sales coach: Give 3 short closing tips for a {$l['category']} lead: \"{$l['query']}\". Max 30 words.";
            
            $result = runBizAI([['role' => 'user', 'content' => $prompt]]);
            
            if (isset($result['text'])) {
                $strategy = $result['text'];
            }
            
            $pdo->prepare("UPDATE public_leads SET ai_strategy = ? WHERE id = ?")->execute([$strategy, $l['id']]);
            echo "Enriched Lead #{$l['id']}.\n";
        }
    } catch (Exception $e) { echo "Enrichment Error: " . $e->getMessage() . "\n"; }
}

// 2. SMART MOVEMENT (Proximity & Fairness)
$stale = $pdo->query("SELECT * FROM public_leads WHERE status IN ('new','open') AND assigned_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND (claimed_by_member_id != 0 AND claimed_by_member_id IS NOT NULL)")->fetchAll(PDO::FETCH_ASSOC);

foreach ($stale as $l) {
    echo "Moving Stale Lead #{$l['id']}...\n";
    $cStmt = $pdo->prepare("SELECT id, lat, lng, last_lead_at FROM users WHERE category = ? AND id != ? AND status = 'active'");
    $cStmt->execute([$l['category'], $l['claimed_by_member_id']]);
    $candidates = $cStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $best = null; $minDist = 101;
    foreach ($candidates as $u) {
        $d = getDistance($l['lat'], $l['lng'], $u['lat'], $u['lng']);
        if ($d <= 100) {
            // Fairness: If same distance, pick one who hasn't had a lead recently
            if (!$best || $u['last_lead_at'] < $best['last_lead_at']) {
                $best = $u; $best['dist'] = $d; $minDist = $d;
            }
        }
    }
    
    if ($best) {
        $pdo->prepare("UPDATE public_leads SET claimed_by_member_id = ?, assigned_at = NOW(), recirc_count = recirc_count + 1 WHERE id = ?")->execute([$best['id'], $l['id']]);
        $pdo->prepare("UPDATE users SET last_lead_at = NOW() WHERE id = ?")->execute([$best['id']]);
        sendNotification($pdo, $best['id'], "Smart Lead Re-assigned", "A lead within 100km was moved to you.", 'crm');
        echo "Successfully Re-assigned to User #{$best['id']} (" . round($best['dist'],1) . "km)\n";
    } else {
        $pdo->prepare("UPDATE public_leads SET claimed_by_member_id = 0, assigned_at = NOW() WHERE id = ?")->execute([$l['id']]);
        echo "No 100km match. Moved to Open Pool.\n";
    }
}

echo "Agent Finished.\n";
