<?php
require_once __DIR__ . '/includes/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE email LIKE '%manohar%' OR city LIKE '%Vanasthalipuram%' OR phone LIKE '%98765%'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { echo $e->getMessage(); }
?>
