<?php
// agent/db_leads_patch.php
require_once __DIR__ . '/../includes/db.php';

try {
    // Add missing columns if they don't exist
    $cols = [
        "source VARCHAR(50) DEFAULT 'DIRECT'",
        "query TEXT",
        "intent VARCHAR(50) DEFAULT 'buy'",
        "city VARCHAR(100)",
        "lat DECIMAL(10,8)",
        "lng DECIMAL(11,8)",
        "total_members_notified INT DEFAULT 0",
        "assigned_at DATETIME"
    ];

    foreach ($cols as $col) {
        try {
            $pdo->exec("ALTER TABLE public_leads ADD COLUMN $col");
        } catch (Exception $e) {
            // Probably already exists
        }
    }

    echo "✅ Table `public_leads` normalized successfully.";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
