<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
echo "--- DESCRIBE users ---\n";
try {
    $stmt = $pdo->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }
?>
