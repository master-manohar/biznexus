<?php
require_once __DIR__ . '/../includes/db.php';
$count = $pdo->query("SELECT COUNT(*) FROM groups")->fetchColumn();
echo "Total Groups: $count\n";
if ($count > 0) {
    echo "First 5 Groups:\n";
    print_r($pdo->query("SELECT * FROM groups LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));
}
?>
