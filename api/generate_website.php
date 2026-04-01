<?php
/**
 * /api/generate_website.php
 * Automated Website Generator Agent for BizNexus
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Business Info
$business = $pdo->prepare("SELECT * FROM businesses WHERE user_id = ?");
$business->execute([$user_id]);
$biz = $business->fetch(PDO::FETCH_ASSOC);

if (!$biz) {
    // Fallback: check business_profiles explicitly
    $bp = $pdo->prepare("SELECT * FROM business_profiles WHERE user_id = ?");
    $bp->execute([$user_id]);
    $prof = $bp->fetch(PDO::FETCH_ASSOC);
    
    if ($prof) {
        $bizName = $prof['business_name'] ?: 'My Business';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $bizName)));
        
        $stmt_insert = $pdo->prepare("INSERT INTO businesses (user_id, name, slug, category, city) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->execute([$user_id, $bizName, $slug, $prof['category'] ?? 'General', $prof['city'] ?? '']);
        
        $biz = [
            'id' => $pdo->lastInsertId(),
            'name' => $bizName,
            'slug' => $slug,
            'category' => $prof['category'] ?? 'General',
            'city' => $prof['city'] ?? '',
            'tagline' => 'Quality Services',
            'description' => 'Providing top-notch services to our clients.',
            'email' => '',
            'phone' => $prof['whatsapp'] ?? ''
        ];
    } else {
        echo json_encode(['success' => false, 'error' => 'Business profile not found. Please complete your registration details first.']);
        exit;
    }
}

$slug = $biz['slug'];
if (!$slug) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $biz['name'])));
    $pdo->prepare("UPDATE businesses SET slug = ? WHERE id = ?")->execute([$slug, $biz['id']]);
}

// 2. Prepare AI Prompt for Website Generation
require_once __DIR__ . '/../includes/ai_helper_v3.php';

$user_inputs = $_POST['inputs'] ?? []; // Additional details from chat

$system = "You are the 'BizNexus Website Architect'.
Your task is to generate a COMPLETE, SINGLE-FILE PHP/HTML website for a business.
The website MUST look extremely premium, modern, and professional.
Use Tailwind CSS (via CDN) for styling. Avoid generic looks.
Business Details:
- Name: {$biz['name']}
- Category: {$biz['category']}
- City: {$biz['city']}
- Tagline: " . ($user_inputs['tagline'] ?? $biz['tagline'] ?? 'Quality Services for You') . "
- Description: {$biz['description']}
- Services: " . ($user_inputs['services'] ?? 'General Business Services') . "
- Contact: {$biz['email']} / {$biz['phone']}

REQUIREMENTS:
- Use a stunning color palette (e.g., Deep Indigo and Gold, or Charcoal and Emerald).
- Sections: Nav, Hero, Services Grid, About, Contact Form, Footer.
- The Contact Form should POST to '../../api/website_contact.php' with 'business_id={$biz['id']}'.
- Include smooth scroll animations or hover effects using standard CSS/Tailwind.
- Ensure it is mobile-responsive.
- Return ONLY the PHP/HTML code. No intro text.";

$prompt = "Generate a high-end, 100% finished one-page website for {$biz['name']}. Give it a unique 'vibe' suited for a {$biz['category']} business.";

$result = runBizAI(
    [['role' => 'user', 'content' => $prompt]], 
    $system, 
    'gemini-pro-latest', // Using Pro for complex code generation
    ['maxOutputTokens' => 4000]
);

if (isset($result['text'])) {
    $html = $result['text'];
    // Strip markdown backticks if Gemini adds them
    $html = preg_replace('/^```(?:html|php)?\s*|\s*```$/i', '', trim($html));
    
    // 3. Create Directory and Save
    $dir = __DIR__ . "/../sites/$slug";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents("$dir/index.php", $html);
    
    // 4. Update Database
    $pdo->prepare("UPDATE businesses SET website_generated = 1 WHERE user_id = ?")->execute([$user_id]);
    
    echo json_encode(['success' => true, 'slug' => $slug, 'url' => "https://$slug.biznexus.in"]);
} else {
    echo json_encode(['success' => false, 'error' => "AI Generation failed (Code $code)."]);
}
