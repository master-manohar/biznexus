<?php
require_once __DIR__ . '/includes/db.php';
$pdo->prepare("UPDATE user_agent_state SET next_followup_at = NOW() WHERE user_id = 7")->execute();
$pdo->prepare("DELETE FROM agent_interactions WHERE user_id = 7 AND interaction_type = 'daily_quote' AND DATE(sent_at) = CURDATE()")->execute();
echo "Test state reset for User 7.\n";
?>
