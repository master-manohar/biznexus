<?php
/**
 * sitemap.xml generator — includes all SEO pages
 * Access: https://biznexus.in/sitemap.xml
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Static pages
$static = ['/', '/find.php', '/register_business.php', '/auth/login.php'];
foreach ($static as $s) {
    echo "<url><loc>https://biznexus.in$s</loc><changefreq>weekly</changefreq><priority>0.9</priority></url>";
}

// Business profiles
$profiles = $pdo->query("SELECT id FROM users WHERE status='active' AND email NOT LIKE '%@example.com'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($profiles as $uid) {
    echo "<url><loc>https://biznexus.in/profile/view.php?id=$uid</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>";
}

// SEO pages
$pages = $pdo->query("SELECT slug, last_generated FROM seo_pages LIMIT 10000")->fetchAll(PDO::FETCH_ASSOC);
foreach ($pages as $p) {
    $parts = explode('-in-', $p['slug'], 2);
    if (count($parts) === 2) {
        $cat  = urlencode($parts[0]);
        $city = urlencode($parts[1]);
        $mod  = date('Y-m-d', strtotime($p['last_generated'] ?? 'now'));
        echo "<url><loc>https://biznexus.in/find/$cat/$city</loc><lastmod>$mod</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>";
    }
}

echo '</urlset>';
?>
