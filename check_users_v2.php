<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "Total users: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT email, name, status FROM users LIMIT 5");
    echo "<pre>";
    print_r($stmt->fetchAll());
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
