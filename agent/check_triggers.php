<?php
require_once __DIR__ . '/../includes/db.php';
$stmt = $pdo->query("SHOW TRIGGERS LIKE 'users'");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
$stmt = $pdo->query("SHOW TRIGGERS LIKE 'voocoin_balances'");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
