<?php
// /agent/seo_agent.php
// Expected to be run via Hostinger Daily Cron Job
require_once dirname(__DIR__) . '/includes/db.php';

$logFile = __DIR__ . '/agent_logs/seo_log.txt';
if (!is_dir(__DIR__ . '/agent_logs')) mkdir(__DIR__ . '/agent_logs', 0777, true);

function writeLog($msg) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] [SEO Agent] $msg\n", FILE_APPEND);
    echo "[$date] $msg<br>\n";
}

$secrets = require_once dirname(__DIR__) . '/includes/secrets.php';
$claudeApiKey = $secrets['anthropic_api_key'];

writeLog("Starting daily SEO Sitemap & Meta generation.");

try {
    // 1. Generate XML Sitemap
    $sitemapPath = dirname(__DIR__) . '/sitemap.xml';
    $baseUrl = 'https://biznexus.in';
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Core URLs
    $coreUrls = [
        '/', '/find.php', '/auth/login.php', '/auth/register.php', 
        '/register_business.php', '/pages/pricing.php', '/help.php'
    ];
    
    foreach ($coreUrls as $url) {
        $loc = htmlspecialchars($baseUrl . $url, ENT_XML1);
        $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
    }

    // 2. Dynamic Category URLs
    $stmt = $pdo->query("SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($categories as $cat) {
        $cleanCat = urlencode(trim($cat));
        $loc = htmlspecialchars($baseUrl . "/find.php?q=" . $cleanCat, ENT_XML1);
        $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <changefreq>weekly</changefreq>\n    <priority>0.7</priority>\n  </url>\n";
    }

    // 3. GENERATED SEO PAGES (The "Power Engine" pages)
    $stmtSEO = $pdo->query("SELECT slug FROM seo_pages ORDER BY id DESC LIMIT 5000");
    $seoPages = $stmtSEO->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($seoPages as $slug) {
        $loc = htmlspecialchars($baseUrl . "/services/" . $slug, ENT_XML1);
        $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <changefreq>monthly</changefreq>\n    <priority>0.9</priority>\n  </url>\n";
    }
    
    $xml .= "</urlset>";
    file_put_contents($sitemapPath, $xml);
    writeLog("Successfully updated sitemap.xml with " . (count($coreUrls) + count($categories) + count($seoPages)) . " URLs.");

    // 4. Ping Search Engines
    $sitemapUrl = urlencode($baseUrl . '/sitemap.xml');
    file_get_contents("https://www.google.com/ping?sitemap=" . $sitemapUrl);
    file_get_contents("https://www.bing.com/ping?sitemap=" . $sitemapUrl);
    writeLog("Pushed sitemap update to Google and Bing.");

    // 2. Generate SEO Tags using Gemini AI for newest category if needed
    // In a full implementation, we'd save these to a `seo_meta` table.
    writeLog("Engaging Gemini AI to analyze newest search trends...");
    
    // Randomize one category to optimize today to save tokens
    if (count($categories) > 0) {
        $targetCat = $categories[array_rand($categories)];
        
        require_once dirname(__DIR__) . '/includes/ai_helper_v3.php';
        
        $prompt = "Write a highly optimized 150-character SEO meta description and 5 comma-separated keywords for a B2B directory page targeting the category: '$targetCat' in India.";
        
        $sys = "You are an expert SEO marketer. Output raw text ONLY. Format: Meta: [text]\nKeywords: [text]";
        
        $result = runBizAI([['role' => 'user', 'content' => $prompt]], $sys);
        
        if (isset($result['text'])) {
            $seoContent = trim($result['text']);
            writeLog("AI Optimization Success for '$targetCat':\n" . $seoContent);
        } else {
            writeLog("AI Optimization skipped or failed structure check.");
        }
    }

    writeLog("SEO Agent completed successfully.");

} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
}
?>
