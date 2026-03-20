<?php
require_once 'includes/db.php';
global $pdo;

$sql = "
CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT,
    type ENUM('win', 'question', 'offer', 'looking_for', 'general') DEFAULT 'general',
    likes INT DEFAULT 0,
    status ENUM('active', 'hidden') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY user_id (user_id)
);

CREATE TABLE IF NOT EXISTS community_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY post_id (post_id),
    KEY user_id (user_id)
);

CREATE TABLE IF NOT EXISTS community_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY post_user (post_id, user_id)
);
";

try {
    $pdo->exec($sql);
    echo "Community tables created successfully!";
} catch (Exception $e) {
    echo "Error creating community tables: " . $e->getMessage();
}
?>
