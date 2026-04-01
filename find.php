<?php
header('Content-Type: text/html; charset=utf-8');
// /find.php
// Public Lead Engine
session_start();
require_once 'includes/db.php';
require_once 'includes/visitor_logger.php'; // Log every view for intelligence
require_once 'includes_functions.php';

// Try to include email config if it exists (for sending lead notifications)
if (file_exists('includes/email_config.php')) {
    require_once 'includes/email_config.php';
}

global $pdo;
if (!$pdo) {
    die("Database connection failed.");
}

$matchedMembers = [];

// 1. AI Integration Logic (Gemini API)
if (!empty($searchQuery)) {
    require_once __DIR__ . '/includes/ai_helper_v3.php';

    
    // Dynamic Categories from DB for AI precision
    $catQuery = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
    $dbCategories = $catQuery->fetchAll(PDO::FETCH_COLUMN);
    $categories = !empty($dbCategories) ? array_merge($dbCategories, ['Other']) : ['Real Estate', 'Healthcare', 'IT Services', 'Manufacturing', 'Other'];
    $mCat = 'Other';
    
    // Attempt real AI extraction via Gemini API
    $prompt = "You are a business classifier for BizNexus. Extract the primary business category and intent from this search query: \"$searchQuery\". 
    Return ONLY a JSON object with keys: 'category' (must be one of: " . implode(', ', $categories) . ") and 'intent' (buy/sell/other).";

    $result = runBizAI([['role' => 'user', 'content' => $prompt]]);

    if (isset($result['text'])) {
        $aiText = $result['text'];
        // Strip potential markdown backticks from AI JSON response
        $aiText = preg_replace('/^```json\s*|\s*```$/i', '', $aiText);
        $aiData = json_decode($aiText, true);
        if ($aiData) {
            $mCat = $aiData['category'] ?? 'Other';
            $aiUnderstood = "AI matched this to " . htmlspecialchars($mCat);
        }
    } else {
        $err = $result['error'] ?? 'Unknown Error';
        $v = defined('BIZNEXUS_AI_VERSION') ? BIZNEXUS_AI_VERSION : 'LEGACY';
        $aiUnderstood = "AI Service Unavailable ($err). Version: $v";
    }

    // Fallback if API fails or returns Other
    if ($mCat === 'Other') {
        foreach($categories as $c) {
            if(stripos($searchQuery, $c) !== false || stripos($searchQuery, explode(' ', $c)[0]) !== false) {
                $mCat = $c;
                break;
            }
        }
        $aiUnderstood = "Keyword matched to " . htmlspecialchars($mCat);
    }
    
    $matchedCategory = $mCat;
    
    // Simple city matching
    if (!empty($searchCity)) {
        $matchedCity = $searchCity;
    } else if (preg_match('/\bin ([\w\s]+)$/i', $searchQuery, $matches)) {
        $matchedCity = trim($matches[1]);
    } else if (stripos($searchQuery, 'hyderabad') !== false) {
        $matchedCity = 'Hyderabad';
    }
}

// 2. Form Submission & Lead Dispatch Logic
$leadSubmitted = false;
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_lead'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email'] ?? '');
    
    $leadCat = $_POST['matched_category'] ?? 'Other';
    $leadCity = $_POST['matched_city'] ?? '';
    $leadQuery = $_POST['original_query'] ?? '';

    // Geocoding for Lead
    $city_coords = ['Hyderabad'=>[17.3850,78.4867],'Mumbai'=>[19.0760,72.8777],'Delhi'=>[28.6139,77.2090],'Bangalore'=>[12.9716,77.5946],'Pune'=>[18.5204,73.8567],'Chennai'=>[13.0827,80.2707],'Kolkata'=>[22.5726,88.3639],'Ahmedabad'=>[23.0225,72.5714]];
    $lat = null; $lng = null;
    foreach($city_coords as $cn=>$cc) { if(stripos($leadCity, $cn)!==false) { $lat=$cc[0]; $lng=$cc[1]; break; } }

    if (empty($name) || empty($phone)) {
        $errorMessage = "Name and Phone are required to connect you with businesses.";
    } else {
        require_once __DIR__ . '/includes/lead_dispatch_engine.php';
        $result = dispatchPublicLead($pdo, $name, $phone, $email, $leadQuery, $leadCat, $leadCity);
        if ($result['success']) {
            $leadSubmitted = true;
            $matchedMembers = $result['matched_members'];
        } else {
            $errorMessage = "Error processing your request: " . $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <title><?= !empty($searchQuery) ? htmlspecialchars("Find $searchQuery in $searchCity | BizNexus") : "Find Businesses on BizNexus" ?></title>
    <!-- SEO Schema Markup for Search Action -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "url": "https://biznexus.in/",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "https://biznexus.in/find.php?q={search_term_string}&city={city_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap' rel='stylesheet'>
    <link href='/assets/css/biznexus.css' rel='stylesheet'>
    <style>
        body { background: var(--bg); color: var(--text); }
        .hero { padding: 80px 0; text-align: center; }
        .hero h1 { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--gold); }
        .search-box {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto 40px;
        }
        .form-control {
            background: var(--bg);
            border: 1.5px solid var(--border);
            color: var(--text);
            padding: 15px;
            border-radius: 10px;
        }
        .form-control:focus {
            border-color: var(--gold);
            box-shadow: none;
            background: var(--bg);
            color: var(--text);
        }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold), #e6a800);
            color: #000;
            font-weight: 700;
            padding: 15px 30px;
            border-radius: 10px;
            border: none;
        }
        .category-pill {
            display: inline-block;
            padding: 8px 16px;
            background: #1e1e2e;
            color: var(--text2);
            border-radius: 20px;
            margin: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .category-pill:hover { background: var(--gold); color: #000; }
        .result-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }
        .logo-nav {
            padding: 20px;
            font-family: 'Syne', sans-serif;
            font-weight: 900;
            font-size: 1.5rem;
            color: var(--gold);
            text-decoration: none;
        }
        /* AI Robot Animation */
        .robot-container {
            margin-top: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 40px;
            background: linear-gradient(180deg, transparent, rgba(0,255,136,0.05));
            border-top: 1px solid rgba(0, 255, 136, 0.2);
            border-radius: 0 0 14px 14px;
        }
        .ai-robot {
            width: 60px;
            height: 60px;
            animation: hoverRobot 3s infinite ease-in-out;
            filter: drop-shadow(0 0 10px rgba(0,255,136,0.5));
        }
        .ai-robot-eye {
            animation: blinkEye 4s infinite;
        }
        @keyframes hoverRobot {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(2deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        @keyframes blinkEye {
            0%, 4%, 100% { opacity: 1; }
            2% { opacity: 0; }
        }
        .robot-text {
            color: var(--green);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<nav class="container mt-3">
    <a href="/" class="logo-nav">⚡ BizNexus</a>
    <a href="/auth/login.php" class="btn btn-gold float-end mt-1 py-2 px-4 shadow-sm" style="font-size: 14px;">Member Login</a>
</nav>

<div class="container hero">
    
    <?php if ($leadSubmitted): ?>
        <div class="search-box text-center">
            <h2 style="color: #00ff88; font-family:'Syne',sans-serif;">Request Sent!</h2>
            <p class="mb-4">Your requirement has been verified by our AI.</p>
            <p>Matching businesses in <strong><?= htmlspecialchars($leadCity ?: 'your area') ?></strong> will contact you shortly.</p>
            <p class="text-muted mt-3"><small>Expected response time: <span style="color:var(--gold)">~ 2 Hours</span></small></p>
            <a href="find.php" class="btn btn-gold mt-4">Search Again</a>
        </div>
    <?php else: ?>

        <h1 style="color: #fff;">Find the Perfect <span style="color: var(--gold)">Partner or Service</span></h1>
        <p class="lead mb-5" style="color: #aaa;">Our active AI Engine connects you with verified vendors & services across India instantly.</p>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" style="background: rgba(255, 68, 68, 0.15); color: #ff4455; border: 1px solid rgba(255, 68, 68, 0.2);"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($searchQuery) && empty($errorMessage)): ?>
            <!-- AI Results State -->
            <div class="search-box text-start">
                <div class="mb-4 p-3" style="background: rgba(255, 215, 0, 0.1); border-left: 4px solid var(--gold); border-radius: 4px;">
                    <p class="mb-1" style="color: var(--gold); font-weight: bold;">⚡ AI Understood:</p>
                    <p class="mb-0">"<?= htmlspecialchars($aiUnderstood) ?>"</p>
                </div>

                <h4 class="mb-3" style="font-family:'Syne',sans-serif;">Who should we send these leads to?</h4>
                <form method="POST" action="find.php?q=<?= urlencode($searchQuery) ?>&city=<?= urlencode($searchCity) ?>">
                    <input type="hidden" name="matched_category" value="<?= htmlspecialchars($matchedCategory) ?>">
                    <input type="hidden" name="matched_city" value="<?= htmlspecialchars($matchedCity) ?>">
                    <input type="hidden" name="original_query" value="<?= htmlspecialchars($searchQuery) ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <input type="text" name="name" class="form-control" placeholder="Your Name *" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <input type="text" name="phone" class="form-control" placeholder="WhatsApp / Phone Number *" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <input type="email" name="email" class="form-control" placeholder="Email Address (Optional)">
                    </div>
                    <button type="submit" name="submit_lead" class="btn btn-gold w-100">Get Matched Now →</button>
                    <p class="text-center mt-3 mb-0" style="color: var(--text3); font-size: 0.85rem;">Your details are secure and only shared with verified matched businesses.</p>
                </form>
            </div>
            
            <?php if (!empty($matchedMembers)): ?>
                <div class="max-w-800 mx-auto mt-5">
                    <h5 class="text-start mb-3" style="color: var(--text2);">Active members likely to receive your query:</h5>
                    <div class="row">
                        <?php 
                        // Only show top 3 previews
                        $previewCount = 0;
                        foreach($matchedMembers as $idx => $mm): 
                            if ($previewCount >= 3) break;
                        ?>
                        <div class="col-md-4">
                            <div class="result-card p-3">
                                <h6 style="color: var(--gold); margin-bottom: 5px;"><?= htmlspecialchars($mm['business_name']) ?></h6>
                                <p style="font-size: 0.85rem; color: var(--text2); margin-bottom: 0;">
                                    <?= htmlspecialchars($mm['category']) ?><br>
                                    <small><?= htmlspecialchars($mm['city']) ?></small>
                                </p>
                            </div>
                        </div>
                        <?php $previewCount++; endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Initial Search State -->
            <div class="search-box">
                <form method="GET" action="find.php">
                    <div class="row g-2">
                        <div class="col-md-7">
                            <input type="text" name="q" class="form-control form-control-lg" placeholder="e.g. Paper cup manufacturer in bulk" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="city" class="form-control form-control-lg" placeholder="City (Optional)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-gold w-100 h-100">Find</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="mt-4">
                <p class="text-muted mb-3" style="font-size: 0.9rem;">Popular Categories:</p>
                <div class="d-flex flex-wrap justify-content-center">
                    <?php 
                    $popularCats = $pdo->query("SELECT name FROM categories ORDER BY id ASC LIMIT 16")->fetchAll(PDO::FETCH_COLUMN);
                    if (empty($popularCats)) $popularCats = ['Real Estate', 'Healthcare', 'IT Services', 'Manufacturing'];
                    foreach($popularCats as $pc): 
                    ?>
                        <a href="find.php?q=<?= urlencode($pc) ?>" class="category-pill"><?= htmlspecialchars($pc) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        
    <?php endif; ?>
    
    <!-- Animated AI Footer Component -->
    <div class="robot-container">
        <svg class="ai-robot" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Antenna -->
            <path d="M50 20 L50 10 M40 10 L60 10" stroke="#00ff88" stroke-width="3" stroke-linecap="round"/>
            <circle cx="50" cy="5" r="4" fill="#FFD700" />
            <!-- Head -->
            <rect x="25" y="25" width="50" height="40" rx="10" fill="#1e1e2e" stroke="#00ff88" stroke-width="3"/>
            <!-- Eyes -->
            <rect class="ai-robot-eye" x="35" y="35" width="10" height="8" rx="2" fill="#00ff88"/>
            <rect class="ai-robot-eye" x="55" y="35" width="10" height="8" rx="2" fill="#00ff88"/>
            <!-- Mouth -->
            <path d="M40 55 Q50 60 60 55" stroke="#FFD700" stroke-width="3" stroke-linecap="round" fill="none"/>
        </svg>
        <div class="robot-text">
            BizNexus AI Matchmaking Engine Active <span class="spinner-grow spinner-grow-sm text-success ms-2" role="status" style="width: 12px; height: 12px; animation-duration: 2s;"></span>
        </div>
    </div>
</div>

<!-- AI Matchmaker Chat Widget -->
<script>
window.BizBotConfig = {
    endpoint: '/api/public_bot_chat.php',
    context: 'find',
    autoOpen: true,
    autoOpenDelay: 1500
};
</script>
<script src="/assets/js/nexus_bot.js"></script>

<?php require_once 'includes/turbo_lead_bar.php'; ?>

</body>
</html>
