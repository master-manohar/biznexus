<?php
require_once 'includes/db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )");
    
    // Insert defaults if empty
    $count = $pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn();
    if ($count == 0) {
        $cats = ['IT Services','Manufacturing','Trading','Education','Healthcare','Food & Beverage','Real Estate','Finance','Marketing','Events','Logistics','Construction','Retail','Consulting','Other'];
        $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (name) VALUES (?)");
        foreach($cats as $c) {
            $stmt->execute([$c]);
        }
    }
    echo "Categories table created and seeded.\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
