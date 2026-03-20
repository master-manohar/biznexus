<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("DESCRIBE meetings");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
