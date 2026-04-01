<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

echo "Checking agent_logs table...\n";
try {
    // 1. Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS agent_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT,
        agent_name VARCHAR(100),
        log_entry TEXT,
        is_error TINYINT(1) DEFAULT 0,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 2. Check for agent_name column explicitly (standard MySQL compatible)
    $res = $pdo->query("SHOW COLUMNS FROM agent_logs LIKE 'agent_name'");
    if (!$res->fetch()) {
        $pdo->exec("ALTER TABLE agent_logs ADD COLUMN agent_name VARCHAR(100) AFTER task_id");
        echo "Column 'agent_name' added to agent_logs.\n";
    } else {
        echo "Column 'agent_name' already exists in agent_logs.\n";
    }
    
    // 3. Also check for user table columns needed for seeding
    echo "Checking users table columns...\n";
    $cols = ['business_name' => 'VARCHAR(150)', 'bio' => 'TEXT', 'category' => 'VARCHAR(100)', 'city' => 'VARCHAR(100)'];
    foreach ($cols as $col => $type) {
        $res = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if (!$res->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $type");
            echo "Column '$col' added to users.\n";
        } else {
            echo "Column '$col' already exists in users.\n";
        }
    }
    
    echo "Schema fix complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
