<?php
session_start();
define("BASE", __DIR__);
require_once BASE . "/includes/db.php";
require_once BASE . "/includes_functions.php";

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS meeting_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100),
    business_name VARCHAR(150),
    email VARCHAR(100),
    phone VARCHAR(20),
    category VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Safely try to add column if table was created before this change
try { $pdo->exec("ALTER TABLE meeting_registrations ADD COLUMN business_name VARCHAR(150) AFTER name"); } catch(Exception $e) {}

$errors = [];
$success = false;

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $name          = trim($_POST["name"] ?? "");
    $business_name = trim($_POST["business_name"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $phone         = trim($_POST["phone"] ?? "");
    $category      = trim($_POST["category"] ?? "");
    
    if(strlen($name) < 2) $errors[] = "Full name is required";
    if(strlen($business_name) < 2) $errors[] = "Business name is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required";
    if(empty($phone)) $errors[] = "Mobile number is required";
    if(empty($category)) $errors[] = "Business category is required";
    
    if(empty($errors)){
        // Check if user exists
        $st = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $st->execute([$email]);
        $existing = $st->fetch();
        
        $uid = null;
        if($existing){
            $uid = $existing['id'];
            // Upgrade existing user to silver if they don't have it
            try { $pdo->prepare("UPDATE users SET plan='silver', business_name=? WHERE id=?")->execute([$business_name, $uid]); } catch(Exception $e){}
        } else {
            // Create new user
            try {
                $pass = $phone; // default password is their phone number
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                
                $stmtIns = $pdo->prepare("INSERT INTO users (name, business_name, email, phone, password, role, coins, created_at) VALUES (?, ?, ?, ?, ?, 'member', 100, NOW())");
                $stmtIns->execute([$name, $business_name, $email, $phone, $hash]);
                $uid = $pdo->lastInsertId();
                
                try { $pdo->prepare("UPDATE users SET plan='silver' WHERE id=?")->execute([$uid]); } catch(Exception $e) {}
                
            } catch(Exception $e) {
                // If business_name column fails on older users schema, fall back:
                try {
                $stmtIns = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, coins, created_at) VALUES (?, ?, ?, ?, 'member', 100, NOW())");
                $stmtIns->execute([$name, $email, $phone, $hash]);
                $uid = $pdo->lastInsertId();
                try { $pdo->prepare("UPDATE users SET plan='silver' WHERE id=?")->execute([$uid]); } catch(Exception $e) {}
                } catch(Exception $e2) {
                    $errors[] = "Registration failed: " . $e2->getMessage();
                }
            }
        }
        
        if(empty($errors)){
            // Record in meeting_registrations
            try {
                $stmt = $pdo->prepare("INSERT INTO meeting_registrations (user_id, name, business_name, email, phone, category) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$uid, $name, $business_name, $email, $phone, $category]);
                
                // Auto-login the user so they can access the dashboard
                $_SESSION["user_id"] = $uid;
                $_SESSION["name"] = $name;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = 'member';
                
                // Send custom Promotional Meet Welcome Email (with login credentials)
                try {
                    require_once BASE . "/includes/emails/welcome_meet.php";
                    sendWelcomeMeetEmail($email, $name, $phone);
                } catch(Exception $e) {
                    // Ignore email failures so it doesn't break the registration flow
                }
                
                $success = true;
            } catch(Exception $e) {
                $errors[] = "Failed to record meeting registration: " . $e->getMessage();
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Free Business Meet Registration - BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{font-family:"Inter",sans-serif}
body{background:#0a0a0f;color:#e0e0f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px; background-image: radial-gradient(circle at top right, rgba(255,215,0,0.1), transparent 40%), radial-gradient(circle at bottom left, rgba(68,136,255,0.1), transparent 40%);}
.card{background:rgba(19,19,26,0.8); backdrop-filter: blur(10px); border:1px solid #2a2a3a;border-radius:20px;padding:44px 40px;width:100%;max-width:500px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);}
.logo{color:#FFD700;font-size:2.2rem;font-weight:900;text-align:center;margin-bottom:10px}
.tagline{text-align:center;color:#c0c0d8;font-size:1rem;margin-bottom:20px; font-weight:600;}
.coins-box{background:linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,140,0,0.1));border:1px solid rgba(255,215,0,.3);border-radius:12px;padding:15px;text-align:center;margin-bottom:25px;color:#FFD700;font-weight:700;font-size:1rem;}
label{color:#8888aa;font-size:.85rem;display:block;margin-bottom:6px;font-weight:600;}
input, select{background:#0d0d16;border:1.5px solid #2a2a3a;color:#fff;border-radius:10px;padding:14px;width:100%;font-size:1rem;outline:none;transition:.2s;margin-bottom:18px}
input:focus, select:focus{border-color:#FFD700; box-shadow: 0 0 0 3px rgba(255,215,0,0.1);}
input::placeholder{color:#444;}
.btn-gold{background:linear-gradient(135deg,#FFD700,#f0a500);color:#000;border:none;border-radius:10px;padding:16px;font-weight:800;width:100%;font-size:1.1rem;cursor:pointer;margin-top:10px; transition: transform 0.2s, box-shadow 0.2s;}
.btn-gold:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,215,0,.3)}
.err{background:rgba(255,68,68,.08);border:1px solid rgba(255,68,68,.3);border-radius:10px;padding:14px;margin-bottom:20px;font-size:.9rem;color:#ff8888;line-height:1.6}
.success-card { text-align:center; }
.wa-btn { background: #25D366; color: #fff; padding: 16px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; display: block; text-decoration: none; margin-top: 20px; transition: 0.2s; box-shadow: 0 8px 24px rgba(37,211,102,0.3); }
.wa-btn:hover { background: #1ebe57; color: #fff; transform: translateY(-2px); box-shadow: 0 12px 30px rgba(37,211,102,0.4); }
</style>
</head>
<body>
<div class="card">
<div class="logo">⚡ BizNexus</div>
<div class="tagline">India's 1st AI-Powered Networking Platform</div>

<?php if($success): ?>
    <div class="success-card">
        <div style="font-size:4rem; margin-bottom:10px;">🎉</div>
        <h3 style="color:#00e87a; font-weight:800; margin-bottom:10px;">Registration Successful!</h3>
        <p style="color:#c0c0d8; font-size:1rem; margin-bottom:20px;">
            You have successfully registered for the upcoming Business Meet. We've also upgraded your BizNexus account to the <strong>Silver Package for FREE</strong>!
        </p>
        <?php if(!isset($existing)): ?>
            <p style="color:#8888aa; font-size:0.85rem; margin-bottom:25px; background:rgba(0,0,0,0.2); padding:10px; border-radius:8px;">
                <strong>Login Details:</strong><br>
                Email: <?= htmlspecialchars($email) ?><br>
                Password: <?= htmlspecialchars($phone) ?>
            </p>
        <?php endif; ?>
        
        <h5 style="color:#FFD700; margin-bottom:15px; font-weight:700;">Final Step to Secure Your Spot:</h5>
        <a href="https://chat.whatsapp.com/JalVsmbMNNxALFP3FLKBk6" class="wa-btn" target="_blank" onclick="setTimeout(()=>window.location.href='/dashboard/index.php', 2000);">
            Join the WhatsApp Group Now 👇
        </a>
        <p style="color:#666; font-size:0.85rem; margin-top:15px;">You will be automatically redirected to your BizNexus Dashboard after joining.</p>
    </div>
<?php else: ?>
    <div class="coins-box">
        🚀 FREE UPCOMING BUSINESS MEET<br>
        <span style="font-size:0.85rem; color:#fff; font-weight:500;">Register below & get Silver Package FREE</span>
    </div>

    <?php if($errors): ?><div class="err"><?php foreach($errors as $e) echo "<div>• $e</div>"; ?></div><?php endif; ?>

    <form method="POST">
        <label>Full Name *</label>
        <input type="text" name="name" placeholder="E.g., Rahul Sharma" value="<?=htmlspecialchars($_POST["name"]??"")?>" required>
        
        <label>Business / Company Name *</label>
        <input type="text" name="business_name" placeholder="E.g., Sharma Event Planners" value="<?=htmlspecialchars($_POST["business_name"]??"")?>" required>
        
        <label>Email Address *</label>
        <input type="email" name="email" placeholder="you@company.com" value="<?=htmlspecialchars($_POST["email"]??"")?>" required>
        
        <label>Mobile Number (WhatsApp) *</label>
        <input type="tel" name="phone" placeholder="9876543210" value="<?=htmlspecialchars($_POST["phone"]??"")?>" pattern="[0-9]{10}" title="10 digit mobile number" required>
        
        <label>Business Category *</label>
        <?php 
        $categories = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
        ?>
        <select name="category" required style="background:#0d0d16; border:1.5px solid #2a2a3a; color:#fff; border-radius:10px; padding:14px; width:100%; font-size:1rem; outline:none; transition:.2s; margin-bottom:18px;">
            <option value="">Select your category...</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($_POST["category"]??"") === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn-gold">Secure My Spot & Claim Free Silver 🚀</button>
    </form>
    
    <div style="text-align:center; color:#555; font-size:0.8rem; margin-top:20px;">
        ✅ Real Leads. ✅ AI-Driven Smart Matching. ✅ Growth-Focused Founder Network.
    </div>
<?php endif; ?>
</div>
</body>
</html>
