<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';
try {
    $stmt = $pdo->query("DESCRIBE groups");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    $stmt = $pdo->query("SELECT * FROM groups LIMIT 1");
    echo "Sample data:";
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
