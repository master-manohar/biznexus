<?php
require_once __DIR__ . '/includes/db.php';
$stmt = $pdo->prepare("SELECT * FROM groups WHERE id = 4");
$stmt->execute();
$group = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($group, JSON_PRETTY_PRINT);
