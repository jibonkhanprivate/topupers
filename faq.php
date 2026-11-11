<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Frequently Asked Questions (FAQ) — TopUpers';
$pageDescription = 'Find answers to common questions about gaming top-ups, payments, order processing, delivery, and refunds at TOPUPERS in Bangladesh.';

include __DIR__ . '/includes/header.php';

$siteName = !empty(getSetting('site_name')) ? getSetting('site_name') : (defined('APP_NAME') ? APP_NAME : 'TopUpers');
$siteEmail = !empty(getSetting('site_email')) ? getSetting('site_email') : 'support@topupers.com';
?>

<!-- Reading Progress Bar -->
<div id="faqProgressBar" aria-hidden="true"></div>

<!-- Custom Scoped Styles for Modern FAQ Page -->
<style>
  /* Reading Progress Bar */
  #faqProgressBar {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    width: 0%;
    background: linear-gradient(90deg, var(--rx-primary, #16B8FF), #8B5CF6, var(--rx-secondary, #6857FF));
    z-index: 999999;
    transition: width 0.1s ease-out;
  }

  /* Hero Section */
  .faq-hero-wrap {
    position: relative;
    background: radial-gradient(1000px circle at 50% -20%, rgba(22, 184, 255, 0.12), transparent 70%),
                linear-gradient(180deg, var(--rx-bg-secondary, #0A0E18) 0%, var(--rx-bg, #070A12) 100%);
    border-bottom: 1px solid var(--rx-border, #222A3D);
    padding: 3.5rem 0 3rem;
    overflow: hidden;
  }
  html.light-mode .faq-hero-wrap {
    background: radial-gradient(1000px circle at 50% -20%, rgba(7, 155, 232, 0.08), transparent 70%),
                linear-gradient(180deg, #F8FAFC 0%, #EEF2F6 100%);
    border-bottom-color: var(--rx-border, #E1E5EE);
  }
  .faq-hero-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
    opacity: 0.6;
  }
  html.light-mode .faq-hero-wrap::before {
    background-image: radial-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    opacity: 0.8;
  }

  .faq-badge-pill {
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
  html.light-mode .faq-badge-pill {
    background: rgba(7, 155, 232, 0.08);
    border-color: rgba(7, 155, 232, 0.2);
    color: #0284c7;
  }

  .faq-hero-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    letter-spacing: -0.5px;
    color: var(--rx-text, #F5F7FF);
    line-height: 1.15;
    margin-bottom: 0.8rem;
  }
  .faq-hero-title span {
    background: linear-gradient(135deg, var(--rx-primary, #16B8FF), #A78BFA);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .faq-hero-lead {
    font-size: 1.05rem;
    color: var(--rx-text-secondary, #A5AEC2);
    max-width: 720px;
    margin: 0 auto 2rem;
    line-height: 1.6;
  }

  /* Live Search Box */
  .faq-search-wrap {
    max-width: 540px;
    margin: 0 auto 1.75rem;
    position: relative;
  }
  .faq-search-input {
    width: 100%;
    padding: 14px 20px 14px 48px;
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 14px;
    color: var(--rx-text, #F5F7FF);
    font-size: 1rem;
    outline: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    transition: all 0.25s ease;
  }
  html.light-mode .faq-search-input {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #0F172A;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
  }
  .faq-search-input:focus {
    border-color: var(--rx-primary, #16B8FF);
    box-shadow: 0 0 0 4px rgba(22, 184, 255, 0.18);
  }
  .faq-search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rx-primary, #16B8FF);
    font-size: 1.2rem;
    pointer-events: none;
  }
  .faq-search-clear {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--rx-text-muted, #6F7890);
    font-size: 1.1rem;
    cursor: pointer;
    display: none;
  }
  .faq-search-clear:hover { color: var(--rx-text, #F5F7FF); }


  /* Quick Category Tabs Bar */
  .faq-nav-tabs-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 2rem 0 2.5rem;
  }
  .faq-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    color: var(--rx-text-secondary, #A5AEC2);
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
  }
  html.light-mode .faq-nav-btn {
    background: #ffffff;
    border-color: #E2E8F0;
    color: #475569;
  }
  .faq-nav-btn:hover {
    background: var(--rx-surface-hover, #151C2D);
    color: var(--rx-primary, #16B8FF);
    border-color: rgba(22, 184, 255, 0.4);
    transform: translateY(-1px);
  }
  html.light-mode .faq-nav-btn:hover {
    background: #F1F5F9;
    color: #0284c7;
  }
  .faq-nav-btn.active {
    background: linear-gradient(135deg, rgba(22, 184, 255, 0.18), rgba(104, 87, 255, 0.15));
    border-color: var(--rx-primary, #16B8FF);
    color: var(--rx-primary, #16B8FF);
  }
  html.light-mode .faq-nav-btn.active {
    background: rgba(7, 155, 232, 0.1);
    color: #0284c7;
    border-color: #0284c7;
  }

  /* Toolbar: Global Expand/Collapse */
  .faq-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 12px;
  }
  .faq-results-counter {
    font-size: 0.92rem;
    color: var(--rx-text-secondary, #A5AEC2);
    font-weight: 500;
  }
  .faq-toggle-actions {
    display: flex;
    gap: 8px;
  }
  .btn-faq-action {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    color: var(--rx-text-secondary, #A5AEC2);
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  html.light-mode .btn-faq-action {
    background: #ffffff;
    border-color: #E2E8F0;
    color: #475569;
  }
  .btn-faq-action:hover {
    color: var(--rx-primary, #16B8FF);
    border-color: var(--rx-primary, #16B8FF);
  }

  /* Category Group Container */
  .faq-category-card {
    background: var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    transition: border-color 0.3s ease;
    scroll-margin-top: 100px;
  }
  html.light-mode .faq-category-card {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
  }
  .faq-category-card:hover {
    border-color: rgba(22, 184, 255, 0.35);
  }

  .faq-category-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--rx-border-light, rgba(34, 42, 61, 0.7));
  }
  html.light-mode .faq-category-header {
    border-bottom-color: #E2E8F0;
  }
  .faq-cat-pill {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(22, 184, 255, 0.15), rgba(104, 87, 255, 0.15));
    border: 1px solid rgba(22, 184, 255, 0.3);
    color: var(--rx-primary, #16B8FF);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    flex-shrink: 0;
  }
  html.light-mode .faq-cat-pill {
    background: linear-gradient(135deg, rgba(7, 155, 232, 0.1), rgba(103, 87, 232, 0.08));
    border-color: rgba(7, 155, 232, 0.25);
    color: #0284c7;
  }
  .faq-category-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--rx-text, #F5F7FF);
    margin: 0;
  }
  html.light-mode .faq-category-title { color: #0F172A; }

  /* Accordion Item Styling */
  .faq-accordion-item {
    border: 1px solid var(--rx-border, #222A3D);
    background: var(--rx-surface-hover, #151C2D);
    border-radius: 12px !important;
    margin-bottom: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
  }
  html.light-mode .faq-accordion-item {
    background: #F8FAFC;
    border-color: #E2E8F0;
  }
  .faq-accordion-item:hover {
    border-color: rgba(22, 184, 255, 0.35);
  }

  .faq-accordion-button {
    background: transparent !important;
    color: var(--rx-text, #F5F7FF) !important;
    font-size: 1rem;
    font-weight: 600;
    font-family: 'Outfit', sans-serif;
    padding: 16px 20px;
    box-shadow: none !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    border: none;
    text-align: left;
    transition: color 0.2s ease;
  }
  html.light-mode .faq-accordion-button { color: #0F172A !important; }
  .faq-accordion-button:not(.collapsed) {
    color: var(--rx-primary, #16B8FF) !important;
  }
  html.light-mode .faq-accordion-button:not(.collapsed) {
    color: #0284c7 !important;
  }

  .faq-accordion-body {
    padding: 0 20px 18px;
    font-size: 0.95rem;
    line-height: 1.75;
    color: var(--rx-text-secondary, #A5AEC2);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 14px;
  }
  html.light-mode .faq-accordion-body {
    color: #334155;
    border-top-color: #E2E8F0;
  }
  .faq-accordion-body p { margin-bottom: 10px; }
  .faq-accordion-body p:last-child { margin-bottom: 0; }

  /* Custom Clean Lists inside FAQ */
  .faq-list {
    list-style: none;
    padding-left: 0;
    margin: 10px 0 12px;
  }
  .faq-list li {
    position: relative;
    padding-left: 24px;
    margin-bottom: 8px;
    line-height: 1.6;
  }
  .faq-list li::before {
    content: '';
    position: absolute;
    left: 6px;
    top: 9px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--rx-primary, #16B8FF);
    box-shadow: 0 0 8px rgba(22, 184, 255, 0.5);
  }

  /* Product & Payment Tag Wraps */
  .product-tag-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 10px 0 12px;
  }
  .product-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 8px;
    font-size: 0.84rem;
    color: var(--rx-text, #F5F7FF);
    font-weight: 500;
  }
  html.light-mode .product-tag {
    background: #F1F5F9;
    border-color: #CBD5E1;
    color: #1E293B;
  }
  .product-tag i { color: var(--rx-primary, #16B8FF); }

  /* Callouts inside FAQ */
  .faq-callout {
    border-radius: 10px;
    padding: 12px 16px;
    margin: 10px 0;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    font-size: 0.9rem;
  }
  .callout-info {
    background: rgba(22, 184, 255, 0.08);
    border: 1px solid rgba(22, 184, 255, 0.25);
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .callout-info {
    background: rgba(7, 155, 232, 0.06);
    border-color: rgba(7, 155, 232, 0.2);
    color: #334155;
  }
  .callout-warning {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.25);
    color: var(--rx-text-secondary, #A5AEC2);
  }
  html.light-mode .callout-warning {
    background: rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.2);
    color: #7f1d1d;
  }

  /* Still Have Questions Box */
  .still-questions-box {
    background: radial-gradient(800px circle at 50% 0%, rgba(22, 184, 255, 0.12), transparent 70%),
                var(--rx-surface, #111625);
    border: 1px solid var(--rx-border, #222A3D);
    border-radius: 16px;
    padding: 35px 25px;
    text-align: center;
    margin-top: 3rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
  }
  html.light-mode .still-questions-box {
    background: #ffffff;
    border-color: #E2E8F0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
  }
  .still-questions-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--rx-text, #F5F7FF);
    margin-bottom: 8px;
  }
  html.light-mode .still-questions-title { color: #0F172A; }

  /* Floating Back to Top Button */
  #btnBackToTop {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--rx-surface-elevated, #171E30);
    border: 1px solid var(--rx-border-hover, #304A70);
    color: var(--rx-primary, #16B8FF);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
    opacity: 0;
    visibility: hidden;
    transform: translateY(12px);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 999;
  }
  html.light-mode #btnBackToTop {
    background: #ffffff;
    border-color: #CBD5E1;
    color: #0284c7;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
  }
  #btnBackToTop.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }
  #btnBackToTop:hover {
    background: var(--rx-primary, #16B8FF);
    color: #ffffff;
    border-color: var(--rx-primary, #16B8FF);
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(22, 184, 255, 0.4);
  }

  /* Responsive */
  @media (max-width: 767.98px) {
    .faq-category-card { padding: 20px 16px; }
    .faq-accordion-button { padding: 14px 16px; font-size: 0.95rem; }
    .faq-accordion-body { padding: 0 16px 16px; }
  }
</style>

<!-- ============================================================
     HERO HEADER SECTION
     ============================================================ -->
<section class="faq-hero-wrap">
  <div class="container text-center">
    
    <!-- Badge -->
    <div>
      <span class="faq-badge-pill">
        <i class="bi bi-patch-question-fill"></i> Help Center &amp; Knowledge Base
      </span>
    </div>

    <!-- Main Title -->
    <h1 class="faq-hero-title">
      Frequently Asked <span>Questions</span>
    </h1>

    <!-- Subtitle / Lead -->
    <p class="faq-hero-lead">
      Find answers to common questions about gaming top-ups, payments, order processing, delivery, and refunds at TOPUPERS. If you need further assistance, please contact our customer support team.
    </p>

    <!-- Interactive Live Search -->
    <div class="faq-search-wrap" style="margin-bottom: 0;">
      <i class="bi bi-search faq-search-icon"></i>
      <input type="text" id="faqSearchInput" class="faq-search-input" placeholder="Type keywords (e.g. MLBB, bKash, refund, Game ID)..." aria-label="Search FAQs">
      <button type="button" id="faqSearchClear" class="faq-search-clear" aria-label="Clear search">
        <i class="bi bi-x-circle-fill"></i>
      </button>
    </div>

  </div>
</section>

<!-- ============================================================
     MAIN BODY & ACCORDION
     ============================================================ -->
<div class="container py-4" style="min-height: 50vh;">

  <!-- Category Nav Pills Bar -->
  <div class="faq-nav-tabs-bar" id="faqCategoryNav">
    <button type="button" class="faq-nav-btn active" data-filter="all">All Questions</button>
    <a href="#cat-general" class="faq-nav-btn"><i class="bi bi-globe2"></i> 1. General</a>
    <a href="#cat-ordering" class="faq-nav-btn"><i class="bi bi-cart-check"></i> 2. Ordering</a>
    <a href="#cat-payments" class="faq-nav-btn"><i class="bi bi-credit-card-2-front"></i> 3. Payments</a>
    <a href="#cat-delivery" class="faq-nav-btn"><i class="bi bi-lightning-charge"></i> 4. Delivery</a>
    <a href="#cat-refunds" class="faq-nav-btn"><i class="bi bi-arrow-counterclockwise"></i> 5. Refunds &amp; Cancellations</a>
    <a href="#cat-codes" class="faq-nav-btn"><i class="bi bi-gift"></i> 6. Codes &amp; Subscriptions</a>
    <a href="#cat-security" class="faq-nav-btn"><i class="bi bi-shield-lock"></i> 7. Security</a>
    <a href="#cat-support" class="faq-nav-btn"><i class="bi bi-headset"></i> 8. Support</a>
  </div>

  <!-- FAQ Toolbar (Count + Expand/Collapse) -->
  <div class="faq-toolbar">
    <div class="faq-results-counter" id="faqCountText">
      Showing all <strong id="faqTotalCount">28</strong> questions across 8 categories
    </div>
    <div class="faq-toggle-actions">
      <button type="button" class="btn-faq-action" id="btnExpandAll"><i class="bi bi-arrows-expand me-1"></i> Expand All</button>
      <button type="button" class="btn-faq-action" id="btnCollapseAll"><i class="bi bi-arrows-collapse me-1"></i> Collapse All</button>
    </div>
  </div>

  <div class="accordion" id="faqMasterAccordion">

    <!-- ============================================================
         CATEGORY 1: GENERAL QUESTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-general">
      <div class="faq-category-header">
        <div class="faq-cat-pill">01</div>
        <h2 class="faq-category-title">General Questions</h2>
      </div>

      <!-- Q1.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-1-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-1-1" aria-expanded="false" aria-controls="collapse-1-1">
            <span>What is TOPUPERS?</span>
          </button>
        </h3>
        <div id="collapse-1-1" class="accordion-collapse collapse" aria-labelledby="heading-1-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p><strong>TOPUPERS</strong> is a gaming top-up and digital products store serving customers in Bangladesh. We aim to provide convenient ordering, transparent pricing, and reliable delivery for supported gaming products and digital services.</p>
          </div>
        </div>
      </div>

      <!-- Q1.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-1-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-1-2" aria-expanded="false" aria-controls="collapse-1-2">
            <span>What products does TOPUPERS offer?</span>
          </button>
        </h3>
        <div id="collapse-1-2" class="accordion-collapse collapse" aria-labelledby="heading-1-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Depending on availability, we offer:</p>
            <div class="product-tag-wrap">
              <span class="product-tag"><i class="bi bi-controller"></i> MLBB Diamonds &amp; Weekly Passes</span>
              <span class="product-tag"><i class="bi bi-fire"></i> Free Fire Diamonds</span>
              <span class="product-tag"><i class="bi bi-shield-shaded"></i> PUBG Mobile UC</span>
              <span class="product-tag"><i class="bi bi-gem"></i> Gaming Passes &amp; Tokens</span>
              <span class="product-tag"><i class="bi bi-gift"></i> Redeem Codes &amp; Gift Cards</span>
              <span class="product-tag"><i class="bi bi-stars"></i> Digital Subscriptions</span>
            </div>
            <ul class="faq-list">
              <li>Mobile Legends: Bang Bang (MLBB) Diamonds and Weekly Passes.</li>
              <li>Free Fire Diamonds and other supported products.</li>
              <li>PUBG Mobile UC.</li>
              <li>Gaming passes, tokens, and other in-game products.</li>
              <li>Redeem codes, gift cards, subscriptions, and selected digital products.</li>
            </ul>
            <p class="mb-0" style="font-size:0.88rem; color:var(--rx-text-muted);">* Available products may change over time.</p>
          </div>
        </div>
      </div>

      <!-- Q1.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-1-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-1-3" aria-expanded="false" aria-controls="collapse-1-3">
            <span>Is TOPUPERS an official game publisher?</span>
          </button>
        </h3>
        <div id="collapse-1-3" class="accordion-collapse collapse" aria-labelledby="heading-1-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>TOPUPERS operates as an <strong>independent gaming top-up and digital products reseller</strong>. We are not affiliated with or endorsed by a game publisher unless explicitly stated.</p>
            <p class="mb-0">All third-party game names and trademarks belong to their respective owners.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         CATEGORY 2: ORDERING QUESTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-ordering">
      <div class="faq-category-header">
        <div class="faq-cat-pill">02</div>
        <h2 class="faq-category-title">Ordering Questions</h2>
      </div>

      <!-- Q2.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-2-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-2-1" aria-expanded="false" aria-controls="collapse-2-1">
            <span>How do I place an order?</span>
          </button>
        </h3>
        <div id="collapse-2-1" class="accordion-collapse collapse" aria-labelledby="heading-2-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Ordering is simple and takes less than a minute:</p>
            <ul class="faq-list">
              <li><strong>Step 1:</strong> Visit the TOPUPERS website.</li>
              <li><strong>Step 2:</strong> Select your game and desired product.</li>
              <li><strong>Step 3:</strong> Enter the required Game ID, Server ID, or other product information.</li>
              <li><strong>Step 4:</strong> Review your order details and total price.</li>
              <li><strong>Step 5:</strong> Select an available payment method (bKash, Nagad, Rocket, Upay, etc.).</li>
              <li><strong>Step 6:</strong> Complete payment and follow the order status until delivery.</li>
            </ul>
            <p class="mb-0" style="font-size:0.88rem; color:var(--rx-text-muted);">* The exact steps may vary depending on the product and payment method.</p>
          </div>
        </div>
      </div>

      <!-- Q2.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-2-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-2-2" aria-expanded="false" aria-controls="collapse-2-2">
            <span>What information do I need to place an order?</span>
          </button>
        </h3>
        <div id="collapse-2-2" class="accordion-collapse collapse" aria-labelledby="heading-2-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>For a typical game top-up, you may need:</p>
            <ul class="faq-list">
              <li><strong>Game ID or Player ID</strong></li>
              <li><strong>Server ID or region</strong>, where required (e.g. Zone ID in MLBB)</li>
              <li><strong>In-game name (IGN)</strong>, where requested</li>
              <li><strong>Selected product and denomination</strong></li>
            </ul>
            <p class="mb-0"><strong>Tip:</strong> Always double check your information before confirming the order.</p>
          </div>
        </div>
      </div>

      <!-- Q2.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-2-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-2-3" aria-expanded="false" aria-controls="collapse-2-3">
            <span>Do I need to provide my game account password?</span>
          </button>
        </h3>
        <div id="collapse-2-3" class="accordion-collapse collapse" aria-labelledby="heading-2-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <div class="faq-callout callout-warning">
              <i class="bi bi-shield-slash-fill text-danger" style="font-size:1.3rem; flex-shrink:0;"></i>
              <div>
                <strong>Absolute Rule:</strong> No. TOPUPERS does not ordinarily require your game account password or OTP for UID-based top-ups. Never share your password, OTP, recovery code, or other sensitive login credentials with anyone claiming to provide routine top-up support.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Q2.4 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-2-4">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-2-4" aria-expanded="false" aria-controls="collapse-2-4">
            <span>Can I change my Game ID after placing an order?</span>
          </button>
        </h3>
        <div id="collapse-2-4" class="accordion-collapse collapse" aria-labelledby="heading-2-4" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Contact support immediately if you entered incorrect information.</p>
            <p class="mb-0">We may be able to correct it if processing has not started. Once a product has been processed or delivered, changes may not be possible.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         CATEGORY 3: PAYMENT QUESTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-payments">
      <div class="faq-category-header">
        <div class="faq-cat-pill">03</div>
        <h2 class="faq-category-title">Payment Questions</h2>
      </div>

      <!-- Q3.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-3-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-3-1" aria-expanded="false" aria-controls="collapse-3-1">
            <span>Which payment methods are accepted?</span>
          </button>
        </h3>
        <div id="collapse-3-1" class="accordion-collapse collapse" aria-labelledby="heading-3-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>TOPUPERS may support:</p>
            <div class="product-tag-wrap">
              <span class="product-tag"><i class="bi bi-phone text-primary"></i> bKash</span>
              <span class="product-tag"><i class="bi bi-phone text-warning"></i> Nagad</span>
              <span class="product-tag"><i class="bi bi-phone text-danger"></i> Rocket</span>
              <span class="product-tag"><i class="bi bi-wallet2 text-info"></i> Upay</span>
              <span class="product-tag"><i class="bi bi-currency-bitcoin text-warning"></i> Binance Pay</span>
            </div>
            <p class="mb-0">Available methods may change. Always follow the payment instructions shown for your specific order.</p>
          </div>
        </div>
      </div>

      <!-- Q3.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-3-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-3-2" aria-expanded="false" aria-controls="collapse-3-2">
            <span>How do I know if my payment was successful?</span>
          </button>
        </h3>
        <div id="collapse-3-2" class="accordion-collapse collapse" aria-labelledby="heading-3-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Check your order status and payment confirmation.</p>
            <p class="mb-0">A submitted screenshot or transaction ID does not automatically confirm payment. Your order may remain pending until the transaction has been verified.</p>
          </div>
        </div>
      </div>

      <!-- Q3.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-3-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-3-3" aria-expanded="false" aria-controls="collapse-3-3">
            <span>My payment was deducted, but my order is still pending. What should I do?</span>
          </button>
        </h3>
        <div id="collapse-3-3" class="accordion-collapse collapse" aria-labelledby="heading-3-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p><strong>Please do not immediately pay again.</strong></p>
            <p class="mb-0">Contact support with your order number, payment method, and transaction reference (TrxID). We will investigate the payment and order status.</p>
          </div>
        </div>
      </div>

      <!-- Q3.4 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-3-4">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-3-4" aria-expanded="false" aria-controls="collapse-3-4">
            <span>Why is my order still under payment verification?</span>
          </button>
        </h3>
        <div id="collapse-3-4" class="accordion-collapse collapse" aria-labelledby="heading-3-4" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Payment verification may take additional time because of provider network delays, incomplete transaction information, or manual review by our verification team.</p>
            <p class="mb-0">Your order can proceed once the required payment verification is complete.</p>
          </div>
        </div>
      </div>

      <!-- Q3.5 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-3-5">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-3-5" aria-expanded="false" aria-controls="collapse-3-5">
            <span>Can I pay using someone else's account?</span>
          </button>
        </h3>
        <div id="collapse-3-5" class="accordion-collapse collapse" aria-labelledby="heading-3-5" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Use only a payment method you are authorized to use. If a transaction raises a legitimate ownership or fraud concern, additional verification may be necessary.</p>
            <p class="mb-0"><strong>Important:</strong> Never share your payment PIN or OTP.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         CATEGORY 4: DELIVERY QUESTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-delivery">
      <div class="faq-category-header">
        <div class="faq-cat-pill">04</div>
        <h2 class="faq-category-title">Delivery Questions</h2>
      </div>

      <!-- Q4.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-4-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-4-1" aria-expanded="false" aria-controls="collapse-4-1">
            <span>How long does delivery take?</span>
          </button>
        </h3>
        <div id="collapse-4-1" class="accordion-collapse collapse" aria-labelledby="heading-4-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Many routine top-ups may be delivered <strong>within minutes</strong> after successful payment verification. However, delivery time depends on the product, supplier availability, game systems, and order volume.</p>
            <p class="mb-0">A specific delivery time is guaranteed only if TOPUPERS expressly states that commitment for the relevant product.</p>
          </div>
        </div>
      </div>

      <!-- Q4.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-4-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-4-2" aria-expanded="false" aria-controls="collapse-4-2">
            <span>How can I check my order status?</span>
          </button>
        </h3>
        <div id="collapse-4-2" class="accordion-collapse collapse" aria-labelledby="heading-4-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>If your order was placed through the website, check the order status using the available account or order-tracking feature in your user dashboard.</p>
            <p class="mb-0">If you ordered through Facebook or another official channel, contact support with your order reference.</p>
          </div>
        </div>
      </div>

      <!-- Q4.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-4-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-4-3" aria-expanded="false" aria-controls="collapse-4-3">
            <span>My order says Completed, but I have not received the product. What should I do?</span>
          </button>
        </h3>
        <div id="collapse-4-3" class="accordion-collapse collapse" aria-labelledby="heading-4-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Please contact support immediately and provide your <strong>order number</strong>, <strong>Game ID</strong>, <strong>Server ID</strong> where applicable, and a description of the issue.</p>
            <p class="mb-0">We will check the available fulfillment records and investigate with the supplier if necessary.</p>
          </div>
        </div>
      </div>

      <!-- Q4.4 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-4-4">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-4-4" aria-expanded="false" aria-controls="collapse-4-4">
            <span>Why is my order delayed?</span>
          </button>
        </h3>
        <div id="collapse-4-4" class="accordion-collapse collapse" aria-labelledby="heading-4-4" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Possible reasons include:</p>
            <ul class="faq-list">
              <li>Payment verification delays.</li>
              <li>Supplier or API interruptions.</li>
              <li>Game server maintenance or update patches.</li>
              <li>Product or regional restrictions.</li>
              <li>High order volume queues.</li>
              <li>Manual processing requirements.</li>
            </ul>
            <p class="mb-0">Contact support if the delay exceeds the timeframe communicated for your order.</p>
          </div>
        </div>
      </div>

      <!-- Q4.5 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-4-5">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-4-5" aria-expanded="false" aria-controls="collapse-4-5">
            <span>Will I receive my product immediately after payment?</span>
          </button>
        </h3>
        <div id="collapse-4-5" class="accordion-collapse collapse" aria-labelledby="heading-4-5" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Not necessarily. Payment must be verified, and the order must be processed successfully.</p>
            <p class="mb-0">Some products may require additional processing or have a scheduled release time.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         CATEGORY 5: REFUND AND CANCELLATION QUESTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-refunds">
      <div class="faq-category-header">
        <div class="faq-cat-pill">05</div>
        <h2 class="faq-category-title">Refund and Cancellation Questions</h2>
      </div>

      <!-- Q5.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-5-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-5-1" aria-expanded="false" aria-controls="collapse-5-1">
            <span>Can I cancel my order?</span>
          </button>
        </h3>
        <div id="collapse-5-1" class="accordion-collapse collapse" aria-labelledby="heading-5-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>You may request cancellation before processing begins.</p>
            <p class="mb-0">Once an order has entered processing, cancellation may not be possible because digital transactions can be irreversible. Contact support as soon as possible if you need to cancel.</p>
          </div>
        </div>
      </div>

      <!-- Q5.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-5-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-5-2" aria-expanded="false" aria-controls="collapse-5-2">
            <span>Are all digital products non-refundable?</span>
          </button>
        </h3>
        <div id="collapse-5-2" class="accordion-collapse collapse" aria-labelledby="heading-5-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p><strong>No.</strong> Although successfully delivered or redeemed digital products are generally irreversible, refunds or other remedies may be available for failed orders, duplicate payments, incorrect charges, or products that TOPUPERS cannot supply.</p>
            <p class="mb-0">All requests are assessed under our <a href="<?= APP_URL ?>/refund.php" style="color:var(--rx-primary); font-weight:600;">Return &amp; Refund Policy</a> and applicable law.</p>
          </div>
        </div>
      </div>

      <!-- Q5.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-5-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-5-3" aria-expanded="false" aria-controls="collapse-5-3">
            <span>What happens if I pay but receive nothing?</span>
          </button>
        </h3>
        <div id="collapse-5-3" class="accordion-collapse collapse" aria-labelledby="heading-5-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Contact support with your order number and transaction reference.</p>
            <p class="mb-0">We will investigate the payment and fulfillment status. If the order cannot be fulfilled, an appropriate resolution, including a refund where applicable, will be offered.</p>
          </div>
        </div>
      </div>

      <!-- Q5.4 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-5-4">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-5-4" aria-expanded="false" aria-controls="collapse-5-4">
            <span>Can I get a refund if I enter the wrong Game ID?</span>
          </button>
        </h3>
        <div id="collapse-5-4" class="accordion-collapse collapse" aria-labelledby="heading-5-4" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Refund eligibility depends on the circumstances.</p>
            <p class="mb-0">If the incorrect information was supplied by the customer and the product was successfully delivered to that account, a refund may not be possible. Contact support immediately because an order that has not yet been processed may still be correctable.</p>
          </div>
        </div>
      </div>

      <!-- Q5.5 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-5-5">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-5-5" aria-expanded="false" aria-controls="collapse-5-5">
            <span>How long does a refund take?</span>
          </button>
        </h3>
        <div id="collapse-5-5" class="accordion-collapse collapse" aria-labelledby="heading-5-5" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Processing time depends on the payment method, provider, and circumstances of the refund.</p>
            <p class="mb-0">Support will communicate the expected timeframe once the refund is approved and the applicable processing method is confirmed.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         CATEGORY 6: REDEEM CODES AND SUBSCRIPTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-codes">
      <div class="faq-category-header">
        <div class="faq-cat-pill">06</div>
        <h2 class="faq-category-title">Redeem Codes and Subscriptions</h2>
      </div>

      <!-- Q6.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-6-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-6-1" aria-expanded="false" aria-controls="collapse-6-1">
            <span>How do I redeem a digital code?</span>
          </button>
        </h3>
        <div id="collapse-6-1" class="accordion-collapse collapse" aria-labelledby="heading-6-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Follow the redemption instructions provided with the product or by the relevant game publisher or service provider.</p>
            <p class="mb-0"><strong>Important:</strong> Before purchasing, confirm that the code matches your region, platform, and account eligibility.</p>
          </div>
        </div>
      </div>

      <!-- Q6.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-6-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-6-2" aria-expanded="false" aria-controls="collapse-6-2">
            <span>What if my redeem code does not work?</span>
          </button>
        </h3>
        <div id="collapse-6-2" class="accordion-collapse collapse" aria-labelledby="heading-6-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Contact support promptly and provide your order number and relevant error details.</p>
            <div class="faq-callout callout-info mb-0">
              <i class="bi bi-info-circle-fill text-primary"></i>
              <div>
                <strong>Privacy Notice:</strong> Do not publicly share the full code. We may investigate the issue with our supplier and request reasonable evidence.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Q6.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-6-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-6-3" aria-expanded="false" aria-controls="collapse-6-3">
            <span>Can I get a refund for a subscription?</span>
          </button>
        </h3>
        <div id="collapse-6-3" class="accordion-collapse collapse" aria-labelledby="heading-6-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Refund eligibility depends on the subscription type, activation status, provider conditions, and the cause of the problem.</p>
            <p class="mb-0">Successfully activated or consumed subscriptions may be irreversible, but verified fulfillment errors will be investigated under our refund policy.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         CATEGORY 7: ACCOUNT AND SECURITY QUESTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-security">
      <div class="faq-category-header">
        <div class="faq-cat-pill">07</div>
        <h2 class="faq-category-title">Account and Security Questions</h2>
      </div>

      <!-- Q7.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-7-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-7-1" aria-expanded="false" aria-controls="collapse-7-1">
            <span>Is it safe to place an order with TOPUPERS?</span>
          </button>
        </h3>
        <div id="collapse-7-1" class="accordion-collapse collapse" aria-labelledby="heading-7-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>We aim to use appropriate security practices and verified payment procedures. Customers should always access our official website or official social media page and verify payment instructions before sending money.</p>
            <p class="mb-0">No online service can guarantee absolute security, so customers should protect their passwords and payment credentials.</p>
          </div>
        </div>
      </div>

      <!-- Q7.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-7-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-7-2" aria-expanded="false" aria-controls="collapse-7-2">
            <span>What should I do if I suspect a fraudulent transaction?</span>
          </button>
        </h3>
        <div id="collapse-7-2" class="accordion-collapse collapse" aria-labelledby="heading-7-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Contact your payment provider promptly if you suspect an unauthorized payment. Also contact TOPUPERS support with the relevant order or transaction reference so we can investigate.</p>
            <p class="mb-0"><strong>Never share an OTP or payment PIN during the investigation.</strong></p>
          </div>
        </div>
      </div>

      <!-- Q7.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-7-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-7-3" aria-expanded="false" aria-controls="collapse-7-3">
            <span>How is my personal information used?</span>
          </button>
        </h3>
        <div id="collapse-7-3" class="accordion-collapse collapse" aria-labelledby="heading-7-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>Information may be used to process orders, verify payments, deliver products, provide customer support, prevent fraud, and meet applicable legal obligations.</p>
            <p class="mb-0">For full details, please read our <a href="<?= APP_URL ?>/privacy.php" style="color:var(--rx-primary); font-weight:600;">Privacy Policy</a>.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================
         CATEGORY 8: SUPPORT QUESTIONS
         ============================================================ -->
    <div class="faq-category-card" id="cat-support">
      <div class="faq-category-header">
        <div class="faq-cat-pill">08</div>
        <h2 class="faq-category-title">Support Questions</h2>
      </div>

      <!-- Q8.1 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-8-1">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-8-1" aria-expanded="false" aria-controls="collapse-8-1">
            <span>How can I contact TOPUPERS?</span>
          </button>
        </h3>
        <div id="collapse-8-1" class="accordion-collapse collapse" aria-labelledby="heading-8-1" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>You can contact us through the official support email or the official Facebook page published on our website.</p>
            <p><strong>Support Email:</strong> <a href="mailto:<?= e($siteEmail) ?>" style="color:var(--rx-primary); font-weight:600;"><?= e($siteEmail) ?></a></p>
            <p class="mb-0">For order-related enquiries, include your order number and a brief explanation of the issue.</p>
          </div>
        </div>
      </div>

      <!-- Q8.2 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-8-2">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-8-2" aria-expanded="false" aria-controls="collapse-8-2">
            <span>When will customer support respond?</span>
          </button>
        </h3>
        <div id="collapse-8-2" class="accordion-collapse collapse" aria-labelledby="heading-8-2" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p class="mb-0">We aim to respond as quickly as reasonably possible. Response times may vary according to order volume and the complexity of your enquiry.</p>
          </div>
        </div>
      </div>

      <!-- Q8.3 -->
      <div class="accordion-item faq-accordion-item">
        <h3 class="accordion-header" id="heading-8-3">
          <button class="accordion-button collapsed faq-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-8-3" aria-expanded="false" aria-controls="collapse-8-3">
            <span>Where can I find your policies?</span>
          </button>
        </h3>
        <div id="collapse-8-3" class="accordion-collapse collapse" aria-labelledby="heading-8-3" data-bs-parent="#faqMasterAccordion">
          <div class="accordion-body faq-accordion-body">
            <p>You can review the following legal and operational pages on our website:</p>
            <div class="d-flex flex-wrap gap-2 mt-2">
              <a href="<?= APP_URL ?>/terms.php" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                <i class="bi bi-file-earmark-text me-1"></i> Terms &amp; Conditions
              </a>
              <a href="<?= APP_URL ?>/privacy.php" class="btn btn-sm btn-outline-info" style="border-radius:8px;">
                <i class="bi bi-shield-check me-1"></i> Privacy Policy
              </a>
              <a href="<?= APP_URL ?>/refund.php" class="btn btn-sm btn-outline-success" style="border-radius:8px;">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Return &amp; Refund Policy
              </a>
              <a href="<?= APP_URL ?>/contact.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                <i class="bi bi-headset me-1"></i> Contact Us
              </a>
            </div>
            <p class="mt-3 mb-0" style="font-size:0.88rem; color:var(--rx-text-muted);">These pages explain our service terms, data handling practices, refund conditions, and support options.</p>
          </div>
        </div>
      </div>

    </div>

  </div>

  <!-- ============================================================
       STILL HAVE QUESTIONS? (BOTTOM CALLOUT)
       ============================================================ -->
  <div class="still-questions-box">
    <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-3" style="background:rgba(22,184,255,0.1); border:1px solid rgba(22,184,255,0.3); color:var(--rx-primary); font-size:1.8rem;">
      <i class="bi bi-chat-dots-fill"></i>
    </div>
    <h3 class="still-questions-title">Still Have Questions?</h3>
    <p style="color:var(--rx-text-secondary); max-width:620px; margin:0 auto 1.5rem; font-size:0.98rem; line-height:1.6;">
      We are here to help! Contact TOPUPERS support with your question, and include your order number if your enquiry concerns an existing purchase.
    </p>
    <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
      <a href="<?= APP_URL ?>/contact.php" class="btn btn-primary px-4 py-2" style="border-radius:10px; font-weight:600;">
        <i class="bi bi-headset me-2"></i> Contact Customer Support
      </a>
      <a href="mailto:<?= e($siteEmail) ?>" class="btn btn-outline-secondary px-4 py-2" style="border-radius:10px; font-weight:600;">
        <i class="bi bi-envelope me-2"></i> Email: <?= e($siteEmail) ?>
      </a>
    </div>
    <div class="mt-4 pt-3 border-top" style="border-color:var(--rx-border-light) !important;">
      <span style="font-family:'Rajdhani', sans-serif; font-weight:700; font-size:1.05rem; letter-spacing:1px; color:var(--rx-primary);">
        TOPUPERS — GAMING TOP-UP &amp; DIGITAL STORE
      </span>
    </div>
  </div>

</div>

<!-- Back to Top Button -->
<button type="button" id="btnBackToTop" aria-label="Back to top of page" title="Back to top">
  <i class="bi bi-chevron-up"></i>
</button>

<!-- Interactive Client-side Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Reading Progress Bar
  const progressBar = document.getElementById('faqProgressBar');
  window.addEventListener('scroll', () => {
    const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
    if (progressBar) progressBar.style.width = scrolled + '%';
  }, { passive: true });

  // 2. Back to Top Button
  const btnBackToTop = document.getElementById('btnBackToTop');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 400) {
      btnBackToTop.classList.add('visible');
    } else {
      btnBackToTop.classList.remove('visible');
    }
  }, { passive: true });
  btnBackToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // 3. Live FAQ Search Filtering
  const searchInput = document.getElementById('faqSearchInput');
  const searchClear = document.getElementById('faqSearchClear');
  const faqItems = document.querySelectorAll('.faq-accordion-item');
  const categoryCards = document.querySelectorAll('.faq-category-card');
  const countText = document.getElementById('faqCountText');
  const totalCount = document.getElementById('faqTotalCount');

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const q = this.value.trim().toLowerCase();
      if (q) {
        searchClear.style.display = 'block';
      } else {
        searchClear.style.display = 'none';
      }

      let visibleCount = 0;

      faqItems.forEach(item => {
        const text = item.innerText.toLowerCase();
        const collapseEl = item.querySelector('.accordion-collapse');
        const btn = item.querySelector('.accordion-button');

        if (!q || text.includes(q)) {
          item.style.display = 'block';
          visibleCount++;
          // auto-expand matches when searching
          if (q && collapseEl && !collapseEl.classList.contains('show')) {
            collapseEl.classList.add('show');
            if (btn) btn.classList.remove('collapsed');
          }
        } else {
          item.style.display = 'none';
        }
      });

      // Show/hide category containers if all items inside are hidden
      categoryCards.forEach(card => {
        const cardItems = card.querySelectorAll('.faq-accordion-item');
        let cardHasVisible = false;
        cardItems.forEach(ci => {
          if (ci.style.display !== 'none') cardHasVisible = true;
        });
        card.style.display = cardHasVisible ? 'block' : 'none';
      });

      if (q) {
        countText.innerHTML = `Found <strong>${visibleCount}</strong> question${visibleCount === 1 ? '' : 's'} matching "<em>${escapeHtml(q)}</em>"`;
      } else {
        countText.innerHTML = `Showing all <strong>${faqItems.length}</strong> questions across 8 categories`;
      }
    });

    searchClear.addEventListener('click', function() {
      searchInput.value = '';
      searchInput.dispatchEvent(new Event('input'));
      searchInput.focus();
    });
  }

  // 4. Expand / Collapse All
  const btnExpandAll = document.getElementById('btnExpandAll');
  const btnCollapseAll = document.getElementById('btnCollapseAll');

  if (btnExpandAll && btnCollapseAll) {
    btnExpandAll.addEventListener('click', () => {
      document.querySelectorAll('.faq-accordion-item').forEach(item => {
        if (item.style.display !== 'none') {
          const collapse = item.querySelector('.accordion-collapse');
          const btn = item.querySelector('.accordion-button');
          if (collapse) collapse.classList.add('show');
          if (btn) btn.classList.remove('collapsed');
        }
      });
    });

    btnCollapseAll.addEventListener('click', () => {
      document.querySelectorAll('.faq-accordion-item').forEach(item => {
        const collapse = item.querySelector('.accordion-collapse');
        const btn = item.querySelector('.accordion-button');
        if (collapse) collapse.classList.remove('show');
        if (btn) btn.classList.add('collapsed');
      });
    });
  }

  // 5. Category Nav Active State
  const navBtns = document.querySelectorAll('.faq-nav-btn');
  navBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      navBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      if (this.getAttribute('data-filter') === 'all') {
        categoryCards.forEach(c => c.style.display = 'block');
        faqItems.forEach(i => i.style.display = 'block');
        if (searchInput) searchInput.value = '';
        if (searchClear) searchClear.style.display = 'none';
        countText.innerHTML = `Showing all <strong>${faqItems.length}</strong> questions across 8 categories`;
      }
    });
  });

  function escapeHtml(str) {
    return str.replace(/[&<>'"]/g, tag => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      "'": '&#39;',
      '"': '&quot;'
    }[tag] || tag));
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
