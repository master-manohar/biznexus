<?php
require_once 'includes/db.php';
$q = $pdo->query("SHOW FULL COLUMNS FROM public_leads");
echo "<h2>public_leads:</h2><pre>";
print_r($q->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

$q2 = $pdo->query("SHOW FULL COLUMNS FROM referrals");
echo "<h2>referrals:</h2><pre>";
print_r($q2->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
