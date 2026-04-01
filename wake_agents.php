<?php
// wake_agents.php - Spawns tasks for all sleeping agents
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

$tasks = [
    ['task_type' => 'welcome_drip',     'goal' => 'Send welcome drip sequence to new members'],
    ['task_type' => 'followup',         'goal' => 'Re-engage members inactive for 7+ days'],
    ['task_type' => 'referral_nudge',   'goal' => 'Remind active members to share their referral link'],
    ['task_type' => 'review_collector', 'goal' => 'Request testimonials from 30-day members'],
    ['task_type' => 'marketing_campaign','goal' => 'Send AI personalized marketing emails to active members'],
    ['task_type' => 'seo_dominance',    'goal' => 'Generate 25 AI-optimized local landing pages'],
];

$spawned = 0;
foreach ($tasks as $t) {
    // Only spawn if no pending/running task exists
    $exists = $pdo->prepare("SELECT COUNT(*) FROM agent_tasks WHERE task_type=? AND status IN ('pending','running')");
    $exists->execute([$t['task_type']]);
    if ($exists->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status, created_at, updated_at) VALUES (?, ?, 'pending', NOW(), NOW())")->execute([$t['task_type'], $t['goal']]);
        echo "✅ WOKE UP: {$t['task_type']}\n";
        $spawned++;
    } else {
        echo "⚡ ALREADY ACTIVE: {$t['task_type']}\n";
    }
}
echo "\n$spawned agents awakened. Run runner.php to process.\n";
?>
