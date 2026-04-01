<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT * FROM agent_tasks WHERE task_type = 'social_posting' ORDER BY id DESC LIMIT 5");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
