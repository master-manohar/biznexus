<?php
require_once __DIR__ . '/../includes/db.php';
$pdo->prepare("DELETE FROM seo_pages WHERE slug = 'web-development-in-medak'")->execute();
echo "✅ Medak SEO page deleted for re-generation.\n";
