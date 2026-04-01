<?php
/**
 * includes/agent_templates.php
 * Templates and logic for the BizNexus Sub-Agent personas.
 */

require_once __DIR__ . '/ai_helper_v3.php';

function getAgentAdvice($category, $stage, $name) {
    $system = "You are the BizNexus Personal Business Assistant. Your goal is to help Indian SMEs grow.";
    
    $prompts = [
        'onboarding_start' => "Write a friendly 1-sentence welcome for $name in the $category industry. Encourage them to complete their profile to unlock AI growth tools.",
        'missing_marketplace' => "Give $name (owner of a $category business) a quick 1-sentence tip on why adding their products to the 'Marketplace' will get them 5x more leads.",
        'daily_motivation' => "Provide a powerful 1-sentence business motivation quote for a $category business owner. Make it feel authentic to the Indian market.",
        'referral_suggestion' => "Suggest a reason why a $category business owner should give a referral to a fellow group member today. Use a helpful, networking-focused tone."
    ];

    $prompt = $prompts[$stage] ?? $prompts['onboarding_start'];
    return runBizAIString($prompt, $system);
}

function getDailyQuote($category) {
    if (empty($category)) $category = 'Business';

    $system = "You are a business mentor for Indian entrepreneurs. Respond ONLY with the quote and no other text.";
    $prompt = "Write a unique, inspiring daily business quote for someone in the $category sector. Focus on growth and local networking. Limit to 1 powerful sentence.";
    
    $quote = runBizAIString($prompt, $system);
    if (empty($quote)) {
        $quote = getFallbackQuote($category);
    }
    return $quote;
}

function getFallbackQuote($category) {
    $tips = [
        'Healthcare' => "Build trust by prioritizing patient care quality; satisfied patients are your best brand ambassadors.",
        'Technology' => "Scale fast by automating the routine; focus your human talent only on high-value innovation.",
        'Retail' => "Your inventory is your capital; optimize your stock turnover to keep your cash flow healthy.",
        'Manufacturing' => "Consistency is the backbone of manufacturing; a small improvement in process is a large gain in profit.",
        'Education' => "In the knowledge economy, your students' success is your only true metric of business growth.",
        'Finance' => "Transparency and integrity are the currencies of finance; once lost, they are impossible to buy back.",
        'Construction' => "Quality construction lasts decades; your reputation is built one brick at a time.",
        'Default' => "Networking is not just about connecting people; it's about connecting people with ideas and opportunities."
    ];

    foreach ($tips as $key => $tip) {
        if (stripos($category, $key) !== false) return $tip;
    }
    return $tips['Default'];
}

function getUpgradeAdvice($category, $current_tier) {
    if ($current_tier === 'platinum') return "";
    
    $next_tier = ($current_tier === 'free') ? 'Gold' : 'Platinum';
    $system = "You are a sales-focused business growth advisor for BizNexus.";
    $prompt = "Write a 1-sentence persuasive reason why a $category business on the $current_tier plan should upgrade to $next_tier. 
               Highlight one benefit like 'Verified Badge for trust' or 'Unlimited Lead Claims' or 'AI Website Builder'.";
               
    return runBizAIString($prompt, $system);
}
