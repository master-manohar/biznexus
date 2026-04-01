<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Universal Diagnostic</h3>";
echo "CUR_DIR: " . __DIR__ . "<br>";
echo "DB_PHP_PATH: " . realpath(__DIR__ . '/../db.php') . "<br>";
echo "DB_PHP_EXISTS: " . (file_exists(__DIR__ . '/../db.php') ? 'YES' : 'NO') . "<br>";

if (file_exists(__DIR__ . '/../db.php')) {
    try {
        require_once __DIR__ . '/../db.php';
        echo "PDO_STATE: " . (isset($pdo) ? 'PDO SET' : 'PDO NOT SET') . "<br>";
        if (isset($pdo)) {
            $v = $pdo->query("SELECT VERSION()")->fetchColumn();
            echo "SQL_VERSION: $v<br>";
        }
    } catch (Exception $e) {
        echo "ERR: " . $e->getMessage();
    }
}
助
