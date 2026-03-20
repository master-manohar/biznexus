<?php
require_once 'db.php';
$stmt = $pdo->query("DESCRIBE groups");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
?>
