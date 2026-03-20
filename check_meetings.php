<?php
require_once 'includes/db.php';
global $pdo;
echo "--- Meetings Schema ---\n";
try {
    $stmt = $pdo->query("DESCRIBE meetings");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Meetings table error: " . $e->getMessage();
}
?>
