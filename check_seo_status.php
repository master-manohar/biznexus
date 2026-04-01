<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    require_once __DIR__ . '/includes/db.php';
    $count = $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();
    $last = $pdo->query("SELECT MAX(last_generated) FROM seo_pages")->fetchColumn();
    echo "TOTAL_PAGES: $count\nLAST_GENERATED: $last\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
