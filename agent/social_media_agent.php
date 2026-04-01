<?php
/**
 * agent/social_media_agent.php
 * v3.0 - BizNexus Branded Instagram Agent
 * - Alternates VIDEO → PHOTO → VIDEO → PHOTO (no repeats)
 * - Posts every 4 hours
 * - Indian human visuals only
 * - Business personas: LIC agent, real estate, event manager, etc.
 * - Brand colors: Black, White, Yellow
 * - BizNexus logo watermark instructions
 * - Business quote on every photo post
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);
ini_set('memory_limit', '256M');
ignore_user_abort(true);

if (!isset($_GET['key']) || $_GET['key'] !== 'BizCron2024') {
    session_start();
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        die("Unauthorized.");
    }
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';
require_once __DIR__ . '/../includes/social_config.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';
require_once __DIR__ . '/../includes/video_bgm.php';

$taskId = $_GET['task_id'] ?? null;

try { $pdo->exec("SET SESSION wait_timeout = 600"); } catch (Exception $e) {}

function refreshDB(&$pdo) {
    try { $pdo->query('SELECT 1'); } catch (PDOException $e) {
        $pdo = new PDO("mysql:host=localhost;dbname=u175452495_biznexus;charset=utf8mb4", 'u175452495_biznexus', 'Mano@123sql', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("SET SESSION wait_timeout = 600");
        echo "🔄 DB reconnected.\n";
    }
}

// ─────────────────────────────────────────────────────────────────
// BUSINESS PERSONAS — Indian SMB categories with vivid Pexels queries
// ─────────────────────────────────────────────────────────────────
$BUSINESS_PERSONAS = [
    ['category' => 'LIC Agent',         'query_video' => 'indian financial advisor meeting client office', 'query_photo' => 'indian insurance agent smiling professional suit',    'city' => 'Hyderabad'],
    ['category' => 'Real Estate',        'query_video' => 'indian real estate agent showing house keys',   'query_photo' => 'indian property dealer handshake luxury apartment',  'city' => 'Hyderabad'],
    ['category' => 'Event Management',   'query_video' => 'indian wedding event decoration stage lights',  'query_photo' => 'indian event manager coordinating wedding ceremony',  'city' => 'Hyderabad'],
    ['category' => 'Digital Marketing',  'query_video' => 'indian social media marketing team laptop',     'query_photo' => 'indian digital marketer working startup office',      'city' => 'Bangalore'],
    ['category' => 'Jewellery',          'query_video' => 'indian gold jewellery shop woman trying bangles','query_photo' => 'indian jewellery store traditional gold necklace',  'city' => 'Hyderabad'],
    ['category' => 'Organic Food',       'query_video' => 'indian organic farmer fresh vegetables market', 'query_photo' => 'indian woman selling organic spices natural products','city' => 'Hyderabad'],
    ['category' => 'Education',          'query_video' => 'indian teacher coaching class students engaged', 'query_photo' => 'indian tutor whiteboard students smiling class',       'city' => 'Hyderabad'],
    ['category' => 'Healthcare',         'query_video' => 'indian doctor patient consultation clinic',      'query_photo' => 'indian doctor stethoscope smiling hospital',          'city' => 'Hyderabad'],
    ['category' => 'Web Development',    'query_video' => 'indian software developers team working laptop', 'query_photo' => 'indian programmer coding screen dark office',          'city' => 'Bangalore'],
    ['category' => 'Catering',           'query_video' => 'indian catering food buffet wedding reception',  'query_photo' => 'indian chef cooking biryani restaurant kitchen',      'city' => 'Hyderabad'],
    ['category' => 'Interior Design',    'query_video' => 'indian interior designer modern home renovation','query_photo' => 'indian architect home decor blueprint luxury room',    'city' => 'Hyderabad'],
    ['category' => 'Jute Products',      'query_video' => 'indian artisan weaving jute handcraft workshop',  'query_photo' => 'indian woman eco friendly jute bag market selling',  'city' => 'Hyderabad'],
];

// ─────────────────────────────────────────────────────────────────
// BUSINESS QUOTES (shown on photo posts)
// ─────────────────────────────────────────────────────────────────
$QUOTES = [
    '"Your network is your net worth." — Porter Gale',
    '"Success is not the key to happiness. Happiness is the key to success." — Albert Schweitzer',
    '"The secret of getting ahead is getting started." — Mark Twain',
    '"Don\'t watch the clock; do what it does. Keep going." — Sam Levenson',
    '"Opportunities don\'t happen. You create them." — Chris Grosser',
    '"A satisfied customer is the best business strategy of all." — Michael LeBoeuf',
    '"Small business isn\'t for the faint of heart." — Unknown',
    '"Your most unhappy customers are your greatest source of learning." — Bill Gates',
    '"Business opportunities are like buses — there\'s always another one coming." — Richard Branson',
    '"The best way to predict the future is to create it." — Peter Drucker',
    '"एकजुट हों, आगे बढ़ें — BizNexus के साथ।"',
    '"सफलता उन्हें मिलती है जो कोशिश करना नहीं छोड़ते।"',
];

// ─────────────────────────────────────────────────────────────────
// 1. Determine content type: ALTERNATE video ↔ photo (no repeats)
// ─────────────────────────────────────────────────────────────────
$lastPost = $pdo->query("SELECT media_type FROM social_posts WHERE status='published' ORDER BY id DESC LIMIT 1")->fetchColumn();
$contentType = ($lastPost === 'reel') ? 'post' : 'reel'; // alternate

// Pick a persona not used in last 12 posts (no repeats)
$recentCats = $pdo->query("SELECT category FROM social_posts ORDER BY id DESC LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);
$freshPersonas = array_filter($BUSINESS_PERSONAS, fn($p) => !in_array($p['category'], $recentCats));
if (empty($freshPersonas)) $freshPersonas = $BUSINESS_PERSONAS; // Reset if all used
$persona = $freshPersonas[array_rand($freshPersonas)];

$cat        = $persona['category'];
$city       = $persona['city'];
$pexelQuery = ($contentType === 'reel') ? $persona['query_video'] : $persona['query_photo'];
$quote      = $QUOTES[array_rand($QUOTES)];

echo "=== BizNexus Instagram Agent v3.0 ===\n";
echo "🎯 Category: $cat | City: $city | Type: " . strtoupper($contentType) . "\n";
echo "📜 Quote: $quote\n\n";

// ─────────────────────────────────────────────────────────────────
// 2. Generate AI Caption with brand personality
// ─────────────────────────────────────────────────────────────────
$quoteBlock = ($contentType === 'post') ? "\n\n💬 Quote of the Day:\n\"$quote\"\n" : "";

$prompt = "You are a viral Instagram content strategist for BizNexus — India's AI-powered SMB networking platform.
Brand colors: Black, White, Gold/Yellow. Target audience: Indian small business owners.

Write an Instagram {$contentType} caption for a {$cat} business owner in {$city}, India.

RULES:
1. Start with 3-5 relevant emojis and a SHORT HOOK (under 10 words) in Hindi or English that grabs attention
2. 3-4 punchy lines about how BizNexus helps {$cat} businesses get more leads and grow
3. Mention: 'Join 5,000+ Indian businesses on BizNexus.in — Register FREE'
4. {$quoteBlock}
5. End with 25 hashtags including: #BizNexus #IndianBusiness #SMBIndia #{$cat} #{$city}Biz #MakeInIndia #BusinessGrowth #LocalBusiness #B2BNetworking
6. Caption length: 800–1100 characters total

Also write a VOICE_SCRIPT (for video): 3 sentences, energetic, mentions {$cat} in {$city} and BizNexus.

Format EXACTLY:
[CAPTION]your caption[/CAPTION]
[VOICE_SCRIPT]your voice script[/VOICE_SCRIPT]";

$aiResponse = runBizAIString($prompt, "You are a viral Indian social media content expert. Follow format exactly.");

$caption      = '';
$voice_script = '';
if (preg_match('/\[CAPTION\](.*?)\[\/CAPTION\]/is',      $aiResponse, $m)) $caption      = trim($m[1]);
if (preg_match('/\[VOICE_SCRIPT\](.*?)\[\/VOICE_SCRIPT\]/is', $aiResponse, $m)) $voice_script = trim($m[1]);

if (empty($caption)) {
    $caption = "🚀🇮🇳💼⚡🌟\n\nAre you a *$cat* business owner in *$city*?\n\nBizNexus connects you with REAL leads, verified partners, and AI-powered growth tools — all FREE.\n\n$quoteBlock\n👉 Join 5,000+ Indian businesses at BizNexus.in\n\n#BizNexus #IndianBusiness #$cat #${city}Biz #SMBIndia #B2BNetworking #MakeInIndia #BusinessGrowth #LIC #RealEstate #EventManagement #StartupIndia #DigitalIndia #LocalBusiness #GrowthHacking #BusinessTips #EntrepreneurIndia #NetworkingIndia #LeadGeneration #BizNexusIndia #AIBusiness #BusinessCommunity #SmallBusiness #FreeLead #SellMore";
}
if (empty($voice_script)) {
    $voice_script = "Attention $cat professionals in $city! BizNexus is India's #1 AI B2B platform. Get verified leads, build trust, and grow your network — all for FREE. Register today at BizNexus dot in!";
}

echo "✅ Caption ready (" . strlen($caption) . " chars)\n";

// ─────────────────────────────────────────────────────────────────
// 3. Fetch Indian-focused media from Pexels
// ─────────────────────────────────────────────────────────────────
function fetchIndianMedia(string $query, string $type): string {
    // Add "india" to force Indian results
    $search = urlencode("india $query");

    if ($type === 'reel') {
        $url = "https://api.pexels.com/videos/search?query=$search&per_page=15&orientation=portrait&locale=en-IN";
    } else {
        $url = "https://api.pexels.com/v1/search?query=$search&per_page=15&orientation=square&locale=en-IN";
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . PEXELS_API_KEY]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if ($type === 'reel') {
        $videos = $response['videos'] ?? [];
        foreach ($videos as $vid) {
            $dur = $vid['duration'] ?? 0;
            if ($dur >= 15 && $dur <= 60) {
                foreach ($vid['video_files'] as $f) {
                    if ($f['quality'] === 'hd' && $f['width'] >= 720) return $f['link'];
                }
                return $vid['video_files'][0]['link'] ?? '';
            }
        }
        return $videos[0]['video_files'][0]['link'] ?? 'https://biznexus.in/assets/videos/default_business.mp4';
    } else {
        $photos = $response['photos'] ?? [];
        if (!empty($photos)) {
            $photo = $photos[array_rand($photos)];
            return $photo['src']['large2x'] ?? $photo['src']['large'] ?? 'https://biznexus.in/assets/images/instagram_launch.png';
        }
        return 'https://biznexus.in/assets/images/instagram_launch.png';
    }
}

$media_url = fetchIndianMedia($pexelQuery, $contentType);
echo "📸 Media: " . substr($media_url, 0, 80) . "...\n";

// ─────────────────────────────────────────────────────────────────
// 4. Add BGM for Reels
// ─────────────────────────────────────────────────────────────────
if ($contentType === 'reel') {
    $mood = 'corporate';
    if (preg_match('/food|catering|organic/i', $cat))       $mood = 'ambient';
    elseif (preg_match('/tech|software|digital|web/i', $cat)) $mood = 'upbeat';
    elseif (preg_match('/fashion|beauty|jewel/i', $cat))    $mood = 'motivational';
    elseif (preg_match('/education|coaching/i', $cat))       $mood = 'inspiring';

    echo "🎵 Merging BGM ($mood)...\n";
    $bgm_url = mergeVideoWithBGM($media_url, $mood);
    if ($bgm_url && $bgm_url !== $media_url) {
        $media_url = $bgm_url;
        echo "✅ BGM merged!\n";
    } else {
        echo "⚠️ BGM skipped\n";
    }
    refreshDB($pdo);
}

// ─────────────────────────────────────────────────────────────────
// 5. Save to DB
// ─────────────────────────────────────────────────────────────────
$pdo->prepare("INSERT INTO social_posts (category, city, caption, media_url, media_type, status, scheduled_at) VALUES (?, ?, ?, ?, ?, 'queued', NOW())")
    ->execute([$cat, $city, $caption, $media_url, $contentType]);
$post_id = $pdo->lastInsertId();
echo "💾 Queued. Post ID: $post_id\n";

// ─────────────────────────────────────────────────────────────────
// 6. Publish to Instagram
// ─────────────────────────────────────────────────────────────────
echo "📤 Publishing to Instagram...\n";
$result = ($contentType === 'reel')
    ? publishReelToInstagram($media_url, $caption, IG_ACCESS_TOKEN, IG_BUSINESS_ID)
    : publishToInstagram($media_url, $caption, IG_ACCESS_TOKEN, IG_BUSINESS_ID);

// ─────────────────────────────────────────────────────────────────
// 6b. Publish to LinkedIn Company Page (same content)
// ─────────────────────────────────────────────────────────────────
function getLinkedInMemberUrn(string $accessToken): string {
    // First: use hardcoded member ID if saved in config (by OAuth flow)
    if (defined('LI_MEMBER_ID') && LI_MEMBER_ID) {
        return 'urn:li:member:' . LI_MEMBER_ID;
    }
    // Fallback: try /v2/me (requires r_liteprofile scope)
    $ch = curl_init('https://api.linkedin.com/v2/me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'X-Restli-Protocol-Version: 2.0.0',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (!empty($res['id'])) {
        return 'urn:li:member:' . $res['id'];
    }
    return '';
}

function publishToLinkedIn(string $caption, string $mediaUrl, string $accessToken, string $orgId): array {
    if (empty($accessToken)) {
        return ['success' => false, 'error' => 'LinkedIn token not configured.'];
    }

    // Auto-detect scope: w_member_social → post as person, w_organization_social → post as org
    $scope = defined('LI_SCOPE') ? LI_SCOPE : 'w_organization_social';
    if ($scope === 'w_member_social') {
        $author = getLinkedInMemberUrn($accessToken);
        if (empty($author)) {
            return ['success' => false, 'error' => 'Could not fetch LinkedIn member URN.'];
        }
        echo "👤 Posting as member: $author\n";
    } else {
        $author = "urn:li:organization:$orgId";
        echo "🏢 Posting as company page: $author\n";
    }

    // LinkedIn caption max 3000 chars
    $liCaption = mb_substr(strip_tags($caption), 0, 2900) . "\n\nLearn more: https://biznexus.in";

    $body = json_encode([
        'author'     => $author,
        'lifecycleState' => 'PUBLISHED',
        'specificContent' => [
            'com.linkedin.ugc.ShareContent' => [
                'shareCommentary'  => ['text' => $liCaption],
                'shareMediaCategory' => 'ARTICLE',
                'media' => [[
                    'status'      => 'READY',
                    'description' => ['text' => 'BizNexus — India\'s AI Business Network'],
                    'originalUrl' => 'https://biznexus.in',
                    'title'       => ['text' => 'Join BizNexus — Free for Indian SMBs'],
                ]],
            ],
        ],
        'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
    ]);

    $ch = curl_init('https://api.linkedin.com/v2/ugcPosts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0',
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode === 201 && isset($data['id'])) {
        return ['success' => true, 'id' => $data['id']];
    }
    return ['success' => false, 'error' => $response, 'code' => $httpCode];
}

if (defined('LI_ACCESS_TOKEN') && LI_ACCESS_TOKEN) {
    echo "📤 Publishing to LinkedIn...\n";
    $liResult = publishToLinkedIn($caption, $media_url, LI_ACCESS_TOKEN, LI_ORGANIZATION_ID);
    if ($liResult['success']) {
        echo "✅ LinkedIn Post Published! ID: " . $liResult['id'] . "\n";
    } else {
        echo "⚠️ LinkedIn failed: " . json_encode($liResult['error'] ?? '') . "\n";
    }
} else {
    echo "⚠️ LinkedIn skipped — token not configured yet.\n";
}


refreshDB($pdo);

// ─────────────────────────────────────────────────────────────────
// 7. Update & Schedule NEXT POST in 4 HOURS
// ─────────────────────────────────────────────────────────────────
if ($result['success']) {
    $pdo->prepare("UPDATE social_posts SET status='published', published_at=NOW(), error_log=? WHERE id=?")
        ->execute(['Post ID: ' . $result['id'], $post_id]);

    echo "\n🚀 SUCCESS! " . strtoupper($contentType) . " Published!\n";
    echo "📱 Instagram Post ID: " . $result['id'] . "\n";
    echo "🌐 https://www.instagram.com/biznexus.in/\n";

    // Schedule next in 4 hours (was 2 hours)
    $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status, created_at) VALUES ('social_posting', 'Auto Instagram post — next 4-hour cycle', 'pending', DATE_ADD(NOW(), INTERVAL 4 HOUR))")
        ->execute();
    echo "\n📅 Next post auto-scheduled in 4 hours.\n";
} else {
    $error = json_encode($result['error'] ?? $result);
    $pdo->prepare("UPDATE social_posts SET status='failed', error_log=? WHERE id=?")->execute([$error, $post_id]);

    echo "\n❌ PUBLISH FAILED: $error\n";
    echo "Media URL: $media_url\n";
    echo "Caption Length: " . strlen($caption) . "\n";

    // Retry in 30 min
    $pdo->prepare("INSERT INTO agent_tasks (task_type, goal, status, created_at) VALUES ('social_posting', 'Retry after Instagram fail', 'pending', DATE_ADD(NOW(), INTERVAL 30 MINUTE))")
        ->execute();
    echo "⏱️ Retry scheduled in 30 minutes.\n";
}

if ($taskId) {
    $pdo->prepare("UPDATE agent_tasks SET status=?, updated_at=NOW() WHERE id=?")
        ->execute([$result['success'] ? 'completed' : 'failed', $taskId]);
}
?>
