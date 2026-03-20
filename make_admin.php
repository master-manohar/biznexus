<?php
// Fix admin role and show result
require_once 'includes/db.php';

// Promote manohar.nch@gmail.com to admin
$pdo->exec("UPDATE users SET role = 'admin' WHERE email = 'manohar.nch@gmail.com'");
echo "Updated manohar.nch@gmail.com to role=admin\n";

// Also update sknkalakshetram@gmail.com (user 11) if it exists
$pdo->exec("UPDATE users SET role = 'admin' WHERE email = 'sknkalakshetram@gmail.com'");
echo "Updated sknkalakshetram@gmail.com to role=admin\n";

// Double check
$stmt = $pdo->query("SELECT id, email, role FROM users WHERE role='admin'");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>Current admins:\n";
foreach($admins as $a) echo "ID:{$a['id']} | {$a['email']} | {$a['role']}\n";
echo "</pre>";
?>
