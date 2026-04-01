<?php
/**
 * agent/import_prospects.php
 * Purpose: Manual CSV upload for marketing leads.
 */
session_start();
require_once __DIR__ . '/../../db.php';

// Auth check (basic for now)
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

$message = "";

if (isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    $rowCount = 0;
    
    // Skip header
    fgetcsv($handle);
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $name = $data[0] ?? "";
        $email = $data[1] ?? "";
        $bizName = $data[2] ?? "";
        $cat = $data[3] ?? "";
        $city = $data[4] ?? "";
        
        if (!empty($email)) {
            $stmt = $pdo->prepare("INSERT INTO marketing_prospects (name, email, business_name, category, city, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$name, $email, $bizName, $cat, $city]);
            $rowCount++;
        }
    }
    fclose($handle);
    $message = "✅ Successfully imported $rowCount leads.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import Prospects - BizNexus</title>
    <style>
        body { font-family: sans-serif; background: #0a0a0f; color: #fff; padding: 40px; }
        .card { background: #13131a; border: 1px solid #2a2a3a; padding: 30px; border-radius: 12px; max-width: 500px; margin: auto; }
        input[type=file] { margin-bottom: 20px; display: block; }
        .btn { background: #FFD700; color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .msg { color: #00ff88; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>📂 Import Marketing Leads</h2>
        <p style="color:#888;">Upload a CSV file with columns: <b>Name, Email, Business Name, Category, City</b></p>
        
        <?php if ($message): ?><div class="msg"><?= $message ?></div><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit" class="btn">Start Import</button>
        </form>
        <br>
        <a href="../superadmin.php?s=agents" style="color:#4488ff; text-decoration:none;">← Back to Agent Command Center</a>
    </div>
</body>
</html>
