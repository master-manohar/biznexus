<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS h2h_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        email VARCHAR(150) NOT NULL,
        created_at DATETIME DEFAULT NOW()
    )");
    echo "SUCCESS: h2h_leads table created.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
