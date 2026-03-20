<?php
// /pages/contact.php
session_start();
require_once '../includes/email_config.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $issue = htmlspecialchars($_POST['issue'] ?? '');
    
    // Send email to hello@biznexus.in
    $subject = "Support Request from $name";
    $body = "<h3>New Support Request</h3>
             <p><strong>Name:</strong> $name</p>
             <p><strong>Email:</strong> $email</p>
             <p><strong>Issue:</strong><br>" . nl2br($issue) . "</p>";
    
    // Save to CSV for easy tracking
    $csvFile = __DIR__ . '/../admin/tickets.csv';
    $isNewFile = !file_exists($csvFile);
    $fp = fopen($csvFile, 'a');
    if ($isNewFile) fputcsv($fp, ['Date', 'Name', 'Email', 'Issue']);
    fputcsv($fp, [date('Y-m-d H:i:s'), $name, $email, $issue]);
    fclose($fp);

    // Send the email (Reply-to is the user's email)
    if(sendEmail('hello@biznexus.in', $subject, $body, '', $email)) {
        $msg = "<div class='alert alert-success' style='background: rgba(0,255,136,0.1); border: 1px solid #00ff88; color: #00ff88;'>Thank you. Your support ticket has been logged. We will contact you at $email shortly.</div>";
    } else {
        $msg = "<div class='alert alert-danger' style='background: rgba(255,68,85,0.1); border: 1px solid #ff4455; color: #ff4455;'>Sorry, there was an issue sending your message. Please try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: #06060a; color: #e0e0f0; font-family: 'DM Sans', sans-serif; }
        .nav { background: rgba(6,6,10,.95); border-bottom: 1px solid rgba(255,215,0,.1); padding: 13px 0; }
        .logo { font-family: 'Syne', sans-serif; font-size: 1.35rem; font-weight: 800; color: #FFD700; text-decoration: none; }
        .logo span { color: #00ff88; }
        .contact-box { background: #0e0e16; border: 1px solid #1e1e2e; border-radius: 14px; padding: 40px; }
        .form-control { background: #06060a; border: 1px solid #2a2a3a; color: #fff; padding: 12px; }
        .form-control:focus { background: #06060a; border-color: #FFD700; color: #fff; box-shadow: none; }
        .btn-gold { background: #FFD700; color: #000; font-weight: 700; border: none; padding: 12px 30px; border-radius: 8px; }
    </style>
</head>
<body>
    <nav class="nav">
      <div class="container d-flex align-items-center justify-content-between">
        <a href="/" class="logo">Biz<span>Nexus</span></a>
        <div><a href="/" class="btn btn-outline-light btn-sm rounded-pill px-3">← Back Home</a></div>
      </div>
    </nav>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="contact-box">
                    <h2 style="font-family:'Syne',sans-serif;font-weight:800;color:#FFD700;margin-bottom:10px;">Contact Support</h2>
                    <p class="text-muted mb-4">Email us at <strong>hello@biznexus.in</strong> or fill out the form below to open a support ticket. We handle all inquiries digitally.</p>
                    
                    <?= $msg ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted">Your Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted">Describe your issue in detail</label>
                            <textarea name="issue" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-gold w-100">Submit Ticket</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
