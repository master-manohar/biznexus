<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');

// Add firm_type column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE business_profiles ADD COLUMN firm_type VARCHAR(50) DEFAULT NULL AFTER pan_number");
    echo "✅ Added firm_type column to business_profiles\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ️ firm_type already exists\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Add address_line column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE business_profiles ADD COLUMN gst_verified TINYINT(1) DEFAULT 0 AFTER gst_number");
    echo "✅ Added gst_verified column\n";
} catch (Exception $e) {
    echo "ℹ️ gst_verified: " . (strpos($e->getMessage(),'Duplicate')!==false ? 'already exists' : $e->getMessage()) . "\n";
}

echo "\n=== Updated business_profiles columns ===\n";
$cols = $pdo->query("DESCRIBE business_profiles")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . " | " . $c['Type'] . "\n";
?>
