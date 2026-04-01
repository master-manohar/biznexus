<?php
/**
 * agent/prospect_discovery_agent.php
 * Purpose: Automates "Lead Hunting" by targeting specific industries.
 * Updated: Sector rotation, SMB-only focus, @example.com blocked.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';

$taskId = $_GET['task_id'] ?? $argv[1] ?? null;

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) {
    die("Unauthorized.");
}

$agent_name = "Prospect Hunter";

// Priority target sectors (low & medium businesses only)
$TARGET_SECTORS = [
    ['sector' => 'Event Management', 'keywords' => 'event planners, wedding planners, corporate event organizers'],
    ['sector' => 'Real Estate', 'keywords' => 'small real estate agents, independent property dealers, local housing brokers'],
    ['sector' => 'Web Development', 'keywords' => 'freelance web developers, small IT companies, local software development firms'],
    ['sector' => 'Organic Food', 'keywords' => 'organic sellers, natural food stores, farm to table sellers, organic grocery'],
    ['sector' => 'Jute Products', 'keywords' => 'jute bag manufacturers, jute handicrafts, eco-friendly bag sellers'],
    ['sector' => 'Jewellery', 'keywords' => 'gold silver jewellery shops, costume jewellery, local jewellers, handmade jewellery'],
    ['sector' => 'Organic Beauty', 'keywords' => 'organic face cream makers, natural skincare brands, herbal cosmetics'],
    ['sector' => 'Business Networking', 'keywords' => 'local business associations, chamber of commerce members, SME networking groups'],
];

function logAgent($pdo, $taskId, $name, $action, $detail) {
    if (!$taskId) return;
    $stmt = $pdo->prepare("INSERT INTO agent_logs (task_id, agent_name, action, detail) VALUES (?, ?, ?, ?)");
    $stmt->execute([$taskId, $name, $action, $detail]);
}

try {
    if (!$taskId) die("Task ID required.");

    // Fetch the goal from task
    $stmt = $pdo->prepare("SELECT goal FROM agent_tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $goal = $stmt->fetchColumn();

    $pdo->prepare("UPDATE agent_tasks SET status = 'running', updated_at = NOW() WHERE id = ?")->execute([$taskId]);
    logAgent($pdo, $taskId, $agent_name, "start", "Hunting for leads: $goal");

    // Pick a sector to target (rotate based on task ID)
    $sectorIndex = ($taskId - 1) % count($TARGET_SECTORS);
    $target = $TARGET_SECTORS[$sectorIndex];
    $sector = $target['sector'];
    $keywords = $target['keywords'];

    logAgent($pdo, $taskId, $agent_name, "sector", "Targeting: $sector ($keywords)");

    // Use AI to generate realistic SMB prospects in this sector
    $prompt = "You are a lead generation assistant for a B2B platform in India targeting SMALL and MEDIUM businesses only (NOT enterprise or franchises).

Goal: '$goal'
Sector: '$sector'
Keywords: '$keywords'

Generate exactly 10 realistic small/medium business leads in Hyderabad or surrounding Telangana cities.
Each lead must be a genuine-sounding local business (not generic).

Return ONLY valid JSON array:
[{\"name\": \"Owner Name\", \"business_name\": \"Business Name\", \"category\": \"$sector\", \"city\": \"City\", \"email\": \"contact@domain.com\", \"phone\": \"+91XXXXXXXXXX\"}]

RULES:
- Do NOT use @example.com emails
- Emails must look real (match the business name or plausible domain)
- Business size: small or medium only
- Cities: prefer Hyderabad, Secunderabad, Warangal, Nizamabad, Karimnagar";

    $jsonResponse = runBizAIString($prompt);
    // Strip markdown code blocks if present
    $jsonResponse = preg_replace('/^```json\s*|\s*```$/i', '', trim($jsonResponse));
    $leads = json_decode($jsonResponse, true);

    if (!$leads || !is_array($leads)) {
        logAgent($pdo, $taskId, $agent_name, "error", "AI failed to generate lead targets. Raw: " . substr($jsonResponse, 0, 100));
        $pdo->prepare("UPDATE agent_tasks SET status = 'failed', result = 'AI JSON Error', updated_at = NOW() WHERE id = ?")->execute([$taskId]);
        exit;
    }

    $added = 0;
    foreach ($leads as $l) {
        $name    = $l['name'] ?? 'Contact';
        $bizName = $l['business_name'] ?? 'Business';
        $cat     = $l['category'] ?? $sector;
        $city    = $l['city'] ?? 'Hyderabad';
        $email   = strtolower(trim($l['email'] ?? ''));

        // Skip @example.com and empty emails
        if (empty($email) || strpos($email, '@example.com') !== false || strpos($email, 'example') !== false) {
            continue;
        }

        // Check for duplicates
        $check = $pdo->prepare("SELECT COUNT(*) FROM marketing_prospects WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() == 0) {
            $ins = $pdo->prepare("INSERT INTO marketing_prospects (name, business_name, email, category, city, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $ins->execute([$name, $bizName, $email, $cat, $city]);
            $added++;
            logAgent($pdo, $taskId, $agent_name, "found", "Discovered: $bizName in $city");
        }
    }

    $pdo->prepare("UPDATE agent_tasks SET status = 'completed', result = 'Discovered $added new leads in $sector', updated_at = NOW() WHERE id = ?")->execute([$taskId]);
    logAgent($pdo, $taskId, $agent_name, "finish", "Mission Complete. Found $added valid prospects in $sector.");

    echo "SUCCESS: $added leads discovered in $sector.";

} catch (Exception $e) {
    if ($taskId) {
        $pdo->prepare("UPDATE agent_tasks SET status = 'failed', result = ?, updated_at = NOW() WHERE id = ?")->execute([$e->getMessage(), $taskId]);
        logAgent($pdo, $taskId, $agent_name, "fatal", $e->getMessage());
    }
    echo "ERROR: " . $e->getMessage();
}
?>
