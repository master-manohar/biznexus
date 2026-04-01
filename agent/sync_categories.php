<?php
require_once __DIR__ . '/../includes/db.php';

// 1. Ensure required categories exist correctly
$categories_to_ensure = [
    'Education' => 'Higher Education', // Rename from if exists
    'Dance' => null,
    'Music' => null,
    'Healthcare' => null,
    'Technology' => null,
    'Consulting' => null,
    'Retail' => null,
    'Real Estate' => null,
    'Finance' => null,
    'Manufacturing' => null,
    'Digital Marketing' => null
];

foreach ($categories_to_ensure as $cat => $old) {
    // Check if it exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$cat]);
    if (!$stmt->fetch()) {
        if ($old) {
            // Check if old name exists and rename
            $stmt_old = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt_old->execute([$old]);
            $oid = $stmt_old->fetchColumn();
            if ($oid) {
                $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$cat, $oid]);
                echo "Renamed '$old' to '$cat'.\n";
                // Update existing business profiles
                $pdo->prepare("UPDATE business_profiles SET category = ? WHERE category = ?")->execute([$cat, $old]);
                echo "Updated business_profiles from '$old' to '$cat'.\n";
                continue;
            }
        }
        // Insert new
        $slug = strtolower(str_replace(' ', '-', $cat));
        $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")->execute([$cat, $slug]);
        echo "Inserted new category: '$cat'.\n";
    } else {
        echo "Category '$cat' already exists.\n";
    }
}

echo "\n--- FINAL CATEGORY LIST ---\n";
$list = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
print_r($list);
?>
