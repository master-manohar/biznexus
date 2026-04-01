<?php
require_once __DIR__ . '/includes/db.php';
$count = $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();
$recent = $pdo->query("SELECT id, category, city, created_at FROM seo_pages ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "Total SEO Pages: $count\n\nRecent Pages:\n";
echo json_encode($recent, JSON_PRETTY_PRINT);
