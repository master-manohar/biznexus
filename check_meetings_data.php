<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("SELECT * FROM meetings LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
