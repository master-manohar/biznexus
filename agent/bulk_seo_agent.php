<?php
/**
 * agent/bulk_seo_agent.php — BizNexus SEO Marathon Engine v3.0
 *
 * HOW IT WORKS (NO MORE TIMEOUTS):
 *  - Each HTTP call generates exactly $BATCH_SIZE pages (default 10)
 *  - After finishing, it AUTO-QUEUES the next seo_dominance task in agent_tasks
 *  - runner.php (cron every 5 min) picks it up and fires the next batch
 *  - This continues 24/7 until all 10,000 pages are done — NO BROWSER NEEDED
 *
 * Direct trigger (starts the marathon):
 *   https://biznexus.in/agent/bulk_seo_agent.php?key=BizCron2024&batch=10
 *
 * Or called via runner.php as task_type=seo_dominance (auto-triggered daily)
 */

// Prevent single-request timeout — each batch is small so this is safe
set_time_limit(120);
ini_set('memory_limit', '256M');
ignore_user_abort(true); // Keep running even if browser closes mid-batch

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';

// ─── Auth ────────────────────────────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && ($_GET['key'] ?? '') !== 'BizCron2024') {
    die(json_encode(['error' => 'Unauthorized']));
}

$taskId     = $_GET['task_id'] ?? $argv[1] ?? null;
$BATCH_SIZE = (int)($_GET['batch'] ?? $argv[2] ?? 10);
if ($BATCH_SIZE < 1)  $BATCH_SIZE = 10;
if ($BATCH_SIZE > 25) $BATCH_SIZE = 25; // Safety cap for shared hosting

// ─── Data ────────────────────────────────────────────────────────────────────
$cities = [
    'Adilabad','Bhadradri-Kothagudem','Hyderabad','Jagitial','Jangaon',
    'Jayashankar-Bhupalpally','Jogulamba-Gadwal','Kamareddy','Karimnagar',
    'Khammam','Kumuram-Bheem-Asifabad','Mahabubabad','Mahabubnagar','Mancherial',
    'Medak','Medchal-Malkajgiri','Mulugu','Nagarkurnool','Nalgonda','Narayanpet',
    'Nirmal','Nizamabad','Peddapalli','Rajanna-Sircilla','Ranga-Reddy','Sangareddy',
    'Siddipet','Suryapet','Vikarabad','Wanaparthy','Warangal','Hanamkonda',
    'Yadadri-Bhuvanagiri',
    'Alluri-Sitharama-Raju','Anakapalli','Ananthapuramu','Annamayya','Bapatla',
    'Chittoor','Konaseema','East-Godavari','Eluru','Guntur','Kakinada','Krishna',
    'Kurnool','Nandyal','NTR-District','Palnadu','Parvathipuram-Manyam','Prakasam',
    'Nellore','Sri-Sathya-Sai','Srikakulam','Tirupati','Visakhapatnam',
    'Vizianagaram','West-Godavari','YSR-Kadapa'
];

$categories = $pdo->query("SELECT name FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

// ─── Stats ───────────────────────────────────────────────────────────────────
$totalExisting = (int)$pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();
$totalPossible = count($categories) * count($cities);
$remaining     = $totalPossible - $totalExisting;

function seoLog($msg) {
    echo "[" . date('H:i:s') . "] $msg\n";
    if (ob_get_level()) { ob_flush(); flush(); }
}

if ($taskId) {
    $pdo->prepare("UPDATE agent_tasks SET status='running', updated_at=NOW() WHERE id=?")->execute([$taskId]);
}

seoLog("SEO Marathon Engine — Batch of $BATCH_SIZE starting.");
seoLog("Total possible pages: $totalPossible | Already live: $totalExisting | Remaining: $remaining");

if ($remaining <= 0) {
    seoLog("🎉 ALL $totalPossible pages already generated! Nothing to do.");
    if ($taskId) {
        $pdo->prepare("UPDATE agent_tasks SET status='completed', result='All SEO pages complete!', updated_at=NOW() WHERE id=?")->execute([$taskId]);
    }
    exit;
}

// ─── Generate ────────────────────────────────────────────────────────────────
$generated = 0;
$skipped   = 0;
$failed    = 0;

// Shuffle categories so each batch covers different areas
shuffle($categories);

foreach ($categories as $cat) {
    foreach ($cities as $city) {
        if ($generated >= $BATCH_SIZE) break 2;

        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower("$cat-in-$city")), '-');

        // Skip if already exists
        $chk = $pdo->prepare("SELECT id FROM seo_pages WHERE slug=?");
        $chk->execute([$slug]);
        if ($chk->fetch()) { $skipped++; continue; }

        seoLog("Generating: $cat in $city...");

        $prompt = "Write a 500-word high-authority local business guide for '$cat' in '$city', Telangana/Andhra Pradesh, India.
Include: market overview, 3 local trends, why hire local, LSI keywords, specific local landmarks.
End with a meta description (150 chars max) and 3 FAQs with detailed answers.
Return ONLY valid JSON: {\"h1\":\"...\",\"overview\":\"...\",\"meta_desc\":\"...\",\"faqs\":[{\"q\":\"...\",\"a\":\"...\"}]}";

        usleep(1200000); // 1.2s — safe rate limit for Anthropic + Gemini fallback

        $result = runBizAI([['role' => 'user', 'content' => $prompt]],
            "You are a senior SEO copywriter. Output RAW JSON ONLY — no markdown, no backticks.");

        if (!isset($result['text'])) {
            $errMsg = $result['error'] ?? 'Unknown error';
            seoLog("❌ Both AI APIs failed for $slug — $errMsg");
            $failed++;
            continue;
        }

        $json = preg_replace('/^```json\s*|\s*```$/i', '', trim($result['text']));
        $data = json_decode($json, true);

        if (!$data || !isset($data['overview'])) {
            seoLog("⚠️ Invalid JSON for $slug");
            $failed++;
            continue;
        }

        try {
            $pdo->prepare("INSERT INTO seo_pages (category, city, slug, meta_title, meta_desc, faq_json, ai_content, last_generated) VALUES (?,?,?,?,?,?,?,NOW())")
                ->execute([
                    $cat, $city, $slug,
                    $data['h1']       ?? "$cat in $city | BizNexus",
                    $data['meta_desc'] ?? "",
                    json_encode($data['faqs'] ?? []),
                    $data['overview'],
                ]);
            seoLog("✅ Done: $slug");
            $generated++;
        } catch (Exception $e) {
            seoLog("❌ DB Error: " . $e->getMessage());
            $failed++;
        }
    }
}

// ─── Sitemap update ──────────────────────────────────────────────────────────
if ($generated > 0) {
    seoLog("Updating sitemap...");
    $_GET['key'] = 'BizCron2024';
    @include __DIR__ . '/../sitemap.php';
    seoLog("✅ Sitemap updated.");
}

$newTotal   = $totalExisting + $generated;
$newRemaining = $totalPossible - $newTotal;
seoLog("Batch done. Generated: $generated | Skipped: $skipped | Failed: $failed | Total live: $newTotal / $totalPossible");

// ─── Self-chain: queue next batch automatically ───────────────────────────────
if ($newRemaining > 0) {
    // Check if another seo_dominance task is already pending/running
    $alreadyQueued = $pdo->query(
        "SELECT COUNT(*) FROM agent_tasks WHERE task_type='seo_dominance' AND status IN ('pending','running')"
    )->fetchColumn();

    if (!$alreadyQueued) {
        $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status, created_at, updated_at) VALUES ('seo_dominance', ?, 'pending', NOW(), NOW())")
            ->execute(["SEO Marathon auto-chain: generate next $BATCH_SIZE pages ($newRemaining remaining of $totalPossible total)"]);
        seoLog("🔄 Next batch queued in agent_tasks — runner will execute in ~5 minutes.");
    } else {
        seoLog("⏳ Next batch already queued — runner will pick it up soon.");
    }
} else {
    seoLog("🎉 ALL $totalPossible SEO pages are now LIVE!");
}

// ─── Mark task complete ──────────────────────────────────────────────────────
if ($taskId) {
    $pdo->prepare("UPDATE agent_tasks SET status='completed', result=?, updated_at=NOW() WHERE id=?")
        ->execute(["Generated $generated pages. Total live: $newTotal / $totalPossible. $newRemaining remaining.", $taskId]);
}
?>
