<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../includes/db.php';

$tables = ['users', 'businesses', 'business_profiles', 'meetings', 'appointments'];
foreach ($tables as $t) {
    echo "--- Table: $t ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
