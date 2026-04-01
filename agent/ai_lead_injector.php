<?php
// agent/ai_lead_injector.php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
echo "AI LEAD INJECTOR v2 START\n";

$leads = [
    [
        'name' => 'Invest India Creative RFP',
        'email' => 'procurement@investindia.org.in',
        'phone' => '011-23048155',
        'category' => 'Web Development',
        'city' => 'Delhi',
        'requirement' => 'Request for Proposals: Creative design and audio-visual content development. Deadline: April 6, 2026.',
        'source' => 'AI_SCOUT_RFP'
    ],
    [
        'name' => 'Ministry IT Infrastructure',
        'email' => 'it-support@gov.in',
        'phone' => '011-24301000',
        'category' => 'IT Services',
        'city' => 'Delhi',
        'requirement' => 'Bulk procurement of unified SAN & Servers. High priority infrastructure project. Deadline: April 2, 2026.',
        'source' => 'AI_SCOUT_RFP'
    ],
    [
        'name' => 'India Post Media Agency',
        'email' => 'media@indiapost.gov.in',
        'phone' => '18002666868',
        'category' => 'Marketing',
        'city' => 'Mumbai',
        'requirement' => 'Empanelment of Media Production Agencies for national campaigns. Deadline: April 1, 2026.',
        'source' => 'AI_SCOUT_RFP'
    ],
    [
        'name' => 'Bengaluru Print Expo 2026 Buyer',
        'email' => 'info@printexpo.in',
        'phone' => '080-22262355',
        'category' => 'Manufacturing',
        'city' => 'Bengaluru',
        'requirement' => 'B2B Sourcing requirement: Looking for specialized offset and digital printing partners for 2026 event season.',
        'source' => 'AI_SCOUT_EVENT'
    ],
    [
        'name' => 'Bharat Print Expo Pharma Lead',
        'email' => 'sourcing@printbuyer.in',
        'phone' => '044-28521231',
        'category' => 'Manufacturing',
        'city' => 'Chennai',
        'requirement' => 'Bulk packaging requirement for pharmaceutical sector. Looking for ISO certified vendors.',
        'source' => 'AI_SCOUT_CORP'
    ]
];

$inserted = 0;
foreach ($leads as $l) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO public_leads (name, email, phone, category, city, query, source, intent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'buy', NOW())");
        $res = $stmt->execute([
            $l['name'],
            $l['email'],
            $l['phone'],
            $l['category'],
            $l['city'],
            $l['requirement'],
            $l['source']
        ]);
        if ($res && $stmt->rowCount() > 0) $inserted++;
    } catch (Exception $e) {}
}

echo "TOTAL NEW LEADS: $inserted\n";
echo "AI LEAD INJECTOR END\n";
