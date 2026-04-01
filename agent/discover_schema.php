<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=u175452495_biznexus;charset=utf8mb4', 'u175452495_bizuser', 'Biz@9990', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $pdo->query("DESCRIBE users");
    echo "<h2>Users Table Schema</h2><pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

    $stmt = $pdo->query("DESCRIBE business_profiles");
    echo "<h2>Business Profiles Table Schema</h2><pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    $stmt = $pdo->query("DESCRIBE businesses");
    echo "<h2>Businesses Table Schema</h2><pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
