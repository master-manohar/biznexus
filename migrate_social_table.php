<?php
require_once __DIR__ . '/db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS social_posts (
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
    echo "TABLE social_posts CREATED/VERIFIED";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
