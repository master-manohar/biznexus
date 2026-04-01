<?php
require_once __DIR__ . '/db.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'social_posts'");
if ($stmt->fetch()) {
    $stmt = $pdo->query("DESCRIBE social_posts");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
} else {
    echo "TABLE 'social_posts' MISSING";
}
