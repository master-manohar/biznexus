<?php
// admin/seo_monitor.php
require_once __DIR__ . '/../includes/db.php';
$total = $pdo->query("SELECT COUNT(*) FROM seo_pages")->fetchColumn();
$recent = $pdo->query("SELECT category, city, last_generated FROM seo_pages ORDER BY id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>BizNexus Growth Monitor</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: sans-serif; padding: 40px; text-align: center; }
        .stat { font-size: 4rem; color: #fbbf24; font-weight: bold; margin: 20px 0; }
        .box { background: #1e293b; padding: 20px; border-radius: 12px; display: inline-block; min-width: 300px; border: 1px solid #334155; }
        table { margin: 20px auto; color: #94a3b8; border-collapse: collapse; }
        td, th { padding: 10px; border-bottom: 1px solid #334155; }
        .success { color: #4ade80; }
    </style>
    <meta http-equiv="refresh" content="30">
</head>
<body>
    <h1>🚀 BizNexus Live Growth</h1>
    <div class="box">
        <div class="stat"><?= number_format($total) ?></div>
        <div>SEO Landing Pages Live</div>
    </div>
    
    <h2>Latest Generated Pages</h2>
    <table>
        <tr><th>Category</th><th>City</th><th>Time</th></tr>
        <?php foreach ($recent as $r): ?>
        <tr>
            <td><?= $r['category'] ?></td>
            <td><?= $r['city'] ?></td>
            <td class="success"><?= date('H:i:s', strtotime($r['last_generated'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><i>Auto-refreshing every 30 seconds... Keep this tab open!</i></p>
</body>
</html>
