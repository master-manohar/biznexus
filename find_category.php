<?php
// find_category.php
session_start();
require_once 'includes/db.php';

$rawCategory = $_GET['category'] ?? '';
$rawCity = $_GET['city'] ?? '';

$category = htmlspecialchars(urldecode(trim($rawCategory)));
$city = htmlspecialchars(urldecode(trim($rawCity)));

if(empty($category) || empty($city)) {
    header("Location: /find.php");
    exit;
}

$pageTitle = "Top " . ucwords($category) . " Businesses in " . ucwords($city) . " - BizNexus";

// Fetch active members matching
$stmt = $pdo->prepare("
    SELECT u.id, u.name, bp.business_name, bp.description, bp.whatsapp, bp.category, bp.city, u.is_verified, (SELECT trust_badge FROM users WHERE id = u.id) as trust_badge 
    FROM users u 
    JOIN business_profiles bp ON u.id = bp.user_id 
    WHERE u.status = 'active' AND bp.category = ? AND bp.city LIKE ?
    ORDER BY u.is_verified DESC, u.plan DESC
");
$stmt->execute([$category, "%$city%"]);
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON-LD Schema
$schemaElements = [];
$pos = 1;
foreach($businesses as $b) {
    // Generate valid JSON-LD for LocalBusiness
    $schemaElements[] = [
        "@type" => "ListItem",
        "position" => $pos++,
        "item" => [
            "@type" => "LocalBusiness",
            "name" => $b['business_name'] ?: $b['name'],
            "description" => $b['description'] ?? '',
            "address" => [
                "@type" => "PostalAddress",
                "addressLocality" => $b['city'],
                "addressCountry" => "IN"
            ],
            "url" => "https://biznexus.in/find/" . urlencode(strtolower($category)) . "/" . urlencode(strtolower($b['city']))
        ]
    ];
}

$schema = [
    "@context" => "https://schema.org",
    "@type" => "ItemList",
    "itemListElement" => $schemaElements
];

$jsonSchema = json_encode($schema, JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="Find the best <?= htmlspecialchars(ucwords($category)) ?> businesses and services in <?= htmlspecialchars(ucwords($city)) ?> on BizNexus. Verified professionals.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/biznexus.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .verified-badge { background:#FFD700; color:#000; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:bold; margin-left:8px; display:inline-flex; align-items:center;}
        .biz-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px; transition: transform 0.2s;}
        .biz-card:hover { border-color: var(--gold); transform: translateY(-3px);}
    </style>
    <script type="application/ld+json">
        <?= $jsonSchema ?>
    </script>
</head>
<body style="background: var(--bg); color: var(--text);">

<nav class="navbar navbar-expand-lg navbar-dark" style="background:#0a0a0f; border-bottom:1px solid #1e1e2e;">
    <div class="container">
        <a class="navbar-brand" href="/"><span style="color:#FFD700; font-weight:bold; font-family:'Syne'">Biz</span>Nexus</a>
    </div>
</nav>

<div class="container py-5">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/" style="color:var(--text2); text-decoration:none;">Home</a></li>
            <li class="breadcrumb-item"><a href="/find.php" style="color:var(--text2); text-decoration:none;">Find</a></li>
            <li class="breadcrumb-item active" style="color:var(--gold)" aria-current="page"><?= htmlspecialchars(ucwords($category)) ?> in <?= htmlspecialchars(ucwords($city)) ?></li>
          </ol>
        </nav>
        <h1 style="font-family: 'Syne', sans-serif; font-weight: 800;"><?= htmlspecialchars(ucwords($category)) ?> in <?= htmlspecialchars(ucwords($city)) ?></h1>
        <p style="color:var(--text2)">Showing top verified professionals and businesses matching your criteria.</p>
    </div>

    <div class="row">
        <?php if(empty($businesses)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-search fa-3x mb-3" style="color:#2a2a3e;"></i>
                <h4 style="color:#e0e0e0;">No verified businesses found in this area yet.</h4>
                <p style="color:#888;">Be the first to list your <?= htmlspecialchars($category) ?> business in <?= htmlspecialchars($city) ?>!</p>
                <a href="/register_business.php" class="btn mt-2" style="background:var(--gold); border:none; font-weight:bold; color:#000;">Register Business Free</a>
            </div>
        <?php else: ?>
            <?php foreach($businesses as $b): ?>
                <div class="col-md-6 mb-4">
                    <div class="biz-card">
                        <h4 style="color:#fff; margin-bottom:5px;">
                            <?= htmlspecialchars($b['business_name'] ?: $b['name']) ?>
                            <?php if($b['is_verified']): ?>
                                <span class="verified-badge"><i class="fas fa-check-circle me-1"></i> Verified</span>
                            <?php endif; ?>
                            <?php if(!empty($b['trust_badge'])): ?>
                                <span class="badge" style="background:linear-gradient(45deg,#0052D4,#4364F7); font-size:11px; margin-left:8px;"><?= htmlspecialchars(ucwords($b['trust_badge'])) ?> Trusted</span>
                            <?php endif; ?>
                        </h4>
                        <p style="color:var(--text2); font-size:14px;"><i class="fas fa-map-marker-alt" style="color:#888;"></i> <?= htmlspecialchars($b['city']) ?></p>
                        <p style="font-size:14px;"><?= htmlspecialchars($b['description'] ?? 'No description provided.') ?></p>
                        <div class="mt-3">
                            <a href="/find.php" class="btn btn-outline-light btn-sm">Request Quote</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
