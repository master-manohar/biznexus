<?php
session_start();
if(isset($_SESSION["user_id"])){ header("Location: /dashboard/index.php"); exit; }
define("BASE", dirname(__DIR__));
require_once BASE . "/includes/db.php";
require_once BASE . "/includes/functions.php";
$errors=[];
if($_SERVER["REQUEST_METHOD"]==="POST"){
    $name    = trim($_POST["name"]??"");
    $email   = trim($_POST["email"]??"");
    $phone   = trim($_POST["phone"]??"");
    $pass    = $_POST["password"]??"";
    $confirm = $_POST["confirm_password"]??"";
    if(strlen($name)<2)          $errors[]="Full name is required";
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]="Valid email required";
    if(strlen($pass)<6)          $errors[]="Password must be at least 6 characters";
    if($pass!==$confirm)         $errors[]="Passwords do not match";
    if(empty($errors)){
        $st=$pdo->prepare("SELECT id FROM users WHERE email=?");
        $st->execute([$email]);
        if($st->fetch()){
            $errors[]="Email already registered. <a href=\"/auth/login.php\" style=\"color:#FFD700\">Login here</a>";
        } else {
            try{
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmtIns = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, coins, created_at) VALUES (?, ?, ?, ?, 'member', 50, NOW())");
                $stmtIns->execute([$name, $email, $phone, $hash]);
                $uid = $pdo->lastInsertId();
                $_SESSION["user_id"]=$uid;
                $_SESSION["name"]=$name;
                $_SESSION["email"]=$email;
                $_SESSION["role"]="member";
                require_once "../includes/emails/welcome.php";
                sendWelcomeEmail($email, $name);
                header("Location: /dashboard/index.php"); exit;
            } catch(Exception $e){
                $errors[]="Registration failed: ".$e->getMessage();
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register Free - BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{font-family:"Inter",sans-serif}
body{background:#0a0a0f;color:#e0e0f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#13131a;border:1px solid #2a2a3a;border-radius:20px;padding:44px 40px;width:100%;max-width:460px}
.logo{color:#FFD700;font-size:2rem;font-weight:900;text-align:center;margin-bottom:4px}
.tagline{text-align:center;color:#555;font-size:.88rem;margin-bottom:20px}
.coins-box{background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.2);border-radius:10px;padding:10px;text-align:center;margin-bottom:20px;color:#FFD700;font-weight:600;font-size:.88rem}
label{color:#888;font-size:.82rem;display:block;margin-bottom:4px;font-weight:500}
input{background:#0f0f18;border:1.5px solid #2a2a3a;color:#e0e0f0;border-radius:10px;padding:12px 14px;width:100%;font-size:.92rem;outline:none;font-family:"Inter",sans-serif;transition:.2s;margin-bottom:14px}
input:focus{border-color:#FFD700}
input::placeholder{color:#333}
.btn-gold{background:linear-gradient(135deg,#FFD700,#f0a500);color:#000;border:none;border-radius:10px;padding:14px;font-weight:800;width:100%;font-size:1rem;cursor:pointer;margin-top:4px}
.btn-gold:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,215,0,.3)}
.err{background:rgba(255,68,68,.08);border:1px solid rgba(255,68,68,.3);border-radius:10px;padding:14px;margin-bottom:16px;font-size:.85rem;color:#ff8888;line-height:1.8}
.link{text-align:center;margin-top:16px;color:#555;font-size:.85rem}
.link a{color:#FFD700;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="card">
<div class="logo">⚡ BizNexus</div>
<div class="tagline">India's #1 AI Business Networking Platform</div>
<div class="coins-box">🪙 Get 100 VooCoins FREE on joining!</div>
<?php if($errors): ?><div class="err"><?php foreach($errors as $e) echo "<div>• $e</div>"; ?></div><?php endif; ?>
<form method="POST">
<label>Full Name *</label>
<input type="text" name="name" placeholder="Your full name" value="<?=htmlspecialchars($_POST["name"]??"")?>" required>
<label>Email Address *</label>
<input type="email" name="email" placeholder="your@email.com" value="<?=htmlspecialchars($_POST["email"]??"")?>" required>
<label>Phone Number</label>
<input type="tel" name="phone" placeholder="9876543210" value="<?=htmlspecialchars($_POST["phone"]??"")?>">
<label>Password *</label>
<input type="password" name="password" placeholder="Minimum 6 characters" required>
<label>Confirm Password *</label>
<input type="password" name="confirm_password" placeholder="Repeat your password" required>
<button type="submit" class="btn-gold">🚀 Create My Free Account</button>
</form>
<div class="link">Already a member? <a href="/auth/login.php">Login here</a></div>
<div class="link" style="margin-top:8px"><a href="/" style="color:#444">← Back to Home</a></div>
</div>
</body>
</html>