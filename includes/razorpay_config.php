<?php
// Razorpay Configuration — BizNexus (operated by BookAnEvent)
// ✅ LIVE MODE
define('RAZORPAY_KEY_ID',     'rzp_live_STKB8b3Dk0wTU1');
define('RAZORPAY_KEY_SECRET', 'e97PHpVIfUCjLKaGmnD2tpMk');
define('RAZORPAY_MODE',       'live');
define('RAZORPAY_CURRENCY',   'INR');
define('RAZORPAY_COMPANY',    'BookAnEvent');

// All prices in paise (₹ × 100)
// monthly_amount  = full monthly rate charged now
// yearly_amount   = total charged now (discounted monthly × 12)
// monthly_display = what per month shows as
// yearly_ppm      = effective per-month on yearly plan
define('PLAN_PRICES', [
    'silver' => [
        'label'          => 'Silver',
        'emoji'          => '⚪',
        'color'          => '#c0c0c0',
        'monthly_amount' => 120000,          // ₹1,200  charged/month
        'yearly_amount'  => 1199900,         // ₹11,999 charged/year (≈₹999/mo)
        'monthly_ppm'    => '₹1,200',
        'yearly_ppm'     => '₹999',          // effective per month
        'yearly_total'   => '₹11,999/year',
        'saving'         => 'Save ₹2,401/yr',
        'features'       => ['Business Website (3-Page)','SEO Basics','1 Social Media Post/mo','Lead Claims & CRM','200 Bonus Coins'],
        'coins_monthly'  => 200,
        'coins_yearly'   => 500,
    ],
    'gold' => [
        'label'          => 'Gold',
        'emoji'          => '🥇',
        'color'          => '#FFD700',
        'monthly_amount' => 240000,          // ₹2,400/month
        'yearly_amount'  => 2399900,         // ₹23,999/year (≈₹1,999/mo)
        'monthly_ppm'    => '₹2,400',
        'yearly_ppm'     => '₹1,999',
        'yearly_total'   => '₹23,999/year',
        'saving'         => 'Save ₹4,801/yr',
        'features'       => ['Premium Website (5-Page)','Advanced SEO','4 Social Media Posts/mo','1 Business Promo Video','Monthly Mentor Training','500 Bonus Coins'],
        'coins_monthly'  => 500,
        'coins_yearly'   => 1500,
        'popular'        => true,
    ],
    'platinum' => [
        'label'          => 'Platinum',
        'emoji'          => '💎',
        'color'          => '#a259ff',
        'monthly_amount' => 500000,          // ₹5,000/month
        'yearly_amount'  => 4799900,         // ₹47,999/year (≈₹3,999/mo)
        'monthly_ppm'    => '₹5,000',
        'yearly_ppm'     => '₹3,999',
        'yearly_total'   => '₹47,999/year',
        'saving'         => 'Save ₹12,001/yr',
        'features'       => ['Custom Dynamic Website','Own Domain (.com/.in)','Social Media Blitz (12/mo)','4 Business Videos/yr','Weekly Support & Mentoring','Everything in Gold','3000 Bonus Coins'],
        'coins_monthly'  => 1000,
        'coins_yearly'   => 3000,
    ],
]);
