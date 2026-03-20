<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("DESCRIBE meetings");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $cols);
?>
