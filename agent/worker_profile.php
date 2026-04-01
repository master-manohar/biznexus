<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/worker_base.php";

/**
 * Profile Enrichment & Seeding Agent
 */

if (php_sapi_name() !== 'cli') {
    $hasKey = (isset($_GET['key']) && $_GET['key'] === 'BizCron2024');
    if (!isset($_SESSION['user_id']) && !$hasKey) {
        die("Access Denied: No Session or Invalid Key.");
    }
}

try {
    $taskId = (int)($_GET['task_id'] ?? $argv[1] ?? 0);
    if (!$taskId) die("Task ID required.");

    // 1. Get task details
    $st = $pdo->prepare("SELECT task_type, goal FROM agent_tasks WHERE id=?");
    $st->execute([$taskId]);
    $taskData = $st->fetch();
    $taskType = $taskData['task_type'] ?? 'profile';
    $goal = $taskData['goal'] ?? '';

    $pdo->prepare("UPDATE agent_tasks SET status='running' WHERE id=?")->execute([$taskId]);

    $worker = new AgentWorker($pdo, $taskId, "ProfileAgent");
    $worker->log("Starting profile " . ($taskType == 'profile:seed' ? "seeding" : "enrichment") . " task...");
    $worker->log("Goal: " . $goal);

    if ($taskType == 'profile:seed') {
        // --- BULK SEEDING LOGIC ---
        // Extract category from goal
        preg_match('/category: (.*?) \(Profile/', $goal, $matches);
        $category = $matches[1] ?? 'General Business';
        
        $worker->log("Generating realistic profile for category: $category");

        // Realistic generators
        $prefixes = ["Global", "Nexus", "Elite", "Prime", "Swift", "Apex", "Horizon", "Vision", "Blue", "Gold", "Mega", "Smart"];
        $suffixes = ["Solutions", "Systems", "Services", "Hub", "Lab", "Works", "Group", "Partners", "Co", "Consulting", "Enterprises"];
        $cities = ["Mumbai", "Delhi", "Bangalore", "Hyderabad", "Ahmedabad", "Chennai", "Kolkata", "Pune", "Jaipur", "Surat"];
        
        $bizName = $prefixes[array_rand($prefixes)] . " " . $category . " " . $suffixes[array_rand($suffixes)];
        $city = $cities[array_rand($cities)];
        $email = strtolower(str_replace([' ', '&', '(', ')'], ['.', '', '', ''], $bizName)) . "@example.com";
        $phone = "+91 " . rand(70000, 99999) . " " . rand(10000, 99999);
        
        $bio = "🌟 Welcome to $bizName! We are a leading provider of $category services in $city. Dedicated to excellence, innovation, and client growth. We pride ourselves on delivering premium value to our partners. #BizNexus #$category #BusinessExcellence #$city";
        
        // Ensure columns exist (safety for prototype)
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS business_name VARCHAR(150), ADD COLUMN IF NOT EXISTS bio TEXT, ADD COLUMN IF NOT EXISTS category VARCHAR(100), ADD COLUMN IF NOT EXISTS city VARCHAR(100)");
        } catch(Exception $e) {}

        // Insert new seeded user
        $stmt = $pdo->prepare("INSERT INTO users (name, business_name, email, phone, category, city, bio, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'member', 'active', NOW())");
        $stmt->execute([$bizName . " Rep", $bizName, $email, $phone, $category, $city, $bio]);
        $newUserId = $pdo->lastInsertId();

        $worker->log("Seeded profile created: $bizName (User ID: $newUserId) in $city.");
        $worker->setStatus('done', "Successfully seeded profile: $bizName at $city.");

    } else {
        // --- EXISTING ENRICHMENT LOGIC ---
        $worker->log("Searching for existing profiles to enrich...");
        $users = $pdo->query("SELECT id, name FROM users WHERE role='member' AND (bio IS NULL OR bio = '') LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $u) {
            $worker->log("Enriching profile for: " . $u['name']);
            usleep(200000); 
            $bio = "Expert professional in their field, dedicated to growing their network on BizNexus.";
            $pdo->prepare("UPDATE users SET bio=? WHERE id=?")->execute([$bio, $u['id']]);
            $worker->log("Generated bio for user ID {$u['id']}.");
        }

        $worker->log("All profiles enriched successfully.", false);
        $worker->setStatus('done', "Enriched " . count($users) . " profiles.");
    }

} catch (Exception $e) {
    if (isset($worker)) $worker->log("Error: " . $e->getMessage(), true);
    if (isset($pdo) && isset($taskId)) $pdo->prepare("UPDATE agent_tasks SET status='failed', result=? WHERE id=?")->execute([$e->getMessage(), $taskId]);
    echo "Error: " . $e->getMessage() . "\n";
}
