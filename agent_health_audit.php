<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

echo "=== AGENT HEALTH AUDIT ===\n\n";

// 1. Task type summary
echo "--- Task Types in Queue ---\n";
$rows = $pdo->query("SELECT task_type, status, COUNT(*) as cnt FROM agent_tasks GROUP BY task_type, status ORDER BY task_type")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "{$r['task_type']} [{$r['status']}]: {$r['cnt']}\n";
}

echo "\n--- Worker Files Existence ---\n";
$workers = ['prospect_discovery_agent.php','social_media_agent.php','outreach_marketing_agent.php','seo_power_agent.php','media_pr_agent.php','marketing_agent.php','worker_profile.php'];
foreach ($workers as $w) {
    $path = __DIR__ . '/agent/' . $w;
    echo "$w: " . (file_exists($path) ? "EXISTS" : "MISSING") . "\n";
}

echo "\n--- Marketing Prospects (last 5, excluding example.com) ---\n";
$prospects = $pdo->query("SELECT id, business_name, email, category, created_at FROM marketing_prospects WHERE email NOT LIKE '%@example.com' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($prospects);

echo "\n--- Example.com accounts currently in marketing_prospects ---\n";
$ex = $pdo->query("SELECT COUNT(*) FROM marketing_prospects WHERE email LIKE '%@example.com'")->fetchColumn();
echo "Count: $ex\n";

echo "\n--- Real users (non-example.com) who are members ---\n";
$real = $pdo->query("SELECT COUNT(*) FROM users WHERE email NOT LIKE '%@example.com' AND status='active'")->fetchColumn();
echo "Active real users: $real\n";
?>
