<?php
require_once __DIR__ . '/../includes/db.php';
try {
    $pdo->exec("ALTER TABLE marketing_prospects ADD COLUMN IF NOT EXISTS source_network VARCHAR(50) DEFAULT 'Manual'");
    $pdo->exec("ALTER TABLE marketing_prospects ADD COLUMN IF NOT EXISTS bsr_boost_applied TINYINT(1) DEFAULT 0");
    echo "Schema updated successfully for migration tracking.";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage();
}
?>
