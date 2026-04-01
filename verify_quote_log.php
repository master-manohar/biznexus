<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT content FROM agent_interactions WHERE user_id = 7 AND interaction_type = 'daily_quote' ORDER BY id DESC LIMIT 1");
$row = $stmt->fetch();
echo "Last daily_quote content:\n" . ($row['content'] ?? 'NO CONTENT FOUND') . "\n";
?>
