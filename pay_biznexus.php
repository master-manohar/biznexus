<?php
/**
 * /pay_biznexus.php
 * DO NOT CHANGE - SECURE BRIDGE FOR BIZNEXUS PAYMENTS
 * PLACE THIS ON WWW.BOOKANEVENT.IN
 */

$order_id = $_GET['order_id'] ?? '';
$user_id  = $_GET['user_id']  ?? '';
$plan     = $_GET['plan']     ?? '';
$billing  = $_GET['billing']  ?? '';
$amount   = $_GET['amount']   ?? ''; // in paise
$key_id   = 'rzp_live_vP3D8Zk402qQvD'; // BookAnEvent Live Key (from includes/razorpay_config.php)

if (!$order_id || !$user_id || !$plan) {
    die("Error: Missing parameters for Secure Bridge.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure Checkout — BookAnEvent Bridge</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { background: #0a0a0f; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .loader { border: 4px solid #1a1a24; border-top: 4px solid #FFD700; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .box { text-align: center; }
    </style>
</head>
<body>
    <div class="box">
        <div class="loader"></div>
        <p>Connecting to Secure Payment Gateway...</p>
        <p style="font-size: 0.8rem; color: #555;">domain: bookanevent.in (Authorized)</p>

        <div style="margin-top: 40px; border-top: 1px solid #1a1a24; padding-top: 20px;">
            <p style="font-size: 0.85rem; color: #888;">Taking too long?</p>
            <a href="https://wa.me/919989998188?text=Hi%2C%20I%20want%20to%20pay%20for%20<?= $plan ?>%20plan%20on%20BizNexus.%20Please%20send%20direct%20UPI/Link." 
               style="display: inline-block; background: #25D366; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem;">
               <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" style="height:16px; vertical-align:middle; margin-right:8px;"> Pay Direct via WhatsApp
            </a>
            <p style="font-size: 0.75rem; color: #444; margin-top: 10px;">Immediate confirmation for all UPI/Direct transfers.</p>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    var options = {
        key:         '<?= $key_id ?>',
        amount:      '<?= $amount ?>',
        currency:    'INR',
        name:        'BizNexus',
        description: 'Digital Business Services (via BookAnEvent)',
        image:       'https://biznexus.in/assets/img/logo-icon.png',
        order_id:    '<?= $order_id ?>',
        theme: { color: '#FFD700' },
        handler: function(response) {
            // Success! Redirect back to BizNexus with the results
            var url = "https://biznexus.in/api/payment_verify.php?";
            url += "razorpay_payment_id=" + response.razorpay_payment_id;
            url += "&razorpay_order_id=" + response.razorpay_order_id;
            url += "&razorpay_signature=" + response.razorpay_signature;
            url += "&plan=<?= $plan ?>";
            url += "&billing=<?= $billing ?>";
            
            // We use an auto-submitting form for POST if preferred, but verification script handles both
            window.location.href = url;
        },
        modal: {
            ondismiss: function() {
                window.location.href = "https://biznexus.in/membership/upgrade.php?plan=<?= $plan ?>&err=Payment cancelled";
            }
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.open();
    </script>
</body>
</html>
