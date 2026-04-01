<?php
// agent/diag_v6.php
header('Content-Type: text/plain');
echo "BIZNEXUS DIAGNOSTICS v6\n";

$files = [
    '../includes/visitor_logger.php',
    '../includes/turbo_lead_bar.php',
    '../api/capture_public_lead.php',
    '../sitemap.php',
    '../sitemap.xml'
];

foreach ($files as $f) {
    echo "$f: " . (file_exists($f) ? "EXISTS (" . filesize($f) . " bytes)" : "MISSING") . "\n";
}

session_start();
echo "Session Started. ID: " . session_id() . "\n";
echo "Lead Submitted Flag: " . (isset($_SESSION['lead_submitted']) ? "YES" : "NO") . "\n";

require_once '../includes/db.php';
$cnt = $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();
echo "SEO Pages in DB: $cnt\n";

$leads = $pdo->query("SELECT COUNT(*) FROM public_leads")->fetchColumn();
echo "Public Leads in DB: $leads\n";
