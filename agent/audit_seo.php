<?php
require_once __DIR__ . '/../includes/db.php';
$stmt = $pdo->query("SELECT * FROM agent_tasks WHERE task_type='seo_dominance' ORDER BY id DESC LIMIT 5");
echo "<h2>SEO Dominance Tasks</h2>";
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
