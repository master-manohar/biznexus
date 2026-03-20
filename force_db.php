<?php
require_once __DIR__ . '/includes/db.php';
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
    echo "SUCCESS: is_verified added.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
