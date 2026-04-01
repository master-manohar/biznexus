<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->prepare("SELECT id, name, email, role, group_id, group_role FROM users WHERE id = 2913");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($user, JSON_PRETTY_PRINT);
