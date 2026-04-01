<?php
require_once dirname(__DIR__) . '/includes/db.php';
$stmt = $pdo->query("SELECT id, name, category, ai_strategy, lat, lng FROM public_leads ORDER BY id DESC LIMIT 10");
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "LATEST 10 LEADS STATUS:\n";
foreach($leads as $l) {
    $hasAi = $l['ai_strategy'] ? "YES" : "NO";
    $hasGeo = ($l['lat'] && $l['lng']) ? "YES" : "NO";
    echo "ID: {$l['id']} | Name: {$l['name']} | Cat: {$l['category']} | AI: $hasAi | Geo: $hasGeo\n";
    if ($l['ai_strategy']) echo "   Strategy: " . substr($l['ai_strategy'], 0, 50) . "...\n";
}

$u = $pdo->query("SELECT COUNT(*) FROM users WHERE lat IS NOT NULL")->fetchColumn();
echo "\nUsers with Geo: $u\n";
