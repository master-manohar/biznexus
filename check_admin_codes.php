<?php
require_once __DIR__ . '/db.php';
$stmt = $pdo->query("SELECT id, name, email, refer_code FROM users WHERE email = 'manohar.nch@gmail.com' OR email = 'hello@biznexus.in' OR role = 'admin'");
$users = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($users, JSON_PRETTY_PRINT);
