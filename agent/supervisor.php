<?php
session_start();
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes_functions.php";

/**
 * Supervisor Agent: Task Orchestrator
 */

if (php_sapi_name() !== 'cli') {
    $hasKey = (isset($_GET['key']) && $_GET['key'] === 'BizCron2024');
    
    // Emergency Action: Deploy or Seed
    if ($hasKey && isset($_GET['action'])) {
        if ($_GET['action'] === 'deploy') {
            // ... (existing deploy logic)
        }
        if ($_GET['action'] === 'daily') {
            global $pdo;
            $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status) VALUES ('pr_outreach', 'Send daily batch of 10 PR emails', 'pending')")->execute();
            $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status) VALUES ('marketing_campaign', 'Run daily category-based re-engagement', 'pending')")->execute();
            die("Daily Tasks Spawned. Runner will process them shortly.");
        }
        if ($_GET['action'] === 'auto-seo') {
            // Check if any SEO task is pending/running
            $count = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE task_type='seo_dominance' AND status IN ('pending', 'running')")->fetchColumn();
            if ($count == 0) {
                $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status) VALUES ('seo_dominance', 'Generate 25 AIO-optimized local landing pages', 'pending')")->execute();
                die("SEO Batch Task Injected.");
            } else {
                die("SEO Engine is already processing a batch. Skip injection.");
            }
        }
        if ($_GET['action'] === 'auto-outreach') {
            global $pdo;
            $count = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE task_type='outreach_marketing' AND status IN ('pending', 'running')")->fetchColumn();
            if ($count == 0) {
                $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status) VALUES ('outreach_marketing', 'Process batch of 20 pending marketing prospects', 'pending')")->execute();
                die("Marketing Outreach Task Injected.");
            } else {
                die("Outreach Agent is already processing a batch.");
            }
        }
    }

    if (!isset($_SESSION['user_id']) && !$hasKey) {
        die("Access Denied: No Session or Invalid Key.");
    }
    // ... (rest of session check)
}

function spawnTasks($goal) {
    global $pdo;

    // In a real implementation, we would call the Gemini API here to get sub-tasks.
    // For now, we simulate the 'decomposition' of a goal into tasks.
    
    $tasks = [];
    if (stripos($goal, 'every category') !== false || stripos($goal, 'all categories') !== false) {
        // Bulk Seeding Logic
        $tasks[] = ['type' => 'system', 'goal' => "Bulk Profile Seeding: Triggering ProfileSeeder for 105 categories."];
        
        // Trigger the seeder directly if possible
        include_once __DIR__ . '/profile_seeder.php'; 
        
    } elseif (stripos($goal, 'profile') !== false) {
        $tasks[] = ['type' => 'profile', 'goal' => "Generate SEO-friendly bios for photographers."];
        $tasks[] = ['type' => 'profile', 'goal' => "Generate SEO-friendly bios for educators."];
    } elseif (stripos($goal, 'find') !== false || stripos($goal, 'search') !== false || stripos($goal, 'discover') !== false) {
        $tasks[] = ['type' => 'prospect_discovery', 'goal' => $goal];
    } elseif (stripos($goal, 'site') !== false || stripos($goal, 'website') !== false) {
        $tasks[] = ['type' => 'design', 'goal' => "Create landing page template for SME members."];
    } else {
        $tasks[] = ['type' => 'general', 'goal' => $goal];
    }

    foreach ($tasks as $t) {
        $stmt = $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$t['type'], $t['goal']]);
    }

    return count($tasks);
}

// Check for incoming requests only if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $goal = $argv[1] ?? $_GET['goal'] ?? null;

    if ($goal) {
        if (php_sapi_name() !== 'cli') {
            echo "<h2>🚀 Super Agent: Goal Received</h2>";
            echo "<p>Goal: <strong>" . htmlspecialchars($goal) . "</strong></p>";
        }
        
        $count = spawnTasks($goal);
        
        if (php_sapi_name() !== 'cli') {
            echo "<p>✅ Status: <strong>Spawned $count sub-tasks.</strong></p>";
            echo "<p>The Agent Runner will now process these in the background.</p>";
            echo "<hr><a href='../superadmin.php?s=agents'>View Progress in Superadmin</a>";
        } else {
            echo "Spawned $count sub-tasks for goal: $goal\n";
        }
    } else {
        if (php_sapi_name() !== 'cli') {
            echo "<h3>Usage: ?goal=Your Goal Description</h3>";
        }
    }
}
