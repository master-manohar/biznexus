<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

echo "=== System Check ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "CWD: " . getcwd() . "\n";

echo "\n=== Table Existence ===\n";
$tables = ['agent_tasks', 'agent_logs', 'marketing_prospects', 'public_leads'];
foreach ($tables as $t) {
    echo "$t: ";
    try {
        $pdo->query("SELECT 1 FROM $t LIMIT 1");
        echo "EXISTS\n";
    } catch (Exception $e) {
        echo "MISSING (" . $e->getMessage() . ")\n";
    }
}

echo "\n=== Database Counts ===\n";
try {
    echo "agent_tasks: " . $pdo->query("SELECT COUNT(*) FROM agent_tasks")->fetchColumn() . "\n";
    echo "agent_logs: " . $pdo->query("SELECT COUNT(*) FROM agent_logs")->fetchColumn() . "\n";
    echo "marketing_prospects: " . $pdo->query("SELECT COUNT(*) FROM marketing_prospects")->fetchColumn() . "\n";
    echo "public_leads: " . $pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn() . "\n";
} catch (Exception $e) { echo "Error counting: " . $e->getMessage() . "\n"; }

echo "\n=== Agent Tasks (Last 5) ===\n";
try {
    $tasks = $pdo->query("SELECT * FROM agent_tasks ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($tasks);
} catch (Exception $e) { echo "Error fetching tasks: " . $e->getMessage() . "\n"; }
?>
