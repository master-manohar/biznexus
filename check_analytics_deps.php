<?php
require_once 'includes/db.php';
global $pdo;
$tables = ['profile_views', 'connections', 'referrals', 'crm_leads'];
foreach($tables as $t) {
    echo "--- $t ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
?>
