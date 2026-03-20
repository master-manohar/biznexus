<?php
// /includes/email_config.php

function sendEmail($to, $subject, $message, $bcc = '', $replyTo = '') {
    $smtp_host = 'ssl://smtp.hostinger.com';
    $smtp_port = 465;
    $smtp_user = 'hello@biznexus.in';
    $smtp_pass = 'Biz@9990';
    $from_name = 'BizNexus AI Network';
    
    $actualReplyTo = !empty($replyTo) ? $replyTo : $smtp_user;

    // ... (headers same)
    $headers  = "From: =?UTF-8?B?".base64_encode($from_name)."?= <$smtp_user>\r\n";
    $headers .= "Reply-To: $actualReplyTo\r\n";
    if (!empty($bcc)) {
        $headers .= "Bcc: $bcc\r\n";
    }
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $headers .= "Date: " . date("r") . "\r\n";

    $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 20);
    if (!$socket) {
        $error_log = date('Y-m-d H:i:s') . " | Connection Fail: $errno - $errstr\n";
        file_put_contents(__DIR__ . '/../reset_log.txt', $error_log, FILE_APPEND);
        return false;
    }

    function get_res($s) {
        $full_res = "";
        while ($line = fgets($s, 515)) {
            $full_res .= $line;
            $log = "SMTP RECV: " . trim($line) . "\n";
            file_put_contents(__DIR__ . '/../reset_log.txt', $log, FILE_APPEND);
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $full_res;
    }

    function send_cmd($s, $c) {
        $log = "SMTP SEND: " . trim($c) . "\n";
        file_put_contents(__DIR__ . '/../reset_log.txt', $log, FILE_APPEND);
        fputs($s, $c . "\r\n");
    }

    $res = get_res($socket);
    if (strpos($res, '220') === false) { fclose($socket); return false; }

    send_cmd($socket, "EHLO smtp.hostinger.com");
    $res = get_res($socket);

    send_cmd($socket, "AUTH LOGIN");
    $res = get_res($socket);

    send_cmd($socket, base64_encode($smtp_user));
    $res = get_res($socket);

    send_cmd($socket, base64_encode($smtp_pass));
    $res = get_res($socket);
    if (strpos($res, '235') === false) { fclose($socket); return false; }

    send_cmd($socket, "MAIL FROM: <$smtp_user>");
    $res = get_res($socket);

    send_cmd($socket, "RCPT TO: <$to>");
    $res = get_res($socket);
    
    // ... (rest same, omitting bcc for brevity in target)
    if (!empty($bcc)) {
        $bcc_emails = explode(',', $bcc);
        foreach ($bcc_emails as $b) {
            $b = trim($b);
            if (!empty($b)) {
                send_cmd($socket, "RCPT TO: <$b>");
                $res = get_res($socket);
            }
        }
    }

    send_cmd($socket, "DATA");
    $res = get_res($socket);

    fputs($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
    $res = get_res($socket);
    if (strpos($res, '250') === false) { fclose($socket); return false; }

    send_cmd($socket, "QUIT");
    fclose($socket);

    return true;
}

function emailTemplate($title, $content) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #080810; margin: 0; padding: 0; color: #e8e8f0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #0f0f1a; border: 1px solid #1e1e30; border-radius: 12px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #FFD700, #e6a800); padding: 20px; text-align: center; }
        .header h1 { color: #000; margin: 0; font-size: 24px; font-weight: 800; }
        .content { padding: 30px; line-height: 1.6; }
        .content h2 { color: #FFD700; margin-top: 0; }
        .footer { background-color: #080810; padding: 20px; text-align: center; font-size: 12px; color: #666688; border-top: 1px solid #1e1e30; }
        .btn { display: inline-block; background: #FFD700; color: #000; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; margin-top: 20px; }
    </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⚡ BizNexus</h1>
            </div>
            <div class='content'>
                $content
            </div>
            <div class='footer'>
                BizNexus - India's AI-Powered Business Network<br>
                Hostinger | Hyderabad
            </div>
        </div>
    </body>
    </html>
    ";
}
