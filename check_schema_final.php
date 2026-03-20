<?php
require_once 'includes/db.php';
global $pdo;

echo "<h3>Meetings Table</h3>";
$stmt = $pdo->query("DESCRIBE meetings");
echo "<pre>"; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";

echo "<h3>Users Table</h3>";
$stmt = $pdo->query("DESCRIBE users");
echo "<pre>"; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";
?>
