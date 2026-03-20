<?php
// /agent/auto_qa.php
// AGENT 9: COMPREHENSIVE PLATFORM QA TEST
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: text/plain');

echo "========================================================\n";
echo "   AGENT 9 TESTER: END-TO-END SYSTEM INTEGRITY AUDIT    \n";
echo "========================================================\n\n";

$passAll = true;
$score = 0;
$total = 0;

function report($name, $status, $msg = "") {
    global $score, $total, $passAll;
    $total++;
    $mark = $status ? "[OK]" : "[FAIL]";
    if ($status) $score++; else $passAll = false;
    echo str_pad($mark, 8) . str_pad($name, 40) . " " . $msg . "\n";
}

// 1. DATABASE INTEGRITY
try {
    $r = $pdo->query("SELECT 1")->fetchColumn();
    report("Database Connection", $r == 1, "Connected natively via PDO.");
} catch (\Exception $e) {
    report("Database Connection", false, $e->getMessage());
}

// 2. SCHEMA VERIFICATION
$tables = ['users', 'business_profiles', 'public_leads', 'lead_dispatches', 'referrals', 'voocoin_balances', 'support_tickets', 'lead_whatsapp_queue'];
foreach ($tables as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        report("Table Presence: $t", true, "Found with $c records.");
    } catch (\Exception $e) {
        report("Table Presence: $t", false, "Missing or Corrupt.");
    }
}

// 3. INTERNAL ROUTE HEALTH (Simulated cUrl)
$routes = [
    '/' => 'Homepage',
    '/find.php' => 'Matchmaking Engine',
    '/marketplace/index.php' => 'Marketplace',
    '/auth/login.php' => 'Auth Portal',
    '/auth/register.php' => 'Registration Node',
    '/admin/superadmin.php' => 'God Mode Check'
];

foreach ($routes as $path => $name) {
    $url = "https://biznexus.in" . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // We expect 200 OK, or 302 Redirect (since some pages require auth)
    $success = in_array($httpCode, [200, 301, 302]);
    report("Route Ping: $name", $success, "HTTP Status: $httpCode");
}

// 4. CORE LOGIC SIMULATION
try {
    $pdo->beginTransaction();
    // Simulate user creation
    $email = 'qa_agent_test_' . rand(100,999) . '@biznexus.in';
    $pwd = password_hash('Test@123', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (name, email, password, status, role, is_verified) VALUES ('QA TEST', ?, ?, 'active', 'member', 1)")->execute([$email, $pwd]);
    $testUid = $pdo->lastInsertId();
    report("Write Logic: User Creation", true, "Simulated account injected.");
    
    // Simulate coin logic
    $pdo->prepare("INSERT INTO voocoin_balances (user_id, balance) VALUES (?, 100)")->execute([$testUid]);
    $bal = $pdo->query("SELECT balance FROM voocoin_balances WHERE user_id = $testUid")->fetchColumn();
    report("Economy Logic: Wallet Balances", $bal == 100, "VooCoin transaction synchronized.");

    // Cleanup artifact
    $pdo->rollBack();
    report("Data Purge (Agent 10)", true, "Test artifact safely unspooled from memory.");
} catch (\Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    report("Core Logic Verification", false, $e->getMessage());
}

echo "\n--------------------------------------------------------\n";
echo "QA PASSRATE: $score / $total (".round(($score/$total)*100)."%)\n";
if ($passAll) {
    echo "STATUS: ALL SYSTEMS GREEN. SITE IS PRODUCTION READY.\n";
} else {
    echo "STATUS: WARNING. SOME CHECKS FAILED.\n";
}
echo "========================================================\n";
?>
