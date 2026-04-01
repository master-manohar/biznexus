<?php
require_once dirname(__DIR__) . '/includes/db.php';
echo "AGENT DB TEST START\n";
$stmt = $pdo->query("SELECT id FROM public_leads WHERE ai_strategy IS NULL OR ai_strategy = '' LIMIT 1");
$id = $stmt->fetchColumn();
if ($id) {
    echo "Updating Lead #$id...\n";
    $res = $pdo->prepare("UPDATE public_leads SET ai_strategy = 'AGENT_TEST_WRITE' WHERE id = ?")->execute([$id]);
    echo "Result: " . ($res ? "SUCCESS" : "FAIL") . "\n";
} else {
    echo "No leads to update.\n";
}
echo "AGENT DB TEST END\n";
