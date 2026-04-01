<?php
require_once __DIR__ . '/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS referral_source VARCHAR(50) DEFAULT NULL AFTER refer_code");
    echo "SUCCESS: Column referral_source added to users table.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
助
