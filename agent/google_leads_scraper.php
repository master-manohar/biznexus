<?php
/**
 * agent/google_leads_scraper.php
 * BizNexus Real Email Harvester v1.0
 *
 * Flow:
 *  1. Uses Gemini (Google Search grounding) to find REAL businesses in target sectors
 *  2. Extracts website URLs from the results
 *  3. Fetches each website and regex-mines for email addresses
 *  4. Stores verified leads in marketing_prospects table
 *  5. Skips duplicates and @example.com addresses
 *
 * Run via:
 *   https://biznexus.in/agent/google_leads_scraper.php?key=BizCron2024
 *   Or triggered automatically as task_type = 'google_scrape'
 */

set_time_limit(180);
ini_set('memory_limit', '256M');
ignore_user_abort(true);

require_once __DIR__ . '/../includes/db.php';

if (php_sapi_name() !== 'cli' && ($_GET['key'] ?? '') !== 'BizCron2024') {
    die(json_encode(['error' => 'Unauthorized']));
}

$taskId = $_GET['task_id'] ?? $argv[1] ?? null;
if ($taskId) {
    $pdo->prepare("UPDATE agent_tasks SET status='running', updated_at=NOW() WHERE id=?")->execute([$taskId]);
}

// ── Config ────────────────────────────────────────────────────────────────────
$secrets   = require __DIR__ . '/../includes/secrets.php';
$GEMINI_KEY = $secrets['gemini_api_key'] ?? '';

$MAX_LEADS_PER_RUN = 30; // Leads to find per run

// Target sectors × cities — rotates each run
$SECTORS = [
    'Event Management Companies',
    'Wedding Planners',
    'Real Estate Agents',
    'Digital Marketing Agency',
    'Web Development Company',
    'Interior Designers',
    'Chartered Accountants',
    'Catering Services',
    'Photographers',
    'Jewellery Shops',
    'Organic Food Sellers',
    'Fitness Centers',
    'Hospital and Clinics',
    'Travel Agents',
    'Logistics and Transport',
];

$CITIES = [
    'Hyderabad', 'Secunderabad', 'Warangal', 'Karimnagar',
    'Nizamabad', 'Khammam', 'Nalgonda', 'Medak',
    'Vijayawada', 'Visakhapatnam', 'Guntur', 'Tirupati',
];

// Pick one sector + city for this run (rotate by hour)
$hour    = (int)date('G');
$sector  = $SECTORS[$hour % count($SECTORS)];
$city    = $CITIES[$hour % count($CITIES)];

function scrapeLog($msg) {
    echo "[" . date('H:i:s') . "] $msg\n";
    if (ob_get_level()) { ob_flush(); flush(); }
}

scrapeLog("🔍 Google Lead Scraper starting — Sector: $sector | City: $city");

// ── Step 1: Use Gemini with Google Search to find real businesses ─────────────
function geminiSearchBusinesses(string $sector, string $city, string $apiKey): array {
    $prompt = "Find 15 real small and medium businesses in the '$sector' sector in $city, India.
For each business provide:
- Business name
- Website URL (must be real, not Google Maps link)
- Phone number (Indian format)
- Brief description

Return ONLY a JSON array:
[{\"name\":\"...\",\"website\":\"...\",\"phone\":\"...\",\"description\":\"...\",\"city\":\"$city\",\"category\":\"$sector\"}]

Rules:
- Only include businesses that have a real website URL
- No Facebook pages, no Google Maps links — only actual websites
- Only small/medium businesses (not chains or enterprises)
- Businesses must actually be located in $city";

    $payload = [
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'tools' => [['google_search' => (object)[]]],  // Enable Google Search grounding
        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 4096],
    ];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 45,
    ]);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        scrapeLog("❌ Gemini Search failed (HTTP $httpCode): " . substr($raw, 0, 200));
        return [];
    }

    $resp = json_decode($raw, true);
    $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    // Strip markdown
    $json = preg_replace('/^```json\s*|\s*```$/i', '', trim($text));
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// ── Step 2: Fetch website and extract email addresses ─────────────────────────
function extractEmailsFromWebsite(string $url): array {
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) return [];

    // Ensure URL has scheme
    if (!preg_match('/^https?:\/\//i', $url)) $url = 'https://' . $url;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; BizNexusBot/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Accept: text/html'],
    ]);
    $html     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$html || $httpCode >= 400) {
        // Also try /contact page
        $contactUrl = rtrim($url, '/') . '/contact';
        $ch2 = curl_init($contactUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; BizNexusBot/1.0)',
        ]);
        $html = curl_exec($ch2);
        curl_close($ch2);
    }

    if (!$html) return [];

    // Extract emails via regex
    preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $html, $matches);
    $emails = $matches[0] ?? [];

    // Filter out junk
    $blacklist = ['example.com', 'sentry.io', 'w3.org', 'schema.org', 'google.com',
                  'facebook.com', 'instagram.com', 'twitter.com', 'wixpress.com',
                  'gravatar.com', 'jquery.com', 'wordpress.org', 'png', 'jpg', 'gif'];

    $clean = [];
    foreach (array_unique($emails) as $email) {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        $bad = false;
        foreach ($blacklist as $bl) {
            if (strpos($domain, $bl) !== false) { $bad = true; break; }
        }
        if (!$bad && strlen($email) < 80) $clean[] = strtolower(trim($email));
    }
    return array_slice($clean, 0, 3); // Max 3 emails per site
}

// ── Main: find businesses, extract emails, store leads ───────────────────────
$businesses = geminiSearchBusinesses($sector, $city, $GEMINI_KEY);
scrapeLog("Found " . count($businesses) . " businesses from Google Search grounding");

$saved  = 0;
$skipped = 0;

foreach ($businesses as $biz) {
    if ($saved >= $MAX_LEADS_PER_RUN) break;

    $name    = trim($biz['name']        ?? '');
    $website = trim($biz['website']     ?? '');
    $phone   = trim($biz['phone']       ?? '');
    $desc    = trim($biz['description'] ?? '');
    $cat     = trim($biz['category']    ?? $sector);
    $bizCity = trim($biz['city']        ?? $city);

    if (empty($name)) continue;

    scrapeLog("🌐 Scraping: $name ($website)");

    $emails = [];
    if (!empty($website)) {
        sleep(1); // polite delay
        $emails = extractEmailsFromWebsite($website);
    }

    if (empty($emails)) {
        // Store without email — at least we have the business name + phone + website
        $email = ''; // Will be enriched later
        scrapeLog("  ⚠️ No email found — storing with website/phone only");
    } else {
        $email = $emails[0];
        scrapeLog("  ✅ Email found: $email");
    }

    // Check duplicate (by name OR email)
    $dupCheck = $pdo->prepare("SELECT id FROM marketing_prospects WHERE email = ? OR (business_name = ? AND city = ?)");
    $dupCheck->execute([$email, $name, $bizCity]);
    if ($dupCheck->fetch()) {
        scrapeLog("  ⏭ Duplicate — skipping");
        $skipped++;
        continue;
    }

    // Store in marketing_prospects
    try {
        $pdo->prepare("INSERT INTO marketing_prospects
            (name, business_name, email, phone, website, category, city, description, source, status, created_at)
            VALUES (?,?,?,?,?,?,?,?,'google_scraper','pending',NOW())")
            ->execute([$name, $name, $email, $phone, $website, $cat, $bizCity, $desc]);
        $saved++;
        scrapeLog("  💾 Saved: $name");
    } catch (Exception $e) {
        // phone/website columns may not exist — retry without them
        try {
            $pdo->prepare("INSERT INTO marketing_prospects
                (name, business_name, email, category, city, status, created_at)
                VALUES (?,?,?,?,?,'pending',NOW())")
                ->execute([$name, $name, $email, $cat, $bizCity]);
            $saved++;
            scrapeLog("  💾 Saved (basic): $name");
        } catch (Exception $e2) {
            scrapeLog("  ❌ DB Error: " . $e2->getMessage());
        }
    }
}

$result = "Google Scraper: $saved real leads saved, $skipped duplicates skipped. Sector: $sector, City: $city";
scrapeLog("✅ Done — $result");

if ($taskId) {
    $pdo->prepare("UPDATE agent_tasks SET status='completed', result=?, updated_at=NOW() WHERE id=?")
        ->execute([$result, $taskId]);
}

// Auto-queue next run (different sector/city by hour rotation)
$alreadyQueued = $pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE task_type='google_scrape' AND status IN ('pending','running')")->fetchColumn();
if (!$alreadyQueued) {
    $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status, created_at) VALUES ('google_scrape',?,  'pending', NOW())")
        ->execute(["Auto-chain: scrape next sector/city for real business leads"]);
    scrapeLog("🔄 Next scrape run queued.");
}
?>
