<?php
require_once __DIR__ . '/../db.php';
echo "<h3>Outreach Monitoring (Logs & Status)</h3>";
$q1 = $pdo->prepare("SELECT * FROM marketing_prospects WHERE email = 'manohar.nch@gmail.com'");
$q1->execute();
echo "<h4>Prospect Status:</h4><pre>";
print_r($q1->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

$q2 = $pdo->prepare("SELECT * FROM agent_logs WHERE detail LIKE '%manohar.nch@gmail.com%' ORDER BY timestamp DESC LIMIT 5");
$q2->execute();
echo "<h4>Agent Logs for User:</h4><pre>";
print_r($q2->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
