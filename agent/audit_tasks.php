<?php
require_once __DIR__ . '/../includes/db.php';
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM agent_tasks GROUP BY status");
echo "<h2>Agent Task Status Counts</h2>";
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

$stmt = $pdo->query("SELECT * FROM agent_tasks ORDER BY id DESC LIMIT 10");
echo "<h2>Latest 10 Tasks</h2>";
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
