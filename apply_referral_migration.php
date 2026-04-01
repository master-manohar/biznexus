<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN referral_source VARCHAR(50) DEFAULT NULL AFTER refer_code");
    echo "SUCCESS: Column referral_source added to users.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
