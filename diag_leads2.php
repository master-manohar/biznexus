<?php
require_once __DIR__.'/includes/db.php';
header('Content-Type: text/plain');
echo "=== public_leads ===\n";
$c=$pdo->query("DESCRIBE public_leads")->fetchAll(PDO::FETCH_ASSOC);
foreach($c as $r) echo $r['Field']." | ".$r['Type']."\n";
echo "\n=== Sample row ===\n";
$r=$pdo->query("SELECT * FROM public_leads LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if($r) { foreach($r as $k=>$v) echo "$k: $v\n"; }
