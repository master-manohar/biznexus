<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

echo "--- AGENT TASKS ---\n";
try {
    $stmt = $pdo->query("SELECT * FROM agent_tasks ORDER BY id DESC LIMIT 10");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }

echo "\n--- RECENT AGENT LOGS ---\n";
try {
    $stmt = $pdo->query("SELECT * FROM agent_logs ORDER BY timestamp DESC LIMIT 10");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }
