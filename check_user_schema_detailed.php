<?php
require_once __DIR__ . '/db.php';
$stmt = $pdo->query("DESCRIBE users");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
