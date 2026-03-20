<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("DESCRIBE meetings");
echo "<pre>"; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";
?>
