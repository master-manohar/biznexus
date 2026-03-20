<?php
require_once dirname(__DIR__) . '/includes/db.php';
$stmt = $pdo->query("SELECT id, name, role, status FROM users LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($rows);
echo "</pre>";
?>
