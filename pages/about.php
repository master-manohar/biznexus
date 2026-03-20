<?php
// /pages/about.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - BizNexus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: #06060a; color: #e0e0f0; font-family: 'DM Sans', sans-serif; }
        .nav { background: rgba(6,6,10,.95); border-bottom: 1px solid rgba(255,215,0,.1); padding: 13px 0; }
        .logo { font-family: 'Syne', sans-serif; font-size: 1.35rem; font-weight: 800; color: #FFD700; text-decoration: none; }
        .logo span { color: #00ff88; }
        .content-box { background: #0e0e16; border: 1px solid #1e1e2e; border-radius: 14px; padding: 40px; text-align: center; }
    </style>
</head>
<body>
    <nav class="nav">
      <div class="container d-flex align-items-center justify-content-between">
        <a href="/" class="logo">Biz<span>Nexus</span></a>
        <div><a href="/" class="btn btn-outline-light btn-sm rounded-pill px-3">← Back Home</a></div>
      </div>
    </nav>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="content-box">
                    <h1 style="font-family:'Syne',sans-serif;font-weight:800;color:#00ff88;">About BizNexus</h1>
                    <p class="mt-4" style="line-height:1.8; color:#bbb; font-size:1.1rem;">
                        BizNexus is India's rapidly growing B2B autonomous ecosystem. Powered by Claude AI, we generate high-quality, verified leads matched directly to Indian SMEs.<br><br>
                        Stop paying ₹5 Lakh+ for dead directories, and leverage our closed-loop pipeline for just ₹15,000/year. 
                    </p>
                    <a href="/auth/register.php" class="btn btn-warning mt-4 fw-bold">Join the Network</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
