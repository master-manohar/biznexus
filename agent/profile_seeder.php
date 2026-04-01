<?php
require_once __DIR__ . '/../includes/db.php';

/**
 * Profile Seeder Agent
 * Purpose: Iterates through all categories and spawns 3 profile generation tasks for each.
 */

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) {
    die("Access Denied.");
}

echo "Starting Profile Seeder...\n";

// 1. Get all categories
$stmt = $pdo->query("SELECT name FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($categories)) {
    die("No categories found in database. Please run categories_setup.php first.\n");
}

$count = 0;
foreach ($categories as $cat) {
    echo "Processing Category: $cat\n";
    
    // Create 3 tasks for each category
    for ($i = 1; $i <= 3; $i++) {
        $goal = "Create a realistic business profile for category: $cat (Profile #$i). Ensure all details (name, bio, contact, location) are filled with realistic placeholder data for social media visibility.";
        
        $stmt = $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status) VALUES ('profile:seed', ?, 'pending')");
        $stmt->execute([$goal]);
        $count++;
    }
}

echo "Successfully spawned $count profile generation tasks across " . count($categories) . " categories.\n";
