<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

// Check business_profiles schema
echo "=== business_profiles columns ===\n";
$cols = $pdo->query("DESCRIBE business_profiles")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . " | " . $c['Type'] . " | " . $c['Null'] . "\n";

echo "\n=== users columns (KYC related) ===\n";
$ucols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($ucols as $c) echo $c['Field'] . " | " . $c['Type'] . "\n";
?>
