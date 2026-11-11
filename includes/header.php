<?php
// includes/header.php
// This file is included at the top of every public-facing page.
// $pageTitle and $pageDescription should be set before including this file.
if (!isset($pageTitle)) $pageTitle = APP_NAME;
if (!isset($pageDescription)) $pageDescription = APP_TAGLINE;

startUserSession();
require_once __DIR__ . '/currency.php';
$activeCurrency = getActiveCurrency();
$currencySymbol = getCurrencySymbol($activeCurrency);
$cartCount = getCartCount();
$user = currentUser();
$games = getGames(false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($pageDescription) ?>">
  <title><?= strpos($pageTitle, APP_NAME) !== false ? e($pageTitle) : e($pageTitle) . ' | ' . e(APP_NAME) ?></title>

  <!-- Preconnect & Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Rajdhani:wght@400;500;600;700&display=swap">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- RechaX CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  <!-- Game Selector Modal CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/selector.css?v=<?= filemtime(__DIR__ . '/../assets/css/selector.css') ?>">
  <?php if (isset($extraCss)) echo $extraCss; ?>
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/favicon.svg">
  <!-- Theme & App Init Script -->
  <script>
    window.APP_URL = '<?= APP_URL ?>';
    if (localStorage.getItem('rx-theme') === 'light') {
      document.documentElement.classList.add('light-mode');
    }
  </script>
  <!-- Custom Preloader CSS (Inlined for speed) -->
  <style>
    .rx-preloader {
        position: fixed; inset: 0; width: 100%; height: 100%; background: #0a0b14;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        z-index: 99999; transition: opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.35s; overflow: hidden;
    }
    html.light-mode .rx-preloader { background: #f2f4f8; }
    .rx-preloader.fade-out { opacity: 0; visibility: hidden; pointer-events: none; }
    .loader-pulse { position: relative; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; }
    .loader-pulse img { width: 70px; height: 70px; z-index: 2; animation: loaderPulse 1.5s ease-in-out infinite; object-fit: contain; }
    .loader-pulse::after { content: ''; position: absolute; width: 100%; height: 100%; border-radius: 50%; background: var(--primary, #6c63ff); opacity: 0.15; animation: loaderRipple 1.5s ease-out infinite; }
    @keyframes loaderPulse { 0%, 100% { transform: scale(1); filter: drop-shadow(0 0 10px rgba(108,99,255,0.4)); } 50% { transform: scale(1.15); filter: drop-shadow(0 0 25px rgba(108,99,255,0.8)); } }
    @keyframes loaderRipple { 0% { transform: scale(0.8); opacity: 0.5; } 100% { transform: scale(2.5); opacity: 0; } }
    .loader-neon { width: 70px; height: 70px; border-radius: 50%; border: 3px solid transparent; border-top-color: var(--primary, #6c63ff); border-bottom-color: var(--secondary, #00d4ff); animation: loaderRotate 1s linear infinite; position: relative; }
    .loader-neon::before { content: ''; position: absolute; inset: 8px; border-radius: 50%; border: 3px solid transparent; border-left-color: var(--accent, #ff3e9d); border-right-color: var(--warning, #ffd740); animation: loaderRotate 1.5s linear infinite reverse; }
    @keyframes loaderRotate { to { transform: rotate(360deg); } }
    .loader-bar-wrap { width: 240px; height: 4px; background: rgba(255,255,255,0.05); border-radius: 2px; overflow: hidden; position: relative; margin-top: 20px; }
    .loader-bar-inner { position: absolute; height: 100%; background: linear-gradient(90deg, var(--primary, #6c63ff), var(--secondary, #00d4ff)); width: 100%; left: -100%; animation: loaderBarMove 2s cubic-bezier(0.4, 0, 0.2, 1) infinite; box-shadow: 0 0 15px #6c63ff; }
    @keyframes loaderBarMove { 0% { left: -100%; } 50% { left: 0; } 100% { left: 100%; } }
    .loader-glitch { font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 800; color: #fff; letter-spacing: 5px; position: relative; text-transform: uppercase; }
    html.light-mode .loader-glitch { color: #1e2022; }
    .loader-glitch::before, .loader-glitch::after { content: attr(data-text); position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.8; }
    .loader-glitch::before { color: #00d4ff; z-index: -1; animation: glitchEffect 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) both infinite; }
    .loader-glitch::after { color: #ff3e9d; z-index: -2; animation: glitchEffect 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) reverse both infinite; }
    @keyframes glitchEffect { 0% { transform: translate(0); } 20% { transform: translate(-3px, 3px); } 40% { transform: translate(-3px, -3px); } 60% { transform: translate(3px, 3px); } 80% { transform: translate(3px, -3px); } 100% { transform: translate(0); } }
  </style>
</head>
<body>

<!-- Preloader -->
<?php if (getSetting('site_loader_active', '1') === '1'): 
    $loaderStyle = getSetting('site_loader_style', 'pulse');
    $loaderImg   = getSetting('site_loader_img');
?>
<div class="rx-preloader" id="rxPreloader">
<?php if ($loaderStyle === 'pulse'): ?>
        <div class="loader-pulse">
            <?php $siteLogo = getSetting('site_logo', 'assets/img/favicon.svg'); ?>
            <img src="<?= $loaderImg ? APP_URL . '/' . e($loaderImg) : APP_URL . '/' . e($siteLogo) ?>" alt="Loading...">
        </div>
    <?php elseif ($loaderStyle === 'neon'): ?>
        <div class="loader-neon"></div>
    <?php elseif ($loaderStyle === 'glitch'): ?>
        <div class="loader-glitch" data-text="LOADING">LOADING</div>
    <?php elseif ($loaderStyle === 'bar'): ?>
        <div class="loader-bar-wrap">
            <div class="loader-bar-inner"></div>
        </div>
    <?php endif; ?>
</div>
<script>
    (function() {
        const preloader = document.getElementById('rxPreloader');
        if (!preloader) return;
        function dismiss() {
            if (!preloader.classList.contains('fade-out')) {
                preloader.classList.add('fade-out');
                setTimeout(() => { preloader.remove(); }, 350);
            }
        }
        if (document.readyState === 'interactive' || document.readyState === 'complete') {
            dismiss();
        } else {
            document.addEventListener('DOMContentLoaded', dismiss);
            window.addEventListener('load', dismiss);
            setTimeout(dismiss, 1200);
        }
    })();
</script>
<?php endif; ?>

<!-- ============================================================
     NAVBAR (Responsive Mobile & Desktop Single-Row Header)
     ============================================================ -->
<nav class="rx-navbar navbar">
  <div class="container d-flex align-items-center justify-content-between flex-nowrap">

    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center logo-brand-wrap me-2" href="<?= APP_URL ?>">
      <?php 
        $siteLogo = getSetting('site_logo', 'assets/img/favicon.svg');
        $siteName = getSetting('site_name', defined('APP_NAME') ? APP_NAME : 'TopUpers');
        $hasCustomLogo = !empty($siteLogo) && file_exists(APP_ROOT . '/' . $siteLogo) && strpos($siteLogo, 'favicon') === false;
      ?>
      <?php if (!empty($siteLogo) && file_exists(APP_ROOT . '/' . $siteLogo)): ?>
        <img src="<?= APP_URL ?>/<?= e($siteLogo) ?>?v=<?= filemtime(APP_ROOT . '/' . $siteLogo) ?>" 
             alt="<?= e($siteName) ?>" 
             class="navbar-logo-img">
      <?php endif; ?>
      <?php if (!$hasCustomLogo): ?>
        <span class="logo-text ms-2"><?= e($siteName) ?></span>
      <?php endif; ?>
    </a>

    <!-- Right Action Icons (Visible & Responsive on Mobile, Tablet & Desktop) -->
    <div class="rx-nav-actions d-flex align-items-center gap-2 gap-sm-3 ms-auto">
      <style>
        .nav-icon-modern {
          width: 40px; height: 40px;
          border-radius: 50%;
          display: inline-flex; align-items: center; justify-content: center;
          background: #152036;
          border: 1.5px solid #2A3C5E;
          color: var(--text-primary);
          transition: all 0.22s ease;
          text-decoration: none;
          flex-shrink: 0;
        }
        .nav-icon-modern:hover {
          background: #1E2D4A;
          border-color: #38BDF8;
          color: #fff;
          transform: translateY(-1px);
        }
        /* Profile Avatar Trigger Button */
        .tp-profile-trigger-btn {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          padding: 0;
          border: 2px solid rgba(255, 255, 255, 0.14);
          background: linear-gradient(135deg, #10B981, #059669);
          color: #ffffff;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          font-family: 'Rajdhani', -apple-system, sans-serif;
          font-weight: 700;
          font-size: 1.15rem;
          line-height: 1;
          cursor: pointer;
          transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
          outline: none;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
          position: relative;
          user-select: none;
          flex-shrink: 0;
          -webkit-tap-highlight-color: transparent;
        }
        @media (max-width: 575.98px) {
          .nav-icon-modern,
          .tp-profile-trigger-btn {
            width: 36px;
            height: 36px;
          }
        }
        .tp-profile-trigger-btn:hover,
        .tp-profile-trigger-btn:focus,
        .tp-profile-trigger-btn[aria-expanded="true"] {
          border-color: #34D399;
          box-shadow: 0 0 16px rgba(16, 185, 129, 0.45);
          transform: scale(1.05);
        }
        .tp-profile-trigger-btn::after {
          display: none !important;
        }
        .tp-profile-trigger-img {
          width: 100%;
          height: 100%;
          border-radius: 50%;
          object-fit: cover;
        }
        .tp-profile-trigger-initial {
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        /* Floating Sleek Dark Popup */
        .tp-profile-popup {
          position: absolute !important;
          right: 0 !important;
          left: auto !important;
          background: #131B2E !important;
          border: 1px solid #26334D !important;
          border-radius: 16px !important;
          box-shadow: 0 18px 45px rgba(0, 0, 0, 0.75) !important;
          padding: 8px 6px !important;
          min-width: 195px !important;
          margin-top: 10px !important;
          animation: tpPopupFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
          overflow: hidden;
          backdrop-filter: none !important;
          -webkit-backdrop-filter: none !important;
          list-style: none;
          z-index: 1055;
        }
        @keyframes tpPopupFadeIn {
          from {
            opacity: 0;
            transform: translateY(-6px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        .tp-popup-item {
          display: flex !important;
          align-items: center !important;
          gap: 12px !important;
          padding: 10px 14px !important;
          border-radius: 10px !important;
          color: #E2E8F0 !important;
          font-family: 'Outfit', -apple-system, sans-serif !important;
          font-size: 0.92rem !important;
          font-weight: 500 !important;
          text-decoration: none !important;
          transition: all 0.18s ease !important;
          background: transparent !important;
          white-space: nowrap !important;
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
        }
        .tp-popup-item i {
          font-size: 1.15rem;
          color: #94A3B8;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 20px;
          flex-shrink: 0;
          transition: all 0.18s ease;
        }
        .tp-popup-item:hover,
        .tp-popup-item:focus,
        .tp-popup-item:active {
          background: rgba(255, 255, 255, 0.06) !important;
          color: #FFFFFF !important;
        }
        .tp-popup-item:hover i,
        .tp-popup-item:focus i {
          color: #FFFFFF;
        }
        .tp-popup-divider {
          margin: 6px 8px !important;
          border: none !important;
          border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
          opacity: 1 !important;
        }
        .tp-popup-logout {
          color: #E2E8F0 !important;
        }
        .tp-popup-logout i {
          color: #94A3B8;
        }
        .tp-popup-logout:hover,
        .tp-popup-logout:focus {
          background: rgba(239, 68, 68, 0.12) !important;
          color: #F87171 !important;
        }
        .tp-popup-logout:hover i,
        .tp-popup-logout:focus i {
          color: #F87171 !important;
        }

        /* Light Mode Profile Popup */
        html.light-mode .tp-profile-popup {
          background: #ffffff !important;
          border: 1px solid rgba(0, 0, 0, 0.08) !important;
          box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12), 0 0 1px rgba(0, 0, 0, 0.05) !important;
        }
        html.light-mode .tp-popup-item {
          color: #1e293b !important;
        }
        html.light-mode .tp-popup-item i {
          color: #64748b !important;
        }
        html.light-mode .tp-popup-item:hover,
        html.light-mode .tp-popup-item:focus {
          background: rgba(0, 0, 0, 0.04) !important;
          color: #0f172a !important;
        }
        html.light-mode .tp-popup-item:hover i,
        html.light-mode .tp-popup-item:focus i {
          color: var(--primary) !important;
        }
        html.light-mode .tp-popup-divider {
          border-top: 1px solid rgba(0, 0, 0, 0.06) !important;
        }
        html.light-mode .tp-popup-logout {
          color: #ef4444 !important;
        }
        html.light-mode .tp-popup-logout i {
          color: #ef4444 !important;
        }
        html.light-mode .tp-popup-logout:hover {
          background: rgba(239, 68, 68, 0.08) !important;
          color: #dc2626 !important;
        }
      </style>

      <!-- Live Search Trigger -->
      <button type="button" class="nav-icon-modern rx-search-open-btn" id="rxSearchTriggerBtn" aria-label="Search Games and Products" title="Search Games & Products" style="border:none; cursor:pointer;">
        <i class="bi bi-search" style="font-size: 1.02rem;"></i>
      </button>

      <!-- Theme Toggle -->
      <button type="button" class="nav-icon-modern" id="themeToggleBtn" aria-label="Toggle Theme" style="border:none; cursor:pointer;">
        <i class="bi bi-moon-fill" id="themeToggleIcon" style="font-size: 1.05rem;"></i>
      </button>

      <?php if ($user): 
        $headerAvatarUrl = userAvatarUrl($user['avatar'] ?? null);
        $headerInitial   = strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1));
      ?>
      <!-- User Profile Popup Dropdown -->
      <div class="dropdown tp-profile-dropdown">
        <button class="tp-profile-trigger-btn dropdown-toggle" type="button" id="tpProfileMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User Account Menu">
          <?php if ($headerAvatarUrl): ?>
            <img src="<?= e($headerAvatarUrl) ?>" alt="Avatar" class="tp-profile-trigger-img">
          <?php else: ?>
            <span class="tp-profile-trigger-initial"><?= e($headerInitial) ?></span>
          <?php endif; ?>
        </button>
        
        <ul class="dropdown-menu dropdown-menu-end tp-profile-popup" aria-labelledby="tpProfileMenuBtn">
          <li>
            <a class="tp-popup-item" href="<?= APP_URL ?>/user/orders.php">
              <i class="bi bi-box-seam"></i>
              <span>My Orders</span>
            </a>
          </li>
          <li>
            <a class="tp-popup-item" href="<?= APP_URL ?>/user/add-money.php">
              <i class="bi bi-wallet2"></i>
              <span>Wallet</span>
            </a>
          </li>
          <li>
            <a class="tp-popup-item" href="<?= APP_URL ?>/user/dashboard.php">
              <i class="bi bi-person"></i>
              <span>Profile</span>
            </a>
          </li>
          <li><hr class="tp-popup-divider"></li>
          <li>
            <a class="tp-popup-item tp-popup-logout" href="<?= APP_URL ?>/auth/logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Logout</span>
            </a>
          </li>
        </ul>
      </div>
      <?php else: ?>
      <a href="<?= APP_URL ?>/auth/login.php" class="nav-icon-modern" title="Sign In">
        <i class="bi bi-person-fill" style="font-size: 1.25rem;"></i>
      </a>
      <?php endif; ?>
    </div>

  </div>
</nav>
<!-- End Navbar -->

<!-- Live Search Overlay & Results Box -->
<?php require_once __DIR__ . '/search_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleIcon = document.getElementById('themeToggleIcon');
  
  if (!themeToggleBtn) return;
  
  const currentTheme = localStorage.getItem('rx-theme') || 'dark';
  if (currentTheme === 'light') {
    themeToggleIcon.className = 'bi bi-sun-fill';
  } else {
    themeToggleIcon.className = 'bi bi-moon-fill';
  }
  themeToggleBtn.addEventListener('click', () => {
    const isLight = document.documentElement.classList.toggle('light-mode');
    localStorage.setItem('rx-theme', isLight ? 'light' : 'dark');
    themeToggleIcon.className = isLight ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
  });
});
</script>
