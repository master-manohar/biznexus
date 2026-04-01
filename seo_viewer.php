<?php
/**
 * seo_viewer.php
 * Premium SEO Dynamic Landing Page for BizNexus.
 */
require_once 'includes/db.php';
require_once 'includes/visitor_logger.php'; // Track SEO page views
require_once 'includes_functions.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: /find.php");
    exit;
}

// 1. Fetch SEO Data
$stmt = $pdo->prepare("SELECT * FROM seo_pages WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$seo = $stmt->fetch();

if (!$seo) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 - Page Not Found</h1>";
    exit;
}

$cat = $seo['category'];
$city = $seo['city'];

// 2. Fetch Matching Members for this Page (with Smart Fallback)
$members = [];
try {
    // Attempt 1: Specific City
    $mStmt = $pdo->prepare("
        SELECT u.id, bp.business_name, bp.description, bp.city, bp.category, u.phone as u_phone, bp.whatsapp as bp_whatsapp, u.trust_score
        FROM users u
        INNER JOIN business_profiles bp ON u.id = bp.user_id
        WHERE bp.category LIKE ? AND bp.city LIKE ? AND u.status = 'active'
        ORDER BY u.trust_score DESC, u.is_verified DESC
        LIMIT 6
    ");
    $mStmt->execute(['%' . $cat . '%', '%' . $city . '%']);
    $members = $mStmt->fetchAll();
    
    // Attempt 2: Broad Keyword Match (e.g. "Health" if "Health Insurance Services" is empty)
    if (empty($members)) {
        $keywords = explode(' ', $cat);
        $searchWord = '';
        foreach($keywords as $w) {
            if(strlen($w) > 4) { $searchWord = $w; break; }
        }
        if(!$searchWord) $searchWord = $keywords[0]; // Fallback to first word

        $fStmt = $pdo->prepare("
            SELECT u.id, bp.business_name, bp.description, bp.city, bp.category, u.phone as u_phone, bp.whatsapp as bp_whatsapp, u.trust_score
            FROM users u
            INNER JOIN business_profiles bp ON u.id = bp.user_id
            WHERE (bp.category LIKE ? OR bp.category LIKE ?) AND u.status = 'active'
            ORDER BY u.trust_score DESC, u.is_verified DESC
            LIMIT 6
        ");
        $fStmt->execute(['%' . $searchWord . '%', '%' . $cat . '%']);
        $members = $fStmt->fetchAll();
    }
    
    // Attempt 3: Global Top Rated (Last Resort)
    if (empty($members)) {
        $lStmt = $pdo->query("
            SELECT u.id, bp.business_name, bp.description, bp.city, bp.category, u.phone as u_phone, bp.whatsapp as bp_whatsapp, u.trust_score
            FROM users u
            INNER JOIN business_profiles bp ON u.id = bp.user_id
            WHERE u.status = 'active'
            ORDER BY u.trust_score DESC, u.is_verified DESC
            LIMIT 6
        ");
        $members = $lStmt->fetchAll();
    }
} catch (Exception $e) {}

$faqs = json_decode($seo['faq_json'], true) ?: [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($seo['meta_title'] ?: "$cat in $city | BizNexus") ?></title>
    <meta name="description" content="<?= htmlspecialchars($seo['meta_desc']) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="/assets/css/biznexus.css" rel="stylesheet">
    
    <!-- AIO: Advanced Structured Data for AI Engines -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "BizNexus", "item": "https://biznexus.in/" },
        { "@type": "ListItem", "position": 2, "name": "<?= htmlspecialchars($cat) ?>", "item": "https://biznexus.in/find.php?q=<?= urlencode($cat) ?>" },
        { "@type": "ListItem", "position": 3, "name": "<?= htmlspecialchars($city) ?>", "item": "https://biznexus.in/services/<?= $slug ?>" }
      ]
    }
    </script>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Service",
      "name": "<?= htmlspecialchars($seo['meta_title'] ?: "$cat in $city") ?>",
      "provider": { "@type": "Organization", "name": "BizNexus AI Engine" },
      "areaServed": { "@type": "City", "name": "<?= htmlspecialchars($city) ?>" },
      "description": "<?= htmlspecialchars($seo['meta_desc']) ?>",
      "mainEntityOfPage": "https://biznexus.in/services/<?= $slug ?>",
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Verified Business Partners",
        "itemListElement": [
          <?php foreach($members as $idx => $m): ?>
          {
            "@type": "Offer",
            "itemOffered": {
              "@type": "Service",
              "name": "<?= htmlspecialchars($m['business_name']) ?>",
              "description": "<?= htmlspecialchars(substr($m['description'], 0, 150)) ?>"
            }
          }<?= $idx < count($members) - 1 ? ',' : '' ?>
          <?php endforeach; ?>
        ]
      }
    }
    </script>
    
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "<?= htmlspecialchars($seo['meta_title']) ?>",
      "description": "<?= htmlspecialchars($seo['meta_desc']) ?>",
      "mainEntity": {
        "@type": "ItemList",
        "itemListElement": [
          <?php foreach($members as $idx => $m): ?>
          {
            "@type": "ListItem",
            "position": <?= $idx + 1 ?>,
            "name": "<?= htmlspecialchars($m['business_name']) ?>",
            "url": "https://biznexus.in/profile/view.php?id=<?= $m['id'] ?>"
          }<?= $idx < count($members) - 1 ? ',' : '' ?>
          <?php endforeach; ?>
        ]
      }
    }
    </script>
    
    <?php if(!empty($faqs)): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        <?php foreach($faqs as $idx => $f): ?>
        {
          "@type": "Question",
          "name": "<?= htmlspecialchars($f['q'] ?? $f['question'] ?? '') ?>",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "<?= htmlspecialchars($f['a'] ?? $f['answer'] ?? '') ?>"
          }
        }<?= $idx < count($faqs) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>

    <style>
        :root { --g: #FFD700; --bg: #06060a; --c: #0e0e16; --b: #1e1e2e; }
        body { background: var(--bg); color: #fff; font-family: 'DM Sans', sans-serif; }
        .hero-seo { padding: 80px 0 40px; text-align: center; background: radial-gradient(circle at top, rgba(255,215,0,0.05), transparent); }
        .hero-seo h1 { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 3rem; color: var(--g); margin-bottom: 20px; }
        .content-card { background: var(--c); border: 1px solid var(--b); border-radius: 20px; padding: 40px; line-height: 1.8; color: #ccc; margin-bottom: 40px; }
        .member-card { background: #13131a; border: 1px solid var(--b); border-radius: 15px; padding: 25px; height: 100%; transition: 0.3s; }
        .member-card:hover { border-color: var(--g); transform: translateY(-5px); }
        .faq-item { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; margin-bottom: 15px; border-left: 4px solid var(--g); }
        .faq-item h4 { font-size: 1.1rem; color: var(--g); margin-bottom: 10px; }
        .footer-seo { border-top: 1px solid var(--b); padding: 40px 0; background: var(--c); }
    </style>
</head>
<body>

<nav class="container mt-4 mb-4">
    <a href="/" class="text-decoration-none" style="font-family:'Syne',sans-serif; font-weight:900; color:var(--g); font-size:1.5rem;">⚡ BizNexus</a>
</nav>

<header class="hero-seo">
    <div class="container">
        <h1><?= htmlspecialchars($seo['meta_title']) ?></h1>
        <div class="mt-4">
            <?php include 'includes/search_bar_component.php'; ?>
        </div>
    </div>
</header>

<main class="container mb-5">
    <div class="row">
        <div class="col-lg-8">
            <section class="content-card shadow-lg" id="seoContentSection" style="max-height: 480px; overflow: hidden; position: relative;">
                <div class="mb-4 d-flex align-items-center gap-2">
                    <span class="badge bg-success">Verified Category</span>
                    <span class="badge bg-warning text-dark"><?= htmlspecialchars($city) ?> Region</span>
                </div>
                <div style="font-size: 1.1rem;" id="seoText">
                    <?= nl2br(htmlspecialchars($seo['ai_content'])) ?>
                </div>
                <div id="readMoreBtn" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 80px; background: linear-gradient(transparent, var(--c)); display: flex; align-items: flex-end; justify-content: center; padding-bottom: 10px; cursor: pointer;" onclick="toggleContent()">
                    <span class="btn btn-sm btn-outline-warning fw-bold px-4">Read Full Report ↓</span>
                </div>
            </section>
            <script>
                function toggleContent() {
                    let sec = document.getElementById('seoContentSection');
                    let btn = document.getElementById('readMoreBtn');
                    if (sec.style.maxHeight === '480px') {
                        sec.style.maxHeight = 'none';
                        btn.style.background = 'none';
                        btn.innerHTML = '<span class="btn btn-sm btn-outline-secondary fw-bold px-4">See Less ↑</span>';
                    } else {
                        sec.style.maxHeight = '480px';
                        btn.style.background = 'linear-gradient(transparent, var(--c))';
                        btn.innerHTML = '<span class="btn btn-sm btn-outline-warning fw-bold px-4">Read Full Report ↓</span>';
                    }
                }
            </script>

            <?php if(!empty($faqs)): ?>
            <section class="mb-5">
                <h2 class="mb-4" style="font-family:'Syne',sans-serif;">Frequently Asked Questions</h2>
                <?php foreach($faqs as $f): ?>
                <div class="faq-item">
                    <h4><?= htmlspecialchars($f['q'] ?? $f['question'] ?? '') ?></h4>
                    <p class="mb-0"><?= htmlspecialchars($f['a'] ?? $f['answer'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="mb-4 p-4 text-center rounded shadow-sm" style="background: linear-gradient(135deg, #1e1e2e, #06060a); border: 1px solid var(--g);">
                <h5 class="mb-2" style="font-family:'Syne',sans-serif; color:var(--g);">Grow your business in <?= htmlspecialchars($city) ?></h5>
                <p class="small text-muted mb-3">Get listed on BizNexus and reach local customers every month.</p>
                <a href="/auth/register.php" class="btn btn-warning w-100 fw-bold btn-sm shadow">Join BizNexus Now</a>
            </div>

            <h3 class="mb-4" style="font-family:'Syne',sans-serif; font-size: 1.25rem;">Top Local Partners</h3>
            <?php if(empty($members)): ?>
                <div class="p-4 text-center border rounded border-dashed opacity-50">
                    No members listed in this category yet. <br>Be the first to join!
                </div>
            <?php else: ?>
                <?php foreach($members as $m): 
                    $m_score = $m['trust_score'] ?? 0;
                    $m_level = getTrustLevel($m_score);
                    $wa_phone = !empty($m['bp_whatsapp']) ? $m['bp_whatsapp'] : (!empty($m['u_phone']) ? $m['u_phone'] : '');
                    $wa_phone = preg_replace('/[^0-9]/', '', $wa_phone);
                    if(strlen($wa_phone) == 10) $wa_phone = '91' . $wa_phone;
                ?>
                <div class="member-card mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0" style="color:var(--g);"><?= htmlspecialchars($m['business_name'] ?: 'Verified Business') ?></h5>
                        <span class="badge" style="background: <?= $m_level['color'] ?>22; color: <?= $m_level['color'] ?>; border: 1px solid <?= $m_level['color'] ?>44; font-size: 0.65rem;">
                            <?= $m_level['status'] ?> (<?= $m_score ?>)
                        </span>
                    </div>
                    <p class="small text-muted mb-3"><?= htmlspecialchars(substr($m['description'], 0, 100)) ?>...</p>
                    <div class="d-grid gap-2">
                        <a href="/profile/view.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary">View Profile</a>
                        <?php if($wa_phone): ?>
                        <a href="https://wa.me/<?= $wa_phone ?>?text=Hi, I found you on BizNexus for <?= urlencode($cat) ?> in <?= urlencode($city) ?>. Can we discuss?" 
                           target="_blank" class="btn btn-sm btn-success fw-bold">
                           <i class="fab fa-whatsapp me-1"></i> WhatsApp Inquiry
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="footer-seo">
    <div class="container text-center">
        <div class="mb-3">
            <a href="/" class="text-white text-decoration-none mx-3">Home</a>
            <a href="/find.php" class="text-white text-decoration-none mx-3">Find Business</a>
            <a href="/pages/pricing.php" class="text-white text-decoration-none mx-3">Pricing</a>
        </div>
        <p class="text-muted small">© <?= date('Y') ?> BizNexus. India's AI-Powered Business Network.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once 'includes/turbo_lead_bar.php'; ?>

</body>
</html>
