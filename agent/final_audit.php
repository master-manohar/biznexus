<?php
require_once __DIR__ . '/../../db.php';
echo "<h3>Final Outreach Audit</h3>";
$q = $pdo->prepare("SELECT * FROM agent_logs WHERE task_id = 731 ORDER BY id ASC");
$q->execute();
echo "<h4>Outreach Logs (Task 731):</h4><pre>";
print_r($q->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

echo "<h4>Prospect Status Update:</h4><pre>";
$s = $pdo->query("SELECT id, business_name, email, status, sent_at FROM marketing_prospects ORDER BY id DESC LIMIT 15");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
