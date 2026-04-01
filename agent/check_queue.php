<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../db.php';
$tasks = $pdo->query("SELECT * FROM agent_tasks ORDER BY id DESC LIMIT 10")->fetchAll();
echo "<pre>";
print_r($tasks);
echo "</pre>";
助
