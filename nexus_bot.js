(function() {
    const BIZNEXUS_URL = "https://biznexus.in";
    
    // Create UI Elements
    const bubble = document.createElement('div');
    bubble.id = 'biznexus-bubble';
    bubble.innerHTML = '🤖';
    bubble.style.cssText = "position:fixed;bottom:20px;right:20px;width:60px;height:60px;background:#FFD700;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);z-index:99999;transition:transform 0.3s;";
    
    const frameWrap = document.createElement('div');
    frameWrap.id = 'biznexus-frame-wrap';
    frameWrap.style.cssText = "position:fixed;bottom:90px;right:20px;width:350px;height:500px;background:#13131a;border:1px solid #2a2a3a;border-radius:15px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.5);z-index:99999;display:none;flex-direction:column;";
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = "background:#0d0d16;padding:15px;border-bottom:1px solid #2a2a3a;color:#FFD700;font-weight:bold;display:flex;justify-content:space-between;align-items:center;";
    header.innerHTML = "<span>BizNexus AI Assistant</span><span id='biznexus-close' style='cursor:pointer;'>×</span>";
    
    // Iframe
    const iframe = document.createElement('iframe');
    iframe.src = BIZNEXUS_URL + "/agent/nexus_inquiry.php?embed=1";
    iframe.style.cssText = "width:100%;height:100%;border:none;";
    
    frameWrap.appendChild(header);
    frameWrap.appendChild(iframe);
    
    document.body.appendChild(bubble);
    document.body.appendChild(frameWrap);
    
    // Interaction
    bubble.onmouseover = () => bubble.style.transform = "scale(1.1)";
    bubble.onmouseout = () => bubble.style.transform = "scale(1)";
    bubble.onclick = () => {
        frameWrap.style.display = frameWrap.style.display === 'none' ? 'flex' : 'none';
    };
    
    document.getElementById('biznexus-close').onclick = (e) => {
        e.stopPropagation();
        frameWrap.style.display = 'none';
    };
})();
