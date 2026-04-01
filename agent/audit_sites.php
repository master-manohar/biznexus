<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

$stmt = $pdo->query("SELECT COUNT(*) FROM businesses WHERE website_generated = 1");
$total = $stmt->fetchColumn();
echo "Total Generated Websites: $total\n";

$stmt = $pdo->query("SELECT slug, business_name FROM businesses WHERE website_generated = 1 ORDER BY id DESC LIMIT 5");
$latest = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Latest 5 Websites:\n";
foreach ($latest as $l) {
    echo "- /sites/{$l['slug']}/ ({$l['business_name']})\n";
}
