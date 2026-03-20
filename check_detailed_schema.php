<?php
require_once 'includes/db.php';
global $pdo;
echo "--- Detailed Schema Check ---\n";
foreach (['users', 'business_profiles', 'referrals', 'meetings'] as $t) {
    $stmt = $pdo->query("DESCRIBE $t");
    echo "Table: $t\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Check for distinct categories in business_profiles
try {
    $stmt = $pdo->query("SELECT DISTINCT business_category FROM business_profiles LIMIT 20");
    echo "Sample Categories: ";
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch(Exception $e) { echo "Categorization check fail: " . $e->getMessage(); }
?>
