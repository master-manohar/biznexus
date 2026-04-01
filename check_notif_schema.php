<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $pdo->query("DESCRIBE notifications");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
