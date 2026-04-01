<?php
require_once __DIR__ . '/../../db.php';
echo "<h3>Pending Tasks</h3><pre>";
$q = $pdo->query("SELECT * FROM agent_tasks WHERE status = 'pending' ORDER BY id ASC");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
