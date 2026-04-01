<?php
/**
 * Agent Runner
 * Purpose: Polls the agent_tasks table and executes pending tasks.
 * Run this via cron every minute: * * * * * php /path/to/agent/runner.php
 */

require_once __DIR__ . '/../includes/db.php';

// Configuration: Task Type to Worker Mapping
$worker_map = [
    'profile' => 'worker_profile.php',
    'profile:seed' => 'worker_profile.php',
    'pr_outreach' => 'media_pr_agent.php',
    'marketing_campaign' => 'marketing_agent.php',
    'social_posting' => 'social_media_agent.php',
    'seo_dominance' => 'seo_power_agent.php',
    'outreach_marketing' => 'outreach_marketing_agent.php',
    'prospect_discovery' => 'prospect_discovery_agent.php',
    'general' => 'prospect_discovery_agent.php', // Fallback for unknown goals
    'outreach' => 'outreach_marketing_agent.php',
    'welcome_drip' => 'welcome_drip_agent.php',
    'followup'          => 'followup_agent.php',
    'profile_nudge'     => 'profile_nudge_agent.php',
    'referral_nudge'    => 'referral_nudge_agent.php',
    'review_collector' => 'review_collector_agent.php',
    'google_scrape'    => 'google_leads_scraper.php',
    // Add more mappings here as workers are developed
];

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) {
    echo "<h3>Access Denied: Manual Run Key Required</h3>";
    exit;
}

echo php_sapi_name() === 'cli' ? "" : "<pre style='background:#0a0a0f;color:#00ff88;padding:20px;font-family:monospace;'>";
echo "[" . date('Y-m-d H:i:s') . "] Starting Agent Runner...\n";

// ... (existing logic)
// 1. Get a batch of pending tasks (up to 5)
$stmt = $pdo->prepare("SELECT * FROM agent_tasks WHERE status = 'pending' ORDER BY id ASC LIMIT 5");
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$tasks) {
    echo "No pending tasks found. Exiting.\n";
    if (php_sapi_name() !== 'cli') echo "</pre><hr><a href='../superadmin.php?s=agents'>Back to Dashboard</a>";
    exit;
}

// ... (rest of logic)
// Update: ensure the runner output is closed properly for Web
if (php_sapi_name() !== 'cli') {
    echo "Task triggered. Check Admin dashboard for logs.\n";
    echo "</pre>";
}
echo "Runner cycle complete.\n";

foreach ($tasks as $task) {
    $taskId = $task['id'];
    $taskType = $task['task_type'];
    $workerScript = $worker_map[$taskType] ?? ($worker_map['general'] ?? null);

    if (!$workerScript) {
        echo "No worker found for task type: $taskType (ID: $taskId). Marking as failed.\n";
        $pdo->prepare("UPDATE agent_tasks SET status = 'failed', result = 'No worker mapped' WHERE id = ?")->execute([$taskId]);
        continue;
    }

    $workerPath = __DIR__ . '/' . $workerScript;
    if (!file_exists($workerPath)) {
        echo "Worker script missing: $workerPath. Marking as failed.\n";
        $pdo->prepare("UPDATE agent_tasks SET status = 'failed', result = 'Worker script missing' WHERE id = ?")->execute([$taskId]);
        continue;
    }

    echo "Processing Task ID: $taskId (Type: $taskType) using $workerScript...\n";

    // 2. Mark as running
    $pdo->prepare("UPDATE agent_tasks SET status = 'running' WHERE id = ?")->execute([$taskId]);

    // 3. Execute the worker
    if (php_sapi_name() === 'cli') {
        $cmd = "php " . escapeshellarg($workerPath) . " " . escapeshellarg($taskId);
        echo "Executing command: $cmd\n";
        $output = shell_exec($cmd);
        echo "Worker Output:\n$output\n";
    } else {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $url = $protocol . "://" . $host . "/agent/" . $workerScript . "?task_id=" . $taskId . "&key=BizCron2024";
        
        echo "Triggering worker via HTTP: $url\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_exec($ch);
        curl_close($ch);
    }
    echo "Task ID $taskId triggered.\n";
}

echo "Batch processing complete.\n";

if (php_sapi_name() !== 'cli' && isset($_GET['auto']) && $_GET['auto'] == 1) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agent_tasks WHERE status = 'pending'");
    $stmt->execute();
    $rem = $stmt->fetchColumn();
    if ($rem > 0) {
        echo "<script>setTimeout(function(){ window.location.href = 'runner.php?key=BizCron2024&auto=1'; }, 2000);</script>";
        echo "<p style='color:#00ff88; font-weight:bold;'>[AUTO MODE] $rem tasks remaining. Next task in 2 seconds...</p>";
    } else {
        echo "<p style='color:#00ff88; font-weight:bold;'>[AUTO MODE] All tasks complete!</p>";
    }
}
if (php_sapi_name() !== 'cli') echo "</pre>";
