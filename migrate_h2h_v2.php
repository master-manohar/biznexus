<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Add user_id column to h2h_leads if it doesn't exist
    $pdo->exec("ALTER TABLE h2h_leads ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL AFTER id");
    echo "SUCCESS: h2h_leads table updated with user_id.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
