<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT id, email, status FROM media_outreach ORDER BY id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total Rows: " . count($rows) . "\n";
foreach($rows as $r) {
    echo "ID: {$r['id']} | Email: {$r['email']} | Status: {$r['status']}\n";
}
