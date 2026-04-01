<?php
// agent/diag_schema_v2.php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

$res = $pdo->query("DESCRIBE public_leads");
while($r = $res->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
