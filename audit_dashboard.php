<?php
require_once 'includes/db.php';
global $pdo;

$tables = ['leads', 'public_leads', 'referrals', 'group_roles', 'marketplace', 'member_badges', 'groups'];
foreach ($tables as $t) {
    echo "Table $t: ";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        echo "OK (" . $stmt->rowCount() . " columns)<br>";
        // Print columns for closer inspection
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $c) echo " - " . $c['Field'] . " (" . $c['Type'] . ")<br>";
    } catch (Exception $e) {
        echo "ERROR: Table missing or inaccessible.<br>";
    }
}
?>
