<!-- Help Widget -->
<div id="helpWidget" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999;">
    <button id="helpBtn" style="width: 60px; height: 60px; border-radius: 50%; background: #FFD700; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.4); color: #000; font-size: 1.5rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-headset"></i>
    </button>
    <div id="helpForm" style="display: none; position: absolute; bottom: 80px; right: 0; width: 350px; background: #13131a; border: 1px solid #2a2a3a; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); padding: 25px;">
        <h5 style="color: #FFD700; font-weight: 800; margin-bottom: 20px;">BizNexus Support ⚡</h5>
        <form id="supportSubmitForm">
            <div class="mb-3">
                <label style="color: #888; font-size: 0.8rem; display: block; margin-bottom: 5px;">Subject</label>
                <input type="text" id="supportSubject" class="form-control" style="background:#0f0f18; border:1px solid #2a2a3a; color:#fff" placeholder="Technical Issue / Question" required>
            </div>
            <div class="mb-4">
                <label style="color: #888; font-size: 0.8rem; display: block; margin-bottom: 5px;">How can we help?</label>
                <textarea id="supportMessage" class="form-control" style="background:#0f0f18; border:1px solid #2a2a3a; color:#fff" rows="4" placeholder="Describe your issue here..." required></textarea>
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-bold py-2 rounded-3" id="supportSendBtn">Send Request</button>
        </form>
    </div>
</div>
<!-- BookAnEvent Company Footer -- required for Razorpay compliance -->
<div style="margin-left:var(--sidebar-width,250px);padding:14px 28px;border-top:1px solid #1a1a2a;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:.72rem;color:#555577;">
    <div>
        <span style="color:#8888aa;">© 2025 <strong style="color:#9090b8;">BizNexus</strong> -- A product of
        <a href="https://bookanevent.in" target="_blank" style="color:#FFD700;text-decoration:none;font-weight:600;">BookAnEvent</a>.
        All rights reserved.</span>
    </div>
    <div style="display:flex;gap:16px;">
        <a href="/pages/terms.php" style="color:#555577;text-decoration:none;">Terms</a>
        <a href="/pages/privacy.php" style="color:#555577;text-decoration:none;">Privacy</a>
        <a href="/pages/contact.php" style="color:#555577;text-decoration:none;">Contact</a>
        <a href="/pages/about.php" style="color:#555577;text-decoration:none;">About</a>
    </div>
</div>

</main>

<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }

    // Help Widget Logic
    const helpBtn = document.getElementById('helpBtn');
    const helpForm = document.getElementById('helpForm');
    if(helpBtn) {
        helpBtn.addEventListener('click', () => {
            const isVisible = helpForm.style.display === 'block';
            helpForm.style.display = isVisible ? 'none' : 'block';
            helpBtn.innerHTML = isVisible ? '<i class="fas fa-headset"></i>' : '<i class="fas fa-times"></i>';
        });
    }

    const supportForm = document.getElementById('supportSubmitForm');
    if(supportForm) {
        supportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('supportSendBtn');
            const sub = document.getElementById('supportSubject').value;
            const msg = document.getElementById('supportMessage').value;
            
            btn.disabled = true;
            btn.innerText = 'Sending...';

            fetch('/api/support_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `subject=${encodeURIComponent(sub)}&message=${encodeURIComponent(msg)}`
            }).then(r => r.json()).then(data => {
                if(data.success) {
                    helpForm.innerHTML = '<div class="text-center py-4"><i class="fas fa-check-circle text-success mb-3" style="font-size:3rem"></i><h5 class="text-white">Request Sent!</h5><p style="color:#888; font-size:0.85rem">Our team will get back to you shortly.</p><button class="btn btn-outline-warning btn-sm mt-3" onclick="location.reload()">Close</button></div>';
                } else {
                    alert('Error: ' + data.error);
                    btn.disabled = false;
                    btn.innerText = 'Send Request';
                }
            });
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
