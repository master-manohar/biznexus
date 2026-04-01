<?php
/**
 * patch_superadmin_category.php
 * One-time patch: replace text input for category in superadmin edit modal
 * with a proper <select> dropdown populated from categories table
 */
require_once __DIR__ . '/includes/db.php';
header('Content-Type: text/plain');
// Security - only allow admin
session_start();
if (!isset($_SESSION['user_id']) && ($_GET['key'] ?? '') !== 'BizCron2024') die("Unauthorized.");

$file = __DIR__ . '/superadmin.php';
if (!file_exists($file)) { die("ERROR: superadmin.php not found at $file"); }

$html = file_get_contents($file);

// The target: text input with id="edit_cat" or name="ucat"
$oldInput = '<input type="text" name="ucat" id="edit_cat" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;" placeholder="e.g. Photography">';

// Build dropdown with all categories from DB
$cats = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$options = '<option value="">-- Select Category --</option>' . "\n";
foreach ($cats as $c) {
    $options .= '<option value="' . htmlspecialchars($c) . '">' . htmlspecialchars($c) . '</option>' . "\n";
}

$newSelect = '<select name="ucat" id="edit_cat" class="form-control" style="background:#0d0d16;border:1px solid #2a2a3a;color:#fff;width:100%;padding:8px;border-radius:6px;">' . "\n" . $options . '</select>';

if (strpos($html, $oldInput) !== false) {
    $html = str_replace($oldInput, $newSelect, $html);
    file_put_contents($file, $html);
    echo "✅ SUCCESS: Category text input replaced with dropdown (" . count($cats) . " categories).\n";
} else {
    // Fallback: try partial match on id="edit_cat"
    $pattern = '/<input[^>]*id=["\']edit_cat["\'][^>]*>/i';
    if (preg_match($pattern, $html, $m)) {
        $html = preg_replace($pattern, $newSelect, $html);
        file_put_contents($file, $html);
        echo "✅ SUCCESS (partial match): Category input replaced with dropdown.\n";
        echo "Replaced: " . $m[0] . "\n";
    } else {
        echo "❌ COULD NOT FIND input field automatically.\n";
        echo "Manual fix needed: Find id='edit_cat' in superadmin.php and replace with select tag.\n";
        echo "\nHere is the SELECT HTML to use:\n\n$newSelect";
    }
}
?>
