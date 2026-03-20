<?php
require_once 'includes/db.php';
global $pdo;
$stmt = $pdo->query("DESCRIBE users");
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($fields as $f) {
    if($f['Field'] === 'group_id') { echo "EXISTS"; exit; }
}
echo "MISSING";
?>
