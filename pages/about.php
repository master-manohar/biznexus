<?php
// /pages/about.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - BizNexus | Founded by Master Manohar Nc</title>
    <meta name="description" content="Discover the story of BizNexus, India's autonomous B2B ecosystem founded by Master Manohar Nc. Learn about our AI-driven mission for SMEs.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- SEO Schema.org Markup for Google -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "BizNexus",
      "url": "https://biznexus.in",
      "logo": "https://biznexus.in/assets/img/logo.png",
      "founder": {
        "@type": "Person",
        "name": "Master Manohar Nc",
        "jobTitle": "Founder & CEO"
      },
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Hyderabad",
        "addressRegion": "Telangana",
        "addressCountry": "IN"
      },
      "sameAs": [
        "https://www.linkedin.com/company/biznexus-india"
      ]
    }
    </script>

    <style>
        body { background: #06060a; color: #e0e0f0; font-family: 'DM Sans', sans-serif; }
        .nav { background: rgba(6,6,10,.95); border-bottom: 1px solid rgba(255,215,0,.1); padding: 13px 0; }
        .logo { font-family: 'Syne', sans-serif; font-size: 1.35rem; font-weight: 800; color: #FFD700; text-decoration: none; }
        .logo span { color: #00ff88; }
        .content-box { background: #0e0e16; border: 1px solid #1e1e2e; border-radius: 14px; padding: 50px; text-align: left; }
        .founder-card { background: linear-gradient(135deg, rgba(255,215,0,0.05), transparent); border: 1px solid rgba(255,215,0,0.1); border-radius: 12px; padding: 25px; margin-top: 30px; }
    </style>
</head>
<body>
    <nav class="nav">
      <div class="container d-flex align-items-center justify-content-between">
        <a href="/" class="logo"><img src="/assets/img/logo-icon.png" alt="BizNexus" style="height:28px; width:auto; vertical-align:middle; margin-right:8px; filter: drop-shadow(0 0 5px rgba(255,215,0,0.5));">Biz<span>Nexus</span></a>
        <div><a href="/" class="btn btn-outline-light btn-sm rounded-pill px-3">← Back Home</a></div>
      </div>
    </nav>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="content-box">
                    <h1 style="font-family:'Syne',sans-serif;font-weight:800;color:#00ff88;">Our Mission</h1>
                    <p class="mt-4" style="line-height:1.8; color:#bbb; font-size:1.1rem;">
                        BizNexus is India's leading <b>Autonomous B2B Ecosystem</b>. In a world where SMEs are often trapped by expensive, low-value directory listings, we provide a high-performance alternative powered by state-of-the-art AI. 
                        <br><br>
                        Our platform doesn't just list businesses; it <b>hunts</b> for opportunities. Through our AI Scout technology, we identify real-time B2B requirements across the web and match them directly with our verified members.
                    </p>

                    <div class="founder-card">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div style="width: 80px; height: 80px; border-radius: 50%; background: #FFD700; color:#000; display:flex; align-items:center; justify-content:center; font-size:2rem; font-family:'Syne', sans-serif; font-weight:800;">MN</div>
                            </div>
                            <div class="col">
                                <h3 style="font-family:'Syne',sans-serif; font-weight:800; color:#FFD700; margin:0;">Master Manohar Nc</h3>
                                <p style="color:#888; margin:0;">Founder & CEO, BizNexus</p>
                            </div>
                        </div>
                        <p class="mt-3" style="color:#d0d0d0; font-style: italic;">
                            "Our goal is to democratize B2B leads for every Indian SME. We are building the heartbeat of the modern Indian economy."
                        </p>
                    </div>

                    <div class="mt-5 pt-3" style="border-top: 1px solid #1e1e2e;">
                        <h5 style="color:#FFD700;">Corporate Office</h5>
                        <p style="color:#888;">
                            BizNexus Digital Platforms<br>
                            Hyderabad, Telangana, India<br>
                            Email: hello@biznexus.in
                        </p>
                    </div>

                    <a href="/auth/register.php" class="btn btn-warning mt-4 fw-bold px-5 py-3 rounded-pill">Join the Network</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
