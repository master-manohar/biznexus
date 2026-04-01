<?php
/**
 * agent/db_seo_init.php
 * Initializes the SEO Power Page database structure.
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS seo_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(100) NOT NULL,
        city VARCHAR(100) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        meta_title VARCHAR(255),
        meta_desc TEXT,
        faq_json TEXT,
        ai_content TEXT,
        last_generated DATETIME,
        status ENUM('active', 'draft') DEFAULT 'active',
        INDEX (category),
        INDEX (city),
        INDEX (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "✅ Database table `seo_pages` initialized successfully.\n";
} catch (Exception $e) {
    echo "❌ Error initializing table: " . $e->getMessage() . "\n";
}
