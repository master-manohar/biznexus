<?php
require_once dirname(__DIR__) . '/includes/db.php';
$res = $pdo->prepare("UPDATE public_leads SET ai_strategy = 'TEST STRATEGY' WHERE id = 1")->execute();
echo "Update Result: " . ($res ? "SUCCESS" : "FAIL") . "\n";
$stmt = $pdo->prepare("SELECT ai_strategy FROM public_leads WHERE id = 1");
$stmt->execute();
echo "New Value: " . $stmt->fetchColumn() . "\n";
