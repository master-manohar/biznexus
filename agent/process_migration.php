<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';

// Auth Check (Superadmin Only)
$uid = (int)($_SESSION['user_id'] ?? 0);
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    die("Unauthorized Access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $networkType = $_POST['network_type'] ?? 'Other';
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Skip header
        fgetcsv($handle, 1000, ",");
        
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $bizName = trim($data[0] ?? '');
            $person  = trim($data[1] ?? '');
            $email   = trim($data[2] ?? '');
            $phone   = trim($data[3] ?? '');
            $source  = trim($data[4] ?? $networkType);
            $notes   = trim($data[5] ?? '');
            
            if (!$email && !$phone) continue;

            // Check if already exists in prospects or users
            $exists = $pdo->prepare("SELECT id FROM marketing_prospects WHERE email = ? OR business_name = ?");
            $exists->execute([$email, $bizName]);
            if ($exists->fetchColumn()) continue;

            // Insert as high-trust prospect
            $stmt = $pdo->prepare("INSERT INTO marketing_prospects (business_name, contact_person, email, phone, category, source_network, bsr_boost_applied, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, 'pending', NOW())");
            $stmt->execute([$bizName, $person, $email, $phone, 'Migration', $source]);
            $count++;
            
            // Log the action
            $pdo->prepare("INSERT INTO agent_logs (agent_name, action, detail, created_at) VALUES (?,?,?,NOW())")->execute([
                'Migration Hub', 'import', "Imported $bizName ($source) via Migration Tool"
            ]);
        }
        fclose($handle);
        
        header("Location: ../superadmin.php?s=migration&msg=Successfully+Imported+$count+Members+from+$networkType");
        exit;
    }
}
echo "Invalid Request.";
?>
