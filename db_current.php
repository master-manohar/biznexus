<?php
$host='localhost';$db='u175452495_biznexus';$user='u175452495_bizuser';$pass='Biz@9990';
try{
    $pdo=new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4",$user,$pass,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC));
}catch(PDOException $e){
    die(json_encode(array('error'=>'DB failed')));
}
?>