<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT email, name FROM users WHERE status = 'active' LIMIT 5");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
?>
