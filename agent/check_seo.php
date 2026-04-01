<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT id, slug, last_generated FROM seo_pages ORDER BY id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total Generated: " . count($rows) . "\n";
foreach($rows as $r) {
    echo "ID: {$r['id']} | Slug: {$r['slug']} | Generated: {$r['last_generated']}\n";
}
