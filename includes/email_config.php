<?php
// /includes/email_config.php

if (!function_exists('get_res')) {
    function get_res($s)
    {
        $full_res = "";
        while ($line = fgets($s, 515)) {
            $full_res .= $line;
            if (isset($line[3]) && $line[3] === ' ')
                break;
        }
        return $full_res;
    }
}

if (!function_exists('send_cmd')) {
    function send_cmd($s, $c)
    {
        fputs($s, $c . "\r\n");
    }
}

function sendEmail($to, $subject, $message, $bcc = '', $replyTo = '')
{
    $smtp_host = 'ssl://smtp.hostinger.com';
    $smtp_port = 465;
    $smtp_user = 'hello@biznexus.in';
    $smtp_pass = 'Biz@9990';
    $from_name = 'BizNexus AI Network';

    $actualReplyTo = !empty($replyTo) ? $replyTo : $smtp_user;

    $headers = "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$smtp_user>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Reply-To: $actualReplyTo\r\n";
    if (!empty($bcc))
        $headers .= "Bcc: $bcc\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Date: " . date("r") . "\r\n";

    $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 20);
    if (!$socket)
        return false;

    $res = get_res($socket);
    if (strpos($res, '220') === false) {
        fclose($socket);
        return false;
    }

    send_cmd($socket, "EHLO smtp.hostinger.com");
    $res = get_res($socket);

    send_cmd($socket, "AUTH LOGIN");
    $res = get_res($socket);

    send_cmd($socket, base64_encode($smtp_user));
    $res = get_res($socket);

    send_cmd($socket, base64_encode($smtp_pass));
    $res = get_res($socket);
    if (strpos($res, '235') === false) {
        fclose($socket);
        return false;
    }

    send_cmd($socket, "MAIL FROM: <$smtp_user>");
    $res = get_res($socket);

    send_cmd($socket, "RCPT TO: <$to>");
    $res = get_res($socket);

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
    if (strpos($res, '250') === false) {
        fclose($socket);
        return false;
    }

    send_cmd($socket, "QUIT");
    fclose($socket);
    return true;
}

function sendBizEmail($to, $to_name, $subject, $body_html)
{
    return sendEmail($to, $subject, $body_html);
}

if (!function_exists('emailTemplate')) {    function emailTemplate($title, $content, $cta_text = "Visit BizNexus.in", $cta_url = "https://biznexus.in")
    {
        return "
    <!DOCTYPE html>
    <html>
    <head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #080810; margin: 0; padding: 0; color: #ffffff !important; }
        .container { max-width: 600px; margin: 30px auto; background-color: #0f0f1a; border: 1px solid #1e1e30; border-radius: 12px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #FFD700, #e6a800); padding: 20px; text-align: center; }
        .header h1 { color: #000 !important; margin: 0; font-size: 24px; font-weight: 800; }
        .content { padding: 30px; line-height: 1.6; color: #ffffff !important; }
        .content h2 { color: #FFD700 !important; margin-top: 0; }
        .content p, .content ul, .content li, .content div { color: #ffffff !important; }
        .footer { background-color: #080810; padding: 20px; text-align: center; font-size: 12px; color: #666688; border-top: 1px solid #1e1e30; }
        .cta-btn { 
            display: block; 
            background: #FFD700; 
            color: #000000 !important; 
            text-decoration: none; 
            padding: 18px 30px; 
            border-radius: 10px; 
            font-weight: 900; 
            margin: 30px 0; 
            text-align: center; 
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
    </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='color:#000 !important;'>⚡ BizNexus</h1>
            </div>
            <div class='content'>
                $content
                <a href='$cta_url' class='cta-btn'>$cta_text</a>
            </div>
            <div class='footer'>
                BizNexus - India's AI-Powered Business Network<br>
                Hostinger | Hyderabad
            </div>
        </div>
    </body>
    </html>
    ";    }
}

function sendH2HWelcomeEmail($email, $name)
{
    $subject = "Welcome to BizNexus! 🤝 It was great meeting you.";
    $html = emailTemplate("Welcome to the Network! 🤝", "
        <p style='color:#ffffff !important; font-size:1.1rem;'>Hi <strong>$name</strong>,</p>
        <p style='color:#ffffff !important;'>It was a pleasure meeting you today at the H2H meeting.</p>
        <p style='color:#ffffff !important;'><strong>BizNexus</strong> is India's premier B2B networking platform, designed to help businesses like yours grow through <strong>AI-matched leads</strong>, <strong>verified trust scores</strong>, and <strong>seamless referrals</strong>.</p>
        
        <div style='background:rgba(255,215,0,0.1); border:1px solid #FFD700; border-radius:10px; padding:15px; margin:20px 0; color:#FFD700 !important;'>
            <strong style='color:#FFD700 !important;'>Direct Support & Assistance:</strong><br>
            Feel free to reach out to me directly for any business queries:<br>
            📞 <strong>+91 7569755529</strong><br>
            📞 <strong>9985985958</strong>
        </div>

        <p style='color:#ffffff !important;'>Click the big highlighter below to explore the portal and get started!</p>
    ", "🚀 OPEN BIZNEXUS.IN", "https://biznexus.in");
    return sendEmail($email, $subject, $html);
}
