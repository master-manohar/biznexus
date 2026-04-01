<?php
require_once __DIR__ . '/db.php';
$stmt = $pdo->query("SELECT id, name, refer_code FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$log = "AUDIT LOG:\n";
foreach($users as $u) {
    if (empty($u['refer_code'])) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $u['name']), 0, 4)) . $u['id'] . rand(10,99);
        $pdo->prepare("UPDATE users SET refer_code = ? WHERE id = ?")->execute([$code, $u['id']]);
        $log .= "FIXED: ID {$u['id']} -> $code\n";
    } else {
        $log .= "OK: ID {$u['id']} -> {$u['refer_code']}\n";
    }
}
file_put_contents('referral_audit.txt', $log);
echo "Audit complete. Check referral_audit.txt";
