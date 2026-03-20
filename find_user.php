<?php
require_once "includes/db.php";
$email = "manohar.nch@gmail.com";
try {
    $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "User Found:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Password (Raw/Hash): " . $user['password'] . "\n";
    } else {
        echo "User not found for email: $email\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
