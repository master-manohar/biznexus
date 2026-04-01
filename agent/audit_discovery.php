<?php
require_once __DIR__ . '/../../db.php';
echo "<h3>Discovery Agent Audit</h3>";
$q = $pdo->prepare("SELECT * FROM agent_logs WHERE agent_name = 'Prospect Hunter' ORDER BY created_at DESC LIMIT 20");
$q->execute();
echo "<h4>Logs:</h4><pre>";
print_r($q->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

echo "<h4>New Prospects in DB:</h4><pre>";
$s = $pdo->query("SELECT * FROM marketing_prospects ORDER BY id DESC LIMIT 10");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
