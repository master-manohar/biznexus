<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/includes/db.php';
try {
    echo "Attempting to DROP table...\n";
    $pdo->exec("DROP TABLE IF EXISTS social_posts");
    echo "DROP success. Attempting to CREATE table...\n";
    $pdo->exec("CREATE TABLE social_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(100),
        city VARCHAR(100),
        caption TEXT,
        media_url TEXT,
        media_type ENUM('post', 'reel') DEFAULT 'post',
        status ENUM('queued', 'published', 'failed') DEFAULT 'queued',
        published_at DATETIME,
        error_log TEXT,
        scheduled_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "CREATE SUCCESS!";
} catch (PDOException $e) {
    echo "FATAL ERROR: " . $e->getMessage();
}
