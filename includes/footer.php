<?php
// includes/footer.php
$footerSiteName    = trim(getSetting('site_name')) ?: (defined('APP_NAME') ? APP_NAME : 'TopUpers');
$footerSiteTagline = trim(getSetting('site_tagline')) ?: (defined('APP_TAGLINE') ? APP_TAGLINE : 'Instant Game Top-Ups. Safe, Fast, Reliable.');
$footerSiteLogo    = trim(getSetting('site_logo')) ?: 'assets/img/favicon.svg';
$footerAbout       = trim(getSetting('footer_about'));
$footerEmail       = trim(getSetting('support_email')) ?: (trim(getSetting('site_email')) ?: 'support@topupers.com');
$footerPhone       = trim(getSetting('support_whatsapp')) ?: (trim(getSetting('site_phone')) ?: '+8801700000000');
$footerAddress     = trim(getSetting('site_address')) ?: 'Dhaka, Bangladesh';
$footerMessenger   = trim(getSetting('support_messenger')) ?: (trim(getSetting('support_link')) ?: 'https://m.me/topupers');
$footerTelegram    = trim(getSetting('support_telegram')) ?: 'https://t.me/TopUpersSupport';

// Format WhatsApp link
$cleanWaPhone = preg_replace('/[^0-9]/', '', (string)$footerPhone);
if (str_starts_with($cleanWaPhone, '01') && strlen($cleanWaPhone) === 11) {
    $cleanWaPhone = '88' . $cleanWaPhone;
}
$footerWaUrl = !empty($cleanWaPhone) ? 'https://wa.me/' . $cleanWaPhone : '#';

// Format Messenger / Facebook link
if (!empty($footerMessenger)) {
    $footerFbUrl = (str_starts_with($footerMessenger, 'http://') || str_starts_with($footerMessenger, 'https://'))
        ? $footerMessenger
        : 'https://m.me/' . ltrim($footerMessenger, '@');
} else {
    $footerFbUrl = '#';
}

// Format Telegram link
if (!empty($footerTelegram)) {
    $footerTgUrl = (str_starts_with($footerTelegram, 'http://') || str_starts_with($footerTelegram, 'https://'))
        ? $footerTelegram
        : 'https://t.me/' . ltrim($footerTelegram, '@');
} else {
    $footerTgUrl = '#';
}

// Popular games for footer links (top 5)
$footerPopularGames = [];
if (!empty($games) && is_array($games)) {
    $footerPopularGames = array_slice($games, 0, 5);
} else {
    try {
        $footerPopularGames = db()->query("SELECT name, slug FROM games WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, id ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $footerPopularGames = [];
    }
}

$footerMethods = [];
try {
    $footerMethods = db()->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $footerMethods = [];
}
?>

<!-- ============================================================
     MODERN GAMING MARKETPLACE FOOTER
     ============================================================ -->
<footer class="rx-footer" role="contentinfo">
  <div class="rx-footer-glow-line" aria-hidden="true"></div>
  <div class="rx-footer-ambient rx-footer-ambient-left" aria-hidden="true"></div>
  <div class="rx-footer-ambient rx-footer-ambient-right" aria-hidden="true"></div>

  <div class="container position-relative" style="z-index: 2;">
    <div class="row g-4 g-lg-5 align-items-start justify-content-between rx-footer-main">

      <!-- Column 1: Brand, About, Contact & Socials -->
      <div class="col-lg-4 col-md-12">
        <a href="<?= APP_URL ?>" class="rx-footer-logo-link">
          <?php 
            $isCustomFooterLogo = !empty($footerSiteLogo) && file_exists(APP_ROOT . '/' . $footerSiteLogo) && strpos($footerSiteLogo, 'favicon') === false;
          ?>
          <?php if (!empty($footerSiteLogo) && file_exists(APP_ROOT . '/' . $footerSiteLogo)): ?>
            <img src="<?= APP_URL ?>/<?= e($footerSiteLogo) ?>?v=<?= filemtime(APP_ROOT . '/' . $footerSiteLogo) ?>" 
                 alt="<?= e($footerSiteName) ?> Logo" 
                 class="rx-footer-logo-img">
          <?php endif; ?>
          <?php if (!$isCustomFooterLogo): ?>
            <span class="footer-logo"><?= e($footerSiteName) ?></span>
          <?php endif; ?>
        </a>

        <p class="footer-desc">
          <?= !empty($footerAbout) ? nl2br(e($footerAbout)) : "Bangladesh's trusted gaming top-up store. Instant delivery for Mobile Legends, Free Fire, PUBG and all your favorite games." ?>
        </p>

        <!-- Compact Contact Info -->
        <div class="rx-footer-contacts">
          <?php if (!empty($footerPhone)): ?>
            <a href="<?= e($footerWaUrl) ?>" target="_blank" rel="noopener noreferrer" class="rx-contact-chip">
              <i class="bi bi-whatsapp text-success"></i>
              <span><?= e($footerPhone) ?></span>
            </a>
          <?php endif; ?>
          <?php if (!empty($footerEmail)): ?>
            <a href="mailto:<?= e($footerEmail) ?>" class="rx-contact-chip">
              <i class="bi bi-envelope-fill" style="color: var(--primary);"></i>
              <span><?= e($footerEmail) ?></span>
            </a>
          <?php endif; ?>
        </div>

        <!-- Social Media Icons -->
        <div class="social-links">
          <a href="<?= e($footerFbUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-link social-fb" aria-label="Facebook" title="Facebook">
            <i class="bi bi-facebook"></i>
          </a>
          <a href="#" class="social-link social-ig" aria-label="Instagram" title="Instagram">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="<?= e($footerWaUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-link social-wa" aria-label="WhatsApp" title="WhatsApp">
            <i class="bi bi-whatsapp"></i>
          </a>
          <a href="<?= e($footerTgUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-link social-tg" aria-label="Telegram" title="Telegram">
            <i class="bi bi-telegram"></i>
          </a>
          <a href="#" class="social-link social-yt" aria-label="YouTube" title="YouTube">
            <i class="bi bi-youtube"></i>
          </a>
        </div>
      </div>

      <!-- Column 2: Quick Links (Organized 2-Column Grid) -->
      <div class="col-lg-4 col-md-6">
        <h6 class="footer-heading">Quick Links</h6>
        <ul class="footer-links footer-links-grid">
          <li>
            <a href="<?= APP_URL ?>/about.php">
              <span class="rx-link-dot"></span>
              <span>About Us</span>
            </a>
          </li>
          <li>
            <a href="<?= APP_URL ?>/privacy.php">
              <span class="rx-link-dot"></span>
              <span>Privacy Policy</span>
            </a>
          </li>
          <li>
            <a href="<?= APP_URL ?>/terms.php">
              <span class="rx-link-dot"></span>
              <span>Terms &amp; Conditions</span>
            </a>
          </li>
          <li>
            <a href="<?= APP_URL ?>/refund.php">
              <span class="rx-link-dot"></span>
              <span>Return &amp; Refunds</span>
            </a>
          </li>
          <li>
            <a href="<?= APP_URL ?>/contact.php">
              <span class="rx-link-dot"></span>
              <span>Contact Us</span>
            </a>
          </li>
          <li>
            <a href="<?= APP_URL ?>/faq.php">
              <span class="rx-link-dot"></span>
              <span>FAQs</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Column 3: Payment Methods -->
      <div class="col-lg-4 col-md-6">
        <h6 class="footer-heading">Payment Methods</h6>
        <div class="rx-pay-grid">
          <?php if (!empty($footerMethods)): ?>
            <?php foreach ($footerMethods as $fm): ?>
              <?php if (!empty($fm['icon'])): ?>
                <div class="footer-pay-card" title="<?= e((string)$fm['method_label']) ?>">
                  <img src="<?= APP_URL ?>/<?= e((string)$fm['icon']) ?>" alt="<?= e((string)$fm['method_label']) ?>" loading="lazy" decoding="async">
                </div>
              <?php else: ?>
                <span class="footer-pay-pill"><?= e((string)$fm['method_label']) ?></span>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="footer-pay-pill">bKash</span>
            <span class="footer-pay-pill">Nagad</span>
            <span class="footer-pay-pill">Rocket</span>
            <span class="footer-pay-pill">Upay</span>
            <span class="footer-pay-pill">Binance Pay</span>
          <?php endif; ?>
        </div>
        <div class="rx-pay-trust-note">
          <i class="bi bi-shield-lock-fill"></i>
          <span>100% Secure &amp; Instant Automated Checkout</span>
        </div>
      </div>

    </div>

    <!-- Bottom Bar -->
    <div class="rx-footer-bottom">
      <p class="footer-copy mb-0">
        &copy; <?= date('Y') ?> <strong class="rx-copy-brand"><?= e($footerSiteName) ?></strong>. All rights reserved.
      </p>
    </div>
  </div>
</footer>

<!-- ============================================================
     CLEAN & MINIMAL CUSTOMER SUPPORT WIDGET
     ============================================================ -->
<?php if (getSetting('support_widget_active', '1') !== '0'): 
  $fswTitle     = trim(getSetting('support_title')) ?: ($footerSiteName . ' Support');
  $fswSubtitle  = trim(getSetting('support_subtitle')) ?: 'Online • 24/7 Support';
  $fswWhatsapp  = trim(getSetting('support_whatsapp')) ?: $footerPhone;
  $fswMessenger = trim(getSetting('support_messenger')) ?: $footerMessenger;
  $fswTelegram  = trim(getSetting('support_telegram')) ?: $footerTelegram;
  $fswEmail     = trim(getSetting('support_email')) ?: $footerEmail;
  $fswUser      = currentUser();
?>
<style>
.floating-support-widget {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1060;
  font-family: 'Inter', 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
  -webkit-font-smoothing: antialiased;
}

.fsw-toggle-btn {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border: 1px solid rgba(255, 255, 255, 0.16);
  color: #ffffff;
  font-size: 1.4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(79, 70, 229, 0.45);
  transition: transform 0.2s ease, background 0.2s ease;
}

.fsw-toggle-btn:hover {
  transform: translateY(-2px);
}

.fsw-toggle-btn .icon-close {
  display: none;
  font-size: 1.2rem;
}

.floating-support-widget.open .fsw-toggle-btn {
  background: #1e293b;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45);
}

.floating-support-widget.open .fsw-toggle-btn .icon-chat {
  display: none;
}

.floating-support-widget.open .fsw-toggle-btn .icon-close {
  display: inline-block;
}

/* Popup Panel */
.fsw-popup {
  position: absolute;
  bottom: 68px;
  right: 0;
  width: 345px;
  max-width: calc(100vw - 32px);
  background: #0f1422;
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 16px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
  overflow: hidden;
  opacity: 0;
  visibility: hidden;
  transform: translateY(12px) scale(0.97);
  transform-origin: bottom right;
  transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
  z-index: 1061;
}

.floating-support-widget.open .fsw-popup {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
}

/* Header */
.fsw-header {
  background: #151c2e;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.fsw-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.fsw-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #4f46e5;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1rem;
  position: relative;
  flex-shrink: 0;
}

.fsw-online-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  background: #10b981;
  border: 2px solid #151c2e;
  border-radius: 50%;
}

.fsw-title {
  color: #ffffff;
  font-weight: 700;
  font-size: 0.9rem;
  margin: 0;
  line-height: 1.2;
}

.fsw-subtitle {
  color: #94a3b8;
  font-size: 0.72rem;
  margin: 2px 0 0;
}

.fsw-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 0.8rem;
}

.fsw-close-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

/* 3-Tab Bar */
.fsw-tabs-nav {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: #0b0f1a;
  padding: 4px;
  gap: 4px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.fsw-tab-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 7px 6px;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.fsw-tab-btn:hover {
  color: #e2e8f0;
}

.fsw-tab-btn.active {
  background: #182034;
  color: #ffffff;
}

/* Tab Content */
.fsw-tab-pane {
  display: none;
  padding: 14px;
}

.fsw-tab-pane.active {
  display: block;
}

.fsw-textarea {
  width: 100%;
  background: #0b0f1a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9px;
  padding: 9px 11px;
  color: #ffffff;
  font-size: 0.8rem;
  resize: none;
  outline: none;
  margin-bottom: 10px;
}

.fsw-textarea:focus,
.fsw-input:focus,
.fsw-select:focus {
  border-color: #6366f1;
}

.fsw-textarea::placeholder,
.fsw-input::placeholder {
  color: #64748b;
}

/* 2x2 Channel Grid */
.fsw-channels-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.fsw-channel-btn {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px 10px;
  border-radius: 10px;
  background: #151c2e;
  border: 1px solid rgba(255, 255, 255, 0.07);
  color: #ffffff;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
  text-align: left;
}

.fsw-channel-btn:hover {
  background: #1c253d;
  border-color: rgba(255, 255, 255, 0.15);
}

.fsw-ch-icon {
  width: 30px;
  height: 30px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.92rem;
  flex-shrink: 0;
  color: #fff;
}

.fsw-channel-btn.whatsapp .fsw-ch-icon { background: #16a34a; }
.fsw-channel-btn.messenger .fsw-ch-icon { background: #2563eb; }
.fsw-channel-btn.telegram .fsw-ch-icon { background: #0284c7; }
.fsw-channel-btn.email .fsw-ch-icon { background: #4f46e5; }

.fsw-ch-name {
  font-size: 0.78rem;
  font-weight: 600;
  color: #f8fafc;
  line-height: 1.2;
}

.fsw-ch-sub {
  font-size: 0.66rem;
  color: #94a3b8;
  display: block;
}

/* Inputs & Buttons */
.fsw-search-box {
  display: flex;
  gap: 6px;
}

.fsw-input,
.fsw-select {
  width: 100%;
  background: #0b0f1a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 8px 10px;
  color: #fff;
  font-size: 0.79rem;
  outline: none;
}

.fsw-btn-primary {
  background: #4f46e5;
  border: none;
  color: #fff;
  font-weight: 600;
  font-size: 0.79rem;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.fsw-btn-primary:hover {
  background: #4338ca;
}

.fsw-btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.fsw-track-card {
  background: #151c2e;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 12px;
  margin-top: 10px;
}

@media (max-width: 575.98px) {
  .floating-support-widget {
    bottom: 16px;
    right: 16px;
  }
  .fsw-popup {
    width: calc(100vw - 28px);
    right: 0;
  }
}
</style>

<div class="floating-support-widget" id="floatingSupportWidget">
  <!-- Popup Card -->
  <div class="fsw-popup" id="fswPopup" role="dialog" aria-label="Customer Support">
    <!-- Header -->
    <div class="fsw-header">
      <div class="fsw-brand">
        <div class="fsw-avatar">
          <i class="bi bi-headset"></i>
          <span class="fsw-online-dot"></span>
        </div>
        <div class="min-w-0">
          <h6 class="fsw-title text-truncate"><?= e($fswTitle) ?></h6>
          <p class="fsw-subtitle text-truncate"><?= e($fswSubtitle) ?></p>
        </div>
      </div>
      <button type="button" class="fsw-close-btn" id="fswCloseBtn" aria-label="Close">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- 3 Essential Tabs -->
    <div class="fsw-tabs-nav" role="tablist">
      <button type="button" class="fsw-tab-btn active" data-target="chat" role="tab">
        <i class="bi bi-chat-dots"></i>
        <span>Chat</span>
      </button>
      <button type="button" class="fsw-tab-btn" data-target="track" role="tab">
        <i class="bi bi-box-seam"></i>
        <span>Track</span>
      </button>
      <button type="button" class="fsw-tab-btn" data-target="ticket" role="tab">
        <i class="bi bi-ticket-detailed"></i>
        <span>Ticket</span>
      </button>
    </div>

    <!-- TAB 1: CHAT -->
    <div class="fsw-tab-pane active" id="fswPane-chat">
      <textarea id="fswCustomMessage" class="fsw-textarea" rows="2" maxlength="500" placeholder="Write your message or Order ID (optional)..."></textarea>

      <div class="fsw-channels-grid">
        <button type="button" class="fsw-channel-btn whatsapp" data-channel="whatsapp" data-target="<?= e($fswWhatsapp) ?>">
          <div class="fsw-ch-icon"><i class="bi bi-whatsapp"></i></div>
          <div>
            <div class="fsw-ch-name">WhatsApp</div>
            <span class="fsw-ch-sub">Fastest Reply</span>
          </div>
        </button>

        <button type="button" class="fsw-channel-btn messenger" data-channel="messenger" data-target="<?= e($fswMessenger) ?>">
          <div class="fsw-ch-icon"><i class="bi bi-messenger"></i></div>
          <div>
            <div class="fsw-ch-name">Messenger</div>
            <span class="fsw-ch-sub">Live Chat</span>
          </div>
        </button>

        <button type="button" class="fsw-channel-btn telegram" data-channel="telegram" data-target="<?= e($fswTelegram) ?>">
          <div class="fsw-ch-icon"><i class="bi bi-telegram"></i></div>
          <div>
            <div class="fsw-ch-name">Telegram</div>
            <span class="fsw-ch-sub">24/7 Support</span>
          </div>
        </button>

        <button type="button" class="fsw-channel-btn email" data-channel="email" data-target="<?= e($fswEmail) ?>">
          <div class="fsw-ch-icon"><i class="bi bi-envelope-fill"></i></div>
          <div>
            <div class="fsw-ch-name">Email</div>
            <span class="fsw-ch-sub">Helpdesk</span>
          </div>
        </button>
      </div>
    </div>

    <!-- TAB 2: TRACK ORDER -->
    <div class="fsw-tab-pane" id="fswPane-track">
      <div class="fsw-search-box">
        <input type="text" id="fswTrackInput" class="fsw-input" placeholder="Enter Order # or Player ID">
        <button type="button" id="fswTrackBtn" class="fsw-btn-primary">Track</button>
      </div>
      <div id="fswTrackResult"></div>
    </div>

    <!-- TAB 3: SUBMIT TICKET -->
    <div class="fsw-tab-pane" id="fswPane-ticket">
      <form id="fswTicketForm">
        <?php if (!$fswUser): ?>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <input type="text" name="name" class="fsw-input" placeholder="Your Name *" required>
          </div>
          <div class="col-6">
            <input type="text" name="contact" class="fsw-input" placeholder="Phone / Email *" required>
          </div>
        </div>
        <?php endif; ?>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <select name="category" class="fsw-select">
              <option value="order">Order Issue</option>
              <option value="payment">Payment</option>
              <option value="account">Player ID</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-6">
            <input type="text" name="order_number" id="fswTicketOrder" class="fsw-input" placeholder="Order # (Optional)">
          </div>
        </div>

        <div class="mb-2">
          <input type="text" name="subject" id="fswTicketSubject" class="fsw-input" placeholder="Subject *" required>
        </div>

        <textarea name="message" class="fsw-textarea" rows="3" placeholder="Describe your issue..." required></textarea>

        <button type="submit" id="fswTicketSubmitBtn" class="fsw-btn-primary w-100">Submit Ticket</button>
        <div id="fswTicketResponse" style="display:none; font-size:0.75rem; padding:8px 10px; border-radius:8px;"></div>
      </form>
    </div>
  </div>

  <!-- Floating Trigger Button -->
  <button type="button" class="fsw-toggle-btn" id="fswToggleBtn" aria-label="Customer Support">
    <i class="bi bi-headset icon-chat"></i>
    <i class="bi bi-x-lg icon-close"></i>
  </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const widget    = document.getElementById('floatingSupportWidget');
  const toggleBtn = document.getElementById('fswToggleBtn');
  const closeBtn  = document.getElementById('fswCloseBtn');
  const msgInput  = document.getElementById('fswCustomMessage');

  if (!widget || !toggleBtn) return;

  function toggleSupport(forceState) {
    const isOpen = typeof forceState === 'boolean' ? forceState : !widget.classList.contains('open');
    widget.classList.toggle('open', isOpen);
  }

  toggleBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    toggleSupport();
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      toggleSupport(false);
    });
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && widget.classList.contains('open')) {
      toggleSupport(false);
    }
  });

  document.addEventListener('click', function(e) {
    if (widget.classList.contains('open') && !widget.contains(e.target)) {
      toggleSupport(false);
    }
  });

  // Tab Switching
  const tabBtns  = widget.querySelectorAll('.fsw-tab-btn');
  const tabPanes = widget.querySelectorAll('.fsw-tab-pane');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const target = this.getAttribute('data-target');
      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanes.forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      const pane = document.getElementById('fswPane-' + target);
      if (pane) pane.classList.add('active');
    });
  });

  // Channel Buttons
  widget.querySelectorAll('.fsw-channel-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const channel = this.getAttribute('data-channel');
      const target  = (this.getAttribute('data-target') || '').trim();
      const rawMsg  = msgInput && msgInput.value.trim() !== ''
        ? msgInput.value.trim()
        : 'Hi! I need assistance with my order on <?= e(addslashes($footerSiteName)) ?>.';
      const encodedMsg = encodeURIComponent(rawMsg);
      let finalUrl = '#';

      if (channel === 'whatsapp') {
        let phone = target.replace(/[^0-9]/g, '');
        if (phone.startsWith('01') && phone.length === 11) phone = '88' + phone;
        finalUrl = `https://wa.me/${phone}?text=${encodedMsg}`;
      } else if (channel === 'telegram') {
        let tgBase = target;
        if (!tgBase.startsWith('http://') && !tgBase.startsWith('https://')) {
          tgBase = 'https://t.me/' + tgBase.replace(/^@/, '');
        }
        finalUrl = `${tgBase}${tgBase.includes('?') ? '&' : '?'}text=${encodedMsg}`;
      } else if (channel === 'messenger') {
        let fbBase = target;
        if (!fbBase.startsWith('http://') && !fbBase.startsWith('https://')) {
          fbBase = 'https://m.me/' + fbBase.replace(/^@/, '');
        }
        if (navigator.clipboard && rawMsg) {
          navigator.clipboard.writeText(rawMsg).catch(() => {});
        }
        finalUrl = `${fbBase}${fbBase.includes('?') ? '&' : '?'}text=${encodedMsg}`;
      } else if (channel === 'email') {
        const subj = encodeURIComponent('Support Request - <?= e($footerSiteName) ?>');
        window.location.href = `mailto:${target}?subject=${subj}&body=${encodedMsg}`;
        return;
      }

      if (finalUrl && finalUrl !== '#') {
        window.open(finalUrl, '_blank', 'noopener,noreferrer');
      }
    });
  });

  // Order Tracker
  const trackBtn    = document.getElementById('fswTrackBtn');
  const trackInput  = document.getElementById('fswTrackInput');
  const trackResult = document.getElementById('fswTrackResult');

  function runOrderTrack() {
    if (!trackInput || !trackResult) return;
    const q = trackInput.value.trim();
    if (!q) {
      trackInput.focus();
      return;
    }

    if (trackBtn) {
      trackBtn.disabled = true;
      trackBtn.textContent = '...';
    }

    fetch(`<?= APP_URL ?>/ajax/track_order.php?query=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(data => {
        if (trackBtn) {
          trackBtn.disabled = false;
          trackBtn.textContent = 'Track';
        }

        if (data.success && data.order) {
          const o = data.order;
          trackResult.innerHTML = `
            <div class="fsw-track-card">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-white" style="font-size:0.8rem;">#${o.order_number}</span>
                <span class="badge" style="background:${o.status_color}22;color:${o.status_color};border:1px solid ${o.status_color}55;font-size:0.66rem;">
                  ${o.status_label}
                </span>
              </div>
              <div style="font-size:0.74rem;color:#cbd5e1;">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Item:</span>
                  <strong class="text-white text-end">${o.game_name} — ${o.product_name}</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Player ID:</span>
                  <span class="text-info">${o.player_id}</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Amount:</span>
                  <span class="text-white fw-semibold">${o.total_price} (${o.payment_method})</span>
                </div>
              </div>
            </div>
          `;
        } else {
          trackResult.innerHTML = `
            <div class="rx-alert rx-alert-danger mt-2 mb-0" style="font-size:0.75rem;padding:8px 10px;">
              ${data.message || 'Order not found.'}
            </div>
          `;
        }
      })
      .catch(() => {
        if (trackBtn) {
          trackBtn.disabled = false;
          trackBtn.textContent = 'Track';
        }
      });
  }

  if (trackBtn && trackInput) {
    trackBtn.addEventListener('click', runOrderTrack);
    trackInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        runOrderTrack();
      }
    });
  }

  // Submit Ticket Form
  const ticketForm = document.getElementById('fswTicketForm');
  const ticketResp = document.getElementById('fswTicketResponse');
  const submitBtn  = document.getElementById('fswTicketSubmitBtn');

  if (ticketForm) {
    ticketForm.addEventListener('submit', function(e) {
      e.preventDefault();
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
      }
      if (ticketResp) ticketResp.style.display = 'none';

      fetch('<?= APP_URL ?>/ajax/submit_ticket.php', {
        method: 'POST',
        body: new FormData(ticketForm)
      })
      .then(r => r.json())
      .then(data => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit Ticket';
        }
        if (ticketResp) {
          ticketResp.className = data.success ? 'rx-alert rx-alert-success mt-2' : 'rx-alert rx-alert-danger mt-2';
          ticketResp.textContent = data.message;
          ticketResp.style.display = 'block';
        }
        if (data.success) ticketForm.reset();
      })
      .catch(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit Ticket';
        }
      });
    });
  }

  window.openTopUpersSupport = function(targetTab, orderId) {
    toggleSupport(true);
    if (targetTab) {
      const tabBtn = widget.querySelector(`.fsw-tab-btn[data-target="${targetTab}"]`);
      if (tabBtn) tabBtn.click();
    }
    if (orderId) {
      const trackInp  = document.getElementById('fswTrackInput');
      const ticketOrd = document.getElementById('fswTicketOrder');
      if (trackInp) trackInp.value = orderId;
      if (ticketOrd) ticketOrd.value = orderId;
    }
  };
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/selector_modal.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<!-- RechaX JS -->
<script src="<?= APP_URL ?>/assets/js/main.js" defer></script>
<!-- Game Selector JS -->
<script src="<?= APP_URL ?>/assets/js/selector.js?v=<?= filemtime(__DIR__ . '/../assets/js/selector.js') ?>" defer></script>
</body>
</html>
