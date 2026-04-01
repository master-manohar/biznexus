<?php
require_once __DIR__ . '/includes/db.php';
$requests = $pdo->query("SELECT * FROM roadmap_modules WHERE run_request = 1")->fetchAll(PDO::FETCH_ASSOC);
if ($requests) {
    echo "REQUESTS_FOUND:\n";
    foreach ($requests as $r) {
        echo "- " . $r['name'] . " (ID: " . $r['id'] . ")\n";
    }
} else {
    echo "NO_REQUESTS";
}
