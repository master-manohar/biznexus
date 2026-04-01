<?php
/**
 * register_business.php
 * Proactive AI registration assistant for BizNexus
 */
session_start();
define('BASE', __DIR__);
$sess_key = 'biz_reg_v2';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email_config.php';

if (isset($_GET['ref'])) {
    $_SESSION['active_ref'] = trim($_GET['ref']);
}
if (isset($_GET['src'])) {
    $_SESSION['active_src'] = trim($_GET['src']);
}

// ── Handle AJAX Requests ──────────────────────────────────────────────────────
if (isset($_POST['msg'])) {
    $msg = trim($_POST['msg']);
    $profile  = $_SESSION[$sess_key]['profile']  ?? [];

    require_once __DIR__ . '/includes/ai_helper_v3.php';

    $system = "You are a helpful onboarding assistant for BizNexus, an AI-powered B2B business network in India.
Your goal is to gather business information to create an account.
Be professional, encouraging, and friendly.

GUIDELINES:
1. If a user doesn't know a detail or provides a vague answer, SKIP it for now and move to the next field.
2. Tell the user they can provide the skipped details later in their profile settings.
3. Keep the conversation moving and focused on getting the core account ready.

FIELDS TO COLLECT:
1. owner_name, 2. business_name, 3. category, 4. city, 5. whatsapp, 6. email, 7. password, 8. description, 9. products

REPLY FORMAT (ONLY JSON):
{
  \"message\": \"Response text...\",
  \"extracted\": { \"field\": \"value\" },
  \"ready\": true/false
}";

    $history = $_SESSION[$sess_key]['history'] ?? [];
    $messages = $history;
    $messages[] = ['role'=>'user','content'=>$msg];

    $result = runBizAI($messages, $system);
    $bot_reply = "I am having trouble. Try again!";
    $extracted = []; $ready = false;

    if (isset($result['text'])) {
        $text = preg_replace('/^```json\s*|\s*```$/i', '', trim($result['text']));
        $parsed = json_decode($text, true);
        if ($parsed) {
            $bot_reply = $parsed['message'] ?? $bot_reply;
            $extracted = $parsed['extracted'] ?? [];
            $ready     = $parsed['ready'] ?? false;
        }
    }

    foreach ($extracted as $k => $v) {
        if ($v && $v !== 'null') $profile[$k] = $v;
    }

    $history[] = ['role'=>'user','content'=>$msg];
    $history[] = ['role'=>'assistant','content'=>json_encode(['message'=>$bot_reply,'extracted'=>$extracted])];
    if (count($history) > 20) $history = array_slice($history, -20);

    $_SESSION[$sess_key] = ['history'=>$history, 'profile'=>$profile];

    $user_id = null; $website_url = null;
    if ($ready && !empty($profile['email']) && !empty($profile['password']) && !empty($profile['business_name'])) {
        try {
            $pdo->beginTransaction();

            $exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $exists->execute([$profile['email']]);
            if (!$exists->fetch()) {
                $refer_code = strtoupper(substr(md5($profile['email'].time()), 0, 8));
                $verify_token = bin2hex(random_bytes(16));
                $slug = strtolower(preg_replace('/[^a-z0-9]/','',str_replace(' ','-',$profile['business_name']??'biz')));
                
                $gid = (!empty($profile['category'])) ? 108 : null; // Sreelakshmi's Elite Group


                $ref_src = $_SESSION['active_src'] ?? null;
                // Safely handle referral_source column if it exists
                $col_check = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_source'")->fetch();
                if ($col_check) {
                    $stmt = $pdo->prepare("INSERT INTO users (business_name, email, password, whatsapp, whatsapp_number, category, city, membership, coins, refer_code, referral_source, verify_token, profile_complete, group_id, group_role, created_at) VALUES (?,?,?,?,?,?,?,?,100,?,?,?,1,?,'member',NOW())");
                    $stmt->execute([$profile['business_name'], $profile['email'], password_hash($profile['password'],PASSWORD_DEFAULT), $profile['whatsapp']??'', $profile['whatsapp']??'', $profile['category']??'', $profile['city']??'', 'free', $refer_code, $ref_src, $verify_token, $gid]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (business_name, email, password, whatsapp, whatsapp_number, category, city, membership, coins, refer_code, verify_token, profile_complete, group_id, group_role, created_at) VALUES (?,?,?,?,?,?,?,?,100,?,?,1,?,'member',NOW())");
                    $stmt->execute([$profile['business_name'], $profile['email'], password_hash($profile['password'],PASSWORD_DEFAULT), $profile['whatsapp']??'', $profile['whatsapp']??'', $profile['category']??'', $profile['city']??'', 'free', $refer_code, $verify_token, $gid]);
                }
                $user_id = $pdo->lastInsertId();
                if ($user_id) {
                    $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, type, description, created_at) VALUES (?, 100, 'credit', 'Welcome Bonus - 100 VooCoins', NOW())")->execute([$user_id]);
                }

                $pdo->prepare("INSERT INTO businesses (user_id, name, slug, tagline, description, category, city, phone, email, whatsapp, website, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name)")->execute([
                    $user_id, $profile['business_name'], $slug, $profile['description']??'', $profile['description']??'', $profile['category']??'', $profile['city']??'', $profile['whatsapp']??'', $profile['email'], $profile['whatsapp']??'', $profile['website_url']??''
                ]);

                // Referral Logic
                if (!empty($_SESSION['active_ref'])) {
                    $refCode = $_SESSION['active_ref'];
                    $refStmt = $pdo->prepare("SELECT id FROM users WHERE refer_code = ?");
                    $refStmt->execute([$refCode]);
                    $referrer = $refStmt->fetch();
                    
                    if ($referrer) {
                        $referrerId = $referrer['id'];
                        // 1. Link the new user
                        $pdo->prepare("UPDATE users SET referred_by = ? WHERE id = ?")->execute([$referrerId, $user_id]);
                        
                        // 2. Award Referrer (+200 VooCoins, +50 Trust)
                        $pdo->prepare("UPDATE users SET coins = coins + 200, trust_score = trust_score + 50 WHERE id = ?")->execute([$referrerId]);
                        $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, type, description, created_at) VALUES (?, 200, 'credit', 'Referral Bonus - New Partner Joined', NOW())")->execute([$referrerId]);
                        
                        // 3. Award New User (+100 Extra VooCoins)
                        $pdo->prepare("UPDATE users SET coins = coins + 100 WHERE id = ?")->execute([$user_id]);
                        $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, type, description, created_at) VALUES (?, 100, 'credit', 'Referral Bonus - Joined via Network', NOW())")->execute([$user_id]);
                        
                        // Clear session once used
                        unset($_SESSION['active_ref']);
                    }
                }

                $pdo->prepare("INSERT INTO business_profiles (user_id, business_name, tagline, description, category, city) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE business_name=VALUES(business_name)")->execute([
                    $user_id, $profile['business_name'], $profile['description']??'', $profile['description']??'', $profile['category']??'', $profile['city']??''
                ]);

                if (!empty($profile['products'])) {
                    $prods = explode(',', $profile['products']);
                    foreach ($prods as $p) {
                        $p = trim($p);
                        if ($p) $pdo->prepare("INSERT IGNORE INTO business_products (user_id, name, description, price) VALUES (?,?,?,0)")->execute([$user_id, $p, $p]);
                    }
                }

                $pdo->prepare("UPDATE businesses SET website_generated=1 WHERE user_id=?")->execute([$user_id]);
                $pdo->commit();

                try {
                    $verify_link = "https://biznexus.in/auth/verify.php?token=" . $verify_token;
                    $emailMsg = "<h2>Welcome to BizNexus!</h2>
                                 <p>Hi {$profile['business_name']}, your business AI network is ready.</p>
                                 <p>To secure your account and get <strong>+150 Security Points</strong>, please verify your email:</p>
                                 <a href='{$verify_link}' class='btn' style='display:inline-block; background:#FFD700; color:#000; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:bold;'>Verify My Email</a>
                                 <p style='margin-top:20px;'>Your refer code: <strong>$refer_code</strong></p>";
                    $html = emailTemplate("Welcome to BizNexus! 🚀", $emailMsg);
                    sendEmail($profile['email'], "Welcome to BizNexus! 🚀", $html);

                    // Admin Notification (Temporary)
                    $adminEmail = "manohar.nch@gmail.com"; 
                    $adminBody = "<h3>New Business Partner! 🚀</h3>
                                 <p><strong>Name:</strong> {$profile['business_name']}</p>
                                 <p><strong>Category:</strong> " . ($profile['category'] ?? 'N/A') . "</p>
                                 <p><strong>Location:</strong> " . ($profile['city'] ?? 'N/A') . "</p>
                                 <p><strong>Email:</strong> {$profile['email']}</p>
                                 <p><strong>WhatsApp:</strong> " . ($profile['whatsapp'] ?? 'N/A') . "</p>";
                    sendEmail($adminEmail, "New Registration: {$profile['business_name']}", emailTemplate("Admin Alert", $adminBody));

                } catch (Exception $eMail) { }

                $website_url = "/sites/{$slug}/";
                $_SESSION[$sess_key] = []; 
                $bot_reply .= "\n\n✅ Account created! Your referral code is **{$refer_code}**. Redirecting you to login...";
            } else {
                $bot_reply = "That email is already registered! Would you like to login instead? 👉 biznexus.in/auth/login.php";
                $ready = false;
            }
        } catch(Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $bot_reply = "issue: " . $e->getMessage(); 
            error_log("BizNexus Registration Error: " . $e->getMessage() . " | Profile: " . json_encode($profile));
            $ready = false;
        }
    }

    $progress = 0;
    $required = ['owner_name','business_name','category','city','whatsapp','email','password','description'];
    foreach ($required as $f) if (!empty($profile[$f])) $progress++;
    $pct = round($progress / count($required) * 100);

    echo json_encode(['reply'=>$bot_reply,'ready'=>$ready,'user_id'=>$user_id,'website_url'=>$website_url,'profile'=>$profile,'progress'=>$pct]);
    exit;
}

if (isset($_GET['reset'])) { $_SESSION[$sess_key] = []; header("Location: /register_business.php"); exit; }
$profile = $_SESSION[$sess_key]['profile'] ?? [];
$progress_fields = ['owner_name','business_name','category','city','whatsapp','email','password','description'];
$done = array_filter($progress_fields, fn($f)=>!empty($profile[$f]));
$pct = count($done) ? round(count($done)/count($progress_fields)*100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register Your Business · BizNexus</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#06060a;--card:#0e0e16;--border:#1e1e2e;--gold:#FFD700;--gold2:#a67c2e;--text:#e0e0f0;--muted:#555}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:grid;place-items:center;padding:20px}
.wrap{width:100%;max-width:520px}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-family:'DM Serif Display',serif;font-size:1.8rem;color:var(--gold)}
.logo p{color:var(--muted);font-size:.85rem;margin-top:4px}
.card{background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden}
.card-head{background:linear-gradient(135deg,#0a0a14,#12122a);padding:20px 24px;border-bottom:1px solid var(--border)}
.card-head h2{font-size:1rem;font-weight:700;color:var(--gold);display:flex;align-items:center;gap:8px}
.progress-bar{height:4px;background:#1a1a2e;border-radius:2px;margin-top:12px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--gold),#00ff88);border-radius:2px;transition:.5s}
.chat-area{height:340px;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px}
.msg{max-width:88%;padding:11px 14px;border-radius:16px;font-size:.88rem;line-height:1.6;animation:fadeIn .2s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.msg-bot{background:#1a1a2e;color:var(--text);align-self:flex-start;border-bottom-left-radius:4px}
.msg-bot strong{color:var(--gold)}
.msg-user{background:var(--gold);color:#000;font-weight:500;align-self:flex-end;border-bottom-right-radius:4px}
.msg-system{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.2);color:#00ff88;font-size:.78rem;text-align:center;border-radius:8px;padding:8px 12px;margin:4px 0}
.typing-dot{display:inline-block;width:6px;height:6px;background:var(--muted);border-radius:50%;animation:bounce .8s infinite}
.typing-dot:nth-child(2){animation-delay:.15s}.typing-dot:nth-child(3){animation-delay:.3s}
@keyframes bounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-6px)}}
.input-area{padding:14px;border-top:1px solid var(--border);display:flex;gap:10px;align-items:center;background:#0a0a10}
.input-area input{flex:1;background:#1a1a2e;border:1px solid var(--border);color:var(--text);padding:10px 16px;border-radius:50px;font-size:.9rem;font-family:'DM Sans',sans-serif;outline:none;transition:.2s}
.input-area input:focus{border-color:var(--gold)}
.send-btn{width:40px;height:40px;background:var(--gold);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:.15s;flex-shrink:0}
.send-btn:hover{background:var(--gold2)}
.profile-pills{display:flex;flex-wrap:wrap;gap:6px;padding:10px 16px;border-top:1px solid var(--border);background:#08080f}
.pill{padding:3px 10px;background:#1a1a2e;border:1px solid #2a2a3a;border-radius:50px;font-size:.7rem;color:#aaa}
.pill.done{border-color:rgba(0,255,136,.3);color:#00ff88}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <h1>⚡ BizNexus</h1>
    <p>Register your business in minutes with our AI assistant</p>
  </div>
  <div class="card">
    <div class="card-head">
      <h2>🤖 BizBot — Your Onboarding Assistant</h2>
      <div style="color:var(--muted);font-size:.75rem;margin-top:4px"><span id="prog-text"><?= $pct ?></span>% complete · <a href="/register_business.php?reset=1" style="color:#555;font-size:.72rem">Start over</a></div>
      <div class="progress-bar"><div class="progress-fill" id="prog-fill" style="width:<?=$pct?>%"></div></div>
    </div>
    <div class="profile-pills" id="profile-pills">
      <?php foreach(['business_name','category','city','email','whatsapp'] as $f): ?>
      <span class="pill <?= !empty($profile[$f])?'done':'' ?>" id="chip-<?=$f?>"><?= !empty($profile[$f]) ? '✓ '.htmlspecialchars($profile[$f]) : $f ?></span>
      <?php endforeach; ?>
    </div>
    <div class="chat-area" id="chat">
      <div class="msg msg-bot">
        👋 Hi! I'm <strong>BizBot</strong>, your BizNexus onboarding assistant.<br>
        Let's start — <strong>what's your name and business name?</strong>
      </div>
    </div>
    <div class="input-area">
      <input type="text" id="msg-input" placeholder="Type your message..." autocomplete="off">
      <button class="send-btn" id="send-btn">➤</button>
    </div>
  </div>
</div>
<script>
let isTyping = false;
const chat = document.getElementById('chat');
const inp = document.getElementById('msg-input');
const btn = document.getElementById('send-btn');

function send(text) {
  const msg = text || inp.value.trim();
  if (!msg || isTyping) return;
  inp.value = '';
  isTyping = true;
  appendMsg(msg, 'user');
  const typing = appendMsg('<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>', 'bot', true);
  fetch(window.location.pathname, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'msg=' + encodeURIComponent(msg)
  }).then(r=>r.json()).then(data=>{
    typing.remove();
    if (data.reply) appendMsg(data.reply.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>'), 'bot');
    if (data.progress !== undefined) {
      document.getElementById('prog-fill').style.width = data.progress + '%';
      document.getElementById('prog-text').textContent = data.progress;
    }
    if (data.profile) {
      ['business_name','category','city','email','whatsapp'].forEach(f => {
        const chip = document.getElementById('chip-'+f);
        if (chip && data.profile[f]) {
          chip.className = 'pill done';
          chip.textContent = '✓ ' + data.profile[f].substring(0,25);
        }
      });
    }
    if (data.ready && data.user_id) {
      appendMsg('🎉 <strong>Your account has been created!</strong> Redirecting to login...', 'system');
      setTimeout(()=>{ window.location = '/auth/login.php?registered=1'; }, 3000);
    }
    isTyping = false;
  }).catch(e=>{ typing.remove(); isTyping=false; console.error(e); });
}
function appendMsg(text, type, raw=false) {
  const d = document.createElement('div');
  d.className = type==='system'?'msg-system':'msg msg-'+type;
  d.innerHTML = text;
  chat.appendChild(d);
  chat.scrollTop = chat.scrollHeight;
  return d;
}
btn.onclick = () => send();
inp.onkeypress = e => { if(e.key==='Enter') send(); };
</script>
</body>
</html>
