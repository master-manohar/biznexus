<?php
// /smtp_check.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>BizNexus SMTP Diagnostic Tool</h2>";

$host = 'smtp.hostinger.com';
$port = 587;
$user = 'hello@biznexus.in';
$pass = 'Hello@biznexus1';

echo "Testing connection to $host:$port...<br>";

$socket = fsockopen($host, $port, $errno, $errstr, 10);

if (!$socket) {
    echo "<b style='color:red'>FAILED</b>: Connection refused ($errno: $errstr)<br>";
} else {
    echo "<b style='color:green'>SUCCESS</b>: Connected to socket.<br>";
    
    $res = fgets($socket, 515);
    echo "Server Response: " . htmlspecialchars($res) . "<br>";
    
    fputs($socket, "EHLO $host\r\n");
    $res = fgets($socket, 515);
    echo "EHLO Response: " . htmlspecialchars($res) . "<br>";
    
    echo "Starting STARTTLS...<br>";
    fputs($socket, "STARTTLS\r\n");
    $res = fgets($socket, 515);
    echo "STARTTLS Response: " . htmlspecialchars($res) . "<br>";
    
    if (strpos($res, '220') !== false) {
        if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            echo "<b style='color:green'>SUCCESS</b>: TLS Encryption enabled.<br>";
            
            fputs($socket, "EHLO $host\r\n");
            $res = fgets($socket, 515);
            
            fputs($socket, "AUTH LOGIN\r\n");
            $res = fgets($socket, 515);
            
            fputs($socket, base64_encode($user) . "\r\n");
            $res = fgets($socket, 515);
            
            fputs($socket, base64_encode($pass) . "\r\n");
            $res = fgets($socket, 515);
            
            if (strpos($res, '235') !== false) {
                echo "<b style='color:green'>SUCCESS</b>: Authentication Successful!<br>";
            } else {
                echo "<b style='color:red'>FAILED</b>: Authentication failed. Response: " . htmlspecialchars($res) . "<br>";
            }
        } else {
            echo "<b style='color:red'>FAILED</b>: Could not enable crypto.<br>";
        }
    }
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
}

echo "<br><hr>Check complete.";
?>
