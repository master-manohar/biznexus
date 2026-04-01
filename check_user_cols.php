<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) { echo $col['Field'] . "\n"; }
?>
