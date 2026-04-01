<?php
// /bizfeed/migrate_bizfeed.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bizfeed_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            image_path VARCHAR(255) DEFAULT NULL,
            post_type ENUM('update', 'need', 'offer') DEFAULT 'update',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'hidden', 'deleted') DEFAULT 'active'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS bizfeed_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (post_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS bizfeed_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'deleted') DEFAULT 'active'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "✅ BizFeed tables created successfully!\n";
} catch (PDOException $e) {
    die("❌ Error creating BizFeed tables: " . $e->getMessage() . "\n");
}
?>
