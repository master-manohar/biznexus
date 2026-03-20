<?php
// /includes/email_config.php

function sendEmail($to, $subject, $message, $bcc = '', $replyTo = '') {
    $smtp_host = 'smtp.hostinger.com';
    $smtp_port = 587;
    $smtp_user = 'hello@biznexus.in';
    $smtp_pass = 'Biz@9990';
    $from_name = 'BizNexus AI Network';
    
    $actualReplyTo = !empty($replyTo) ? $replyTo : $smtp_user;

    // Construct headers
    $headers  = "From: =?UTF-8?B?".base64_encode($from_name)."?= <$smtp_user>\r\n";
    $headers .= "Reply-To: $actualReplyTo\r\n";
    if (!empty($bcc)) {
        $headers .= "Bcc: $bcc\r\n";
    }
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $headers .= "Date: " . date("r") . "\r\n";

    // Since we don't have Composer/PHPMailer on Hostinger Shared,
    // we use fsockopen for direct SMTP connection with STARTTLS
    $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 20);
    if (!$socket) {
        error_log("SMTP Error: $errno - $errstr");
        return false;
    }

    $res = fread($socket, 515);

    fputs($socket, "EHLO $smtp_host\r\n");
    $res = fread($socket, 515);

    fputs($socket, "STARTTLS\r\n");
    $res = fread($socket, 515);

    // Enable crypto
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    fputs($socket, "EHLO $smtp_host\r\n");
    $res = fread($socket, 515);

    fputs($socket, "AUTH LOGIN\r\n");
    $res = fread($socket, 515);

    fputs($socket, base64_encode($smtp_user) . "\r\n");
    $res = fread($socket, 515);

    fputs($socket, base64_encode($smtp_pass) . "\r\n");
    $res = fread($socket, 515);

    fputs($socket, "MAIL FROM: <$smtp_user>\r\n");
    $res = fread($socket, 515);

    fputs($socket, "RCPT TO: <$to>\r\n");
    $res = fread($socket, 515);
    
    // If multiple BCCs, loop and add them
    if (!empty($bcc)) {
        $bcc_emails = explode(',', $bcc);
        foreach ($bcc_emails as $b) {
            $b = trim($b);
            if (!empty($b)) {
                fputs($socket, "RCPT TO: <$b>\r\n");
                $res = fread($socket, 515);
            }
        }
    }

    fputs($socket, "DATA\r\n");
    $res = fread($socket, 515);

    fputs($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
    $res = fread($socket, 515);

    fputs($socket, "QUIT\r\n");
    $res = fread($socket, 515);
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
