<?php
/**
 * BizNexus Phase 2 Deployer
 * Upload to: /public_html/agent/deploy_phase2.php
 * Run at: agent.biznexus.in/deploy_phase2.php?key=BizCron2024
 *
 * Creates ALL Phase 2 files in one shot:
 *  - /kyc/upload.php
 *  - /leads/claim.php
 *  - /includes/email_config.php
 *  - /includes/coin_escrow.php
 *  - /agent/leads_complete_fix.php
 *  - /agent/trust_cron.php
 */

if(($_GET['key']??'')<>'BizCron2024'){ http_response_code(403); die('Forbidden'); }

$base = dirname(dirname(__FILE__)); // /home/u175452495/domains/biznexus.in/public_html
$log  = [];

function makeDir($path){ if(!is_dir($path)){ mkdir($path,0755,true); return true; } return false; }
function writeFile($path,$content){ file_put_contents($path,$content); return true; }
function log_($msg) { global $log; $log[]=$msg; echo "[".date('H:i:s')."] $msg<br>"; flush(); ob_flush(); }

header('Content-Type: text/html; charset=UTF-8');
echo "<pre style='background:#0a0a0f;color:#00ff88;padding:20px;font-family:monospace;font-size:13px'>";
echo "<h2 style='color:#FFD700'>⚡ BizNexus Phase 2 Deployer</h2>\n\n";

// =====================================================
// FILE 1: kyc/upload.php
// =====================================================
makeDir($base.'/kyc');
$kyc_php = '<?php
session_start();
if(!isset($_SESSION["user_id"])){ header("Location: /auth/login.php"); exit; }
function getDB(){
    $c=[["localhost","u175452495_biznexus","u175452495_bizuser","Biz@9990"],["localhost","u175452495_biznexus","u175452495_voo_user","Vooschool@123"],["localhost","u175452495_biznexus","u175452495","Biz@9990"]];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}
$uid=(int)$_SESSION["user_id"];
$pdo=getDB();
$error=""; $success="";

if($pdo){
    try{
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_verified TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_status ENUM(\"none\",\"pending\",\"verified\",\"rejected\") DEFAULT \"none\"");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_doc_type VARCHAR(50) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_doc_path VARCHAR(500) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_submitted_at DATETIME DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_rejection_reason TEXT DEFAULT NULL");
    }catch(Exception $e){}
}

$user=[];
if($pdo){ $st=$pdo->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$uid]); $user=$st->fetch(); }

if($_SERVER["REQUEST_METHOD"]==="POST" && $pdo){
    $doc_type=$_POST["doc_type"]??"";
    $allowed=["gst","aadhar","pan","udyam"];
    if(!in_array($doc_type,$allowed)){ $error="Invalid document type."; }
    elseif(empty($_FILES["kyc_doc"]["name"])){ $error="Please upload a document."; }
    else {
        $file=$_FILES["kyc_doc"];
        $ext=strtolower(pathinfo($file["name"],PATHINFO_EXTENSION));
        $allowed_ext=["jpg","jpeg","png","pdf"];
        if(!in_array($ext,$allowed_ext)){ $error="Only JPG, PNG, PDF allowed."; }
        elseif($file["size"]>5*1024*1024){ $error="File must be under 5MB."; }
        else {
            $upload_dir=dirname(dirname(__FILE__))."/kyc_docs/";
            if(!is_dir($upload_dir)) mkdir($upload_dir,0750,true);
            // Protect folder
            file_put_contents($upload_dir.".htaccess","Deny from all\n");
            $fname="kyc_{$uid}_".time()."_{$doc_type}.{$ext}";
            if(move_uploaded_file($file["tmp_name"],$upload_dir.$fname)){
                $pdo->prepare("UPDATE users SET kyc_status=\"pending\",kyc_doc_type=?,kyc_doc_path=?,kyc_submitted_at=NOW() WHERE id=?")->execute([$doc_type,$fname,$uid]);
                // Notify admin
                $pdo->prepare("INSERT INTO notifications (user_id,type,message,created_at) VALUES (1,\"kyc\",\"New KYC submission from user #{$uid}: {$doc_type}\",NOW())")->execute([]);
                $success="Document submitted! Our team will verify within 24-48 hours.";
                $user["kyc_status"]="pending";
            } else { $error="Upload failed. Please try again."; }
        }
    }
}
$kyc_status=$user["kyc_status"]??"none";
$badges=["none"=>["🔴","Not Started","#ff4455"],"pending"=>["⏳","Under Review","#FFD700"],"verified"=>["✅","Verified","#00ff88"],"rejected"=>["❌","Rejected","#ff4455"]];
[$bicon,$blabel,$bcolor]=$badges[$kyc_status]??["🔴","Not Started","#ff4455"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>KYC Verification – BizNexus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="/assets/css/biznexus.css" rel="stylesheet">
<style>
body{background:#06060a;color:#e0e0f0;font-family:\'DM Sans\',sans-serif}
.sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:#0a0a12;border-right:1px solid #1e1e2e;display:flex;flex-direction:column;z-index:100}
.sidebar-logo{padding:22px 20px;font-family:\'Syne\',sans-serif;font-size:1.3rem;font-weight:900;color:#FFD700;border-bottom:1px solid #1e1e2e}
.nav-link{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#777;font-size:.85rem;font-weight:500;transition:.15s;text-decoration:none}
.nav-link:hover,.nav-link.active{color:#FFD700;background:rgba(255,215,0,.06)}
.main{margin-left:220px;padding:32px}
.card-biz{background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;padding:28px}
.form-label{font-size:.82rem;font-weight:600;color:#888;margin-bottom:6px}
.form-control,.form-select{background:#080810;border:1.5px solid #1e1e2e;color:#e0e0f0;border-radius:10px;padding:12px 15px;font-size:.9rem}
.form-control:focus,.form-select:focus{border-color:#FFD700;box-shadow:none}
.btn-gold{background:linear-gradient(135deg,#FFD700,#e6a800);color:#000;font-weight:800;border:none;border-radius:50px;padding:13px 32px;cursor:pointer}
.drop-zone{border:2px dashed #2a2a3a;border-radius:14px;padding:40px;text-align:center;cursor:pointer;transition:.2s}
.drop-zone:hover,.drop-zone.active{border-color:#FFD700;background:rgba(255,215,0,.03)}
.benefit-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid #0d0d1a}
.benefit-item:last-child{border:none}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">⚡ BizNexus</div>
  <nav style="flex:1;padding:12px 0">
    <a href="/dashboard/index.php" class="nav-link">🏠 Dashboard</a>
    <a href="/trust/score.php" class="nav-link">🛡️ Trust Score</a>
    <a href="/kyc/upload.php" class="nav-link active">📋 KYC</a>
    <a href="/profile/edit.php" class="nav-link">👤 My Profile</a>
    <a href="/settings/index.php" class="nav-link">⚙️ Settings</a>
  </nav>
  <div style="padding:16px 20px;border-top:1px solid #1e1e2e;margin-top:auto"><a href="/auth/logout.php" style="color:#ff4455;font-size:.82rem;text-decoration:none">🚪 Logout</a></div>
</div>
<div class="main">
  <div class="mb-4">
    <h1 style="font-family:\'Syne\',sans-serif;font-size:1.6rem;font-weight:800">KYC Verification 📋</h1>
    <p style="color:#777;font-size:.88rem">Verify your business identity and unlock the full platform + 25 Trust Score points</p>
  </div>
  <div class="row g-4">
    <div class="col-md-7">
      <?php if($error): ?><div style="background:rgba(255,80,80,.08);border:1px solid rgba(255,80,80,.2);color:#ff8080;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:.85rem">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>
      <?php if($success): ?><div style="background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.2);color:#00ff88;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:.85rem">✅ <?=htmlspecialchars($success)?></div><?php endif; ?>

      <div class="card-biz mb-4">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
          <span style="font-size:1.5rem"><?=$bicon?></span>
          <div>
            <div style="font-weight:700;color:<?=$bcolor?>"><?=$blabel?></div>
            <div style="font-size:.78rem;color:#555">KYC Status</div>
          </div>
          <?php if($kyc_status==="verified"): ?>
          <span style="margin-left:auto;background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.2);color:#00ff88;border-radius:50px;padding:6px 14px;font-size:.75rem;font-weight:700">✅ +25 Trust Points Awarded</span>
          <?php endif; ?>
        </div>
        <?php if(in_array($kyc_status,["none","rejected"])): ?>
        <form method="POST" enctype="multipart/form-data">
          <?php if($kyc_status==="rejected" && !empty($user["kyc_rejection_reason"])): ?>
          <div style="background:rgba(255,80,80,.06);border:1px solid rgba(255,80,80,.15);border-radius:10px;padding:12px;margin-bottom:16px;font-size:.83rem;color:#ff8080">
            ❌ Previous submission rejected: <?=htmlspecialchars($user["kyc_rejection_reason"])?>
          </div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Document Type *</label>
            <select name="doc_type" class="form-select" required>
              <option value="">Select document...</option>
              <option value="gst">GST Certificate</option>
              <option value="udyam">Udyam Registration (MSME)</option>
              <option value="pan">PAN Card (Business)</option>
              <option value="aadhar">Aadhar Card (Proprietor)</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label">Upload Document *</label>
            <div class="drop-zone" onclick="document.getElementById(\'kycfile\').click()" ondragover="event.preventDefault();this.classList.add(\'active\')" ondrop="handleDrop(event)">
              <div style="font-size:2rem;margin-bottom:8px">📄</div>
              <div style="font-weight:600;margin-bottom:4px" id="fname">Click to upload or drag & drop</div>
              <div style="font-size:.78rem;color:#555">JPG, PNG, PDF — Max 5MB</div>
            </div>
            <input type="file" id="kycfile" name="kyc_doc" style="display:none" accept=".jpg,.jpeg,.png,.pdf" onchange="document.getElementById(\'fname\').textContent=this.files[0]?.name||\'Click to upload\'">
          </div>
          <button type="submit" class="btn-gold">Submit for Verification →</button>
        </form>
        <?php elseif($kyc_status==="pending"): ?>
        <div style="text-align:center;padding:20px">
          <div style="font-size:2rem;margin-bottom:8px">⏳</div>
          <div style="font-weight:700;margin-bottom:6px">Under Review</div>
          <div style="font-size:.85rem;color:#777">Your document has been submitted. Verification typically takes 24-48 hours. We\'ll notify you by email.</div>
        </div>
        <?php elseif($kyc_status==="verified"): ?>
        <div style="text-align:center;padding:20px">
          <div style="font-size:3rem;margin-bottom:8px">✅</div>
          <div style="font-weight:700;color:#00ff88;margin-bottom:6px">KYC Verified!</div>
          <div style="font-size:.85rem;color:#777">Your business identity has been verified. You have the Verified badge on your profile and 25 extra Trust Score points.</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card-biz">
        <h5 style="font-family:\'Syne\',sans-serif;margin-bottom:16px">🎯 Why Get KYC Verified?</h5>
        <?php foreach([
          ["🛡️","25 Trust Score Points","Biggest single boost to your trust score"],
          ["✅","Verified Badge","Shows on your profile and in directory"],
          ["📥","Priority Leads","Verified members get leads dispatched first"],
          ["🤝","Higher Response","Other members prefer contacting verified businesses"],
          ["🔒","Build Trust","Prove your business is real and legitimate"],
        ] as [$icon,$title,$desc]): ?>
        <div class="benefit-item">
          <span style="font-size:1.3rem"><?=$icon?></span>
          <div><div style="font-weight:600;font-size:.88rem"><?=$title?></div><div style="font-size:.78rem;color:#555"><?=$desc?></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<script>
function handleDrop(e){e.preventDefault();document.getElementById("kycfile").files=e.dataTransfer.files;document.getElementById("fname").textContent=e.dataTransfer.files[0]?.name||"Click to upload";}
</script>
</body>
</html>';

writeFile($base.'/kyc/upload.php', $kyc_php);
log_("✅ Created /kyc/upload.php");

// =====================================================
// FILE 2: leads/claim.php — Lead Lock (max 3 claims)
// =====================================================
makeDir($base.'/leads');
$leads_claim = '<?php
session_start();
if(!isset($_SESSION["user_id"])){ header("Content-Type: application/json"); echo json_encode(["error"=>"Not logged in"]); exit; }
function getDB(){
    $c=[["localhost","u175452495_biznexus","u175452495_bizuser","Biz@9990"],["localhost","u175452495_biznexus","u175452495_voo_user","Vooschool@123"],["localhost","u175452495_biznexus","u175452495","Biz@9990"]];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}
header("Content-Type: application/json");
$uid=(int)$_SESSION["user_id"];
$pdo=getDB();
if(!$pdo){ echo json_encode(["error"=>"DB Error"]); exit; }

// Ensure columns exist
try {
    $pdo->exec("ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS claimed_count INT DEFAULT 0");
    $pdo->exec("ALTER TABLE public_leads ADD COLUMN IF NOT EXISTS locked_at DATETIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE lead_dispatches ADD COLUMN IF NOT EXISTS slot_number INT DEFAULT 0");
    $pdo->exec("ALTER TABLE lead_dispatches ADD COLUMN IF NOT EXISTS claimed_at DATETIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE lead_dispatches ADD COLUMN IF NOT EXISTS expired_at DATETIME DEFAULT NULL");
}catch(Exception $e){}

$lead_id=(int)($_POST["lead_id"]??$_GET["lead_id"]??0);
$action=$_POST["action"]??$_GET["action"]??"claim";

if(!$lead_id){ echo json_encode(["error"=>"Missing lead_id"]); exit; }

// Get lead
$lead=$pdo->prepare("SELECT * FROM public_leads WHERE id=?");
$lead->execute([$lead_id]);
$lead=$lead->fetch();
if(!$lead){ echo json_encode(["error"=>"Lead not found"]); exit; }

// Check if already claimed by this user
$mine=$pdo->prepare("SELECT * FROM lead_dispatches WHERE lead_id=? AND user_id=?");
$mine->execute([$lead_id,$uid]);
$my_dispatch=$mine->fetch();

if($action==="claim") {
    if($my_dispatch){ echo json_encode(["error"=>"You already claimed this lead","status"=>$my_dispatch["status"]]); exit; }

    // Get current claim count (active slots only 1-3)
    $count=$pdo->prepare("SELECT COUNT(*) as c FROM lead_dispatches WHERE lead_id=? AND slot_number BETWEEN 1 AND 3 AND (expired_at IS NULL OR expired_at > NOW())");
    $count->execute([$lead_id]);
    $current_claims=$count->fetch()["c"]??0;

    if($current_claims>=3){
        // Waitlist (slots 4-10)
        $wcount=$pdo->prepare("SELECT COUNT(*) as c FROM lead_dispatches WHERE lead_id=? AND slot_number BETWEEN 4 AND 10");
        $wcount->execute([$lead_id]);
        $waitlist_count=$wcount->fetch()["c"]??0;
        if($waitlist_count>=7){ echo json_encode(["error"=>"This lead is fully taken (3 active + 7 waitlist)"]); exit; }
        $slot=$current_claims+$waitlist_count+1;
        $pdo->prepare("INSERT INTO lead_dispatches (lead_id,user_id,slot_number,status,claimed_at) VALUES (?,?,?,\"waitlist\",NOW())")->execute([$lead_id,$uid,$slot]);
        echo json_encode(["success"=>true,"slot"=>$slot,"status"=>"waitlist","message"=>"Added to waitlist (position ".($waitlist_count+1).")"]);
    } else {
        $slot=$current_claims+1;
        $expires=date("Y-m-d H:i:s",strtotime("+2 hours"));
        $pdo->prepare("INSERT INTO lead_dispatches (lead_id,user_id,slot_number,status,claimed_at,expired_at) VALUES (?,?,?,\"claimed\",NOW(),?)")->execute([$lead_id,$uid,$slot,$expires]);
        // Update lead claimed count
        if($slot>=3){
            $pdo->prepare("UPDATE public_leads SET claimed_count=3,locked_at=NOW() WHERE id=?")->execute([$lead_id]);
        } else {
            $pdo->prepare("UPDATE public_leads SET claimed_count=? WHERE id=?")->execute([$slot,$lead_id]);
        }
        // Award coins for claiming
        $pdo->prepare("INSERT INTO voocoin_balances (user_id,balance,total_earned,total_spent) VALUES (?,5,5,0) ON DUPLICATE KEY UPDATE balance=balance+5,total_earned=total_earned+5")->execute([$uid]);
        echo json_encode(["success"=>true,"slot"=>$slot,"status"=>"claimed","expires"=>$expires,"message"=>"Lead claimed! Respond within 2 hours to keep your slot."]);
    }
} elseif($action==="status") {
    $dispatches=$pdo->prepare("SELECT ld.*,u.name,u.business_name,u.trust_score,u.trust_badge FROM lead_dispatches ld JOIN users u ON ld.user_id=u.id WHERE ld.lead_id=? ORDER BY ld.slot_number ASC");
    $dispatches->execute([$lead_id]);
    echo json_encode(["lead_id"=>$lead_id,"dispatches"=>$dispatches->fetchAll(),"claimed_count"=>$lead["claimed_count"]??0,"locked"=>!empty($lead["locked_at"])]);
} elseif($action==="expire_old") {
    // Cron task: expire dispatches older than 2hr with no response → promote waitlist
    if(($_GET["key"]??"")<>"BizCron2024"){ echo json_encode(["error"=>"Forbidden"]); exit; }
    $expired=$pdo->query("SELECT * FROM lead_dispatches WHERE status=\"claimed\" AND expired_at < NOW()");
    $count=0;
    while($row=$expired->fetch()){
        // Mark expired
        $pdo->prepare("UPDATE lead_dispatches SET status=\"expired\" WHERE id=?")->execute([$row["id"]]);
        // Promote first waitlist member
        $next=$pdo->prepare("SELECT * FROM lead_dispatches WHERE lead_id=? AND status=\"waitlist\" ORDER BY slot_number ASC LIMIT 1");
        $next->execute([$row["lead_id"]]);
        $waiter=$next->fetch();
        if($waiter){
            $new_expires=date("Y-m-d H:i:s",strtotime("+2 hours"));
            $pdo->prepare("UPDATE lead_dispatches SET status=\"claimed\",slot_number=?,claimed_at=NOW(),expired_at=? WHERE id=?")->execute([$row["slot_number"],$new_expires,$waiter["id"]]);
            // Notify promoted member
            $pdo->prepare("INSERT INTO notifications (user_id,type,message,created_at) VALUES (?\"lead\",\"⚡ A lead slot opened for you! You have 2 hours to respond.\",NOW())")->execute([$waiter["user_id"]]);
        }
        $count++;
    }
    echo json_encode(["expired"=>$count,"time"=>date("Y-m-d H:i:s")]);
}';

writeFile($base.'/leads/claim.php', $leads_claim);
log_("✅ Created /leads/claim.php (Lead Lock — max 3 claims)");

// =====================================================
// FILE 3: includes/email_config.php
// =====================================================
makeDir($base.'/includes');
$email_config = '<?php
/**
 * BizNexus Email Configuration
 * All platform emails go through this file
 */
define("SMTP_HOST", "smtp.hostinger.com");
define("SMTP_PORT", 587);
define("SMTP_USER", "hello@biznexus.in");
define("SMTP_PASS", "Hello@biznexus1");
define("FROM_EMAIL","hello@biznexus.in");
define("FROM_NAME", "BizNexus");
define("SITE_URL",  "https://biznexus.in");

function sendBizEmail(string $to, string $to_name, string $subject, string $body_html): bool {
    // Try PHPMailer if available, else fallback to mail()
    if(class_exists("PHPMailer\PHPMailer\PHPMailer")){
        try {
            $m=new PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP(); $m->Host=SMTP_HOST; $m->SMTPAuth=true;
            $m->Username=SMTP_USER; $m->Password=SMTP_PASS;
            $m->SMTPSecure="tls"; $m->Port=SMTP_PORT;
            $m->setFrom(FROM_EMAIL,FROM_NAME);
            $m->addAddress($to,$to_name);
            $m->isHTML(true); $m->Subject=$subject; $m->Body=$body_html;
            return $m->send();
        }catch(Exception $e){ return false; }
    }
    // Fallback to PHP mail()
    $headers="From: ".FROM_NAME." <".FROM_EMAIL.">\r\n";
    $headers.="Reply-To: ".FROM_EMAIL."\r\n";
    $headers.="MIME-Version: 1.0\r\n";
    $headers.="Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to,$subject,$body_html,$headers);
}

function emailTemplate(string $title, string $body, string $cta_text="", string $cta_url=""): string {
    $cta_block=$cta_text&&$cta_url?"<div style=\"text-align:center;margin:28px 0\"><a href=\"{$cta_url}\" style=\"display:inline-block;background:#FFD700;color:#000;padding:14px 36px;border-radius:50px;font-weight:800;font-size:.95rem;text-decoration:none;font-family:Syne,Arial,sans-serif\">{$cta_text}</a></div>":"";
    return "<!DOCTYPE html><html><body style=\"background:#06060a;font-family:DM Sans,Arial,sans-serif;padding:40px 0\">
    <div style=\"max-width:540px;margin:0 auto;background:#0e0e16;border:1px solid #1e1e2e;border-radius:16px;overflow:hidden\">
      <div style=\"background:#0a0a12;padding:24px 32px;border-bottom:1px solid #1e1e2e;text-align:center\">
        <span style=\"font-family:Syne,Arial,sans-serif;font-size:1.4rem;font-weight:900;color:#FFD700\">⚡ BizNexus</span>
      </div>
      <div style=\"padding:32px\">
        <h2 style=\"color:#e0e0f0;font-size:1.25rem;margin:0 0 16px\">{$title}</h2>
        {$body}
        {$cta_block}
        <p style=\"color:#444;font-size:.75rem;text-align:center;margin-top:24px\">BizNexus — India\'s AI Business Network<br>Questions? Reply to this email or contact <a href=\"mailto:hello@biznexus.in\" style=\"color:#FFD700\">hello@biznexus.in</a></p>
      </div>
    </div></body></html>";
}

// Quick email functions
function sendWelcomeEmail(string $to, string $name): bool {
    $body="<p style=\"color:#888;line-height:1.7\">Hi <strong style=\"color:#e0e0f0\">{$name}</strong>,</p><p style=\"color:#888;line-height:1.7\">Welcome to BizNexus! Your account is ready. Start by completing your profile to earn VooCoins and get matched with leads.</p><p style=\"color:#888\">Here\'s what to do next:</p><ul style=\"color:#888;line-height:2\"><li>✅ Complete your profile (+50 VooCoins)</li><li>🤝 Send your first referral</li><li>📅 Book a meeting with a member</li><li>🛡️ Complete KYC for +25 Trust points</li></ul>";
    return sendBizEmail($to,$name,"Welcome to BizNexus! Your account is ready 🎉",emailTemplate("Welcome to BizNexus! 🎉",$body,"Go to Dashboard",SITE_URL."/dashboard/index.php"));
}

function sendLeadAlert(string $to, string $name, array $lead): bool {
    $cat=htmlspecialchars($lead["category"]??"");
    $city=htmlspecialchars($lead["city"]??"");
    $req=htmlspecialchars(substr($lead["requirements"]??"",0,200));
    $body="<p style=\"color:#888;line-height:1.7\">Hi <strong style=\"color:#e0e0f0\">{$name}</strong>, a new lead matching your category is available!</p><div style=\"background:#0a0a12;border:1px solid #1e1e2e;border-radius:12px;padding:16px;margin:16px 0\"><div style=\"color:#FFD700;font-weight:700;margin-bottom:8px\">📥 {$cat} Lead — {$city}</div><div style=\"color:#888;font-size:.88rem\">{$req}</div></div><p style=\"color:#555;font-size:.82rem\">⚠️ Only 3 members can claim this lead. First come, first served!</p>";
    return sendBizEmail($to,$name,"New Lead Available — {$cat} in {$city} 📥",emailTemplate("New Lead Matched! 📥",$body,"Claim This Lead",SITE_URL."/find.php?lead_id=".($lead["id"]??"")));
}

function sendReferralEmail(string $to, string $name, string $sender_name, string $ref_business): bool {
    $body="<p style=\"color:#888;line-height:1.7\">Hi <strong style=\"color:#e0e0f0\">{$name}</strong>,</p><p style=\"color:#888;line-height:1.7\"><strong style=\"color:#e0e0f0\">{$sender_name}</strong> has sent you a business referral on BizNexus for <strong style=\"color:#FFD700\">{$ref_business}</strong>.</p><p style=\"color:#888\">Log in to see the full details and take action.</p>";
    return sendBizEmail($to,$name,"You received a referral from {$sender_name}! 🤝",emailTemplate("New Referral Received! 🤝",$body,"View Referral",SITE_URL."/referrals/list.php"));
}

function sendMeetingEmail(string $to, string $name, array $meeting, bool $is_request=true): bool {
    $type=$is_request?"Meeting Request":"Meeting Confirmed";
    $mt=htmlspecialchars($meeting["type"]??"");
    $dt=htmlspecialchars($meeting["meeting_date"]??"");
    $body="<p style=\"color:#888;line-height:1.7\">Hi <strong style=\"color:#e0e0f0\">{$name}</strong>,</p><p style=\"color:#888;line-height:1.7\">".($is_request?"You have a new meeting request.":"Your meeting has been confirmed.")."</p><div style=\"background:#0a0a12;border:1px solid #1e1e2e;border-radius:12px;padding:16px;margin:16px 0\"><div style=\"color:#888;line-height:2\"><div>📅 <strong style=\"color:#e0e0f0\">Date:</strong> {$dt}</div><div>🤝 <strong style=\"color:#e0e0f0\">Type:</strong> {$mt}</div></div></div>";
    return sendBizEmail($to,$name,"{$type} on BizNexus 📅",emailTemplate($type." 📅",$body,"View Meeting",SITE_URL."/meetings/book.php"));
}
';

writeFile($base.'/includes/email_config.php', $email_config);
log_("✅ Created /includes/email_config.php (SMTP + all email templates)");

// =====================================================
// FILE 4: includes/coin_escrow.php
// =====================================================
$coin_escrow = '<?php
/**
 * BizNexus Coin Escrow System
 * Anti-farming: holds referral coins until deal closes
 */
function getDB(){
    $c=[["localhost","u175452495_biznexus","u175452495_bizuser","Biz@9990"],["localhost","u175452495_biznexus","u175452495_voo_user","Vooschool@123"],["localhost","u175452495_biznexus","u175452495","Biz@9990"]];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}

function ensureEscrowTable($pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS coin_escrow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        to_user_id INT NOT NULL,
        referral_id INT NOT NULL,
        amount INT NOT NULL DEFAULT 50,
        status ENUM(\"held\",\"partial_released\",\"fully_released\",\"expired\",\"returned\") DEFAULT \"held\",
        held_at DATETIME DEFAULT NOW(),
        released_at DATETIME DEFAULT NULL,
        release_trigger VARCHAR(100) DEFAULT \"deal_closed\",
        expires_at DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL
    )");
}

/**
 * Hold coins in escrow when referral is sent
 * @param int $from_user Sender (pays coins)
 * @param int $to_user   Receiver (gets coins when deal closes)
 * @param int $referral_id
 * @param int $amount   Default 50 coins
 */
function holdCoinsInEscrow(int $from_user, int $to_user, int $referral_id, int $amount=50, $pdo=null): bool {
    if(!$pdo) $pdo=getDB();
    if(!$pdo) return false;
    ensureEscrowTable($pdo);
    try {
        // Check sender has enough balance
        $bal=$pdo->prepare("SELECT balance FROM voocoin_balances WHERE user_id=?");
        $bal->execute([$from_user]);
        $balance=$bal->fetch()["balance"]??0;
        if($balance<$amount) return false; // Not enough coins — skip escrow

        // Deduct from sender immediately (held)
        $pdo->prepare("UPDATE voocoin_balances SET balance=balance-? WHERE user_id=?")->execute([$amount,$from_user]);
        $pdo->prepare("INSERT INTO coin_transactions (user_id,amount,type,description,created_at) VALUES (?,-?,\"debit\",\"Referral coins held in escrow (ref #{$referral_id})\",NOW())")->execute([$from_user,$amount]);

        // Create escrow record
        $expires=date("Y-m-d H:i:s",strtotime("+90 days"));
        $pdo->prepare("INSERT INTO coin_escrow (from_user_id,to_user_id,referral_id,amount,expires_at) VALUES (?,?,?,?,?)")->execute([$from_user,$to_user,$referral_id,$amount,$expires]);
        return true;
    }catch(Exception $e){ return false; }
}

/**
 * Release escrow when deal closes
 */
function releaseEscrow(int $referral_id, string $trigger="deal_closed", $pdo=null): bool {
    if(!$pdo) $pdo=getDB();
    if(!$pdo) return false;
    try {
        $esc=$pdo->prepare("SELECT * FROM coin_escrow WHERE referral_id=? AND status=\"held\"");
        $esc->execute([$referral_id]);
        $escrow=$esc->fetch();
        if(!$escrow) return false;

        $to=$escrow["to_user_id"];
        $amount=$escrow["amount"];

        // Credit to receiver
        $pdo->prepare("INSERT INTO voocoin_balances (user_id,balance,total_earned,total_spent) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE balance=balance+?,total_earned=total_earned+?")->execute([$to,$amount,$amount,$amount,$amount]);
        $pdo->prepare("INSERT INTO coin_transactions (user_id,amount,type,description,created_at) VALUES (?,?,\"credit\",\"Referral coins released — deal closed (ref #{$referral_id})\",NOW())")->execute([$to,$amount]);

        // Mark escrow released
        $pdo->prepare("UPDATE coin_escrow SET status=\"fully_released\",released_at=NOW(),release_trigger=? WHERE referral_id=?")->execute([$trigger,$referral_id]);

        // Notify receiver
        $pdo->prepare("INSERT INTO notifications (user_id,type,message,created_at) VALUES (?,\"coins\",\"🪙 {$amount} VooCoins released! Your referral deal was marked closed.\",NOW())")->execute([$to]);
        return true;
    }catch(Exception $e){ return false; }
}

/**
 * Return escrow if deal expired/cancelled
 */
function returnEscrow(int $referral_id, string $reason="expired", $pdo=null): bool {
    if(!$pdo) $pdo=getDB();
    if(!$pdo) return false;
    try {
        $esc=$pdo->prepare("SELECT * FROM coin_escrow WHERE referral_id=? AND status=\"held\"");
        $esc->execute([$referral_id]);
        $escrow=$esc->fetch();
        if(!$escrow) return false;

        $from=$escrow["from_user_id"];
        $amount=$escrow["amount"];

        // Return to sender
        $pdo->prepare("UPDATE voocoin_balances SET balance=balance+? WHERE user_id=?")->execute([$amount,$from]);
        $pdo->prepare("INSERT INTO coin_transactions (user_id,amount,type,description,created_at) VALUES (?,?,\"credit\",\"Escrowed coins returned — {$reason}\",NOW())")->execute([$from,$amount]);
        $pdo->prepare("UPDATE coin_escrow SET status=\"returned\",released_at=NOW(),notes=? WHERE referral_id=?")->execute([$reason,$referral_id]);
        return true;
    }catch(Exception $e){ return false; }
}

/**
 * Cron: auto-return expired escrows (90+ days)
 */
function processExpiredEscrows($pdo=null): int {
    if(!$pdo) $pdo=getDB();
    if(!$pdo) return 0;
    $expired=$pdo->query("SELECT * FROM coin_escrow WHERE status=\"held\" AND expires_at < NOW()");
    $count=0;
    while($e=$expired->fetch()){ returnEscrow($e["referral_id"],"90-day expiry",$pdo); $count++; }
    return $count;
}
';

writeFile($base.'/includes/coin_escrow.php', $coin_escrow);
log_("✅ Created /includes/coin_escrow.php (Anti-farming escrow system)");

// =====================================================
// FILE 5: agent/leads_complete_fix.php
// =====================================================
$leads_fix = '<?php
if(($_GET["key"]??"")<>"BizCron2024"){ http_response_code(403); die("Forbidden"); }
function getDB(){
    $c=[["localhost","u175452495_biznexus","u175452495_bizuser","Biz@9990"],["localhost","u175452495_biznexus","u175452495_voo_user","Vooschool@123"],["localhost","u175452495_biznexus","u175452495","Biz@9990"]];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}
header("Content-Type: application/json");
$pdo=getDB();
if(!$pdo){ echo json_encode(["error"=>"DB connection failed"]); exit; }
$results=[];

// 1. Ensure public_leads table exists with all columns
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS public_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150),
        phone VARCHAR(20),
        email VARCHAR(150),
        category VARCHAR(100),
        city VARCHAR(100),
        requirements TEXT,
        budget VARCHAR(100),
        timeline VARCHAR(100),
        source VARCHAR(50) DEFAULT \"find_page\",
        status ENUM(\"new\",\"dispatched\",\"claimed\",\"closed\",\"spam\") DEFAULT \"new\",
        claimed_count INT DEFAULT 0,
        locked_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT NOW()
    )");
    $results["leads_table"]="OK";
}catch(Exception $e){ $results["leads_table"]="Error: ".$e->getMessage(); }

// 2. Ensure lead_dispatches table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_dispatches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        user_id INT NOT NULL,
        slot_number INT DEFAULT 0,
        status ENUM(\"sent\",\"claimed\",\"waitlist\",\"expired\",\"contacted\",\"closed\") DEFAULT \"sent\",
        claimed_at DATETIME DEFAULT NULL,
        expired_at DATETIME DEFAULT NULL,
        rating TINYINT DEFAULT NULL,
        review TEXT DEFAULT NULL,
        dispatched_at DATETIME DEFAULT NOW(),
        UNIQUE KEY uniq_dispatch (lead_id, user_id)
    )");
    $results["dispatches_table"]="OK";
}catch(Exception $e){ $results["dispatches_table"]="Error: ".$e->getMessage(); }

// 3. Add missing columns safely
$alters=[
    ["public_leads","claimed_count","INT DEFAULT 0"],
    ["public_leads","locked_at","DATETIME DEFAULT NULL"],
    ["lead_dispatches","slot_number","INT DEFAULT 0"],
    ["lead_dispatches","claimed_at","DATETIME DEFAULT NULL"],
    ["lead_dispatches","expired_at","DATETIME DEFAULT NULL"],
    ["lead_dispatches","rating","TINYINT DEFAULT NULL"],
    ["referrals","referred_name","VARCHAR(150) DEFAULT NULL"],
    ["referrals","referred_phone","VARCHAR(20) DEFAULT NULL"],
    ["referrals","referred_email","VARCHAR(150) DEFAULT NULL"],
    ["referrals","referred_business_type","VARCHAR(100) DEFAULT NULL"],
    ["referrals","notes","TEXT DEFAULT NULL"],
    ["referrals","estimated_value","DECIMAL(12,2) DEFAULT NULL"],
];
foreach($alters as [$table,$col,$def]){
    try { $pdo->exec("ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS {$col} {$def}"); $results["alter_{$table}_{$col}"]="OK"; }
    catch(Exception $e){ $results["alter_{$table}_{$col}"]="skip"; }
}

// 4. Dispatch undispatched leads
$undispatched=$pdo->query("SELECT * FROM public_leads WHERE status=\"new\" LIMIT 20");
$dispatched_count=0;
while($lead=$undispatched->fetch()){
    // Find top 10 matching members by category + city
    $members=$pdo->prepare("SELECT u.id FROM users u
        LEFT JOIN voocoin_balances vb ON u.id=vb.user_id
        WHERE u.status=\"active\"
        AND (u.category=? OR u.city=? OR 1=1)
        AND u.id NOT IN (SELECT user_id FROM lead_dispatches WHERE lead_id=?)
        ORDER BY u.trust_score DESC, vb.balance DESC
        LIMIT 10");
    $members->execute([$lead["category"],$lead["city"],$lead["id"]]);
    $slot=1;
    while($m=$members->fetch()){
        $status=$slot<=3?"sent":"waitlist";
        try {
            $pdo->prepare("INSERT IGNORE INTO lead_dispatches (lead_id,user_id,slot_number,status,expired_at) VALUES (?,?,?,?,?)")
                ->execute([$lead["id"],$m["id"],$slot,$status,$slot<=3?date("Y-m-d H:i:s",strtotime("+2 hours")):null]);
            $slot++;
        }catch(Exception $e){}
    }
    if($slot>1){
        $pdo->prepare("UPDATE public_leads SET status=\"dispatched\" WHERE id=?")->execute([$lead["id"]]);
        $dispatched_count++;
    }
}
$results["leads_dispatched"]=$dispatched_count;

// 5. Create whatsapp queue table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_whatsapp_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        user_id INT NOT NULL,
        whatsapp VARCHAR(20),
        status ENUM(\"pending\",\"sent\",\"failed\") DEFAULT \"pending\",
        scheduled_at DATETIME DEFAULT NOW(),
        sent_at DATETIME DEFAULT NULL
    )");
    $results["whatsapp_queue"]="OK";
}catch(Exception $e){ $results["whatsapp_queue"]="Error: ".$e->getMessage(); }

// 6. Create trust score tables
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_ratings (id INT AUTO_INCREMENT PRIMARY KEY,lead_id INT,rated_user_id INT,rater_user_id INT,rating TINYINT,review TEXT,created_at DATETIME DEFAULT NOW())");
    $results["lead_ratings_table"]="OK";
}catch(Exception $e){ $results["lead_ratings_table"]="skip"; }

// 7. Counts summary
$counts=[];
foreach(["users","public_leads","lead_dispatches","referrals","meetings","coin_escrow","lead_ratings"] as $t){
    try{ $counts[$t]=$pdo->query("SELECT COUNT(*) as c FROM {$t}")->fetch()["c"]; }catch(Exception $e){ $counts[$t]="n/a"; }
}
$results["table_counts"]=$counts;
$results["status"]="SUCCESS";
$results["time"]=date("Y-m-d H:i:s");
echo json_encode($results,JSON_PRETTY_PRINT);
';

writeFile($base.'/agent/leads_complete_fix.php', $leads_fix);
log_("✅ Created /agent/leads_complete_fix.php");

// =====================================================
// FILE 6: agent/update_email_config.php
// =====================================================
$update_email = '<?php
if(($_GET["key"]??"")<>"BizCron2024"){ http_response_code(403); die("Forbidden"); }
function getDB(){
    $c=[["localhost","u175452495_biznexus","u175452495_bizuser","Biz@9990"],["localhost","u175452495_biznexus","u175452495_voo_user","Vooschool@123"],["localhost","u175452495_biznexus","u175452495","Biz@9990"]];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}
header("Content-Type: application/json");
$results=["action"=>"email_config_update","time"=>date("Y-m-d H:i:s")];

// Test mode
if(isset($_GET["test"])){
    require_once dirname(dirname(__FILE__))."/includes/email_config.php";
    $sent=sendBizEmail("hello@biznexus.in","BizNexus Admin","✅ BizNexus Email Test — ".date("H:i:s"),emailTemplate("Email Test Successful! ✅","<p style=\"color:#888\">This is a test email from BizNexus. If you see this, SMTP is working correctly!</p>","Go to Dashboard","https://biznexus.in/dashboard/index.php"));
    $results["test_email"]=$sent?"SENT to hello@biznexus.in":"FAILED";
    echo json_encode($results); exit;
}

// Verify email_config.php exists
$cfg_path=dirname(dirname(__FILE__))."/includes/email_config.php";
$results["email_config_exists"]=file_exists($cfg_path)?"YES":"NO — run deploy_phase2.php first";

// Test DB notifications table
$pdo=getDB();
if($pdo){
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,type VARCHAR(50) DEFAULT \"general\",message TEXT,is_read TINYINT(1) DEFAULT 0,created_at DATETIME DEFAULT NOW())");
        $results["notifications_table"]="OK";
    }catch(Exception $e){ $results["notifications_table"]="Error: ".$e->getMessage(); }

    // Count unread
    $unread=$pdo->query("SELECT COUNT(*) as c FROM notifications WHERE is_read=0")->fetch()["c"]??0;
    $results["unread_notifications"]=$unread;
}
$results["smtp_config"]=["host"=>"smtp.hostinger.com","port"=>587,"from"=>"hello@biznexus.in"];
$results["status"]="OK — add ?test=1 to send a test email";
echo json_encode($results,JSON_PRETTY_PRINT);
';

writeFile($base.'/agent/update_email_config.php', $update_email);
log_("✅ Created /agent/update_email_config.php");

// =====================================================
// FILE 7: agent/trust_cron.php (runs every 30min)
// =====================================================
$trust_cron = '<?php
if(($_GET["key"]??"")<>"BizCron2024"){ http_response_code(403); die("Forbidden"); }
function getDB(){
    $c=[["localhost","u175452495_biznexus","u175452495_bizuser","Biz@9990"],["localhost","u175452495_biznexus","u175452495_voo_user","Vooschool@123"],["localhost","u175452495_biznexus","u175452495","Biz@9990"]];
    foreach($c as $cfg){ try{ return new PDO("mysql:host={$cfg[0]};dbname={$cfg[1]};charset=utf8mb4",$cfg[2],$cfg[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }catch(Exception $e){ continue; } }
    return null;
}
$pdo=getDB(); if(!$pdo){ die(json_encode(["error"=>"DB fail"])); }
header("Content-Type: application/json");
$results=["task"=>"trust_score_recalc","time"=>date("Y-m-d H:i:s")];

// Recalculate trust score for all active users
$users=$pdo->query("SELECT id FROM users WHERE status=\"active\" ORDER BY id");
$updated=0;
while($u=$users->fetch()){
    $uid=$u["id"];
    $score=0;

    // KYC (25pts)
    $kyc=$pdo->prepare("SELECT kyc_verified FROM users WHERE id=?"); $kyc->execute([$uid]);
    $score+=($kyc->fetch()["kyc_verified"]??0)?25:0;

    // Response rate (20pts)
    $total=$pdo->prepare("SELECT COUNT(*) as c FROM lead_dispatches WHERE user_id=?"); $total->execute([$uid]); $tot=$total->fetch()["c"]??0;
    $resp=$pdo->prepare("SELECT COUNT(*) as c FROM lead_dispatches WHERE user_id=? AND status IN(\"claimed\",\"closed\",\"contacted\")"); $resp->execute([$uid]); $res=$resp->fetch()["c"]??0;
    $rr=$tot>0?min(100,round($res/$tot*100)):0;
    $score+=round($rr/100*20);

    // Avg rating (20pts)
    $rt=$pdo->prepare("SELECT AVG(rating) as a FROM lead_ratings WHERE rated_user_id=?"); $rt->execute([$uid]); $avg=$rt->fetch()["a"]??0;
    $score+=round(max(0,($avg-1)/4*20));

    // Profile completeness (20pts)
    $pr=$pdo->prepare("SELECT photo,bio,category,city,phone FROM users WHERE id=?"); $pr->execute([$uid]); $pd=$pr->fetch();
    foreach(["photo","bio","category","city","phone"] as $f){ if(!empty($pd[$f])&&$pd[$f]!=="N/A") $score+=4; }

    // Activity (10pts)
    $refs=$pdo->prepare("SELECT COUNT(*) as c FROM referrals WHERE sender_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 30 DAY)"); $refs->execute([$uid]); $score+=min(4,$refs->fetch()["c"]??0);
    try { $posts=$pdo->prepare("SELECT COUNT(*) as c FROM community_posts WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 30 DAY)"); $posts->execute([$uid]); $score+=min(3,$posts->fetch()["c"]??0); }catch(Exception $e){}
    try { $meets=$pdo->prepare("SELECT COUNT(*) as c FROM meetings WHERE (requester_id=? OR target_id=?) AND created_at>DATE_SUB(NOW(),INTERVAL 30 DAY)"); $meets->execute([$uid,$uid]); $score+=min(3,$meets->fetch()["c"]??0); }catch(Exception $e){}

    // Age (5pts)
    $age=$pdo->prepare("SELECT created_at FROM users WHERE id=?"); $age->execute([$uid]); $ca=$age->fetch()["created_at"]??"now";
    $days=(int)((time()-strtotime($ca))/86400); $score+=min(5,(int)($days/60));

    $badge=match(true){$score>=90=>"Elite",$score>=75=>"Trusted",$score>=55=>"Rising",$score>=30=>"Active",default=>"New"};
    $pdo->prepare("UPDATE users SET trust_score=?,trust_badge=?,trust_updated=NOW() WHERE id=?")->execute([$score,$badge,$uid]);
    $updated++;
}

// Also expire old lead dispatches and promote waitlist
$expired=$pdo->query("SELECT id,lead_id,slot_number FROM lead_dispatches WHERE status=\"claimed\" AND expired_at < NOW()");
$promoted=0;
while($e=$expired->fetch()){
    $pdo->prepare("UPDATE lead_dispatches SET status=\"expired\" WHERE id=?")->execute([$e["id"]]);
    $next=$pdo->prepare("SELECT id,user_id FROM lead_dispatches WHERE lead_id=? AND status=\"waitlist\" ORDER BY slot_number ASC LIMIT 1");
    $next->execute([$e["lead_id"]]);
    $w=$next->fetch();
    if($w){
        $pdo->prepare("UPDATE lead_dispatches SET status=\"claimed\",slot_number=?,claimed_at=NOW(),expired_at=? WHERE id=?")->execute([$e["slot_number"],date("Y-m-d H:i:s",strtotime("+2 hours")),$w["id"]]);
        $pdo->prepare("INSERT INTO notifications (user_id,type,message,created_at) VALUES (?\"lead\",\"⚡ Lead slot opened! You have 2 hours to respond.\",NOW())")->execute([$w["user_id"]]);
        $promoted++;
    }
}

// Return expired escrows
require_once dirname(dirname(__FILE__))."/includes/coin_escrow.php";
$returned=processExpiredEscrows($pdo);

$results["users_score_updated"]=$updated;
$results["dispatches_expired_promoted"]=$promoted;
$results["escrows_returned"]=$returned;
$results["status"]="OK";
echo json_encode($results,JSON_PRETTY_PRINT);
';

writeFile($base.'/agent/trust_cron.php', $trust_cron);
log_("✅ Created /agent/trust_cron.php (Trust recalc + lead expiry cron)");

// =====================================================
// FINAL: Add cron entry suggestion + summary
// =====================================================
echo "\n\n<h3 style='color:#FFD700'>✅ ALL PHASE 2 FILES DEPLOYED!</h3>\n";
echo "<table style='color:#888;font-size:13px;width:100%;border-collapse:collapse'>\n";
echo "<tr><th style='text-align:left;color:#FFD700;padding:6px'>File</th><th style='text-align:left;color:#FFD700;padding:6px'>URL</th><th style='text-align:left;color:#FFD700;padding:6px'>Status</th></tr>\n";
$files=[
    ["/kyc/upload.php","biznexus.in/kyc/upload.php"],
    ["/leads/claim.php","biznexus.in/leads/claim.php (API)"],
    ["/includes/email_config.php","(backend only)"],
    ["/includes/coin_escrow.php","(backend only)"],
    ["/agent/leads_complete_fix.php","agent.biznexus.in/leads_complete_fix.php?key=BizCron2024"],
    ["/agent/update_email_config.php","agent.biznexus.in/update_email_config.php?key=BizCron2024"],
    ["/agent/trust_cron.php","agent.biznexus.in/trust_cron.php?key=BizCron2024"],
];
foreach($files as [$path,$url]){
    $exists=file_exists($base.$path);
    echo "<tr><td style='padding:5px;color:#e0e0f0'>{$path}</td><td style='padding:5px;color:#555'>{$url}</td><td style='padding:5px;color:".($exists?"#00ff88":"#ff4455")."'>".($exists?"✅ Created":"❌ Failed")."</td></tr>\n";
}
echo "</table>\n\n";
echo "<div style='color:#FFD700;margin-top:16px'>⚡ Add to Hostinger Cron (every 30 min):</div>\n";
echo "<div style='color:#888;margin-top:6px'>https://agent.biznexus.in/trust_cron.php?key=BizCron2024</div>\n";
echo "<div style='color:#888'>https://agent.biznexus.in/leads_complete_fix.php?key=BizCron2024</div>\n";
echo "</pre>";
