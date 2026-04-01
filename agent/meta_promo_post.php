<?php
/**
 * meta_promo_post.php
 * Instantly blasts a specific promotional campaign to Instagram and LinkedIn.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/social_config.php';

$caption = "🚀 BIG BUSINESS DAY IS HERE! 🚀

Are you doing business? Stop manual networking and start scaling with AI. Join BizNexus.in, India's first AI-powered networking platform.

🎁 SPECIAL LAUNCH OFFER: Register today and get a FREE SILVER PACKAGE subscription! (Limited to today’s registrations only).

What makes BizNexus Different?
🤖 14+ AI Agents: Working 24/7 to automate your business growth.
📈 Next-Gen Lead Gen: A powerful digital platform like Justdial, but smarter.
🌐 Inbuilt SEO & Website: Automatically optimize your digital presence.
🤝 Networking Lines: Connect with high-value founders and scale.

✅ Step 1: Register for the Free Business Meet
👉 https://biznexus.in/meet.php

✅ Step 2: Join the Private Group
(Link provided immediately after you submit the form!)

Scale. Automate. Connect. 🚀

#BizNexus #BusinessMeet #FoundersIndia #B2BNetworking #ScaleWithAI #BusinessGrowth #IndianStartup #HyderabadBusiness";

// Premium business networking image for the post
$image_url = 'https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg'; 

echo "=== Deploying Promotional Post ===\n\n";

// ------------------------------------------------------------------
// 1. Post to Instagram
// ------------------------------------------------------------------
if (defined('IG_BUSINESS_ID') && defined('IG_ACCESS_TOKEN')) {
    echo "1. Posting to Instagram...\n";
    
    // Step A: Upload Image to IG Container
    $url = "https://graph.facebook.com/v19.0/" . IG_BUSINESS_ID . "/media";
    $params = [
        'image_url' => $image_url,
        'caption' => $caption,
        'access_token' => IG_ACCESS_TOKEN
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $ig_data = json_decode($response, true);
    
    if (isset($ig_data['id'])) {
        $creation_id = $ig_data['id'];
        echo "   -> Container created: $creation_id. Publishing...\n";
        
        // Step B: Publish Container
        $pub_url = "https://graph.facebook.com/v19.0/" . IG_BUSINESS_ID . "/media_publish";
        $pub_params = [
            'creation_id' => $creation_id,
            'access_token' => IG_ACCESS_TOKEN
        ];
        
        curl_setopt($ch, CURLOPT_URL, $pub_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($pub_params));
        $pub_response = curl_exec($ch);
        $pub_data = json_decode($pub_response, true);
        
        if (isset($pub_data['id'])) {
            echo "   ✅ Instagram Post Published! ID: " . $pub_data['id'] . "\n";
        } else {
            echo "   ❌ IG Publish Failed: " . print_r($pub_data, true) . "\n";
        }
    } else {
        echo "   ❌ IG Container Failed: " . print_r($ig_data, true) . "\n";
    }
    curl_close($ch);
}

// ------------------------------------------------------------------
// 2. Post to LinkedIn
// ------------------------------------------------------------------
if (defined('LI_ACCESS_TOKEN') && defined('LI_MEMBER_ID')) {
    echo "\n2. Posting to LinkedIn...\n";
    
    $li_url = "https://api.linkedin.com/v2/ugcPosts";
    $payload = [
        "author" => "urn:li:person:" . LI_MEMBER_ID,
        "lifecycleState" => "PUBLISHED",
        "specificContent" => [
            "com.linkedin.ugc.ShareContent" => [
                "shareCommentary" => ["text" => $caption],
                "shareMediaCategory" => "ARTICLE",
                "media" => [
                    [
                        "status" => "READY",
                        "description" => ["text" => "Join BizNexus Business Meet"],
                        "originalUrl" => "https://biznexus.in/meet.php",
                        "title" => ["text" => "Free Business Meet Registration - BizNexus"]
                    ]
                ]
            ]
        ],
        "visibility" => ["com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC"]
    ];

    $ch2 = curl_init($li_url);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . LI_ACCESS_TOKEN,
        'Content-Type: application/json',
        'X-Restli-Protocol-Version: 2.0.0'
    ]);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    
    $li_response = curl_exec($ch2);
    $li_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    if ($li_code == 201 || $li_code == 200) {
        $li_data = json_decode($li_response, true);
        echo "   ✅ LinkedIn Post Published! ID: " . ($li_data['id'] ?? 'Unknown') . "\n";
    } else {
        echo "   ❌ LinkedIn Publish Failed (HTTP $li_code): " . $li_response . "\n";
    }
}

echo "\nDone!\n";
?>
