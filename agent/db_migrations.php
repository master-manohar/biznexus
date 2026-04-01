<?php
/**
 * agent/db_migrations.php
 * Run this to create the social_posts table.
 */
require_once __DIR__ . '/../includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS social_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) DEFAULT 'instagram',
    category VARCHAR(255),
    city VARCHAR(255),
    caption TEXT,
    hashtags TEXT,
    image_url VARCHAR(255),
    design_prompt TEXT,
    status ENUM('draft', 'queued', 'published', 'failed') DEFAULT 'draft',
    scheduled_at DATETIME,
    published_at DATETIME,
    error_log TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "✅ social_posts table created successfully.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
