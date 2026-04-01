<?php
/**
 * seo_page.php - Public-facing SEO landing page renderer
 * URL pattern: /find/{category}/{city}
 * e.g. /find/web-development/hyderabad
 * .htaccess must route these URLs to this file
 */
require_once __DIR__ . '/includes/db.php';

// Parse URL: /find/{category}/{city}
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);
$parts = array_values(array_filter(explode('/', $path)));
// Expected: ['find', 'category-slug', 'city-slug']
$catSlug  = strtolower(str_replace(' ', '-', $parts[1] ?? ''));
$citySlug = strtolower(str_replace(' ', '-', $parts[2] ?? ''));

if (!$catSlug || !$citySlug) {
    http_response_code(404);
    die("Page not found.");
}

$pageSlug = "$catSlug-in-$citySlug";

// Fetch pre-generated SEO page
$stmt = $pdo->prepare("SELECT * FROM seo_pages WHERE slug = ? LIMIT 1");
$stmt->execute([$pageSlug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback: auto-generate on the fly if not in DB yet
if (!$page) {
    // Build a basic fallback page
    $catDisplay  = ucwords(str_replace('-', ' ', $catSlug));
    $cityDisplay = ucwords(str_replace('-', ' ', $citySlug));
    $page = [
        'meta_title'  => "Top $catDisplay in $cityDisplay | BizNexus",
        'meta_desc'   => "Find trusted $catDisplay businesses in $cityDisplay. Verified SMBs, direct contact, no middleman.",
        'ai_content'  => "Discover the best $catDisplay professionals in $cityDisplay. BizNexus connects you with verified local businesses instantly.",
        'faq_json'    => '[]',
        'category'    => $catDisplay,
        'city'        => $cityDisplay,
    ];
} else {
    $catDisplay  = $page['category'];
    $cityDisplay = $page['city'];
}

// Fetch real businesses matching this category + city
$bizStmt = $pdo->prepare("
    SELECT u.id, u.name, u.is_verified, u.plan, bp.business_name, bp.description, bp.whatsapp, bp.category, bp.city, bp.website
    FROM users u
    JOIN business_profiles bp ON u.id = bp.user_id
    WHERE u.status = 'active'
      AND u.email NOT LIKE '%@example.com'
      AND bp.category LIKE ?
      AND bp.city LIKE ?
    ORDER BY u.is_verified DESC, FIELD(u.plan,'platinum','gold','silver','free')
    LIMIT 20
");
$bizStmt->execute(["%$catDisplay%", "%$cityDisplay%"]);
$businesses = $bizStmt->fetchAll(PDO::FETCH_ASSOC);

$faqs = json_decode($page['faq_json'] ?? '[]', true);
$metaTitle = $page['meta_title'] ?? "Top $catDisplay in $cityDisplay | BizNexus";
$metaDesc  = $page['meta_desc']  ?? "Find verified $catDisplay businesses in $cityDisplay.";

// Schema.org JSON-LD
$schemaItems = [];
foreach ($businesses as $i => $b) {
    $schemaItems[] = [
        "@type" => "ListItem",
        "position" => $i + 1,
        "item" => [
            "@type" => "LocalBusiness",
            "name" => $b['business_name'] ?: $b['name'],
            "description" => $b['description'] ?? '',
            "address" => ["@type" => "PostalAddress", "addressLocality" => $cityDisplay, "addressCountry" => "IN"],
            "telephone" => $b['whatsapp'] ?? '',
        ]
    ];
}
$schema = json_encode(["@context" => "https://schema.org", "@type" => "ItemList", "itemListElement" => $schemaItems], JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:url" content="https://biznexus.in/find/<?= $catSlug ?>/<?= $citySlug ?>">
    <link rel="canonical" href="https://biznexus.in/find/<?= $catSlug ?>/<?= $citySlug ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/biznexus.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="application/ld+json"><?= $schema ?></script>
    <style>
        body { background: #0a0a0f; color: #e8e8f5; }
        .hero { background: linear-gradient(135deg, #13131a, #0d0d1a); padding: 48px 0 32px; border-bottom: 1px solid #1e1e2e; }
        .biz-card { background: #13131a; border: 1px solid #2a2a3a; border-radius: 12px; padding: 20px; margin-bottom: 16px; transition: .2s; }
        .biz-card:hover { border-color: #FFD700; transform: translateY(-2px); }
        .verified-badge { background: #FFD700; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .plan-badge-platinum { color: #a259ff; } .plan-badge-gold { color: #FFD700; } .plan-badge-silver { color: #c0c0c0; }
        .faq-item { background: #13131a; border: 1px solid #1e1e2e; border-radius: 10px; padding: 18px; margin-bottom: 12px; }
        .faq-item h5 { color: #FFD700; font-size: 0.9rem; }
        .ai-content { background: rgba(255,215,0,0.04); border-left: 3px solid #FFD700; border-radius: 8px; padding: 18px 20px; color: #c0c0d8; font-size: 0.88rem; line-height: 1.8; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark" style="background:#0a0a0f; border-bottom:1px solid #1e1e2e;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/"><span style="color:#FFD700;">Biz</span>Nexus</a>
        <a href="/find.php" class="btn btn-sm" style="border:1px solid #FFD700; color:#FFD700;">Find Businesses</a>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb" style="font-size:.8rem; color:#666;">
                <li class="breadcrumb-item"><a href="/" style="color:#888;">Home</a></li>
                <li class="breadcrumb-item"><a href="/find.php" style="color:#888;">Find</a></li>
                <li class="breadcrumb-item active" style="color:#FFD700;"><?= htmlspecialchars($catDisplay) ?> in <?= htmlspecialchars($cityDisplay) ?></li>
            </ol>
        </nav>
        <h1 style="font-family:'Syne',sans-serif; font-weight:800; font-size:2rem;">
            Top <?= htmlspecialchars($catDisplay) ?> in <?= htmlspecialchars($cityDisplay) ?>
        </h1>
        <p style="color:#8888aa; max-width:600px;"><?= htmlspecialchars($metaDesc) ?></p>
        <a href="/register_business.php" class="btn mt-2" style="background:#FFD700; color:#000; font-weight:700; border-radius:8px;">
            + List Your Business Free
        </a>
    </div>
</section>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h2 style="font-size:1.1rem; font-weight:700; color:#e8e8f5; margin-bottom:16px;">
                <?= count($businesses) ?> Verified <?= htmlspecialchars($catDisplay) ?> Businesses in <?= htmlspecialchars($cityDisplay) ?>
            </h2>

            <?php if (empty($businesses)): ?>
                <div class="text-center py-5" style="background:#13131a; border-radius:12px; border:1px solid #2a2a3a;">
                    <i class="fas fa-search fa-3x mb-3" style="color:#2a2a3a;"></i>
                    <h4>No businesses listed yet in this area.</h4>
                    <p style="color:#888;">Be the first <?= htmlspecialchars($catDisplay) ?> business in <?= htmlspecialchars($cityDisplay) ?>!</p>
                    <a href="/register_business.php" class="btn mt-2" style="background:#FFD700; color:#000; font-weight:700;">Register Free</a>
                </div>
            <?php else: ?>
                <?php foreach ($businesses as $b): ?>
                    <div class="biz-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 style="font-size:1.05rem; font-weight:700; margin-bottom:4px; color:#fff;">
                                    <?= htmlspecialchars($b['business_name'] ?: $b['name']) ?>
                                    <?php if ($b['is_verified']): ?>
                                        <span class="verified-badge ms-2"><i class="fas fa-check-circle"></i> Verified</span>
                                    <?php endif; ?>
                                </h4>
                                <p style="color:#8888aa; font-size:0.8rem; margin:0;"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($b['city']) ?></p>
                            </div>
                            <span class="plan-badge-<?= $b['plan'] ?>" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">
                                <?= ucfirst($b['plan']) ?>
                            </span>
                        </div>
                        <?php if (!empty($b['description'])): ?>
                            <p style="font-size:0.85rem; color:#c0c0d8; margin-top:10px; margin-bottom:10px;"><?= htmlspecialchars(substr($b['description'], 0, 150)) ?>...</p>
                        <?php endif; ?>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <?php if (!empty($b['whatsapp'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $b['whatsapp']) ?>?text=Hi, I found you on BizNexus. I need <?= urlencode($catDisplay) ?> services."
                                   class="btn btn-sm" style="background:rgba(37,211,102,.15); color:#25d366; border:1px solid #25d36644; font-size:.78rem;">
                                    <i class="fab fa-whatsapp me-1"></i>WhatsApp
                                </a>
                            <?php endif; ?>
                            <a href="/profile/view.php?id=<?= $b['id'] ?>" class="btn btn-sm" style="border:1px solid #2a2a3a; color:#c0c0d8; font-size:.78rem;">
                                View Profile →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- AI Content Block -->
            <?php if (!empty($page['ai_content'])): ?>
                <div class="ai-content mt-4">
                    <div style="font-size:.7rem; color:#FFD700; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">📊 Market Report</div>
                    <?= nl2br(htmlspecialchars($page['ai_content'])) ?>
                </div>
            <?php endif; ?>

            <!-- FAQs -->
            <?php if (!empty($faqs)): ?>
                <h3 style="font-size:1rem; font-weight:700; margin-top:32px; margin-bottom:16px; color:#FFD700;">
                    Frequently Asked Questions
                </h3>
                <?php foreach ($faqs as $faq): ?>
                    <div class="faq-item">
                        <h5><?= htmlspecialchars($faq['q'] ?? $faq['question'] ?? '') ?></h5>
                        <p style="color:#c0c0d8; margin:0; font-size:.85rem;"><?= htmlspecialchars($faq['a'] ?? $faq['answer'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="biz-card text-center sticky-top" style="top:20px;">
                <i class="fas fa-building fa-2x mb-3" style="color:#FFD700;"></i>
                <h5 style="font-weight:700;">List Your Business</h5>
                <p style="color:#8888aa; font-size:.82rem;">Join <?= count($businesses) + 1 ?>+ <?= htmlspecialchars($catDisplay) ?> businesses on BizNexus and get leads directly on WhatsApp.</p>
                <a href="/register_business.php" class="btn w-100" style="background:linear-gradient(135deg,#FFD700,#ff8c00); color:#000; font-weight:700; border-radius:8px;">
                    Register Free Now
                </a>
                <a href="/find.php?category=<?= urlencode($catDisplay) ?>" class="d-block mt-2" style="font-size:.78rem; color:#8888aa;">Browse all <?= htmlspecialchars($catDisplay) ?> →</a>
            </div>
        </div>
    </div>
</div>

<footer style="background:#0a0a0f; border-top:1px solid #1e1e2e; padding:24px; text-align:center; color:#555; font-size:.78rem;">
    © 2026 BizNexus — AI-Powered Business Networking for SMBs in India |
    <a href="/find.php" style="color:#888;">Find Businesses</a> |
    <a href="/register_business.php" style="color:#888;">List Your Business</a>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
