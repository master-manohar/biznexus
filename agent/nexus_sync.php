<?php
// /agent/nexus_sync.php
// Centralized Synchronization Hub for Gemini & Claude
session_start();
require_once __DIR__ . '/../includes/db.php';

$auth_key = "BizCron2024";
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $auth_key) {
    die("Unauthorized Access.");
}

$status_file = __DIR__ . '/nexus_status.json';

// Initialize status if not exists
if (!file_exists($status_file)) {
    $initial = [
        "last_sync" => date('Y-m-d H:i:s'),
        "gemini_zone" => [
            "status" => "Fixing QA Bugs",
            "working_on" => ["find.php (AI Matching)", "Onboarding Redirect", "Marketplace Fix"],
            "last_action" => "Resolved Marketplace 500 error"
        ],
        "claude_zone" => [
            "status" => "Ready to begin",
            "working_on" => [],
            "last_action" => "Provided QA Report"
        ],
        "system_health" => "Optimizing",
        "collaboration_notes" => "Gemini is currently squashing the Critical Bug #1 and #2 from Claude's report."
    ];
    file_put_contents($status_file, json_encode($initial, JSON_PRETTY_PRINT));
}

$status = json_decode(file_get_contents($status_file), true);

// Handle status updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent = $_POST['agent'] ?? ''; // 'gemini' or 'claude'
    if ($agent === 'gemini') {
        $status['gemini_zone']['working_on'] = explode(',', $_POST['tasks'] ?? '');
        $status['gemini_zone']['status'] = $_POST['status'] ?? $status['gemini_zone']['status'];
    } elseif ($agent === 'claude') {
        $status['claude_zone']['working_on'] = explode(',', $_POST['tasks'] ?? '');
        $status['claude_zone']['status'] = $_POST['status'] ?? $status['claude_zone']['status'];
    }
    $status['last_sync'] = date('Y-m-d H:i:s');
    file_put_contents($status_file, json_encode($status, JSON_PRETTY_PRINT));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nexus Sync Hub</title>
    <style>
        body { background: #06060a; color: #e0e0f0; font-family: sans-serif; padding: 40px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #0e0e16; border: 1px solid #1e1e2e; padding: 20px; border-radius: 12px; }
        h2 { color: #FFD700; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .status { font-weight: bold; color: #00ff88; }
        .task-list { margin-top: 15px; }
        .task-item { background: #1a1a24; padding: 8px; margin: 5px 0; border-radius: 6px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <h1>🤝 Nexus AI Synchronization Hub</h1>
    <p>Last Sync: <?= $status['last_sync'] ?></p>

    <div class="grid">
        <div class="card">
            <h2>♊ Gemini Zone</h2>
            <p>Status: <span class="status"><?= $status['gemini_zone']['status'] ?></span></p>
            <div class="task-list">
                <?php foreach($status['gemini_zone']['working_on'] as $task): ?>
                    <div class="task-item">✅ <?= htmlspecialchars(trim($task)) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>🎭 Claude Zone</h2>
            <p>Status: <span class="status"><?= $status['claude_zone']['status'] ?></span></p>
            <div class="task-list">
                <?php foreach($status['claude_zone']['working_on'] as $task): ?>
                    <div class="task-item">⌛ <?= htmlspecialchars(trim($task)) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>📝 Collaboration Notes</h2>
        <p><?= htmlspecialchars($status['collaboration_notes']) ?></p>
    </div>
</body>
</html>
