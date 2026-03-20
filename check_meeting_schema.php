<?php
require_once 'includes/db.php';
global $pdo;
echo "--- BUSINESS PROFILES ---\n";
$stmt = $pdo->query("DESCRIBE business_profiles");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- MEETINGS ---\n";
$stmt = $pdo->query("DESCRIBE meetings");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
