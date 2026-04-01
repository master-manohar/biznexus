<?php
// pages/privacy.php
session_start();
$page_title = 'Privacy Policy | BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<div class="container py-5">
    <div class="card bg-dark text-light border-secondary">
        <div class="card-body p-5">
            <h1 class="font-weight-bold text-success mb-4"><i class="fas fa-user-secret"></i> Privacy Policy</h1>
            <p class="text-muted mb-5">Last Updated: <?= date('F j, Y') ?></p>

            <div class="content" style="line-height: 1.8;">
                <h4 class="text-success">1. Information We Collect</h4>
                <p>BizNexus strictly collects data necessary to provide networking services, AI matchmaking, and automated websites. This includes: business names, categories, contact numbers, email addresses, and geographical locations. We do not store sensitive payment information (e.g., full credit card numbers); such data is processed directly by our secure payment gateway partners.</p>

                <h4 class="text-success mt-5">2. How We Use Your Data</h4>
                <p>We use your business and contact information to:</p>
                <ul class="mb-4 text-muted">
                    <li>Generate your personalized profile and single-page SEO website.</li>
                    <li>Match your business with prospective buyers and B2B leads.</li>
                    <li>Power our AI models and chatbots to accurately represent your offerings to public users.</li>
                    <li>Send relevant networking and legal notifications.</li>
                </ul>

                <h4 class="text-success mt-5">3. Data Sharing and Third Parties</h4>
                <p>BizNexus is a dynamic marketplace, meaning that basic public profile information (Phone, Name, Business Details) is deliberately <strong>made public</strong> and shared with prospective buyers when a matchmaking lead is dispatched.</p>
                <p>Your data may also be processed by our third-party AI partners (such as Anthropic or Google Gemini) strictly for generating your personalized content (e.g., website generation, chat responses). We do not sell your personal data to non-affiliated background marketing agencies.</p>

                <h4 class="text-success mt-5">4. Security Measures</h4>
                <p>While we implement robust encryption and security measures to protect internal databases, user-to-user interactions on our platform are inherently public. BizNexus cannot guarantee absolute security against unauthorized scraping or malicious actors outside of our infrastructure.</p>

                <h4 class="text-success mt-5">5. Communication Opt-Out</h4>
                <p>By registering on BizNexus, you consent to receiving system-critical emails, lead notifications, and AI-generated business advice. You may update your notification preferences or delete your account at any time via your user dashboard, which will instantly retract your public listings.</p>

                <h4 class="text-success mt-5">6. Contact Information</h4>
                <p>If you have any questions or concerns regarding this Privacy Policy, your data, or our practices, please contact our Data Protection Office at: <strong>privacy@biznexus.in</strong>.</p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
