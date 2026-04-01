<?php
/**
 * agent/test_instagram_post.php
 * Official test of the Instagram API posting system.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes_functions.php';
require_once __DIR__ . '/../includes/social_config.php';

$image_url = "https://biznexus.in/assets/images/instagram_launch.png";
$caption = "🚀 BizNexus is scaling Indian businesses 1000x! \n\nJoin the AI-powered network connecting top leaders across every city. \n\n#BizNexus #Entrepreneurship #Growth #DigitalIndia #AI ✨";

echo "Attempting to post to Instagram...\n";
$result = publishToInstagram($image_url, $caption, IG_ACCESS_TOKEN, IG_BUSINESS_ID);

if($result['success']) {
    echo "✅ Success! Post ID: " . $result['id'] . "\n";
    echo "Check it out here: https://www.instagram.com/biznexus.in/\n";
} else {
    echo "❌ Failed!\n";
    print_r($result);
}
