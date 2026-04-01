<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT id, name, business_name, email, city, category FROM users ORDER BY id DESC LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
