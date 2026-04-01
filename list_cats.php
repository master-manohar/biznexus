<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');
try {
    $cats = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    echo "CATEGORIES:\n" . implode("\n", $cats);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\nFALLBACK:\nBusiness Services\nConstruction\nDigital Marketing\nEducation\nFinance\nHealthcare\nManufacturing\nReal Estate\nRetail\nTechnology";
}
?>
