<?php
$htaccessPath = dirname(__DIR__) . '/.htaccess';

$rule = "\n# BizNexus SEO Engine\nRewriteEngine On\nRewriteRule ^find/([^/]+)/([^/]+)/?$ find_category.php?category=$1&city=$2 [L,QSA]\n";

$current = file_exists($htaccessPath) ? file_get_contents($htaccessPath) : "";

if (strpos($current, 'find_category.php') === false) {
    if (file_put_contents($htaccessPath, $rule, FILE_APPEND) !== false) {
        echo "SEO rules appended to .htaccess successfully.";
    } else {
        echo "Failed to write to .htaccess. Check permissions.";
    }
} else {
    echo "SEO rules already exist.";
}
?>
