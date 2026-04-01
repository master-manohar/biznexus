<?php
require_once __DIR__ . '/../db.php';
// Reset prospect
$pdo->prepare("UPDATE marketing_prospects SET status = 'pending', sent_at = NULL WHERE email = 'manohar.nch@gmail.com'")->execute();
// Reset tasks
$pdo->prepare("UPDATE agent_tasks SET status = 'pending' WHERE task_type = 'outreach_marketing'")->execute();
// If no task exists, create one
$stmt = $pdo->prepare("SELECT COUNT(*) FROM agent_tasks WHERE task_type='outreach_marketing' AND status='pending'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status) VALUES ('outreach_marketing', 'Process batch of 20 pending marketing prospects', 'pending')")->execute();
}
echo "RESET_COMPLETE";
?>
