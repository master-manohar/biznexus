<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/includes/db.php';

try {
    $count = $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();
    echo "Total SEO Pages: $count\n\n";
    
    $recent = $pdo->query("SELECT id, category, city, created_at FROM seo_pages ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "Recent 10 Pages:\n" . json_encode($recent, JSON_PRETTY_PRINT) . "\n\n";
    
    $last_task = $pdo->query("SELECT id, status, updated_at FROM agent_tasks WHERE task_type = 'seo_dominance' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    echo "Last SEO Task: " . json_encode($last_task, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage();
}
