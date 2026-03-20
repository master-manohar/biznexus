<?php
session_start();
$logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Help Center - BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --g: #FFD700; --gr: #00ff88; --bg: #06060a; --c: #0e0e16; --b: #1e1e2e; --m: #555; }
        body { background: var(--bg); color: #e0e0f0; font-family: 'DM Sans', sans-serif; overflow-x: hidden; }
        .nav { background: rgba(6,6,10,.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,215,0,.1); padding: 13px 0; position: sticky; top: 0; z-index: 999; }
        .logo { font-family: 'Syne', sans-serif; font-size: 1.35rem; font-weight: 800; color: var(--g); text-decoration: none; }
        .logo span { color: var(--gr); }
        
        .help-hero { padding: 60px 0 40px; text-align: center; background: radial-gradient(circle at top, rgba(255,215,0,0.05), transparent); }
        .search-container { max-width: 700px; margin: 30px auto; position: relative; }
        .search-input { 
            background: var(--c); 
            border: 1px solid var(--b); 
            color: #fff; 
            padding: 20px 30px; 
            border-radius: 50px; 
            width: 100%; 
            font-size: 1.1rem;
            transition: 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .search-input:focus { outline: none; border-color: var(--g); box-shadow: 0 10px 40px rgba(255,215,0,0.1); }
        .search-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: var(--g); border: none; padding: 10px 25px; border-radius: 50px; font-weight: 700; }
        
        .mascot-small { width: 60px; height: 60px; margin-bottom: 20px; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        .kb-section { padding-bottom: 80px; }
        .kb-card { background: var(--c); border: 1px solid var(--b); border-radius: 16px; padding: 25px; height: 100%; transition: 0.3s; cursor: pointer; }
        .kb-card:hover { border-color: var(--g); transform: translateY(-5px); }
        .kb-card h5 { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--g); margin-bottom: 15px; }
        .kb-card p { font-size: 0.95rem; color: #aaa; line-height: 1.6; }

        .ticket-section { padding: 80px 0; background: var(--c); border-top: 1px solid var(--b); }
        .form-control { background: var(--bg); border: 1px solid var(--b); color: #fff; padding: 12px; border-radius: 8px; }
        .form-control:focus { background: var(--bg); color: #fff; border-color: var(--g); box-shadow: none; }
        
        #ai-response { display: none; margin-top: 30px; text-align: left; background: #13131a; border-left: 4px solid var(--gr); padding: 25px; border-radius: 12px; }
        .ai-badge { background: rgba(0,255,136,0.1); color: var(--gr); padding: 4px 12px; border-radius: 4px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 15px; display: inline-block; }
    </style>
</head>
<body>

<nav class="nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="/" class="logo">Biz<span>Nexus</span></a>
    <div>
        <a href="/" class="btn btn-outline-light btn-sm rounded-pill px-3">← Back Home</a>
    </div>
  </div>
</nav>

<section class="help-hero">
    <div class="container">
        <svg class="mascot-small" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 20 L50 10 M40 10 L60 10" stroke="#00ff88" stroke-width="3" stroke-linecap="round"/>
            <circle cx="50" cy="5" r="4" fill="#FFD700" />
            <rect x="25" y="25" width="50" height="40" rx="10" fill="#1e1e2e" stroke="#00ff88" stroke-width="3"/>
            <rect x="35" y="35" width="10" height="8" rx="2" fill="#00ff88"/>
            <rect x="55" y="35" width="10" height="8" rx="2" fill="#00ff88"/>
            <path d="M40 55 Q50 60 60 55" stroke="#FFD700" stroke-width="3" stroke-linecap="round" fill="none"/>
        </svg>
        <h1 style="font-family: 'Syne', sans-serif; font-weight: 800; font-size: clamp(2rem, 5vw, 3rem);">How can we help you <span style="color: var(--g);">today?</span></h1>
        <p class="text-muted mt-2">Ask Nexus AI anything about the platform, growth, or support.</p>
        
        <div class="search-container">
            <input type="text" id="help-search" class="search-input" placeholder="e.g. Why BizNexus? How to grow my business?">
            <button class="search-btn" onclick="askNexus()">Ask Nexus</button>
        </div>

        <div id="ai-response" class="mx-auto" style="max-width: 700px;">
            <div class="ai-badge">Nexus AI Reasoning</div>
            <h4 id="ai-title" style="color: var(--g); font-family: 'Syne', sans-serif; font-weight: 800;"></h4>
            <div id="ai-content" class="mt-3"></div>
            <div class="mt-4 pt-3 border-top border-secondary">
                <small class="text-muted">Was this helpful? <a href="#" class="text-success ms-2">Yes</a> <a href="#" class="text-danger ms-2">No</a></small>
            </div>
        </div>
    </div>
</section>

<section class="kb-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="kb-card" onclick="fillSearch('Why BizNexus?')">
                    <h5>Why BizNexus?</h5>
                    <p>Learn how our AI-powered matchmaking beats traditional directory listings by 10x in quality and price.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kb-card" onclick="fillSearch('How to grow?')">
                    <h5>Grow Your Business</h5>
                    <p>Discover our networking groups, referral system, and how to reach verified decision markers instantly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kb-card" onclick="location.href='#ticket-form'">
                    <h5>Raise a Ticket</h5>
                    <p>Facing a technical issue or need account help? Contact our human support team directly.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ticket-section" id="ticket-form">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="text-center mb-5">
                    <h2 style="font-family: 'Syne', sans-serif; font-weight: 800;">Contact <span style="color: var(--gr);">Support</span></h2>
                    <p class="text-muted">Fill out the form below and our team will get back to you within 24 hours.</p>
                </div>
                
                <form id="support-form">
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Subject</label>
                        <select name="subject" class="form-control" required>
                            <option value="">Select a topic</option>
                            <option value="Account Access">Account Access</option>
                            <option value="Lead Quality">Lead Quality</option>
                            <option value="Payment/Billing">Payment & Billing</option>
                            <option value="Technical Bug">Technical Bug</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Message</label>
                        <textarea name="message" rows="5" class="form-control" placeholder="Describe your issue in detail..." required></textarea>
                    </div>
                    <div id="form-feedback" class="mb-3" style="display:none;"></div>
                    <button type="submit" class="btn btn-gold w-100 py-3">Submit Ticket →</button>
                    <p class="text-center mt-3 mb-0 small text-muted">You can also email us at <span class="text-white">support@biznexus.in</span></p>
                </form>
            </div>
        </div>
    </div>
</section>

<footer class="py-5 border-top border-secondary text-center">
    <div class="container">
        <p class="text-muted small mb-0">&copy; 2024 BizNexus Network. All rights reserved.</p>
    </div>
</footer>

<script>
const kb = [
    {
        q: ["why biznexus", "what is biznexus", "about"],
        t: "The AI Difference",
        a: "BizNexus is India's 1st AI-powered SME network. Unlike old directories (IndiaMart/JustDial) that sell your data and charge for 'dead' phone numbers, BizNexus uses AI to match you with valid, high-intent leads that actually convert. <strong>We focus on Trust, not just Listings.</strong>"
    },
    {
        q: ["how to grow", "get more business", "increase sales"],
        t: "Growth Ecosystem",
        a: "To grow on BizNexus: <br>1. Join a verified Networking Group. <br>2. Give referrals to others to build Trust & earn VooCoins. <br>3. Use Coins to unlock premium leads and advanced CRM tools like our Quote & Invoice generator."
    },
    {
        q: ["voocoins", "coins", "points"],
        t: "VooCoins System",
        a: "VooCoins are the internal currency of trust on BizNexus. You earn them by helping others (giving referrals) or by being an active community member. They can be redeemed for Lead Unlocks and Premium Features."
    },
    {
        q: ["verified", "safety", "secure"],
        t: "Trust & Safety",
        a: "Every business on BizNexus undergoes a multi-layer verification process, including Aadhaar/PAN validation for premium members. This ensures you only talk to real, serious business owners."
    }
];

function fillSearch(val) {
    document.getElementById('help-search').value = val;
    askNexus();
}

function askNexus() {
    const query = document.getElementById('help-search').value.toLowerCase();
    const responseDiv = document.getElementById('ai-response');
    const title = document.getElementById('ai-title');
    const content = document.getElementById('ai-content');

    if (query.length < 3) return;

    let match = kb.find(item => item.q.some(keyword => query.includes(keyword)));

    responseDiv.style.display = 'block';
    if (match) {
        title.innerHTML = match.t;
        content.innerHTML = match.a;
    } else {
        title.innerHTML = "I'm still learning about that...";
        content.innerHTML = "That's a great question! I don't have a specific answer in my knowledge base yet, but our human team does. Please <strong>Raise a Ticket</strong> below and we'll provide the exact info you need!";
    }
    
    responseDiv.scrollIntoView({ behavior: 'smooth' });
}

document.getElementById('support-form').onsubmit = async function(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const feedback = document.getElementById('form-feedback');
    
    btn.disabled = true;
    btn.innerText = 'Sending...';
    
    const formData = new FormData(this);
    try {
        const response = await fetch('/api/support_request.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        feedback.style.display = 'block';
        if (result.status === 'success') {
            feedback.innerHTML = '<div class="alert alert-success">Ticket raised successfully! Your ticket ID is #' + Math.floor(Math.random() * 9000 + 1000) + '</div>';
            this.reset();
        } else {
            feedback.innerHTML = '<div class="alert alert-danger">' + result.message + '</div>';
        }
    } catch (error) {
        feedback.style.display = 'block';
        feedback.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
    }
    
    btn.disabled = false;
    btn.innerText = 'Submit Ticket →';
};
</script>

</body>
</html>
