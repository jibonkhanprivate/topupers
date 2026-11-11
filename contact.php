<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us — TopUpers';
$pageDescription = 'Get in touch with the official TOPUPERS customer support team in Bangladesh for order assistance, payment verification, and inquiries.';

include __DIR__ . '/includes/header.php';

$siteName = !empty(getSetting('site_name')) ? getSetting('site_name') : (defined('APP_NAME') ? APP_NAME : 'TopUpers');
$siteEmail = !empty(getSetting('site_email')) ? getSetting('site_email') : 'support@topupers.com';
$user = currentUser();
?>

<!-- Custom Scoped Styles for Modern Contact Us Page -->
<style>
  /* Hero Section */
  .contact-hero-wrap {
    position: relative;
    background: radial-gradient(1000px circle at 50% -20%, rgba(22, 184, 255, 0.12), transparent 70%),
                linear-gradient(180deg, var(--rx-bg-secondary, #0A0E18) 0%, var(--rx-bg, #070A12) 100%);
    border-bottom: 1px solid var(--rx-border, #222A3D);
    padding: 3.5rem 0 3rem;
    overflow: hidden;
  }
  html.light-mode .contact-hero-wrap {
    background: radial-gradient(1000px circle at 50% -20%, rgba(7, 155, 232, 0.08), transparent 70%),
                linear-gradient(180deg, #F8FAFC 0%, #EEF2F6 100%);
    border-bottom-color: var(--rx-border, #E1E5EE);
  }
  .contact-hero-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
    opacity: 0.6;
  }
  html.light-mode .contact-hero-wrap::before {
    background-image: radial-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    opacity: 0.8;
  }

  .contact-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(22, 184, 255, 0.1);
    border: 1px solid rgba(22, 184, 255, 0.25);
    color: var(--rx-primary, #16B8FF);
    font-size: 0.84rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 1.25rem;
  }
  html.light-mode .contact-badge-pill {
    background: rgba(7, 155, 232, 0.08);
    border-color: rgba(7, 155, 232, 0.2);
    color: #0284c7;
  }

  .contact-hero-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    letter-spacing: -0.5px;
    color: var(--rx-text, #F5F7FF);
    line-height: 1.15;
    margin-bottom: 0.8rem;
  }
  .contact-hero-title span {
    background: linear-gradient(135deg, var(--rx-primary, #16B8FF), #A78BFA);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .contact-hero-subtitle {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 0.75rem;
  }
  html.light-mode .contact-hero-subtitle { color: #0F172A; }

  .contact-hero-lead {
    font-size: 1.02rem;
    color: var(--rx-text-secondary, #A5AEC2);
    max-width: 760px;
    margin: 0 auto 2rem;
    line-height: 1.65;
  }

  /* Metadata Strip */
  .contact-meta-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 12px;
  }
  .contact-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 10px;
    font-size: 0.85rem;
    color: var(--rx-text-secondary, #A5AEC2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  }
  html.light-mode .contact-meta-item {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    color: #475569;
  }
  .contact-meta-item strong {
    color: var(--rx-text, #F5F7FF);
    font-weight: 600;
  }
  html.light-mode .contact-meta-item strong { color: #0F172A; }
  .contact-meta-item i {
    color: var(--rx-primary, #16B8FF);
    font-size: 0.95rem;
  }
  .status-pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22C55E;
    box-shadow: 0 0 8px #22C55E;
    display: inline-block;
    animation: statusPulse 2s infinite ease-in-out;
  }
  @keyframes statusPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
  }

  /* Direct Channel Cards (Top Grid) */
  .contact-channels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 2.5rem;
  }
  .contact-channel-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 14px;
    padding: 22px 20px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  html.light-mode .contact-channel-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
  }
  .contact-channel-card:hover {
    transform: translateY(-3px);
    border-color: rgba(22, 184, 255, 0.4);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
  }
  html.light-mode .contact-channel-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  }
  .channel-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    margin-bottom: 14px;
  }
  .icon-teal { background: rgba(22, 184, 255, 0.12); color: #16B8FF; border: 1px solid rgba(22, 184, 255, 0.25); }
  .icon-blue { background: rgba(59, 130, 246, 0.12); color: #3B82F6; border: 1px solid rgba(59, 130, 246, 0.25); }
  .icon-green { background: rgba(34, 197, 94, 0.12); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.25); }
  .icon-purple { background: rgba(139, 92, 246, 0.12); color: #A78BFA; border: 1px solid rgba(139, 92, 246, 0.25); }

  .contact-channel-card h4 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 6px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .contact-channel-card h4 { color: #0F172A; }
  .contact-channel-card p {
    font-size: 0.88rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin-bottom: 14px;
    line-height: 1.55;
  }

  /* Contact Options Grid (4 Pillars) */
  .contact-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 2.5rem;
  }
  .contact-option-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 14px;
    padding: 20px;
    transition: all 0.25s ease;
  }
  html.light-mode .contact-option-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
  }
  .contact-option-card:hover {
    border-color: rgba(22, 184, 255, 0.35);
  }
  .contact-option-title {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .contact-option-title { color: #0F172A; }
  .contact-option-card p {
    font-size: 0.88rem;
    color: var(--rx-text-secondary, #A5AEC2);
    margin: 0;
    line-height: 1.6;
  }

  /* Main Form & Info Section */
  .contact-form-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
  }
  html.light-mode .contact-form-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  }

  .contact-info-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  html.light-mode .contact-info-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  }

  /* Form Elements */
  .form-label-custom {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  html.light-mode .form-label-custom { color: #1E293B; }

  .form-control-custom, .form-select-custom {
    width: 100%;
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 10px;
    color: var(--rx-text, #F5F7FF);
    padding: 10px 14px;
    font-size: 0.92rem;
    outline: none;
    transition: all 0.2s ease;
  }
  html.light-mode .form-control-custom,
  html.light-mode .form-select-custom {
    background: #F8FAFC;
    border-color: #CBD5E1;
    color: #0F172A;
  }
  .form-control-custom:focus, .form-select-custom:focus {
    border-color: var(--rx-primary, #16B8FF);
    box-shadow: 0 0 0 3px rgba(22, 184, 255, 0.15);
  }
  .form-select-custom option {
    background: #111625;
    color: #F5F7FF;
  }
  html.light-mode .form-select-custom option {
    background: #ffffff;
    color: #0F172A;
  }

  /* Warning / Security Notice Box */
  .contact-security-box {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 12px;
    padding: 14px 18px;
    margin: 16px 0;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 0.88rem;
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .contact-security-box {
    background: rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.2);
    color: #7f1d1d;
  }

  /* Checklist Box */
  .checklist-card {
    background: var(--rx-surface-hover, #151C2D);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 20px;
  }
  html.light-mode .checklist-card {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .checklist-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .checklist-title { color: #0F172A; }
  .checklist-list {
    list-style: none;
    padding-left: 0;
    margin: 0;
  }
  .checklist-list li {
    position: relative;
    padding-left: 24px;
    margin-bottom: 8px;
    font-size: 0.88rem;
    color: var(--rx-text-secondary, #A5AEC2);
    line-height: 1.5;
  }
  html.light-mode .checklist-list li { color: #334155; }
  .checklist-list li::before {
    content: '\F272';
    font-family: 'bootstrap-icons';
    position: absolute;
    left: 0;
    top: 0;
    color: var(--rx-primary, #16B8FF);
    font-size: 0.95rem;
  }

  /* Commitment Banner */
  .commitment-box {
    background: linear-gradient(135deg, rgba(22, 184, 255, 0.08), rgba(104, 87, 255, 0.08));
    border: 1px solid rgba(22, 184, 255, 0.25);
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
  }
  html.light-mode .commitment-box {
    background: linear-gradient(135deg, rgba(7, 155, 232, 0.05), rgba(103, 87, 232, 0.05));
    border-color: rgba(7, 155, 232, 0.2);
  }
  .commitment-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
  }
  html.light-mode .commitment-title { color: #0F172A; }
  .commitment-tagline {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 800;
    font-size: 1.15rem;
    letter-spacing: 1px;
    color: var(--rx-primary, #16B8FF);
    margin: 12px 0 4px;
    text-transform: uppercase;
  }

  /* Success Message Callout */
  .ticket-success-box {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    margin-top: 15px;
    display: none;
  }
  .ticket-success-box.show {
    display: block;
    animation: fadeIn 0.3s ease-out;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Responsive */
  @media (max-width: 767.98px) {
    .contact-form-card, .contact-info-card {
      padding: 22px 18px;
    }
  }
</style>

<!-- ============================================================
     HERO HEADER SECTION
     ============================================================ -->
<section class="contact-hero-wrap">
  <div class="container text-center">
    
    <!-- Badge -->
    <div>
      <span class="contact-badge-pill">
        <i class="bi bi-headset"></i> TOPUPERS Customer Support
      </span>
    </div>

    <!-- Main Title -->
    <h1 class="contact-hero-title">
      Contact <span>Us</span>
    </h1>

    <h2 class="contact-hero-subtitle">
      We're Here to Help You!
    </h2>

    <!-- Subtitle / Lead -->
    <p class="contact-hero-lead">
      Have a question about an order, payment, game top-up, or digital product? The TOPUPERS support team is here to help. Whether you need help with Mobile Legends: Bang Bang (MLBB) Diamonds, Weekly Passes, Free Fire Diamonds, PUBG Mobile UC, subscriptions, or another supported product, feel free to contact us.
    </p>

    <!-- Metadata Badges -->
    <div class="contact-meta-strip">
      <div class="contact-meta-item">
        <i class="bi bi-building"></i>
        <span>Business: <strong>TOPUPERS</strong></span>
      </div>
      <div class="contact-meta-item">
        <i class="bi bi-envelope"></i>
        <span>Email: <a href="mailto:<?= e($siteEmail) ?>" style="color:var(--rx-primary); text-decoration:none;"><strong><?= e($siteEmail) ?></strong></a></span>
      </div>
      <div class="contact-meta-item">
        <i class="bi bi-globe2"></i>
        <span>Website: <a href="https://topupers.com" target="_blank" rel="noopener noreferrer" style="color:var(--rx-primary); text-decoration:none;"><strong>topupers.com</strong></a></span>
      </div>
      <div class="contact-meta-item">
        <span class="status-pulse-dot"></span>
        <span style="color:#22C55E; font-weight:600;">Support Desk Active</span>
      </div>
      <div class="contact-meta-item">
        <i class="bi bi-controller"></i>
        <span>Gaming Top-Up &amp; Digital Store</span>
      </div>
    </div>

  </div>
</section>

<!-- ============================================================
     MAIN BODY CONTENT
     ============================================================ -->
<div class="container py-5" style="min-height: 50vh;">

  <!-- Official Direct Channels (Top Cards Grid) -->
  <div class="contact-channels-grid">
    
    <!-- Email Channel -->
    <div class="contact-channel-card">
      <div>
        <div class="channel-icon-wrap icon-teal">
          <i class="bi bi-envelope-fill"></i>
        </div>
        <h4>Email Support</h4>
        <p>For order-related enquiries, payment verification, delivery delays, failed transactions, cancellations, and refund requests, please contact our support team.</p>
      </div>
      <div>
        <a href="mailto:<?= e($siteEmail) ?>" class="btn btn-outline-primary w-100" style="border-radius:8px;">
          <i class="bi bi-send me-1"></i> <?= e($siteEmail) ?>
        </a>
      </div>
    </div>

    <!-- Facebook Channel -->
    <div class="contact-channel-card">
      <div>
        <div class="channel-icon-wrap icon-blue">
          <i class="bi bi-facebook"></i>
        </div>
        <h4>Official Facebook</h4>
        <p>Connect with our customer support agents on Facebook Messenger for quick inquiries, game updates, and community assistance.</p>
      </div>
      <div>
        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-info w-100" style="border-radius:8px;">
          <i class="bi bi-messenger me-1"></i> Visit Facebook Page
        </a>
      </div>
    </div>

    <!-- On-Site Ticket System -->
    <div class="contact-channel-card">
      <div>
        <div class="channel-icon-wrap icon-purple">
          <i class="bi bi-ticket-detailed-fill"></i>
        </div>
        <h4>Support Ticket</h4>
        <p>Submit a direct inquiry or dispute on our website. You will receive a unique tracking ticket number and swift review from our staff.</p>
      </div>
      <div>
        <a href="#supportFormSection" class="btn btn-primary w-100" style="border-radius:8px;">
          <i class="bi bi-pencil-square me-1"></i> Submit Ticket Below
        </a>
      </div>
    </div>

    <!-- Return & Refund Reference -->
    <div class="contact-channel-card">
      <div>
        <div class="channel-icon-wrap icon-green">
          <i class="bi bi-shield-check"></i>
        </div>
        <h4>Refunds &amp; Disputes</h4>
        <p>Need a cancellation or refund review? Check our comprehensive Return &amp; Refund Policy to learn about eligible situations and procedures.</p>
      </div>
      <div>
        <a href="<?= APP_URL ?>/refund.php" class="btn btn-outline-secondary w-100" style="border-radius:8px;">
          <i class="bi bi-arrow-counterclockwise me-1"></i> View Refund Policy
        </a>
      </div>
    </div>

  </div>

  <!-- Contact Options Section (4 Pillars) -->
  <div class="mb-5">
    <div class="text-center mb-4">
      <h3 style="font-family:'Outfit', sans-serif; font-size:1.6rem; font-weight:700; color:var(--rx-text);">
        How Can We Help You Today?
      </h3>
      <p style="color:var(--rx-text-secondary); font-size:0.95rem;">Select the topic that best matches your situation for faster resolution.</p>
    </div>

    <div class="contact-options-grid">
      
      <!-- Option 1 -->
      <div class="contact-option-card">
        <div class="contact-option-title">
          <i class="bi bi-box-seam text-primary"></i>
          <span>Order Support</span>
        </div>
        <p><strong>Need help with an existing order?</strong><br>Please provide your TOPUPERS order number, selected product, and relevant payment reference so we can investigate your request.</p>
      </div>

      <!-- Option 2 -->
      <div class="contact-option-card">
        <div class="contact-option-title">
          <i class="bi bi-credit-card-2-front text-warning"></i>
          <span>Payment Issues</span>
        </div>
        <p><strong>Payment pending or deducted without completion?</strong><br>Contact us with your order number and transaction reference. <em>Never share your payment PIN, OTP, password, or other authentication credentials.</em></p>
      </div>

      <!-- Option 3 -->
      <div class="contact-option-card">
        <div class="contact-option-title">
          <i class="bi bi-tags text-info"></i>
          <span>Product &amp; Pricing</span>
        </div>
        <p><strong>Want to know about availability, rates, or regions?</strong><br>Contact our support team before placing your order if you need clarification on supported game zones or upcoming promotions.</p>
      </div>

      <!-- Option 4 -->
      <div class="contact-option-card">
        <div class="contact-option-title">
          <i class="bi bi-arrow-repeat text-success"></i>
          <span>Refund &amp; Cancellation</span>
        </div>
        <p><strong>Duplicate payments or failed order remedies?</strong><br>Please review our Return &amp; Refund Policy and contact support with the relevant order details and uncropped payment proof.</p>
      </div>

    </div>
  </div>

  <!-- Interactive Form + Detailed Guidance (Two-Column Layout) -->
  <div class="row g-4" id="supportFormSection">

    <!-- Left Column: Support Form -->
    <div class="col-lg-7">
      <div class="contact-form-card">
        
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color:var(--rx-border-light) !important;">
          <div>
            <h3 style="font-family:'Outfit', sans-serif; font-size:1.35rem; font-weight:700; color:var(--rx-text); margin:0 0 4px;">
              <i class="bi bi-send-fill text-primary me-2"></i> Submit a Support Ticket
            </h3>
            <p class="mb-0" style="font-size:0.85rem; color:var(--rx-text-secondary);">Direct on-site ticketing for priority assistance.</p>
          </div>
          <span class="badge" style="background:rgba(22,184,255,0.12); color:var(--rx-primary); border:1px solid rgba(22,184,255,0.3); font-size:0.75rem;">Direct Dispatch</span>
        </div>

        <form id="contactTicketForm">
          
          <div class="row g-3">
            
            <!-- Name -->
            <div class="col-md-6">
              <label class="form-label-custom">
                <i class="bi bi-person"></i> Your Name *
              </label>
              <input type="text" name="name" class="form-control-custom" placeholder="e.g. Shakib Ahmed" value="<?= e($user ? ($user['full_name'] ?: $user['username']) : '') ?>" required>
            </div>

            <!-- Contact (Email or Phone) -->
            <div class="col-md-6">
              <label class="form-label-custom">
                <i class="bi bi-envelope-at"></i> Email or Phone *
              </label>
              <input type="text" name="contact" class="form-control-custom" placeholder="Email or Phone Number" value="<?= e($user ? ($user['email'] ?: $user['phone']) : '') ?>" required>
            </div>

            <!-- Category -->
            <div class="col-md-6">
              <label class="form-label-custom">
                <i class="bi bi-folder2-open"></i> Inquiry Category *
              </label>
              <select name="category" class="form-select-custom" required>
                <option value="order">Order Support (Existing Order)</option>
                <option value="payment">Payment Verification &amp; Issues</option>
                <option value="product">Product &amp; Pricing Enquiry</option>
                <option value="refund">Refund &amp; Cancellation Request</option>
                <option value="other">General Question / Other</option>
              </select>
            </div>

            <!-- Order Number -->
            <div class="col-md-6">
              <label class="form-label-custom">
                <i class="bi bi-hash"></i> TOPUPERS Order # (If Applicable)
              </label>
              <input type="text" name="order_number" class="form-control-custom" placeholder="e.g. RX261026-1234">
            </div>

            <!-- Subject -->
            <div class="col-12">
              <label class="form-label-custom">
                <i class="bi bi-chat-left-text"></i> Subject *
              </label>
              <input type="text" name="subject" class="form-control-custom" placeholder="Brief summary of your inquiry" required>
            </div>

            <!-- Message -->
            <div class="col-12">
              <label class="form-label-custom">
                <i class="bi bi-textarea-t"></i> Detailed Explanation *
              </label>
              <textarea name="message" class="form-control-custom" rows="5" placeholder="Explain your issue clearly. If regarding an existing order, please include Game ID, Server ID, and Payment Transaction Reference..." required></textarea>
            </div>

          </div>

          <!-- Security Reminder inside form -->
          <div class="contact-security-box">
            <i class="bi bi-shield-slash-fill text-danger" style="font-size:1.25rem; flex-shrink:0;"></i>
            <div>
              <strong>Security Rule:</strong> Please do not share your game account passwords, payment PINs, or OTPs. UID-based top-ups require only your Player ID and Server/Zone ID.
            </div>
          </div>

          <div class="mt-3">
            <button type="submit" id="btnSubmitTicket" class="btn btn-primary px-4 py-2 w-100" style="border-radius:10px; font-weight:600; font-size:0.95rem;">
              <span class="btn-text"><i class="bi bi-send me-2"></i> Submit Inquiry</span>
              <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
            </button>
          </div>

        </form>

        <!-- Success Callout -->
        <div id="ticketSuccessBox" class="ticket-success-box">
          <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
          <h4 style="font-family:'Outfit', sans-serif; font-size:1.25rem; color:var(--rx-text); margin:12px 0 6px;">
            Ticket Submitted Successfully!
          </h4>
          <p id="ticketSuccessMsg" style="color:var(--rx-text-secondary); font-size:0.92rem; margin-bottom:14px;"></p>
          <div class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded" style="background:var(--rx-surface-hover); border:1px solid var(--rx-border);">
            <span style="font-size:0.85rem; color:var(--rx-text-muted);">Tracking Ticket:</span>
            <strong id="ticketNumberDisplay" style="color:var(--rx-primary); font-family:'Rajdhani', sans-serif; font-size:1.1rem; letter-spacing:1px;"></strong>
          </div>
          <div class="mt-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetContactForm()">Submit Another Inquiry</button>
          </div>
        </div>

      </div>
    </div>

    <!-- Right Column: Checklists & Commitment -->
    <div class="col-lg-5">
      <div class="contact-info-card">
        
        <div>
          <!-- Before Contacting Us -->
          <div class="checklist-card">
            <div class="checklist-title">
              <i class="bi bi-clipboard2-check text-primary"></i>
              <span>Before Contacting Us</span>
            </div>
            <p style="font-size:0.85rem; color:var(--rx-text-secondary); margin-bottom:12px;">To help us resolve your issue faster, please have the following ready:</p>
            <ul class="checklist-list">
              <li>Keep your <strong>order number</strong> available.</li>
              <li>Provide the correct <strong>Game ID and Server ID</strong>, where applicable.</li>
              <li>Include your <strong>payment transaction reference (TrxID)</strong> when relevant.</li>
              <li>Explain the issue clearly and concisely.</li>
              <li>Attach only the evidence necessary to investigate your request.</li>
            </ul>
          </div>

          <!-- Support Hours & Expectations -->
          <div class="checklist-card">
            <div class="checklist-title">
              <i class="bi bi-clock-history text-info"></i>
              <span>Support Hours &amp; Response Times</span>
            </div>
            <p style="font-size:0.88rem; color:var(--rx-text-secondary); margin-bottom:10px; line-height:1.6;">
              Our support team aims to respond as quickly as possible.
            </p>
            <p style="font-size:0.84rem; color:var(--rx-text-muted); margin-bottom:0; line-height:1.55;">
              Response times may vary depending on order volume, supplier availability, payment verification requirements, and the complexity of your enquiry. If your order is delayed, please contact us with your order number so we can check its status immediately.
            </p>
          </div>
        </div>

        <!-- Our Commitment -->
        <div class="commitment-box text-center">
          <div class="commitment-title justify-content-center">
            <i class="bi bi-shield-fill-check text-primary"></i>
            <span>Our Commitment to You</span>
          </div>
          <p style="font-size:0.86rem; color:var(--rx-text-secondary); margin-bottom:8px; line-height:1.6;">
            At TOPUPERS, we aim to provide transparent pricing, reliable delivery, secure payment handling, and helpful customer support. Your trust matters to us, and we will make reasonable efforts to investigate your concerns and provide an appropriate resolution.
          </p>
          <div class="commitment-tagline">
            TOPUPERS — POWER UP. PLAY MORE.
          </div>
          <p class="mb-0 text-muted" style="font-size:0.82rem;">Thank you for choosing TOPUPERS!</p>
        </div>

      </div>
    </div>

  </div>

</div>

<!-- Client-side Ticket Submission AJAX Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactTicketForm');
  const btn = document.getElementById('btnSubmitTicket');
  const successBox = document.getElementById('ticketSuccessBox');
  const successMsg = document.getElementById('ticketSuccessMsg');
  const ticketDisplay = document.getElementById('ticketNumberDisplay');

  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const btnText = btn.querySelector('.btn-text');
      const spinner = btn.querySelector('.spinner-border');
      
      btn.disabled = true;
      btnText.classList.add('d-none');
      spinner.classList.remove('d-none');

      const formData = new FormData(form);

      fetch('<?= APP_URL ?>/ajax/submit_ticket.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        btnText.classList.remove('d-none');
        spinner.classList.add('d-none');

        if (data.success) {
          form.style.display = 'none';
          successBox.classList.add('show');
          successMsg.innerText = data.message;
          ticketDisplay.innerText = data.ticket_number || 'TICK-RECEIVED';
        } else {
          alert(data.message || 'Unable to submit ticket. Please try again or email us directly.');
        }
      })
      .catch(err => {
        btn.disabled = false;
        btnText.classList.remove('d-none');
        spinner.classList.add('d-none');
        alert('Network error. Please try again or email us at <?= e($siteEmail) ?>.');
      });
    });
  }
});

function resetContactForm() {
  const form = document.getElementById('contactTicketForm');
  const successBox = document.getElementById('ticketSuccessBox');
  if (form && successBox) {
    form.reset();
    form.style.display = 'block';
    successBox.classList.remove('show');
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
