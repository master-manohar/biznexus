<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

echo "--- TABLE: agent_logs ---\n";
try {
    $stmt = $pdo->query("DESCRIBE agent_logs");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }

echo "\n--- TABLE: users ---\n";
try {
    $stmt = $pdo->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }

echo "\n--- AGENT TASK COUNTS ---\n";
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM agent_tasks GROUP BY status");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }

echo "\n--- DB CONNECTION DETAILS ---\n";
echo "DB: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
echo "SERVER: " . $_SERVER['SERVER_ADDR'] . "\n";
?>
