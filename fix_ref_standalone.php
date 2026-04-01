<?php
$host='localhost';$db='u175452495_biznexus';$user='u175452495_bizuser';$pass='Biz@9990';
try{
    $pdo=new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4",$user,$pass,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC));
    $stmt = $pdo->query("SELECT id, name FROM users WHERE refer_code IS NULL OR refer_code = '' OR refer_code = 'BIZNEXUS'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "FOUND: " . count($users) . "\n";
    foreach($users as $u) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $u['name']), 0, 4)) . $u['id'] . rand(10,99);
        $pdo->prepare("UPDATE users SET refer_code = ? WHERE id = ?")->execute([$code, $u['id']]);
        echo "SET: " . $u['name'] . " -> " . $code . "\n";
    }
}catch(PDOException $e){
    echo "ERROR: " . $e->getMessage();
}
echo "\nFINISHED";
