<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Starting Lead Mover AI Agent...\n";

try {
    require_once __DIR__ . '/../includes/db.php';
    echo "DEBUG: db.php loaded.\n";
    require_once __DIR__ . '/../includes_functions.php';
    echo "DEBUG: includes_functions.php loaded.\n";
    
    // ... Copy of the logic but with more echo ...
    
    // 1. AI ENRICHMENT
    $enrichStmt = $pdo->query("SELECT id, query, category FROM public_leads WHERE ai_strategy IS NULL LIMIT 1");
    $toEnrich = $enrichStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "DEBUG: Found " . count($toEnrich) . " leads to enrich.\n";
    
    // 2. LEAD MOVEMENT
    $staleLeads = $pdo->query("SELECT id FROM public_leads WHERE status IN ('new','open') AND assigned_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND (claimed_by_member_id != 0 AND claimed_by_member_id IS NOT NULL)")->fetchAll(PDO::FETCH_ASSOC);
    echo "DEBUG: Found " . count($staleLeads) . " stale leads.\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
echo "DEBUG: Finished.\n";
