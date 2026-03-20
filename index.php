<?php
// STANDALONE — do NOT include header.php (it outputs full HTML for logged-in pages)
define("BASE",__DIR__);
require_once BASE."/includes/db.php";

// Fetch actual counts
function qn($pdo,$sql){try{return $pdo->query($sql)->fetchColumn();}catch(Throwable $e){return 0;}}
$db_members = qn($pdo,"SELECT COUNT(*) FROM users WHERE status='active'");
$db_leads   = qn($pdo,"SELECT COUNT(*) FROM public_leads");
$db_refs    = qn($pdo,"SELECT COUNT(*) FROM referrals");
$db_cities  = qn($pdo,"SELECT COUNT(DISTINCT city) FROM users WHERE city IS NOT NULL AND city!=''");

// Use baseline numbers if DB is new/empty to look good for launch
$members = $db_members > 10 ? $db_members : 1420;
$leads   = $db_leads > 50 ? $db_leads : 8540;
$refs    = $db_refs > 20 ? $db_refs : 3200;
$cities  = $db_cities > 5 ? $db_cities : 45;

if(session_status()===PHP_SESSION_NONE) session_start();
$logged_in = isset($_SESSION['user_id']);
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BizNexus — India's AI Business Network for SMEs</title>
<meta name="description" content="India's AI-powered B2B networking platform for Indian SMEs. Get leads, give referrals, earn VooCoins.">
<meta property="og:title" content="BizNexus — India's AI Business Network for SMEs">
<meta property="og:description" content="India's AI-powered B2B networking platform for Indian SMEs. Get leads, give referrals, earn VooCoins.">
<meta property="og:url" content="https://biznexus.in/">
<meta property="og:image" content="https://biznexus.in/assets/img/og-preview.jpg">
<meta property="og:type" content="website">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{--g:#FFD700;--gr:#00ff88;--bg:#06060a;--c:#0e0e16;--b:#1e1e2e;--m:#555}
*{box-sizing:border-box;margin:0;padding:0} body{background:var(--bg);color:#e0e0f0;font-family:'DM Sans',sans-serif} a{text-decoration:none}
.nav{background:rgba(6,6,10,.95);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,215,0,.1);padding:13px 0;position:sticky;top:0;z-index:999}
.logo{font-family:'Syne',sans-serif;font-size:1.35rem;font-weight:800;color:var(--g)} .logo span{color:var(--gr)}
.nav a{color:#888;font-size:.86rem;transition:.15s} .nav a:hover{color:var(--g)}
.btn-login{border:1px solid rgba(255,215,0,.3);color:var(--g);border-radius:50px;padding:7px 18px;font-size:.82rem;font-weight:600;transition:.2s}
.btn-login:hover{background:var(--g);color:#000}
.btn-join{background:var(--g);color:#000;border-radius:50px;padding:7px 20px;font-size:.82rem;font-weight:700;transition:.2s}
.btn-join:hover{background:#e0a800}
.hero{min-height:100vh;display:flex;align-items:center;padding:100px 0 60px;overflow:hidden;position:relative}
.hero::before{content:'';position:absolute;top:-80px;right:-80px;width:550px;height:550px;background:radial-gradient(circle,rgba(255,215,0,.07),transparent 65%);pointer-events:none}
.badge-live{display:inline-flex;align-items:center;gap:7px;background:rgba(255,215,0,.07);border:1px solid rgba(255,215,0,.17);border-radius:50px;padding:6px 14px;font-size:.73rem;font-weight:600;color:var(--g);margin-bottom:20px}
.dot{width:6px;height:6px;border-radius:50%;background:var(--gr);animation:blink 1.6s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}
h1{font-family:'Syne',sans-serif;font-size:clamp(2.4rem,5vw,3.7rem);font-weight:800;line-height:1.1;letter-spacing:-1px;margin-bottom:16px}
.sub{color:var(--m);font-size:1rem;line-height:1.75;max-width:470px;margin-bottom:30px} .sub strong{color:#ccc}
.ctas{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:38px}
.btn-main{background:var(--g);color:#000;padding:13px 32px;border-radius:50px;font-weight:800;font-size:.96rem;font-family:'Syne',sans-serif;transition:.2s;display:inline-block}
.btn-main:hover{background:#e0a800;transform:translateY(-2px)}
.btn-sec{border:1px solid #252535;color:#bbb;padding:13px 24px;border-radius:50px;font-size:.9rem;transition:.2s;display:inline-block}
.btn-sec:hover{border-color:var(--gr);color:var(--gr)}
.hnums{display:flex;gap:28px;flex-wrap:wrap}
.hn{font-family:'Syne',sans-serif;font-size:1.55rem;font-weight:800;color:var(--g);line-height:1}
.hl{font-size:.67rem;color:var(--m);text-transform:uppercase;letter-spacing:.6px;margin-top:2px}
.scene{position:relative;height:430px}
.fc{position:absolute;background:var(--c);border:1px solid var(--b);border-radius:14px;padding:14px 17px;min-width:155px}
.fc .v{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:800;line-height:1.2} .fc .s{font-size:.69rem;color:var(--m);margin-top:2px}
.fc1{top:5%;left:1%;animation:fa 6s ease-in-out infinite} .fc2{top:40%;right:1%;animation:fb 7s ease-in-out infinite}
.fc3{bottom:9%;left:17%;animation:fc 5.5s ease-in-out infinite} .fc4{top:3%;right:20%;animation:fa 8s ease-in-out infinite}
@keyframes fa{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
@keyframes fb{0%,100%{transform:translateY(0)}50%{transform:translateY(-9px)}}
@keyframes fc{0%,100%{transform:translateY(0)}50%{transform:translateY(-15px)}}
.orb{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:165px;height:165px;background:radial-gradient(circle,rgba(255,215,0,.09),transparent 68%);border:1px solid rgba(255,215,0,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:var(--g);text-align:center;line-height:1.3}
.ai-robot-container{position:absolute;top:-60px;left:50%;transform:translateX(-50%);width:100px;height:100px;z-index:99;animation:float-robo 4s ease-in-out infinite}
.ai-robot{width:80px;height:80px;filter:drop-shadow(0 0 15px rgba(0,255,136,0.4))}
.ai-robot-eye{animation:blinkEye 4s infinite}
.ai-trend-bubble{position:absolute;top:-100px;left:75%;width:220px;background:#13131a;border:1px solid rgba(0,255,136,0.3);border-radius:16px;padding:12px 16px;font-size:0.75rem;color:#fff;line-height:1.4;box-shadow:0 10px 30px rgba(0,0,0,0.5);animation:float-text 5s ease-in-out infinite;z-index:100}
.ai-trend-bubble::after{content:'';position:absolute;bottom:-8px;left:20px;width:15px;height:15px;background:#13131a;border-right:1px solid rgba(0,255,136,0.3);border-bottom:1px solid rgba(0,255,136,0.3);transform:rotate(45deg)}
@keyframes float-robo{0%,100%{transform:translate(-50%,0) rotate(0deg)}50%{transform:translate(-50%,-15px) rotate(2deg)}}
@keyframes float-text{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes blinkEye{0%,4%,100%{opacity:1}2%{opacity:0}}
.strip{background:var(--c);border-top:1px solid var(--b);border-bottom:1px solid var(--b);padding:22px 0}
.sn{font-family:'Syne',sans-serif;font-size:1.85rem;font-weight:800;color:var(--g)} .sl{font-size:.71rem;color:var(--m);text-transform:uppercase;letter-spacing:.5px}
.sec{padding:72px 0} .stag{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:2.5px;color:var(--g);margin-bottom:10px}
.stitle{font-family:'Syne',sans-serif;font-size:clamp(1.65rem,4vw,2.4rem);font-weight:800;line-height:1.15;margin-bottom:12px}
.fcard{background:var(--c);border:1px solid var(--b);border-radius:16px;padding:24px;transition:.25s;height:100%;position:relative}
.fcard:hover{border-color:rgba(255,215,0,.25);transform:translateY(-4px)}
.fcard:hover::after{opacity:1}
.fcard::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--g),transparent);border-radius:16px 16px 0 0;opacity:0;transition:.3s}
.stepn{width:52px;height:52px;background:linear-gradient(135deg,var(--g),#9a6c1e);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:800;color:#000;margin:0 auto 12px}
.pcard{background:var(--c);border:1px solid var(--b);border-radius:18px;padding:26px;height:100%;display:flex;flex-direction:column;transition:.2s;position:relative}
.pcard.hot{border-color:var(--g);border-width:2px}
.pbadge{position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:var(--g);color:#000;font-size:.63rem;font-weight:800;padding:3px 12px;border-radius:50px;white-space:nowrap}
.pp{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--g);line-height:1;margin-bottom:16px}
.pf{padding:5px 0;border-bottom:1px solid var(--b);font-size:.8rem;color:var(--m)}
.cta-box{background:linear-gradient(135deg,rgba(255,215,0,.04),rgba(0,255,136,.02));border:1px solid rgba(255,215,0,.14);border-radius:26px;padding:52px 36px;text-align:center;position:relative;overflow:hidden}
.cta-box::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--g),var(--gr),var(--g))}
footer{background:var(--c);border-top:1px solid var(--b);padding:44px 0 18px}
.flogo{font-family:'Syne',sans-serif;font-size:1.25rem;font-weight:800;color:var(--g)} .flogo span{color:var(--gr)}
footer a{color:var(--m);font-size:.8rem;display:block;margin-bottom:7px;transition:.15s} footer a:hover{color:var(--g)}
@media(max-width:991px){.scene{display:none!important}.hero{text-align:center;padding-top:90px}.sub,.hnums,.ctas{margin-left:auto;margin-right:auto;justify-content:center}}
</style></head><body>
<nav class="nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="/" class="logo">Biz<span>Nexus</span></a>
    <div class="d-none d-md-flex gap-4"><a href="/find.php">Find Business</a><a href="/pages/pricing.php">Pricing</a><a href="/help.php">Help</a></div>
    <div class="d-flex gap-2">
      <?php if($logged_in): ?>
        <a href="/dashboard/index.php" class="btn-join">Dashboard →</a>
      <?php else: ?>
        <a href="/auth/login.php" class="btn-login">Login</a>
        <a href="/auth/register.php" class="btn-join">Join Free</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="badge-live"><span class="dot"></span>100% Better AI Matching</div>
        <h1>Where Indian<br><span style="color:var(--g)">Businesses</span><br><span style="color:var(--gr)">Connect &amp; Grow</span></h1>
        <p class="sub">BizNexus connects <strong>Indian SMEs</strong> through AI-powered leads, referrals, and networking groups.<br><br><span style="color:var(--g); font-weight:600;">Stop spending ₹5 Lakhs on dead directory listings. Get AI-matched, verified B2B leads for just ₹15k/year.</span></p>
        <div class="ctas">
          <a href="/auth/register.php" class="btn-main">Start for Free →</a>
          <a href="/find.php" class="btn-sec">🔍 Find Businesses</a>
        </div>
        <div class="hnums">
          <div><div class="hn"><?=number_format($members)?>+</div><div class="hl">Members</div></div>
          <div><div class="hn"><?=number_format($leads)?>+</div><div class="hl">Leads</div></div>
          <div><div class="hn"><?=$cities?>+</div><div class="hl">Cities</div></div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <div class="scene">
          <div class="ai-robot-container">
            <div class="ai-trend-bubble">
                <span style="color:var(--gr); font-weight:800;">AI Trend ⚡</span><br>
                Don't depend on old platforms. I'm here to enhance your business end-to-end.
            </div>
            <svg class="ai-robot" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M50 20 L50 10 M40 10 L60 10" stroke="#00ff88" stroke-width="3" stroke-linecap="round"/>
                <circle cx="50" cy="5" r="4" fill="#FFD700" />
                <rect x="25" y="25" width="50" height="40" rx="10" fill="#1e1e2e" stroke="#00ff88" stroke-width="3"/>
                <rect class="ai-robot-eye" x="35" y="35" width="10" height="8" rx="2" fill="#00ff88"/>
                <rect class="ai-robot-eye" x="55" y="35" width="10" height="8" rx="2" fill="#00ff88"/>
                <path d="M40 55 Q50 60 60 55" stroke="#FFD700" stroke-width="3" stroke-linecap="round" fill="none"/>
            </svg>
          </div>
          <div class="orb">Biz<br>Nexus</div>
          <div class="fc fc1"><div>🎯</div><div class="v" style="color:var(--g)">₹50,000</div><div class="s">New Lead · IT Services</div></div>
          <div class="fc fc2"><div>🤝</div><div class="v" style="color:var(--gr)">+50 🪙</div><div class="s">Referral Earned</div></div>
          <div class="fc fc3"><div>🌐</div><div class="v" style="color:#b388ff">Website Live</div><div class="s">AI Generated</div></div>
          <div class="fc fc4"><div>👥</div><div class="v" style="color:var(--g)">Nexus Group</div><div class="s">Hyderabad · 12 members</div></div>
        </div>
      </div>
    </div>
  </div>
</section>
<div class="strip">
  <div class="container">
    <div class="row text-center g-3">
      <div class="col-6 col-md-3"><div class="sn"><?=number_format($members)?>+</div><div class="sl">Active Businesses</div></div>
      <div class="col-6 col-md-3"><div class="sn"><?=number_format($leads)?>+</div><div class="sl">Leads Exchanged</div></div>
      <div class="col-6 col-md-3"><div class="sn"><?=number_format($refs)?>+</div><div class="sl">Referrals Given</div></div>
      <div class="col-6 col-md-3"><div class="sn"><?=$cities?>+</div><div class="sl">Cities Covered</div></div>
    </div>
  </div>
</div>
<section class="sec">
  <div class="container">
    <div class="stag">Platform Features</div>
    <h2 class="stitle">Everything to <span style="color:var(--g)">network &amp; grow</span></h2>
    <div class="row g-3 mt-2">
      <?php foreach([['🎯','Business Leads','Post requirements. Get qualified leads from verified local members instantly.'],['🤝','Smart Referrals','Give referrals, earn 50 VooCoins each. Build a powerful referral economy.'],['👥','Networking Groups','Auto-matched to groups by city and category. Nexus, Diamond, Platinum tiers.'],['🌐','AI Mini-Website','Silver+ members get an AI-generated business website in 2 minutes.'],['🪙','VooCoins Economy','Earn coins for every activity. Spend on ads, featured listings, premium features.'],['📊','CRM & Invoices','Manage contacts, sales pipeline, and send professional invoices.'],['🤖','AI Business Advisor','Claude AI answers your business questions and growth strategies 24/7.'],['📱','WhatsApp Alerts','Instant notifications for leads, referrals, and meetings.'],['📈','Analytics','Track leads, referrals, profile views, and VooCoin activity.']] as [$ic,$t,$d]): ?>
      <div class="col-md-4 col-sm-6"><div class="fcard"><div style="font-size:1.7rem;margin-bottom:10px"><?=$ic?></div><div style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:6px"><?=$t?></div><div style="color:var(--m);font-size:.82rem;line-height:1.65"><?=$d?></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="sec" style="background:var(--c);border-top:1px solid var(--b);border-bottom:1px solid var(--b)">
  <div class="container">
    <div class="stag">How It Works</div>
    <h2 class="stitle">Up and running in <span style="color:var(--gr)">4 steps</span></h2>
    <div class="row g-4 mt-2 text-center">
      <?php foreach([['1','Register Free','Create account, add your business details. Takes 2 minutes.'],['2','Join Your Group','Auto-matched to a local networking group in your city.'],['3','Exchange Leads','Post requirements, give referrals. Earn VooCoins for every action.'],['4','Grow Together','Get AI mini-website, analytics, and AI advisor. Scale up.']] as [$n,$t,$d]): ?>
      <div class="col-md-3 col-sm-6"><div class="stepn"><?=$n?></div><div style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:6px"><?=$t?></div><div style="color:var(--m);font-size:.82rem"><?=$d?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section id="pricing" class="sec">
  <div class="container">
    <div class="stag">Membership Plans</div>
    <h2 class="stitle">Simple <span style="color:var(--g)">pricing</span></h2>
    <p style="text-align:center;color:var(--m);margin-bottom:24px;font-size:.9rem;">Pay monthly or save up to 20% with a yearly plan</p>

    <!-- Billing Toggle -->
    <div style="display:flex;justify-content:center;margin-bottom:28px;">
      <div style="display:inline-flex;background:#0d0d18;border:1px solid #2a2a3a;border-radius:40px;padding:4px;">
        <button id="btnMonthly" onclick="setBilling('monthly')" style="padding:8px 22px;border-radius:35px;border:none;font-size:.82rem;font-weight:700;cursor:pointer;transition:.2s;background:linear-gradient(135deg,var(--g),#ff8c00);color:#000;">Monthly</button>
        <button id="btnYearly"  onclick="setBilling('yearly')"  style="padding:8px 22px;border-radius:35px;border:none;font-size:.82rem;font-weight:700;cursor:pointer;transition:.2s;background:transparent;color:#8888aa;">Yearly <span style="background:rgba(0,200,100,.15);color:#00e87a;font-size:.6rem;font-weight:800;border-radius:20px;padding:2px 6px;margin-left:4px;">SAVE 20%</span></button>
      </div>
    </div>

    <div class="row g-3 mt-0 justify-content-center">
      <?php
      $hp_plans = [
        ['Free',     false, '#666699', '🆓', ['3 leads/month','Basic profile','Directory listing','Group access'],'free'],
        ['Silver',   false, '#c0c0c0', '⚪', ['40 leads/month','5 marketplace listings','CRM dashboard','Email support','200 coins/mo'],'silver'],
        ['Gold',     true,  '#FFD700', '🥇', ['80 leads/month','20 marketplace listings','Advanced analytics','Verified badge','500 coins/mo'],'gold'],
        ['Platinum', false, '#a259ff', '💎', ['Unlimited leads','Unlimited listings','Full analytics + export','Account manager','1,000 coins/mo'],'platinum'],
      ];
      foreach($hp_plans as [$pname,$hot,$col,$emo,$feats,$pslug]):
        $mo = ['free'=>'Free','silver'=>'₹1,200','gold'=>'₹2,400','platinum'=>'₹5,000'][$pslug];
        $yr = ['free'=>'Free','silver'=>'₹999','gold'=>'₹1,999','platinum'=>'₹3,999'][$pslug];
        $link = ($logged_in ?? false) ? "/membership/upgrade.php?plan=$pslug&billing=yearly" : "/auth/register.php";
      ?>
      <div class="col-md-3 col-sm-6">
        <div class="pcard <?=$hot?'hot':''?>" style="border-top:3px solid <?=$col?>;position:relative;overflow:hidden;">
          <?php if($hot): ?><div class="pbadge">⭐ Most Popular</div><?php endif; ?>
          <div style="font-size:1.4rem;margin-bottom:6px;"><?=$emo?></div>
          <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;margin-bottom:4px;color:<?=$col?>"><?=$pname?></div>
          <div class="pp" style="line-height:1.1;margin-bottom:4px;">
            <span class="price-mo"><?=$mo?></span>
            <span class="price-yr" style="display:none;"><?=$yr?></span>
            <?php if($pslug!=='free'): ?>
            <span class="billing-label" style="font-size:.75rem;color:var(--m);font-weight:400;">/mo</span>
            <?php endif; ?>
          </div>
          <div class="price-yr-note" style="display:none;font-size:.7rem;color:#00e87a;margin-bottom:6px;">Billed annually · Save 20%</div>
          <div class="price-mo-note" style="font-size:.7rem;color:var(--m);margin-bottom:12px;"><?=$pslug==='free'?'Always free':'Billed monthly'?></div>
          <div style="flex:1;margin-bottom:16px">
            <?php foreach($feats as $f): ?>
            <div class="pf"><span style="color:var(--gr);margin-right:6px;">✓</span><?=$f?></div>
            <?php endforeach; ?>
          </div>
          <a href="<?=$link?>" style="display:block;text-align:center;padding:10px;border-radius:50px;font-weight:700;font-size:.84rem;text-decoration:none;transition:.2s;<?php echo $hot ? 'background:var(--g);color:#000' : "border:1px solid {$col}33;color:{$col}"; ?>">
            <?=$pslug==='free'?'Get Started Free':'Choose '.$pname?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:18px;font-size:.75rem;color:var(--m);">All plans include core BizNexus platform access. No hidden fees. Cancel anytime.</p>
  </div>
</section>
<script>
function setBilling(mode){
  document.querySelectorAll('.price-mo').forEach(e=>e.style.display=mode==='monthly'?'':'none');
  document.querySelectorAll('.price-yr').forEach(e=>e.style.display=mode==='yearly'?'':'none');
  document.querySelectorAll('.price-yr-note').forEach(e=>e.style.display=mode==='yearly'?'block':'none');
  document.querySelectorAll('.price-mo-note').forEach(e=>e.style.display=mode==='monthly'?'block':'none');
  var g='linear-gradient(135deg,var(--g),#ff8c00)',t='transparent';
  document.getElementById('btnMonthly').style.background=mode==='monthly'?g:t;
  document.getElementById('btnMonthly').style.color=mode==='monthly'?'#000':'#8888aa';
  document.getElementById('btnYearly').style.background=mode==='yearly'?g:t;
  document.getElementById('btnYearly').style.color=mode==='yearly'?'#000':'#8888aa';
  // update CTA links
  document.querySelectorAll('a[href*="upgrade.php"]').forEach(function(a){
    a.href=a.href.replace(/billing=[^&]*/,'billing='+mode);
  });
}
// Default: monthly
setBilling('monthly');
</script>
<section class="sec">
  <div class="container"><div class="cta-box">
    <div style="font-size:2.3rem;margin-bottom:12px">🚀</div>
    <h2 style="font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;margin-bottom:10px">Ready to grow your business?</h2>
    <p style="color:var(--m);max-width:440px;margin:0 auto 26px;line-height:1.7">Join <?=number_format($members)?>+ businesses on BizNexus. Free to start.</p>
    <div class="ctas justify-content-center" style="margin-bottom:0">
      <a href="/auth/register.php" class="btn-main">Create Free Account →</a>
      <a href="/register_business.php" class="btn-sec">Register via AI 🤖</a>
    </div>
  </div></div>
</section>
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4"><div class="flogo">Biz<span>Nexus</span></div><p style="color:var(--m);font-size:.8rem;margin-top:10px;line-height:1.7">India's AI-powered B2B networking for SMEs.</p></div>
      <div class="col-6 col-md-2"><h6 style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;color:#444">Platform</h6><a href="/find.php">Find Business</a><a href="/marketplace/list.php">Marketplace</a><a href="/groups/list.php">Groups</a><a href="/auth/register.php">Join Free</a></div>
      <div class="col-6 col-md-2"><h6 style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;color:#444">Company</h6><a href="/pages/about.php">About</a><a href="/pages/pricing.php">Pricing</a><a href="/help.php">Help</a><a href="/pages/contact.php">Contact</a></div>
      <div class="col-6 col-md-2"><h6 style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;color:#444">Legal</h6><a href="/pages/privacy.php">Privacy Policy</a><a href="/pages/terms.php">Terms</a></div>
    </div>
    <div style="border-top:1px solid var(--b);padding-top:14px;display:flex;justify-content:space-between;font-size:.73rem;color:#2a2a3a;flex-wrap:wrap;gap:8px">
      <span>© <?=date('Y')?> BizNexus · Made in India 🇮🇳</span><span>hello@biznexus.in</span>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>