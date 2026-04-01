<?php
// includes/turbo_lead_bar.php
// High-conversion sticky lead bar for SEO pages

$current_cat = $_GET['category'] ?? $_GET['q'] ?? 'Premium';
$current_city = $_GET['city'] ?? 'India';

// Don't show if already submitted in this session
if (isset($_SESSION['lead_submitted'])) return;
?>
<style>
    .turbo-lead-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(10px);
        border-top: 2px solid #fbbf24;
        padding: 15px 20px;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        box-shadow: 0 -10px 25px rgba(0,0,0,0.5);
        transition: transform 0.3s ease;
    }
    .turbo-lead-bar.hidden { transform: translateY(100%); }
    .turbo-lead-input {
        background: #1e293b;
        border: 1px solid #334155;
        color: #fff;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 0.9rem;
        width: 250px;
    }
    .turbo-lead-btn {
        background: #fbbf24;
        color: #000;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
    }
    @media (max-width: 768px) {
        .turbo-lead-bar { flex-direction: column; gap: 10px; padding: 20px; text-align: center; }
        .turbo-lead-input { width: 100%; }
        .turbo-lead-btn { width: 100%; }
    }
</style>

<div id="turboLeadBar" class="turbo-lead-bar">
    <div class="lead-text">
        <span style="color:#fbbf24; font-weight:800;">⚡ BIZNEXUS CONNECT:</span> 
        Get verified quotes for <b><?= htmlspecialchars($current_cat) ?></b> in <b><?= htmlspecialchars($current_city) ?></b> instantly!
    </div>
    <form id="turboLeadForm" style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
        <input type="text" name="name" class="turbo-lead-input" placeholder="Your Name" required>
        <input type="text" name="whatsapp" class="turbo-lead-input" placeholder="WhatsApp / Mobile Phone" required>
        <button type="submit" class="turbo-lead-btn">GET QUOTES NOW →</button>
    </form>
    <button onclick="document.getElementById('turboLeadBar').classList.add('hidden')" style="background:none; border:none; color:#64748b; cursor:pointer; position:absolute; right:10px; top:10px;">✕</button>
</div>

<script>
document.getElementById('turboLeadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.innerHTML = 'Connecting...';
    btn.disabled = true;

    const formData = new FormData(this);
    formData.append('category', '<?= addslashes($current_cat) ?>');
    formData.append('city', '<?= addslashes($current_city) ?>');
    formData.append('url', window.location.href);

    fetch('/api/capture_public_lead.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            document.getElementById('turboLeadBar').innerHTML = '<div style="color:#4ade80; font-weight:700;">✅ SUCCESS! Our matched businesses will contact you on WhatsApp shortly.</div>';
            setTimeout(() => { document.getElementById('turboLeadBar').classList.add('hidden'); }, 3000);
        }
    });
});
</script>
