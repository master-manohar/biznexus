<?php
require_once 'includes/db.php';
global $pdo;
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS group_id INT DEFAULT NULL");
echo "DONE";
?>
