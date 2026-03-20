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
        $xml .= "  <url>\n    <loc>{$baseUrl}{$url}</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
    }

    // Dynamic Category URLs for SEO indexing
    $stmt = $pdo->query("SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($categories as $cat) {
        $cleanCat = urlencode(trim($cat));
        $xml .= "  <url>\n    <loc>{$baseUrl}/find.php?q={$cleanCat}</loc>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
    }
    
    $xml .= "</urlset>";
    file_put_contents($sitemapPath, $xml);
    writeLog("Successfully updated sitemap.xml with " . (count($coreUrls) + count($categories)) . " URLs.");

    // 2. Generate SEO Tags using Claude AI for newest category if needed
    // In a full implementation, we'd save these to a `seo_meta` table.
    writeLog("Engaging Claude API to analyze newest search trends...");
    
    // Randomize one category to optimize today to save tokens
    if (count($categories) > 0) {
        $targetCat = $categories[array_rand($categories)];
        
        $prompt = "Write a highly optimized 150-character SEO meta description and 5 comma-separated keywords for a B2B directory page targeting the category: '$targetCat' in India.";
        
        $sys = "You are an expert SEO marketer. Output raw text ONLY. Format: Meta: [text]\nKeywords: [text]";
        $payload = [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 100,
            'system' => $sys,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ];
        
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $claudeApiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json'
        ]);
        
        $res = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($res, true);
        if (isset($data['content'][0]['text'])) {
            $seoContent = trim($data['content'][0]['text']);
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
