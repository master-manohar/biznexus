<?php
// pages/terms.php
session_start();
$page_title = 'Terms of Service | BizNexus';
require_once __DIR__ . '/../includes/layout_start.php';
?>
<div class="container py-5">
    <div class="card bg-dark text-light border-secondary">
        <div class="card-body p-5">
            <h1 class="font-weight-bold text-warning mb-4"><i class="fas fa-file-contract"></i> Terms of Service</h1>
            <p class="text-muted mb-5">Last Updated: <?= date('F j, Y') ?></p>

            <div class="content" style="line-height: 1.8;">
                <h4 class="text-warning">1. Agreement to Terms</h4>
                <p>Welcome to BizNexus ("Company", "we", "our", "us"). By accessing or using our platform, directory, networking services, or AI tools (collectively, the "Services"), you agree to be bound by these Terms of Service. If you disagree with any part of these terms, you may not access the Service.</p>

                <h4 class="text-warning mt-5">2. Nature of Service & Limitation of Liability</h4>
                <p><strong>BizNexus operates strictly as an intermediary technology platform.</strong> We are a digital listing directory, networking facilitator, and software provider. We do not manufacture, sell, endorse, or guarantee any products, services, or transactions conducted between users on this platform.</p>
                <ul class="mb-4 text-muted">
                    <li>We are <strong>not a party to any contract</strong> formed between buyers and sellers.</li>
                    <li>We <strong>do not guarantee the quality, safety, legality, or authenticity</strong> of advertised products or services.</li>
                    <li>Users are solely responsible for conducting their own due diligence before engaging in any business transaction, sharing information, or making payments.</li>
                    <li>BizNexus is <strong>not liable for any financial loss, fraud, or damage</strong> arising from interactions or transactions originating on this platform.</li>
                </ul>

                <h4 class="text-warning mt-5">3. User Conduct and Listings</h4>
                <p>As a registered business or user, you are solely responsible for the content you post, including images, text, AI-generated content, and business details. You agree that:</p>
                <ul class="mb-4 text-muted">
                    <li>Your listings do not violate any local, state, or national laws.</li>
                    <li>You will not post deceptive, fraudulent, defamatory, or restricted items/services.</li>
                    <li>We reserve the right to remove any listing, ban any user, or disable any AI-generated website at our sole discretion without prior notice or refund if we suspect fraudulent or malicious activity.</li>
                </ul>

                <h4 class="text-warning mt-5">4. AI Tools and Generated Content</h4>
                <p>BizNexus provides AI tools (including Chat Bots, Matchmakers, and Automated Website Generators) for user convenience. The output of these AI systems is provided "AS IS". We make no warranties regarding the accuracy, completeness, or SEO performance of AI-generated content. You must review and assume responsibility for any AI-generated content associated with your profile.</p>

                <h4 class="text-warning mt-5">5. Dispute Resolution</h4>
                <p>Any dispute arising between users must be resolved directly between the involved parties. BizNexus will not mediate or intervene in user disputes. Any legal disputes involving BizNexus as an entity shall be governed by the laws of India and subject to the exclusive jurisdiction of the courts located in Hyderabad, Telangana.</p>

                <h4 class="text-warning mt-5">6. Fees and Payments</h4>
                <p>Certain features (Premium Memberships, Marketplace Listings, VooCoins) may require payment. All fees are non-refundable unless legally required. We reserve the right to change our fee structure at any time with prior notice provided on the platform.</p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
