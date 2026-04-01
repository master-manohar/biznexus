<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

echo "=== Last 5 Completed/Failed Tasks ===\n";
try {
    $tasks = $pdo->query("SELECT * FROM agent_tasks WHERE status IN ('completed', 'failed') ORDER BY updated_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($tasks);
} catch (Exception $e) { echo "Error: " . $e->getMessage(); }

echo "\n=== Last 5 Logs ===\n";
try {
    $logs = $pdo->query("SELECT * FROM agent_logs ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($logs);
} catch (Exception $e) { echo "Error: " . $e->getMessage(); }
?>
