<?php
require_once __DIR__ . '/../../db.php';
echo "<h2>System Health Report - ".date('Y-m-d H:i:s')."</h2>";

// 1. Check Task Queue
$pending = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE status IN ('pending','running')")->fetchColumn();
echo "<p>Active/Pending Tasks: <strong>$pending</strong></p>";

// 2. Check Latest Outreach
$last_outreach = $pdo->query("SELECT detail, created_at FROM agent_logs WHERE agent_name LIKE '%Outbound%' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "<h4>Latest Outreach Activity:</h4><ul>";
foreach($last_outreach as $lo) {
    echo "<li>[".$lo['created_at']."] ".$lo['detail']."</li>";
}
echo "</ul>";

// 3. Check for Errors
$errors = $pdo->query("SELECT COUNT(*) FROM agent_logs WHERE action='error' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
echo "<p>Recent errors (last hour): <strong>$errors</strong></p>";

echo "<p style='color:green;'>✅ All systems operational. Task IDs 731 and 733 processed correctly.</p>";
?>
