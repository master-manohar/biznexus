(function() {
    if (document.getElementById('biznexus-chat-widget-wrapper')) return;

    const config = window.BizBotConfig || { endpoint: '/api/support_bot_chat.php', context: '' };

    const css = `
    .chat-widget{position:fixed;bottom:24px;right:24px;z-index:9999;font-family:'DM Sans',sans-serif;}
    .chat-toggle{width:60px;height:60px;background:linear-gradient(135deg,#FFD700,#e6c200);border-radius:50%;
                 display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#000;
                 cursor:pointer;box-shadow:0 8px 24px rgba(255,215,0,0.3);transition:transform 0.3s cubic-bezier(0.175,0.885,0.32,1.275);
                 border:none;outline:none;}
    .chat-toggle:hover{transform:scale(1.1);}
    .chat-panel{position:absolute;bottom:80px;right:0;width:350px;height:500px;
                background:#13131a;border:1px solid #2a2a3a;border-radius:20px;
                box-shadow:0 12px 40px rgba(0,0,0,0.5);display:flex;flex-direction:column;
                overflow:hidden;transform:translateY(20px);opacity:0;pointer-events:none;
                transition:all 0.3s cubic-bezier(0.2,0.8,0.2,1);transform-origin:bottom right;}
    .chat-panel.active{transform:translateY(0);opacity:1;pointer-events:all;}
    .chat-header{background:linear-gradient(135deg,#1a1a24,#13131a);padding:18px 20px;
                 border-bottom:1px solid #2a2a3a;display:flex;align-items:center;justify-content:space-between;
                 color:#FFD700;}
    .chat-title{display:flex;align-items:center;gap:12px;font-weight:700;font-size:1.1rem;}
    .chat-avatar{width:36px;height:36px;border-radius:50%;background:#FFD700;
                 display:flex;align-items:center;justify-content:center;color:#000;font-size:1.2rem;}
    .close-chat{background:none;border:none;color:#888;font-size:1.4rem;cursor:pointer;transition:color 0.2s;}
    .close-chat:hover{color:#fff;}
    .chat-body{flex:1;padding:20px;overflow-y:auto;display:flex;flex-direction:column;gap:12px;}
    .chat-msg{max-width:85%;padding:10px 14px;font-size:0.85rem;line-height:1.5;animation:msgFadeIn 0.3s ease;}
    @keyframes msgFadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .msg-bot{background:#1e1e2d;color:#e8e8f0;border-radius:10px;border-left:3px solid #FFD700;align-self:flex-start;}
    .msg-user{background:#FFD700;color:#000;border-radius:10px;align-self:flex-end;font-weight:500;}
    .msg-bot a {color:#FFD700;text-decoration:underline;}
    .chat-input-area{padding:14px;background:#13131a;border-top:1px solid #2a2a3a;display:flex;gap:8px;align-items:center;}
    .chat-input{flex:1;background:#0f0f18;border:1px solid #2a2a3a;color:#fff;
                padding:10px 14px;border-radius:6px;font-size:0.85rem;outline:none;transition:border-color 0.2s;}
    .chat-input:focus{border-color:#FFD700;}
    .chat-send{width:38px;height:38px;background:#FFD700;color:#000;border:none;
               border-radius:6px;display:flex;align-items:center;justify-content:center;
               cursor:pointer;transition:opacity 0.2s;font-weight:bold;}
    .chat-send:hover{opacity:0.85;}
    .chat-typing{display:inline-flex;gap:4px;padding:8px 12px;font-size:0.75rem;color:#888;align-self:flex-start;}
    @media(max-width:480px) {
        .chat-panel {width:calc(100vw - 32px);height:500px;right:-8px;bottom:70px;}
    }
    `;

    const style = document.createElement('style');
    style.innerHTML = css;
    document.head.appendChild(style);

    const html = `
    <div id="biznexus-chat-widget-wrapper" class="chat-widget">
        <div class="chat-panel" id="bn-chatPanel">
            <div class="chat-header">
                <div class="chat-title">
                    <div class="chat-avatar">⚡</div>
                    <div>
                        <div style="font-size:1rem;">BizNexus Agent</div>
                        <div style="font-size:0.7rem;color:#00ff88;font-weight:400;">Online</div>
                    </div>
                </div>
                <button class="close-chat" id="bn-closeChat">×</button>
            </div>
            <div class="chat-body" id="bn-chatBody"></div>
            <div class="chat-input-area">
                <input type="text" class="chat-input" id="bn-chatInput" placeholder="Ask me anything..." autocomplete="off">
                <button class="chat-send" id="bn-sendChat">➤</button>
            </div>
        </div>
        <button class="chat-toggle" id="bn-chatToggle">💬</button>
    </div>
    `;

    document.body.insertAdjacentHTML('beforeend', html);

    const toggleBtn = document.getElementById('bn-chatToggle');
    const closeBtn = document.getElementById('bn-closeChat');
    const panel = document.getElementById('bn-chatPanel');
    const input = document.getElementById('bn-chatInput');
    const sendBtn = document.getElementById('bn-sendChat');
    const body = document.getElementById('bn-chatBody');
    let isTyping = false;
    let chatInit = false;

    function formatText(t){
        return t.replace(/\\*\\*(.*?)\\*\\*/g,'<strong>$1</strong>').replace(/\\n/g,'<br>');
    }

    function triggerWebsiteGen() {
        const loadingMsg = document.createElement('div');
        loadingMsg.style.cssText = "background: #111; padding: 10px; border-radius: 10px; margin-bottom: 10px; border: 1px dashed #FFD700; font-size: 0.8rem; color: #fff;";
        loadingMsg.innerHTML = "🚀 <strong>Website Generator Agent:</strong> Building your premium one-page site... this usually takes 10-20 seconds.";
        body.appendChild(loadingMsg);
        body.scrollTop = body.scrollHeight;

        fetch('/api/generate_website.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            loadingMsg.remove();
            if (data.success) {
                addMsg("✅ <strong>Website matches your vision!</strong> Your professional site is now live.<br><br>Primary Link: <a href='"+data.url+"' target='_blank'>"+data.slug+".biznexus.in</a><br><br>Direct Link: <a href='https://biznexus.in/sites/"+data.slug+"/' target='_blank'>biznexus.in/sites/"+data.slug+"/</a>", 'bot');
            } else {
                addMsg("❌ I'm sorry, I hit a snag while building the site: " + data.error, 'bot');
            }
        }).catch(() => {
            loadingMsg.remove();
            addMsg("❌ Connection lost during website generation.", 'bot');
        });
    }

    function addMsg(txt, type, processTriggers = false){
        const div=document.createElement('div');
        div.className='chat-msg msg-'+type;
        
        let processedText = txt;
        
        if (processTriggers && type === 'bot') {
            if (processedText.includes('[TRIGGER_WEBSITE_GEN]')) {
                processedText = processedText.replace('[TRIGGER_WEBSITE_GEN]', '');
                setTimeout(triggerWebsiteGen, 500);
            }
            const redirectMatch = processedText.match(/\\[REDIRECT_FIND:(.*?):(.*?)\\]/);
            if (redirectMatch) {
                processedText = processedText.replace(redirectMatch[0], '');
                const q = encodeURIComponent(redirectMatch[1].trim());
                const c = encodeURIComponent(redirectMatch[2].trim());
                setTimeout(() => window.location.href = '/find.php?q=' + q + '&city=' + c, 2000);
                processedText += "<br><br><em>Redirecting you to the search page now...</em>";
            }
        }

        div.innerHTML=type==='bot'?formatText(processedText):processedText;
        if(div.innerHTML.trim() !== "") {
            body.appendChild(div);
            body.scrollTop=body.scrollHeight;
        }
    }

    function showTyping(){
        const div=document.createElement('div');
        div.className='chat-typing';
        div.id='bn-typingInd';
        div.innerText='Agent is typing...';
        body.appendChild(div);
        body.scrollTop=body.scrollHeight;
    }

    function removeTyping(){
        const ind=document.getElementById('bn-typingInd');
        if(ind) ind.remove();
    }

    function send(msg = null){
        const txt= msg !== null ? msg : input.value.trim();
        if(msg === null && (!txt || isTyping)) return;
        
        if(msg === null) {
            input.value='';
            addMsg(txt,'user');
            isTyping=true;
        }
        
        showTyping();
        
        let formData = new URLSearchParams();
        formData.append(config.endpoint.includes('public') ? 'message' : 'msg', txt);
        if (msg === '') formData.append('init', '1');
        if (config.context) formData.append('context', config.context);

        fetch(config.endpoint, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: formData.toString()
        }).then(r=>r.json()).then(data=>{
            removeTyping();
            isTyping=false;
            if(data.reply) addMsg(data.reply,'bot', true);
        }).catch(e=>{
            removeTyping();
            isTyping=false;
            addMsg('Sorry, connection failed.','bot');
        });
    }

    sendBtn.addEventListener('click', () => send());
    input.addEventListener('keypress', e => { if(e.key==='Enter') send(); });
    
    toggleBtn.addEventListener('click', ()=>{
        panel.classList.toggle('active');
        if(panel.classList.contains('active')){
            input.focus();
            if(!chatInit) {
                send(''); 
                chatInit = true;
            }
        }
    });

    closeBtn.addEventListener('click', ()=>panel.classList.remove('active'));

    if (config.autoOpen) {
        setTimeout(()=>{
            if(!panel.classList.contains('active')){
                toggleBtn.click();
            }
        }, config.autoOpenDelay || 800);
    }
})();
