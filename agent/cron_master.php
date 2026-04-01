<?php
/**
 * cron_master.php
 * BizNexus Heartbeat: Orchestrates AI Scouting, Lead Dispatch, and SEO Sync.
 * Usage: curl https://biznexus.in/agent/cron_master.php?key=BizCron2024
 */
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$key = $_GET['key'] ?? '';
if ($key !== 'BizCron2024') {
    die(json_encode(['status' => 'error', 'msg' => 'Unauthorized']));
}

$report = ['time' => date('Y-m-d H:i:s')];

// 1. AI SCOUT: Find new B2B requirements (Simulated pulse for now)
try {
    include __DIR__ . '/ai_lead_scout_worker.php';
    $report['scout'] = $scout_result ?? 'Completed';
} catch (Exception $e) { $report['scout_error'] = $e->getMessage(); }

// 2. LEAD DISPATCH: Match and send leads to paid members
try {
    include __DIR__ . '/../includes/lead_dispatch_engine.php';
    // Logic to find 'new' leads and dispatch them
    $stmt = $pdo->query("SELECT * FROM public_leads WHERE status = 'new' AND source LIKE 'AI_SCOUT%' LIMIT 5");
    $dispatched = 0;
    while ($lead = $stmt->fetch()) {
        $res = dispatchPublicLead($pdo, $lead['name'], $lead['phone'], $lead['email'], $lead['query'], $lead['category'], $lead['city']);
        if ($res['success']) {
            $pdo->prepare("UPDATE public_leads SET status = 'dispatched', assigned_at = NOW() WHERE id = ?")->execute([$lead['id']]);
            $dispatched++;
        }
    }
    $report['dispatched'] = $dispatched;
} catch (Exception $e) { $report['dispatch_error'] = $e->getMessage(); }

// 3. SEO SYNC: Update sitemap if new pages exist
try {
    $_GET['key'] = 'BizCron2024';
    ob_start();
    include __DIR__ . '/../sitemap.php';
    $sitemap_json = ob_get_clean();
    $report['sitemap'] = json_decode($sitemap_json, true) ?? 'Updated';
} catch (Exception $e) { $report['sitemap_error'] = $e->getMessage(); }

echo json_encode($report, JSON_PRETTY_PRINT);
