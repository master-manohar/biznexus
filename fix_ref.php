<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT id, name FROM users WHERE refer_code IS NULL OR refer_code = '' OR refer_code = 'BIZNEXUS'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "FOUND: " . count($users) . "\n";
foreach($users as $u) {
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $u['name']), 0, 4)) . $u['id'] . rand(10,99);
    $pdo->prepare("UPDATE users SET refer_code = ? WHERE id = ?")->execute([$code, $u['id']]);
    echo "SET: " . $u['name'] . " -> " . $code . "\n";
}
echo "FINISHED";
