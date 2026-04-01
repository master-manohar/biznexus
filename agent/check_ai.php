<?php
require_once dirname(__DIR__) . '/includes/db.php';
$stmt = $pdo->query("SELECT id, ai_strategy, lat, lng FROM public_leads WHERE ai_strategy IS NOT NULL LIMIT 5");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "AI STRATEGIES UPDATED:\n";
print_r($res);
