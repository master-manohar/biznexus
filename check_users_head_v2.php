<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("DESCRIBE users");
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "--- FIRST 20 FIELDS ---\n";
print_r(array_slice($fields, 0, 20));
?>
