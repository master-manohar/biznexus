<?php
/**
 * agent/agent_scheduler.php
 * BizNexus Auto-Task Injector.
 * Run this via Hostinger Cron Job ONCE DAILY:
 *   php /home/u175452495/domains/biznexus.in/public_html/agent/agent_scheduler.php
 * OR via URL:
 *   https://biznexus.in/agent/agent_scheduler.php?key=BizCron2024
 *
 * Injects recurring tasks that drive all 4 agents:
 *  1. profile_nudge      — emails members to complete their profiles
 *  2. followup           — re-engages inactive members
 *  3. outreach_marketing — sends intro emails to scraped prospects
 *  4. social_posting     — posts Instagram reels/photos
 *  5. seo_dominance      — generates SEO landing pages
 */
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024')) {
    die(json_encode(['status' => 'error', 'msg' => 'Unauthorized']));
}

$injected = [];
$skipped  = [];
$now      = date('Y-m-d H:i:s');

/**
 * Inject a task only if one of the same type is NOT already pending/running.
 */
function injectTask($pdo, string $type, string $goal, string $interval = 'NOW()'): bool {
    $existing = $pdo->prepare("SELECT id FROM agent_tasks WHERE task_type = ? AND status IN ('pending','running') LIMIT 1");
    $existing->execute([$type]);
    if ($existing->fetch()) return false; // already queued

    $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status, created_at) VALUES (?, ?, 'pending', $interval)")
        ->execute([$type, $goal]);
    return true;
}

// 1. Profile Nudge — daily
if (injectTask($pdo, 'profile_nudge', 'Email members with incomplete profiles to fill missing details (business name, category, city, phone, description)')) {
    $injected[] = 'profile_nudge';
} else {
    $skipped[] = 'profile_nudge (already queued)';
}

// 2. Follow-up — re-engage inactive members — daily
if (injectTask($pdo, 'followup', 'Re-engage members who have not logged in for 7+ days with lead count email')) {
    $injected[] = 'followup';
} else {
    $skipped[] = 'followup (already queued)';
}

// 3. Outreach Marketing — send intro emails to new prospects — daily
if (injectTask($pdo, 'outreach_marketing', 'Send personalized BizNexus intro emails to pending marketing_prospects')) {
    $injected[] = 'outreach_marketing';
} else {
    $skipped[] = 'outreach_marketing (already queued)';
}

// 4. Instagram/Social Posting — ⏸ PAUSED (uncomment to re-enable)
// $lastSocial = $pdo->query("SELECT MAX(published_at) FROM social_posts WHERE status='published'")->fetchColumn();
// $needsSocial = !$lastSocial || strtotime($lastSocial) < (time() - 4 * 3600);
// if ($needsSocial) {
//     if (injectTask($pdo, 'social_posting', 'Auto Instagram/LinkedIn branded post — next 4-hour cycle')) {
//         $injected[] = 'social_posting';
//     } else {
//         $skipped[] = 'social_posting (already queued)';
//     }
// } else {
//     $skipped[] = 'social_posting (posted within last 4h)';
// }
$skipped[] = 'social_posting (PAUSED — re-enable in agent_scheduler.php)';

// 5. SEO Dominance — daily, generates 50 new landing pages per run
if (injectTask($pdo, 'seo_dominance', 'Generate 50 new AI-optimised local SEO pages for Telangana & AP districts')) {
    $injected[] = 'seo_dominance';
} else {
    $skipped[] = 'seo_dominance (already queued)';
}

// 6. Welcome Drip — daily (handles Day1/Day3/Day7 sequences automatically)
if (injectTask($pdo, 'welcome_drip', '3-step onboarding drip sequence for new members (Day 1 welcome, Day 3 leads, Day 7 upgrade)')) {
    $injected[] = 'welcome_drip';
} else {
    $skipped[] = 'welcome_drip (already queued)';
}

// 7. Referral Nudge — every 3 days (agent itself controls per-user frequency)
if (injectTask($pdo, 'referral_nudge', 'Remind active members to share their referral link and earn VooCoins')) {
    $injected[] = 'referral_nudge';
} else {
    $skipped[] = 'referral_nudge (already queued)';
}

// 8. Review Collector — weekly (agent itself ensures one-time per 30-day member)
if (injectTask($pdo, 'review_collector', 'Ask 30-day members for a testimonial review in exchange for +100 VooCoins')) {
    $injected[] = 'review_collector';
} else {
    $skipped[] = 'review_collector (already queued)';
}

// 9. Prospect Discovery — daily (AI-generated leads)
if (injectTask($pdo, 'prospect_discovery', 'AI lead scout: Discover 10 new SMB prospects in rotating sectors (Event, Real Estate, Jewellery etc.)')) {
    $injected[] = 'prospect_discovery';
} else {
    $skipped[] = 'prospect_discovery (already queued)';
}

// 10. Google Real Scraper — daily (REAL businesses from Google Search + website email mining)
if (injectTask($pdo, 'google_scrape', 'Harvest real Indian business emails using Google Search grounding + website scraping')) {
    $injected[] = 'google_scrape';
} else {
    $skipped[] = 'google_scrape (already queued)';
}


$report = [
    'status'   => 'ok',
    'time'     => $now,
    'injected' => $injected,
    'skipped'  => $skipped,
    'message'  => count($injected) . ' task(s) queued. Run /agent/runner.php?key=BizCron2024 to execute.',
];

echo json_encode($report, JSON_PRETTY_PRINT);
?>
