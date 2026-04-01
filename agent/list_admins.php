<?php
require_once __DIR__ . "/../includes/db.php";
$stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'admin' LIMIT 5");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($admins as $a) {
    echo "ID: {$a['id']} | Name: {$a['name']} | Email: {$a['email']} | Role: {$a['role']}\n";
}
