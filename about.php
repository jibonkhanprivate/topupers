<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Us';
$pageDescription = 'Learn more about TopUpers, Bangladesh\'s trusted gaming top-up store.';

include __DIR__ . '/includes/header.php';
?>
<!-- Page Header -->
<div style="background:var(--bg-card);border-bottom:1px solid var(--border-light);padding:2.5rem 0;">
  <div class="container text-center">
    <h1 class="section-title">About <span>Us</span></h1>
    <p style="color:var(--text-muted);font-size:.95rem;margin-top:.5rem;">Your Trusted Digital Gaming Partner</p>
  </div>
</div>
<div class="container py-5" style="min-height: 40vh; color: var(--text-primary); line-height: 1.8;">
  <div class="rx-card p-4">
    <?php 
      $aboutContent = getSetting('content_about_us');
      if ($aboutContent) {
          echo $aboutContent;
      } else {
          // Fallback to original content
          ?>
          <p>TopUpers is a digital gaming recharge platform built to provide fast, secure, and reliable top-up services for popular mobile games, subscriptions, and digital products.</p>
          <p>We specialize in delivering in-game currencies such as Diamonds, UC, CP, Coin, Token and vouchers.</p>
          <p>Our goal is to simplify the top-up process so gamers can recharge instantly without complications.</p>
          
          <h5 class="mt-4 mb-3">Our Mission</h5>
          <p>To deliver fast, secure, and hassle-free digital top-up services.</p>
          
          <h5 class="mt-4 mb-3">Our Vision</h5>
          <p>To become a trusted and recognized platform for gamers worldwide.</p>
          
          <h5 class="mt-4 mb-3">Why Choose TopUpers</h5>
          <ul style="list-style-type: disc; padding-left: 20px;">
            <li>Fast & automated delivery system</li>
            <li>Secure payment processing</li>
            <li>Competitive pricing</li>
            <li>Simple and user-friendly interface</li>
            <li>Reliable customer support</li>
          </ul>
          <?php
      }
    ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
