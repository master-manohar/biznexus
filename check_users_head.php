<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("DESCRIBE users");
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(array_slice($fields, 0, 20), JSON_PRETTY_PRINT);
?>
