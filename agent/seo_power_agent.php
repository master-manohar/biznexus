<?php
/**
 * agent/seo_power_agent.php
 * Automated SEO Page Factory for BizNexus.
 * Generates AI-optimized local landing pages for all industry/city pairs.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai_helper_v3.php';

// Configuration: Comprehensive Telangana and Andhra Pradesh District Targeting (59 Total)
$cities = [
    // Telangana (33 Districts)
    'Adilabad', 'Bhadradri-Kothagudem', 'Hyderabad', 'Jagitial', 'Jangaon', 'Jayashankar-Bhupalpally', 'Jogulamba-Gadwal', 'Kamareddy', 'Karimnagar', 'Khammam', 'Kumuram-Bheem-Asifabad', 'Mahabubabad', 'Mahabubnagar', 'Mancherial', 'Medak', 'Medchal-Malkajgiri', 'Mulugu', 'Nagarkurnool', 'Nalgonda', 'Narayanpet', 'Nirmal', 'Nizamabad', 'Peddapalli', 'Rajanna-Sircilla', 'Ranga-Reddy', 'Sangareddy', 'Siddipet', 'Suryapet', 'Vikarabad', 'Wanaparthy', 'Warangal', 'Hanamkonda', 'Yadadri-Bhuvanagiri',
    
    // Andhra Pradesh (26 Districts)
    'Alluri-Sitharama-Raju', 'Anakapalli', 'Ananthapuramu', 'Annamayya', 'Bapatla', 'Chittoor', 'Konaseema', 'East-Godavari', 'Eluru', 'Guntur', 'Kakinada', 'Krishna', 'Kurnool', 'Nandyal', 'NTR-District', 'Palnadu', 'Parvathipuram-Manyam', 'Prakasam', 'Nellore', 'Sri-Sathya-Sai', 'Srikakulam', 'Tirupati', 'Visakhapatnam', 'Vizianagaram', 'West-Godavari', 'YSR-Kadapa'
];
$limit = (int)($_GET['batch'] ?? $argv[2] ?? 50); // Default: 50 pages per run (override via ?batch=N or CLI arg)

if (php_sapi_name() !== 'cli' && ($_GET['key'] ?? '') !== 'BizCron2024') {
    die("Forbidden");
}

$taskId = $argv[1] ?? $_GET['task_id'] ?? null;

function writeLog($msg) {
    echo "[" . date('Y-m-d H:i:s') . "] $msg\n";
}

writeLog("SEO Power Agent started.");

// Get categories from DB
$categories = $pdo->query("SELECT name FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
shuffle($categories); // Shuffle for variety in each run

$generated = 0;

foreach ($categories as $cat) {
    foreach ($cities as $city) {
        if ($generated >= $limit) break 2;
        
        $combined = $cat . '-in-' . $city;
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($combined));
        $slug = trim($slug, '-');
        
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM seo_pages WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) continue;
        
        writeLog("Generating page for: $cat in $city...");
        
        // AI Prompt
        // AI Prompt: Optimized for AIO (AI Search Optimization/GEO)
        $prompt = "Write a concise, high-authority business report (max 600 words) for the category '$cat' in '$city'. Use professional tone but keep it scannable.
               Include:
               - Market Overview of $cat in $city.
               - 3-5 Key Industry Trends for this region.
               - Why choosing a local partner in $city is better.
               - A final encouraging 'Local Insights' summary.
               Use bullet points for readability. Avoid fluff. Focus on business value.
        Integrate 5-8 contextual keywords (LSI) related to '$cat' (e.g. if category is 'Photographer', use 'candid', 'cinematography', 'post-production').
        3. Mention specific local landmarks, industrial zones, or specialized economic hubs in $city relevant to $cat. 
        4. A Meta Description (150 chars).
        5. 3 detailed FAQs with answers that provide unique insights about costs, trends, or regulations in $city.
        
        Return ONLY a JSON object with keys: h1, overview, meta_desc, faqs (array of q/a objects).";

        usleep(1500000); // 1.5s pause — prevents AI rate limits while staying fast
        $result = runBizAI([['role' => 'user', 'content' => $prompt]], "You are a senior SEO copywriter and JSON API. Return ONLY valid JSON, no markdown.");
        
        if (isset($result['text'])) {
            $json = preg_replace('/^```json\s*|\s*```$/i', '', trim($result['text']));
            $data = json_decode($json, true);
            
            if ($data && isset($data['overview'])) {
                try {
                    $pdo->prepare("INSERT INTO seo_pages (category, city, slug, meta_title, meta_desc, faq_json, ai_content, last_generated) VALUES (?,?,?,?,?,?,?,NOW())")
                        ->execute([
                            $cat,
                            $city,
                            $slug,
                            $data['h1'] ?? "$cat in $city | BizNexus",
                            $data['meta_desc'] ?? "",
                            json_encode($data['faqs'] ?? []),
                            $data['overview'],
                        ]);
                    writeLog("✅ Successfully generated: $slug");
                    $generated++;
                } catch (Exception $e) {
                    writeLog("❌ DB Error: " . $e->getMessage());
                }
            } else {
                writeLog("⚠️ AI returned invalid JSON for $slug");
            }
        } else {
            writeLog("❌ AI Generation failed for $slug");
        }
    }
}

writeLog("SEO Power Agent finished. Total generated this run: $generated");

if ($taskId) {
    $resultStr = "Successfully generated $generated local landing pages with AIO optimization.";
    $pdo->prepare("UPDATE agent_tasks SET status = 'done', result = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$resultStr, $taskId]);
    writeLog("Task #$taskId marked as DONE.");
}
