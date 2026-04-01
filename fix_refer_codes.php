<?php
require_once __DIR__ . '/db.php';
$stmt = $pdo->query("SELECT id, name, refer_code FROM users WHERE refer_code IS NULL OR refer_code = ''");
$missing = $stmt->fetchAll();
echo "Users missing refer_code: " . count($missing) . "\n";
foreach($missing as $u) {
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $u['name']), 0, 4)) . $u['id'] . rand(10,99);
    $pdo->prepare("UPDATE users SET refer_code = ? WHERE id = ?")->execute([$code, $u['id']]);
    echo "Updated User {$u['id']} with code: $code\n";
}
echo "Done.";
