<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$stmt = $pdo->query("SELECT COUNT(DISTINCT category) as cat_count, COUNT(*) as total_pages FROM seo_pages");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Distinct Categories Used: {$row['cat_count']}\n";
echo "Total SEO Pages Generated: {$row['total_pages']}\n";

$cat_list = $pdo->query("SELECT DISTINCT category FROM seo_pages LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
echo "Categories List: " . implode(", ", $cat_list) . "\n";
