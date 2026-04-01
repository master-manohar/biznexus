<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE status='active' ORDER BY id ASC LIMIT 20");
echo "=== Sample Users ===\n";
while($row = $stmt->fetch()){
    echo "ID: " . $row['id'] . " | " . $row['name'] . " | " . $row['email'] . "\n";
}
?>
