<?php
require_once __DIR__ . '/../db.php';
$q = $pdo->query("DESCRIBE agent_logs");
echo "<pre>";
print_r($q->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
