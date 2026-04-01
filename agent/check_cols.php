<?php
require_once dirname(__DIR__) . '/includes/db.php';
$stmt = $pdo->query("DESCRIBE public_leads");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
