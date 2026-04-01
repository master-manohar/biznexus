<?php
require_once __DIR__ . '/../db.php';
echo "<h3>Final Verification Audit</h3>";
$q = $pdo->prepare("SELECT * FROM agent_logs WHERE task_id = '720' ORDER BY timestamp DESC");
$q->execute();
echo "<h4>Logs for Task 720:</h4><pre>";
print_r($q->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

echo "<h4>Agent Task Summary:</h4><pre>";
$s = $pdo->query("SELECT task_type, status, COUNT(*) as count FROM agent_tasks GROUP BY task_type, status");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
