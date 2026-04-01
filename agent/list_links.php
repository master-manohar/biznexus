<?php
require_once __DIR__ . '/../includes/db.php';
$stmt = $pdo->query("SELECT DISTINCT category FROM business_profiles");
$cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<h3>Available Categories</h3><pre>";
print_r($cats);
echo "</pre>";

$stmt = $pdo->query("SELECT business_name, category, city FROM business_profiles LIMIT 10");
$seeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Sample Profiles</h3><pre>";
print_r($seeds);
echo "</pre>";

$stmt = $pdo->query("SELECT slug, category, city FROM seo_pages ORDER BY id DESC LIMIT 5");
$slugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Recent SEO Pages</h3><ul>";
foreach($slugs as $s) {
    echo "<li><a href='https://biznexus.in/services/{$s['slug']}' target='_blank'>{$s['slug']}</a> (Cat: {$s['category']} | City: {$s['city']})</li>";
}
echo "</ul>";
?>
