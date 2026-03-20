<?php
/**
 * BizNexus Sitemap Generator
 * Upload to: /public_html/sitemap.php
 * Generates dynamic sitemap and auto-creates /sitemap.xml + /robots.txt
 */
$key = $_GET['key']??'';
if($key !== 'BizCron2024' && php_sapi_name()!=='cli'){
    // Serve cached sitemap if exists
    if(file_exists(__DIR__.'/sitemap.xml')){
        header('Content-Type: application/xml');
        readfile(__DIR__.'/sitemap.xml'); exit;
    }
    header('Content-Type: application/xml'); echo '<?xml version="1.0"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'; exit;
}

function getDB(){
    $c=[['localhost','u175452495_biznexus','u175452495_bizuser','Biz@9990'],['localhost','u175452495_biznexus','u175452495_voo_user','Vooschool@123'],['localhost','u175452495_biznexus','u175452495','Biz@9990']];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}

$base = 'https://biznexus.in';
$today = date('Y-m-d');
$pdo = getDB();

// Static pages
$urls = [
    ['/',         '1.0', 'daily'],
    ['/find.php', '0.9', 'daily'],
    ['/auth/login.php',    '0.8', 'monthly'],
    ['/auth/register.php', '0.9', 'monthly'],
    ['/auth/forgot.php',   '0.5', 'monthly'],
    ['/pages/pricing.php', '0.8', 'weekly'],
    ['/pages/about.php',   '0.7', 'monthly'],
    ['/pages/contact.php', '0.7', 'monthly'],
    ['/pages/terms.php',   '0.5', 'monthly'],
    ['/pages/privacy.php', '0.5', 'monthly'],
    ['/help.php',          '0.6', 'monthly'],
    ['/marketplace/index.php', '0.8', 'daily'],
    ['/groups/index.php',      '0.7', 'daily'],
    ['/community/index.php',   '0.7', 'daily'],
];

// Dynamic SEO pages (category + city combinations)
$categories = ['real-estate','construction','food-beverage','healthcare','education','retail','it-services','finance','legal','events','photography','fashion','manufacturing'];
$cities = ['hyderabad','bangalore','mumbai','delhi','chennai','pune','kolkata','ahmedabad','jaipur','surat','lucknow','kochi','chandigarh','indore','nagpur'];
foreach($categories as $cat){
    foreach($cities as $city){
        $urls[] = ["/find.php?category=".urlencode($cat)."&city=".urlencode($city), '0.6', 'weekly'];
    }
}

// Dynamic member profiles (public ones)
if($pdo){
    try {
        $members=$pdo->query("SELECT id,updated_at FROM users WHERE status='active' AND (profile_visibility='public' OR profile_visibility IS NULL) LIMIT 500");
        while($m=$members->fetch()){
            $upd=date('Y-m-d',strtotime($m['updated_at']??'now'));
            $urls[]=["/profile/view.php?id={$m['id']}", '0.5', 'monthly', $upd];
        }
    }catch(Exception $e){}
}

// Build XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach($urls as $u){
    $lastmod = $u[3]??$today;
    $xml .= "  <url>\n";
    $xml .= "    <loc>".htmlspecialchars($base.$u[0])."</loc>\n";
    $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
    $xml .= "    <changefreq>{$u[2]}</changefreq>\n";
    $xml .= "    <priority>{$u[1]}</priority>\n";
    $xml .= "  </url>\n";
}
$xml .= '</urlset>';

// Write sitemap.xml
file_put_contents(__DIR__.'/sitemap.xml', $xml);

// Write robots.txt
$robots = "User-agent: *\n";
$robots .= "Allow: /\n";
$robots .= "Disallow: /admin/\n";
$robots .= "Disallow: /agent/\n";
$robots .= "Disallow: /auth/logout.php\n";
$robots .= "Disallow: /dashboard/\n";
$robots .= "Disallow: /profile/edit.php\n";
$robots .= "Disallow: /settings/\n";
$robots .= "Disallow: /crm/\n";
$robots .= "Disallow: /invoices/\n";
$robots .= "Disallow: /coins/\n";
$robots .= "Disallow: /kyc/\n";
$robots .= "\n";
$robots .= "Sitemap: https://biznexus.in/sitemap.xml\n";
file_put_contents(__DIR__.'/robots.txt', $robots);

$count = count($urls);
echo json_encode(['status'=>'ok','urls_generated'=>$count,'sitemap_written'=>true,'robots_written'=>true,'time'=>date('Y-m-d H:i:s')]);
