<?php
// agent/diag_leads_v7.php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

$stmt = $pdo->query("SELECT source, COUNT(*) as cnt FROM public_leads GROUP BY source");
while($r = $stmt->fetch()) {
    echo "Source: {$r['source']} | Count: {$r['cnt']}\n";
}

echo "\n--- LATEST AI LEADS ---\n";
$stmt = $pdo->query("SELECT name, query FROM public_leads WHERE source LIKE 'AI_SCOUT%' ORDER BY id DESC LIMIT 5");
while($r = $stmt->fetch()) {
    echo "Lead: {$r['name']} | Req: " . substr($r['query'], 0, 50) . "...\n";
}
