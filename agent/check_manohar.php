<?php
require_once __DIR__ . '/../includes/db.php';
echo "--- MANOHAR (UID 7) DATA ---\n";
$stmt = $pdo->query("SELECT id, name, coins FROM users WHERE id = 7");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT * FROM voocoin_balances WHERE user_id = 7");
$v = $stmt->fetch(PDO::FETCH_ASSOC);
if ($v) {
    print_r($v);
} else {
    echo "NO VOOCOIN BALANCE ROW FOUND FOR UID 7!\n";
}
