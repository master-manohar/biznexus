<?php
// agent/mass_bulk_sites.php
// Instant SEO Website Generator for 600+ users (No AI cost)
require_once __DIR__ . '/../includes/db.php';

// Prepare query to get all active users with a business profile who need a site
$stmt = $pdo->query("
    SELECT u.id as user_id, u.name as user_name, u.email, u.phone, u.plan,
           bp.whatsapp as bp_wa, bp.category, bp.city, bp.description, bp.business_name,
           b.id as biz_id, b.website_generated, b.slug
    FROM users u
    LEFT JOIN business_profiles bp ON u.id = bp.user_id
    LEFT JOIN businesses b ON u.id = b.user_id
    WHERE u.status = 'active'
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$colors = ['indigo', 'emerald', 'rose', 'amber', 'purple', 'cyan', 'blue', 'orange'];
$heroStyles = ['split', 'center'];

// SEO Engine Generator
function generate_seo_html($data, $color, $heroStyle, $products) {
    $biz_name = htmlspecialchars($data['business_name'] ?: $data['user_name']);
    $cat = htmlspecialchars($data['category'] ?: 'Business Services');
    $city = htmlspecialchars($data['city'] ?: 'India');
    $desc = htmlspecialchars($data['description'] ?: "Welcome to {$biz_name}. We specialize in providing top-tier {$cat} solutions for clients in and around {$city}. Our commitment to quality and excellence ensures that you receive the best value.");
    
    $email = htmlspecialchars($data['email']);
    $phone = htmlspecialchars($data['bp_wa'] ?: $data['phone'] ?: '');
    
    // Generate SEO Keywords
    $keywords = "{$cat} in {$city}, best {$cat} {$city}, {$biz_name} {$city}, top rated {$cat}, affordable {$cat} near me";
    
    $slug = $data['slug'] ?? 'home';

    // JSON-LD Schema
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "LocalBusiness",
        "name" => $biz_name,
        "image" => "https://biznexus.in/assets/images/default-biz.png",
        "description" => $desc,
        "address" => [
            "@type" => "PostalAddress",
            "addressLocality" => $city,
            "addressCountry" => "IN"
        ],
        "telephone" => $phone,
        "email" => $email,
        "url" => "https://biznexus.in/sites/{$slug}/"
    ];
    $schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES);

    $colorMap = [
        'indigo' => '#4f46e5', 'emerald' => '#10b981', 'rose' => '#e11d48',
        'amber' => '#f59e0b', 'purple' => '#9333ea', 'cyan' => '#06b6d4',
        'blue' => '#3b82f6', 'orange' => '#f97316'
    ];
    $themeHex = $colorMap[$color];

    $html = <<<HTML
<?php
// Handle form submission to BizNexus public lead system
\$leadMsg = "";
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['site_lead'])) {
    \$name = trim(\$_POST['name']);
    \$phone = trim(\$_POST['phone']);
    if (!empty(\$name) && !empty(\$phone)) {
        require_once __DIR__ . '/../../includes/db.php';
        require_once __DIR__ . '/../../includes/lead_dispatch_engine.php';
        dispatchPublicLead(\$pdo, \$name, \$phone, '', "Website Inquiry for {$biz_name}", "{$cat}", "{$city}");
        \$leadMsg = "Thank you! Your inquiry has been dispatched and we will contact you shortly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$biz_name} | Best {$cat} in {$city}</title>
    <meta name="description" content="Looking for {$cat} in {$city}? {$biz_name} offers premium services. Contact us today for a free quote. {$desc}">
    <meta name="keywords" content="{$keywords}">
    <script type="application/ld+json">{$schemaJson}</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: {$themeHex}; }
        .text-primary { color: var(--primary); }
        .bg-primary { background-color: var(--primary); }
        .border-primary { border-color: var(--primary); }
        .hover-bg-primary:hover { background-color: var(--primary); color: white; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="text-2xl font-bold text-gray-900">{$biz_name}</div>
            <div class="space-x-6 hidden md:flex font-medium">
                <a href="#about" class="hover:text-primary transition">About</a>
                <a href="#services" class="hover:text-primary transition">Services</a>
                <a href="#contact" class="hover:text-primary transition">Contact</a>
                <a href="https://biznexus.in" class="text-gray-400 text-sm ml-4 border-l pl-4">Powered by BizNexus</a>
            </div>
            <a href="tel:{$phone}" class="bg-primary text-white px-5 py-2 rounded-full font-bold shadow-lg hover:opacity-90 transition"><i class="fa fa-phone mr-2"></i> Call Now</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative bg-white overflow-hidden border-b">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32 pt-20">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl max-w-2xl">
                            <span class="block xl:inline">Premium {$cat} in</span>
                            <span class="block text-primary">{$city}</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            {$desc}
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <a href="#contact" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:opacity-90 md:py-4 md:text-lg md:px-10">
                                    Get Started
                                </a>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#services" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-{$color}-100 hover:bg-{$color}-200 md:py-4 md:text-lg md:px-10">
                                    Our Services
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-gray-100 flex items-center justify-center">
            <div class="text-9xl text-{$color}-300 opacity-50"><i class="fa fa-building"></i></div>
        </div>
    </div>

    <!-- Stats / Trust -->
    <div class="bg-primary text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div><div class="text-4xl font-bold mb-2">100%</div><div class="text-sm uppercase tracking-wider opacity-80">Verified by BizNexus</div></div>
                <div><div class="text-4xl font-bold mb-2">{$city}</div><div class="text-sm uppercase tracking-wider opacity-80">Primary Service Area</div></div>
                <div><div class="text-4xl font-bold mb-2">24/7</div><div class="text-sm uppercase tracking-wider opacity-80">Support Response</div></div>
                <div><div class="text-4xl font-bold mb-2"><i class="fa fa-star text-yellow-300"></i><i class="fa fa-star text-yellow-300"></i><i class="fa fa-star text-yellow-300"></i><i class="fa fa-star text-yellow-300"></i><i class="fa fa-star text-yellow-300"></i></div><div class="text-sm uppercase tracking-wider opacity-80">Top Rated</div></div>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <div id="services" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-base text-primary font-semibold tracking-wide uppercase">What We Offer</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">Our Core Services</p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">We provide a comprehensive range of {$cat} tailored to your needs.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
HTML;
    
    $icons = ['fa-layer-group', 'fa-bolt', 'fa-award'];
    foreach($products as $idx => $prod) {
        $icon = $icons[$idx % 3];
        $html .= <<<HTML
                <div class="bg-white rounded-xl shadow-md p-8 hover:-translate-y-2 transition duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-{$color}-100 rounded-lg flex items-center justify-center text-primary text-2xl mb-6"><i class="fa {$icon}"></i></div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">{$prod}</h3>
                    <p class="text-gray-600">Professional {$prod} designed to exceed industry standards and deliver outstanding value for our clients in {$city}.</p>
                </div>
HTML;
    }

    $html .= <<<HTML
            </div>
        </div>
    </div>

    <!-- Contact & Lead Form -->
    <div id="contact" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gray-50 rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row border border-gray-200">
                <div class="md:w-1/2 p-10 md:p-16 bg-primary text-white">
                    <h3 class="text-3xl font-extrabold mb-4">Ready to start?</h3>
                    <p class="text-lg opacity-90 mb-8">Contact us today to discuss your requirements. We typically respond within a few hours.</p>
                    <div class="space-y-4 text-lg">
                        <div class="flex items-center"><i class="fa fa-map-marker-alt w-8 opacity-80"></i> {$city}, India</div>
                        <div class="flex items-center"><i class="fa fa-envelope w-8 opacity-80"></i> {$email}</div>
                        <div class="flex items-center"><i class="fa fa-phone w-8 opacity-80"></i> {$phone}</div>
                    </div>
                </div>
                <div class="md:w-1/2 p-10 md:p-16">
                    <?php if (!empty(\$leadMsg)): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?= htmlspecialchars(\$leadMsg) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="#contact" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                            <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                            <input type="text" name="phone" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                        </div>
                        <button type="submit" name="site_lead" value="1" class="w-full bg-primary text-white font-bold py-3 px-4 rounded-lg hover:opacity-90 transition shadow-lg">Request Callback</button>
                        <p class="text-xs text-gray-500 text-center">By submitting, you agree to be contacted via BizNexus Lead Engine.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl font-bold text-white mb-4">{$biz_name}</h2>
            <p class="mb-6 max-w-2xl mx-auto">Providing exceptional {$cat} services in {$city}.</p>
            <div class="flex justify-center space-x-6 mb-8 text-xl">
                <a href="#" class="hover:text-white transition"><i class="fab fa-facebook"></i></a>
                <a href="#" class="hover:text-white transition"><i class="fab fa-twitter"></i></a>
                <a href="#" class="hover:text-white transition"><i class="fab fa-instagram"></i></a>
            </div>
            
            <div class="mt-8 mb-8 p-6 bg-gray-800 rounded-xl text-left border border-gray-700 max-w-3xl mx-auto">
                <h4 class="text-lg font-bold text-white mb-4"><i class="fa fa-search text-yellow-400 mr-2"></i> Find more Businesses on BizNexus</h4>
                <!-- Search Component Integration -->
                <div class="mb-6">
                    <?php require_once __DIR__ . '/../../includes/search_bar_component.php'; ?>
                </div>
                <h4 class="text-lg font-bold text-white mb-2"><i class="fa fa-bolt text-yellow-400 mr-2"></i> Powered by BizNexus.in</h4>
                <p class="text-sm leading-relaxed text-gray-400">BizNexus is India's fastest-growing AI-powered B2B platform. We help businesses connect, grow, and generate leads effortlessly through automated networking, high-converting websites, and advanced CRM tools. <a href="https://biznexus.in" target="_blank" class="text-primary font-bold hover:underline ml-1">Join BizNexus today and claim your free AI website to boost your SEO.</a></p>
            </div>

            <div class="border-t border-gray-800 pt-8 text-sm">
                &copy; <?= date('Y') ?> {$biz_name}. All rights reserved. Built with ⚡ BizNexus.
            </div>
        </div>
    </footer>

    <!-- AI Support Chat Widget (Global) -->
    <script>
    window.BizBotConfig = {
        endpoint: '/api/public_bot_chat.php',
        context: 'find',
        autoOpen: false
    };
    </script>
    <script src="/assets/js/nexus_bot.js"></script>

</body>
</html>
HTML;
    return $html;
}

$count = 0;
foreach($users as $u) {
    if (empty($u['business_name']) && empty($u['user_name'])) continue; // skip invalid records

    $slug = $u['slug'];
    $biz_id = $u['biz_id'];
    
    // Create businesses row if missing
    if (empty($biz_id)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]/', '', str_replace(' ', '-', $u['business_name'] ?: ($u['user_name'].' biz'))));
        $slug .= '-' . $u['user_id']; // Ensure uniqueness
        
        $pdo->prepare("INSERT INTO businesses (user_id, name, slug, category, city, whatsapp, email, created_at) VALUES (?,?,?,?,?,?,?,NOW())")->execute([
            $u['user_id'], $u['business_name'] ?: $u['user_name'], $slug, $u['category'], $u['city'], $u['bp_wa'], $u['email']
        ]);
        $biz_id = $pdo->lastInsertId();
    }
    
    // Fallback slug handling for existing missing slugs
    if (empty($slug)) {
        $slug = 'site-' . $u['user_id'];
        $pdo->prepare("UPDATE businesses SET slug=? WHERE id=?")->execute([$slug, $biz_id]);
    }
    
    $u['slug'] = $slug;

    $dir = __DIR__ . '/../sites/' . $slug;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $color = $colors[array_rand($colors)];
    $heroStyle = $heroStyles[array_rand($heroStyles)];
    
    $cat = $u['category'] ?: 'Business';
    $prodArr = [];
    if (stripos($cat, 'IT') !== false || stripos($cat, 'Software') !== false) $prodArr = ['Custom Web Design', 'Mobile App Development', 'SEO & Digital Marketing'];
    elseif (stripos($cat, 'Health') !== false) $prodArr = ['Expert Consultation', 'Diagnostic Services', 'Therapy & Wellness'];
    elseif (stripos($cat, 'Real') !== false) $prodArr = ['Property Valuation', 'Residential Sales', 'Commercial Leasing'];
    elseif (stripos($cat, 'Edu') !== false || stripos($cat, 'Class') !== false) $prodArr = ['Beginner Batches', 'Advanced Certification', 'Private Tutoring'];
    elseif (stripos($cat, 'Food') !== false || stripos($cat, 'Restaurant') !== false) $prodArr = ['Dine-in Experience', 'Corporate Catering', 'Home Delivery'];
    elseif (stripos($cat, 'Manu') !== false) $prodArr = ['Bulk Order Production', 'Custom Manufacturing', 'Quality Assurance'];
    else $prodArr = ['Premium Quality Products', 'Professional Consultation', '24/7 Dedicated Support'];
    
    $html = generate_seo_html($u, $color, $heroStyle, $prodArr);
    
    // Write index.php inside slug directory
    file_put_contents("$dir/index.php", trim($html));
    
    // Update website flag in businesses table
    $pdo->prepare("UPDATE businesses SET website_generated = 1, website = ? WHERE id = ?")->execute(["/sites/{$slug}/", $biz_id]);
    
    $count++;
}

echo "✅ Success! Instantly generated and deployed hyper-optimized SEO websites for {$count} users.\n";
