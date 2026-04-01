<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';

echo "VERIFYING CATEGORY SYSTEM...\n";

// 1. Check table count
$count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
echo "Total Categories in DB: $count\n";

// 2. Check find.php logic
$_GET['s'] = 'categories';
include __DIR__ . '/../superadmin.php'; 

echo "\n\nSUCCESS: Superadmin Category section rendered.";
