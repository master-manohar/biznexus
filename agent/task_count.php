<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM agent_tasks GROUP BY status");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
