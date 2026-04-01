<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Two levels up because db.php is in the domain root, not public_html
require_once __DIR__ . '/../../db.php';

$leads = [
    ['Manohar', 'BizNexus President', 'manohar.nch@gmail.com', 'Technology', 'Hyderabad'],
    ['Test Lead 2', 'Organic Foods Inc', 'test2@example.com', 'Agriculture', 'Delhi'],
    ['Test Lead 3', 'Modern Logistics', 'test3@example.com', 'Logistics', 'Mumbai']
];

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO marketing_prospects (name, business_name, email, category, city) VALUES (?, ?, ?, ?, ?)");
    foreach ($leads as $l) {
        $stmt->execute($l);
        echo "Inserted: " . $l[2] . "\n";
    }
} catch (Exception $e) {
    echo "SEED_ERROR: " . $e->getMessage();
}
?>
