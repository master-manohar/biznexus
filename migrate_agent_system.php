<?php
require_once __DIR__ . "/includes/db.php";

$sql = "
CREATE TABLE IF NOT EXISTS agent_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_type VARCHAR(50) NOT NULL,
    goal TEXT NOT NULL,
    status ENUM('pending', 'running', 'done', 'failed', 'cancelled') DEFAULT 'pending',
    result TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    agent_name VARCHAR(100) NOT NULL,
    log_entry TEXT NOT NULL,
    is_error BOOLEAN DEFAULT FALSE,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES agent_tasks(id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    echo "Agent tables created successfully!";
} catch (PDOException $e) {
    echo "Error creating agent tables: " . $e->getMessage();
}
