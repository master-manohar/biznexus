<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("DESCRIBE business_profiles");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
