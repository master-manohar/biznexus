<?php
require_once __DIR__ . '/../db.php';
$count = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE status = 'pending'")->fetchColumn();
echo "PENDING_TASKS: $count";
?>
