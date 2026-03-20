<?php
require_once 'includes/db.php';
$cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
echo implode(',', $cols);
?>
