<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';
// Show users with role field
$stmt = $pdo->query("SELECT id, name, email, role, status FROM users ORDER BY id ASC LIMIT 20");
echo "<pre>";
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
    echo "ID:{$u['id']} | {$u['email']} | role:{$u['role']} | status:{$u['status']}\n";
}
echo "</pre>";
?>
