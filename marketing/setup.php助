<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host='localhost';$db='u175452495_biznexus';$user='u175452495_bizuser';$pass='Biz@9990';
try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS marketing_prospects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200),
        business_name VARCHAR(200),
        email VARCHAR(200) UNIQUE,
        category VARCHAR(100),
        city VARCHAR(100),
        status ENUM('pending', 'sent', 'replied', 'bounced') DEFAULT 'pending',
        sent_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(status),
        INDEX(category),
        INDEX(city)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "MARKETING_DB_READY";
} catch (Exception $e) {
    echo "MARKETING_DB_ERROR: " . $e->getMessage();
}
助
