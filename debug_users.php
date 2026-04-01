<?php
require_once __DIR__ . '/db.php';
$stmt = $pdo->query("SELECT id, name, email, refer_code FROM users LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "DEBUG USERS:\n";
print_r($users);
