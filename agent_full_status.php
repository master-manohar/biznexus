<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

echo "=== BIZNEXUS AGENT ECOSYSTEM — FULL STATUS ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$agents = [
    'prospect_discovery' => 'prospect_discovery_agent.php',
    'outreach_marketing' => 'outreach_marketing_agent.php',
    'social_posting'     => 'social_media_agent.php',
    'seo_dominance'      => 'seo_power_agent.php',
    'welcome_drip'       => 'welcome_drip_agent.php',
    'followup'           => 'followup_agent.php',
    'referral_nudge'     => 'referral_nudge_agent.php',
    'review_collector'   => 'review_collector_agent.php',
    'marketing_campaign' => 'marketing_agent.php',
];

foreach ($agents as $type => $file) {
    $exists = file_exists(__DIR__ . '/agent/' . $file) ? '✅ FILE EXISTS' : '❌ FILE MISSING';
    $pending = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE task_type='$type' AND status='pending'")->fetchColumn();
    $running = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE task_type='$type' AND status='running'")->fetchColumn();
    $lastRun = $pdo->query("SELECT MAX(updated_at) FROM agent_tasks WHERE task_type='$type'")->fetchColumn();
    $status = $running > 0 ? '🟢 RUNNING' : ($pending > 0 ? '🟡 QUEUED' : '🔴 SLEEPING');
    echo "$type\n  File: $exists\n  Status: $status\n  Pending: $pending | Running: $running\n  Last Activity: " . ($lastRun ?: 'Never') . "\n\n";
}

echo "--- DB COUNTS ---\n";
echo "marketing_prospects: " . $pdo->query("SELECT COUNT(*) FROM marketing_prospects")->fetchColumn() . "\n";
echo "public_leads: " . $pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn() . "\n";
echo "active_members: " . $pdo->query("SELECT COUNT(*) FROM users WHERE status='active' AND email NOT LIKE '%@example.com'")->fetchColumn() . "\n";
echo "seo_pages: " . $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn() . "\n";
?>
