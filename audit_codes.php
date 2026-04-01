<?php
require_once __DIR__ . '/db.php';
$stmt = $pdo->query("SELECT id, name, refer_code FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "REF CODE AUDIT:\n";
foreach($users as $u) {
    echo "ID: " . $u['id'] . " | NAME: " . $u['name'] . " | CODE: " . ($u['refer_code'] ?: 'MISSING') . "\n";
}
