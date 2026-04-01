<?php
// agent/db_visitor_init.php
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Table for captured leads (Personal Info)
    $pdo->exec("CREATE TABLE IF NOT EXISTS public_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        city VARCHAR(100),
        category VARCHAR(100),
        source_url TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Table for anonymous visitor tracking (Passive Intelligence)
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitor_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45),
        page_url TEXT,
        referrer TEXT,
        user_agent TEXT,
        time_spent_sec INT DEFAULT 0,
        visit_count INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "SUCCESS: Visitor Intelligence tables ready.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
