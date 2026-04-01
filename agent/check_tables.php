<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
echo "--- ALL TABLES ---\n";
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

foreach (['businesses', 'business_profiles', 'business_products'] as $t) {
    echo "\n--- DESCRIBE $t ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) { echo "Error: $t not found.\n"; }
}
?>
