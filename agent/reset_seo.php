<?php
require_once __DIR__ . '/../includes/db.php';
$pdo->exec("TRUNCATE TABLE seo_pages");
echo "✅ SEO Pages table cleared.\n";
