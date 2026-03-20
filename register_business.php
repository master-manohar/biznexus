<?php
/**
 * BizNexus Business Registration Agent
 * /public_html/register_business.php
 * Friendly AI chatbot that onboards new businesses step-by-step
 */
define("BASE", dirname(__FILE__));
require_once BASE . "/includes/db.php";

session_start();
$sess_key = 'biznexus_onboard';

// ── AJAX: AI conversation handler ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['msg'])) {
    header('Content-Type: application/json');
    $msg = trim($_POST['msg']);
    $history = $_SESSION[$sess_key]['history'] ?? [];
    $profile  = $_SESSION[$sess_key]['profile']  ?? [];

    $secrets = require_once __DIR__ . '/includes/secrets.php';
    $api_key = $secrets['anthropic_api_key'];

    $system = <<<PROMPT
You are BizBot, a friendly onboarding assistant for BizNexus — India's AI business network.

Your job: Collect all necessary info from a new business owner through natural friendly conversation, then save it.

REQUIRED fields to collect (track what's done):
1. owner_name — their full name
2. business_name — their business name
3. category — business type (pick from: Retail, Food & Beverage, Technology, Healthcare, Education, Real Estate, Finance, Manufacturing, Logistics, Marketing, Legal, Consulting, Construction, Agriculture, Tourism, Fashion, Automotive, Events, Beauty, Electronics, Furniture, Jewellery, Pharma, Printing, Textiles, Other)
4. city — which city they operate in
5. whatsapp — WhatsApp number (10 digits)
6. email — business email
7. password — create account password (min 6 chars)
8. description — 1-2 line business description
9. products — what products/services do they offer (brief)
10. website_url — their existing website (optional, say "skip" if none)

Current collected profile: """ . json_encode($profile) . """

Rules:
- Be warm, conversational, professional. Like a helpful friend.
- Ask 1-2 questions at a time, never dump all at once
- When you extract a value from their message, include it in your JSON response
- Validate: phone must be 10 digits, email must have @
- When ALL required fields collected, say you're ready to create the account
- Keep messages SHORT and friendly

Always respond with ONLY a JSON object:
{
  "message": "your friendly reply here",
  "extracted": { "field": "value", ... },
  "ready": false,
  "next_fields": ["field1", "field2"]
}
PROMPT;

    $messages = [];
    foreach ($history as $h) $messages[] = $h;
    $messages[] = ['role'=>'user','content'=>$msg];

    $payload = ['model'=>'claude-sonnet-4-20250514','max_tokens'=>600,'system'=>$system,'messages'=>$messages];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_HTTPHEADER=>["Content-Type: application/json","x-api-key: {$api_key}","anthropic-version: 2023-06-01"],CURLOPT_TIMEOUT=>20]);
    $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);

    $bot_reply = "I'm having a little trouble. Please try again!";
    $extracted = []; $ready = false;

    if ($code === 200) {
        $r = json_decode($resp, true);
        $text = $r['content'][0]['text'] ?? '{}';
        // Clean JSON
        $text = preg_replace('/```json|```/','',$text);
        $parsed = json_decode(trim($text), true);
        if ($parsed) {
            $bot_reply = $parsed['message'] ?? $bot_reply;
            $extracted = $parsed['extracted'] ?? [];
            $ready     = $parsed['ready'] ?? false;
        }
    }

    // Merge extracted fields into profile
    foreach ($extracted as $k => $v) {
        if ($v && $v !== 'null') $profile[$k] = $v;
    }

    // Save conversation
    $history[] = ['role'=>'user','content'=>$msg];
    $history[] = ['role'=>'assistant','content'=>json_encode(['message'=>$bot_reply,'extracted'=>$extracted])];
    if (count($history) > 20) $history = array_slice($history, -20);

    $_SESSION[$sess_key] = ['history'=>$history, 'profile'=>$profile];

    // If ready — create the account
    $user_id = null; $website_url = null;
    if ($ready && !empty($profile['email']) && !empty($profile['password']) && !empty($profile['business_name'])) {
        try {
            // Check if email exists
            $exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $exists->execute([$profile['email']]);
            if (!$exists->fetch()) {
                $refer_code = strtoupper(substr(md5($profile['email'].time()), 0, 8));
                $slug = strtolower(preg_replace('/[^a-z0-9]/','',str_replace(' ','-',$profile['business_name']??'biz')));
                $stmt = $pdo->prepare("INSERT INTO users (business_name, email, password, whatsapp, category, city, membership, coins, refer_code, welcome_sent, profile_complete, created_at) VALUES (?,?,?,?,?,?,'free',100,?,0,0,NOW())");
                $stmt->execute([$profile['business_name'], $profile['email'], password_hash($profile['password'],PASSWORD_DEFAULT), $profile['whatsapp']??'', $profile['category']??'', $profile['city']??'', $refer_code]);
                $user_id = $pdo->lastInsertId();

                // Save business profile
                $pdo->prepare("INSERT INTO businesses (user_id, tagline, description, website_url, created_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE tagline=VALUES(tagline),description=VALUES(description)")->execute([$user_id, $profile['description']??'', $profile['description']??'', $profile['website_url']??'']);

                // Save products
                if (!empty($profile['products'])) {
                    $prods = explode(',', $profile['products']);
                    foreach ($prods as $p) {
                        $p = trim($p);
                        if ($p) $pdo->prepare("INSERT IGNORE INTO business_products (business_id, name, description, price) VALUES (?,?,?,0)")->execute([$user_id, $p, $p]);
                    }
                }

                // Trigger website generation via master agent flag
                $pdo->prepare("UPDATE users SET website_generate=1 WHERE id=?")->execute([$user_id]);

                // Welcome email
                require_once BASE . "/agent/mailer_agent.php";
                mailer_send('welcome', $profile['email'], ['name'=>$profile['business_name'],'refer_code'=>$refer_code,'user_id'=>$user_id], $pdo);

                $website_url = "/sites/{$slug}/";
                $_SESSION[$sess_key] = []; // Clear session
                $bot_reply .= "\n\n✅ Account created! Your referral code is **{$refer_code}**. Redirecting you to login...";
            } else {
                $bot_reply = "That email is already registered! Would you like to login instead? 👉 biznexus.in/auth/login.php";
                $ready = false;
            }
        } catch(Throwable $e) {
            $bot_reply = "There was an issue creating your account. Please try again or contact support.";
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

// ── Reset session ─────────────────────────────────────────────────────────────
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
.quick-replies{display:flex;flex-wrap:wrap;gap:6px;padding:0 16px 10px;background:#08080f}
.qr{padding:5px 12px;background:#1a1a2e;border:1px solid #2a2a3a;border-radius:50px;font-size:.75rem;color:#aaa;cursor:pointer;transition:.15s}
.qr:hover{border-color:var(--gold);color:var(--gold)}
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
      <div style="color:var(--muted);font-size:.75rem;margin-top:4px"><?= $pct ?>% complete · <a href="/register_business.php?reset=1" style="color:#333;font-size:.72rem">Start over</a></div>
      <div class="progress-bar"><div class="progress-fill" id="prog-fill" style="width:<?=$pct?>%"></div></div>
    </div>
    <!-- Profile chips -->
    <div class="profile-pills" id="profile-pills">
      <?php foreach(['business_name','category','city','email','whatsapp'] as $f): ?>
      <span class="pill <?= !empty($profile[$f])?'done':'' ?>" id="chip-<?=$f?>"><?= !empty($profile[$f]) ? '✓ '.htmlspecialchars($profile[$f]) : $f ?></span>
      <?php endforeach; ?>
    </div>
    <!-- Chat -->
    <div class="chat-area" id="chat">
      <div class="msg msg-bot">
        👋 Hi! I'm <strong>BizBot</strong>, your BizNexus onboarding assistant.<br>
        I'll help you register your business in just a few minutes through our friendly chat.<br><br>
        Let's start — <strong>what's your name and business name?</strong>
      </div>
    </div>
    <!-- Quick replies -->
    <div class="quick-replies" id="quick-replies">
      <span class="qr" onclick="send('I want to register my business')">Register my business 🚀</span>
      <span class="qr" onclick="send('What info do you need?')">What info needed?</span>
      <span class="qr" onclick="send('How long does this take?')">How long does this take?</span>
    </div>
    <!-- Input -->
    <div class="input-area">
      <input type="text" id="msg-input" placeholder="Type your message..." autocomplete="off">
      <button class="send-btn" onclick="send()">➤</button>
    </div>
  </div>
  <p style="text-align:center;color:var(--muted);font-size:.75rem;margin-top:16px">Already registered? <a href="/auth/login.php" style="color:var(--gold)">Login here</a></p>
</div>
<script>
let isTyping = false;
const chat = document.getElementById('chat');

function send(text) {
  const inp = document.getElementById('msg-input');
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

    // Update progress
    if (data.progress !== undefined) {
      document.getElementById('prog-fill').style.width = data.progress + '%';
    }

    // Update chips
    if (data.profile) {
      const fields = ['business_name','category','city','email','whatsapp'];
      fields.forEach(f => {
        const chip = document.getElementById('chip-'+f);
        if (chip && data.profile[f]) {
          chip.className = 'pill done';
          chip.textContent = '✓ ' + data.profile[f].substring(0,20);
        }
      });
    }

    if (data.ready && data.user_id) {
      appendMsg('🎉 <strong>Your account has been created!</strong> Redirecting to login...', 'system');
      setTimeout(()=>{ window.location = '/auth/login.php?registered=1'; }, 2500);
    }

    isTyping = false;
    chat.scrollTop = chat.scrollHeight;
  }).catch(()=>{ typing.remove(); isTyping=false; });
}

function appendMsg(text, type, raw=false) {
  const d = document.createElement('div');
  if (type === 'system') d.className = 'msg-system';
  else d.className = 'msg msg-' + type;
  d.innerHTML = text;
  chat.appendChild(d);
  chat.scrollTop = chat.scrollHeight;
  return d;
}

document.getElementById('msg-input').addEventListener('keypress', e=>{if(e.key==='Enter')send();});
</script>
</body>
</html>
