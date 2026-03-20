<?php
require_once 'includes/db.php';
global $pdo;
echo "--- Schema Check ---\n";
$tables = ['meetings', 'community_posts', 'community_comments', 'community_likes'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        echo "Table: $t\n";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Table $t error or missing: " . $e->getMessage() . "\n";
    }
}
?>
