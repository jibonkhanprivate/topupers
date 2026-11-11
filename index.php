<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = APP_NAME . ' - Instant Game Top-Ups';
$pageDescription = 'Buy Mobile Legends Diamonds, Free Fire Diamonds, PUBG UC and more. Instant delivery. Safe and Fast.';

$allGames      = getGames(false);
$featuredGames = array_values(array_filter($allGames, fn($g) => !empty($g['is_featured'])));
$heroBanners   = getBanners(true);
$homepageSections = getHomepageSections(true);

// Get some popular products
$stmt = db()->query('
  SELECT p.*, g.name AS game_name, g.slug AS game_slug
  FROM products p
  JOIN games g ON g.id = p.game_id
  WHERE p.is_popular = 1 AND p.is_active = 1
  LIMIT 6
');
$popularProducts = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';

// Resolve customer country & store badge text
$detectedCountryCode = function_exists('detectCustomerCountry') ? detectCustomerCountry() : 'BD';
$countryNameMap = [
    'BD' => 'Bangladesh',
    'IN' => 'India',
    'US' => 'USA',
    'MY' => 'Malaysia',
    'ID' => 'Indonesia',
    'SG' => 'Singapore',
    'PK' => 'Pakistan',
    'NP' => 'Nepal',
    'PH' => 'Philippines',
    'TH' => 'Thailand',
    'VN' => 'Vietnam',
    'BR' => 'Brazil',
    'TR' => 'Turkey',
    'AE' => 'UAE',
    'SA' => 'Saudi Arabia',
    'GB' => 'United Kingdom',
];
$storeCountryName = $countryNameMap[$detectedCountryCode] ?? 'Bangladesh';
$siteName = getSetting('site_name', defined('APP_NAME') ? APP_NAME : 'TopUpers');
$storeBadgeText = strtoupper($siteName) . ' OFFICIAL STORE • ' . strtoupper($storeCountryName);
?>

<!-- ============================================================
     KAZUKI-STYLE 3D COVERFLOW HERO BANNER
     ============================================================ -->
<section class="hero-slider-section" id="heroBannerSection">
  <!-- Ambient background glow orb -->
  <div class="hero-slider-glow"></div>

  <div class="hero-slider-wrapper">
    <div class="hero-slider-container" id="heroSliderContainer">
      <?php if (!empty($heroBanners)): ?>
        <?php foreach ($heroBanners as $idx => $b): ?>
          <div class="hero-slide hero-banner-slide" data-slide-index="<?= $idx ?>">
            <div class="hs-banner-media">
              <img src="<?= e(bannerUrl($b['image'])) ?>" 
                   srcset="<?= e(bannerSrcset($b['image'])) ?>"
                   sizes="(max-width: 576px) 94vw, (max-width: 992px) 90vw, 1340px"
                   alt="<?= e($b['title'] ?: 'Hero Banner ' . ($idx + 1)) ?>" 
                   class="hs-banner-img"
                   loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>"
                   decoding="async"
                   fetchpriority="<?= $idx === 0 ? 'high' : 'low' ?>">
              <div class="hs-banner-overlay <?= (!empty($b['title']) || !empty($b['subtitle'])) ? 'has-content' : '' ?>"></div>
            </div>

            <?php if (!empty($b['title']) || !empty($b['subtitle']) || !empty($b['link_url'])): ?>
              <div class="hs-content">
                <?php if (!empty($b['title'])): ?>
                  <div class="hs-badge"><i class="bi bi-fire"></i> SPECIAL EVENT</div>
                  <h2 class="hs-title"><?= nl2br(e($b['title'])) ?></h2>
                <?php endif; ?>
                <?php if (!empty($b['subtitle'])): ?>
                  <p class="hs-sub"><?= e($b['subtitle']) ?></p>
                <?php endif; ?>
                <?php if (!empty($b['link_url'])): ?>
                  <a href="<?= e($b['link_url']) ?>" class="hs-cta-btn">
                    <span>EXPLORE EVENT</span> <i class="bi bi-chevron-right"></i>
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($b['link_url']) && empty($b['title']) && empty($b['subtitle'])): ?>
              <a href="<?= e($b['link_url']) ?>" class="hs-banner-link" aria-label="<?= e($b['title'] ?: 'Banner promotion') ?>"></a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Default Fallback Slide -->
        <div class="hero-slide hero-banner-slide active" data-slide-index="0">
          <div class="hs-banner-media default-fallback-bg">
            <div class="hs-banner-overlay has-content"></div>
          </div>
          <div class="hs-content">
            <div class="hs-badge"><i class="bi bi-lightning-charge-fill"></i> INSTANT GAME TOP-UP</div>
            <h2 class="hs-title">LEVEL UP YOUR<br>GAMING EXPERIENCE</h2>
            <p class="hs-sub">Buy Mobile Legends Diamonds, Free Fire, PUBG UC & more with instant delivery.</p>
            <a href="<?= APP_URL ?>/shop" class="hs-cta-btn">
              <i class="bi bi-controller"></i> SHOP NOW
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div><!-- /.hero-slider-container -->

    <?php 
    $totalSlides = !empty($heroBanners) ? count($heroBanners) : 1;
    if ($totalSlides > 1): 
    ?>
      <!-- Prev / Next circular arrows aligned with active slide -->
      <button class="hs-arrow hs-arrow-prev" id="hsPrev" aria-label="Previous slide">
        <i class="bi bi-chevron-left"></i>
      </button>
      <button class="hs-arrow hs-arrow-next" id="hsNext" aria-label="Next slide">
        <i class="bi bi-chevron-right"></i>
      </button>

      <!-- Sleek capsule pagination dots -->
      <div class="hs-dots" id="hsDots">
        <?php for ($i = 0; $i < $totalSlides; $i++): ?>
          <span class="hs-dot <?= $i === 0 ? 'active' : '' ?>" data-idx="<?= $i ?>"></span>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div><!-- /.hero-slider-wrapper -->

  <!-- Official Store Diamond Pill Badge (Kazuki style) -->
  <div class="kzo-store-badge-wrap">
    <div class="kzo-store-badge">
      <i class="bi bi-gem kzo-badge-gem"></i>
      <span><?= e($storeBadgeText) ?></span>
    </div>
  </div>

  <h1 class="visually-hidden">TopUpers - Instant Game Top-Ups and Digital Vouchers in <?= e($storeCountryName) ?></h1>

  <script>
  (function(){
    const stage   = document.getElementById('heroSliderContainer');
    const slides  = Array.from(document.querySelectorAll('.hero-slider-container .hero-slide'));
    const dots    = Array.from(document.querySelectorAll('.hs-dot'));
    const btnPrev = document.getElementById('hsPrev');
    const btnNext = document.getElementById('hsNext');
    if (!slides.length) return;

    const total = slides.length;
    let current = 0;
    let timer = null;
    let isAnimating = false;

    function updateCarousel() {
      slides.forEach((slide, idx) => {
        slide.classList.remove('active', 'prev', 'next', 'hidden-left', 'hidden-right');

        if (total === 1) {
          slide.classList.add('active');
          return;
        }

        const prevIdx = (current - 1 + total) % total;
        const nextIdx = (current + 1) % total;

        if (idx === current) {
          slide.classList.add('active');
        } else if (idx === prevIdx) {
          slide.classList.add('prev');
        } else if (idx === nextIdx) {
          slide.classList.add('next');
        } else {
          const diff = (idx - current + total) % total;
          if (diff > total / 2) {
            slide.classList.add('hidden-left');
          } else {
            slide.classList.add('hidden-right');
          }
        }
      });

      dots.forEach((dot, idx) => {
        dot.classList.toggle('active', idx === current);
      });
    }

    function goTo(n) {
      if (isAnimating) return;
      isAnimating = true;
      current = (n + total) % total;
      updateCarousel();
      setTimeout(() => { isAnimating = false; }, 480);
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startAuto() {
      stopAuto();
      if (total > 1) {
        timer = setInterval(next, 5000);
      }
    }
    function stopAuto() {
      if (timer) clearInterval(timer);
    }
    function resetAuto() {
      stopAuto();
      startAuto();
    }

    if (btnNext) btnNext.addEventListener('click', e => { e.stopPropagation(); next(); resetAuto(); });
    if (btnPrev) btnPrev.addEventListener('click', e => { e.stopPropagation(); prev(); resetAuto(); });

    dots.forEach(d => {
      d.addEventListener('click', e => {
        e.stopPropagation();
        goTo(parseInt(d.dataset.idx, 10));
        resetAuto();
      });
    });

    // Clicking on adjacent peeking slide navigates directly to it
    slides.forEach((slide) => {
      slide.addEventListener('click', e => {
        if (slide.classList.contains('prev')) {
          e.preventDefault();
          prev();
          resetAuto();
        } else if (slide.classList.contains('next')) {
          e.preventDefault();
          next();
          resetAuto();
        }
      });
    });

    // Touch Swipe Support for Mobile & Tablet
    let touchStartX = 0;
    let touchEndX = 0;
    if (stage) {
      stage.addEventListener('mouseenter', stopAuto);
      stage.addEventListener('mouseleave', startAuto);

      stage.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].clientX;
      }, { passive: true });

      stage.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].clientX;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 40) {
          diff > 0 ? next() : prev();
          resetAuto();
        }
      }, { passive: true });
    }

    updateCarousel();
    startAuto();
  })();
  </script>
</section>

<!-- ============================================================
     HORIZONTAL CATEGORY NAVIGATION (Dynamically Generated from Active Sections)
     ============================================================ -->
<?php 
/**
 * Resolve modern theme, subtitle fallback, tag badge, and crisp SVG icon for each homepage section
 */
function resolveSectionModernMeta(array $sec): array {
    $key   = strtolower(($sec['section_key'] ?? '') . ' ' . ($sec['title'] ?? '') . ' ' . ($sec['auto_filter_type'] ?? ''));
    $icon  = trim($sec['icon'] ?? 'bi-grid-fill');
    $sub   = trim($sec['subtitle'] ?? '');

    if (str_contains($key, 'popular') || str_contains($key, 'hot') || str_contains($key, 'featured') || str_contains($key, 'trending') || str_contains($icon, 'fire')) {
        return [
            'theme'    => 'sec-theme-fire',
            'tag'      => 'TRENDING',
            'tag_icon' => 'bi-fire',
            'pill_icon'=> 'bi-fire',
            'subtitle' => $sub !== '' ? $sub : 'Most played & trending titles — instant 24/7 automated delivery',
            'svg'      => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C12 2 7 6.5 7 11.5C7 14.5 9.2 17 12 17C14.8 17 17 14.5 17 11.5C17 9.8 16.2 8.1 15.2 6.8C15 8.3 13.8 9.2 13.8 9.2C14.3 6.2 12 2 12 2Z" fill="currentColor" opacity="0.28"/><path d="M13.5 2.5C13.5 2.5 14.8 5.7 13.1 8.2C12.3 6.9 10.9 5.8 10.9 5.8C10.9 5.8 11.2 8.7 9.2 10.6C7.8 11.9 7 13.5 7 15.2C7 18.95 9.9 22 13.5 22C17.1 22 20 18.95 20 15.2C20 10.2 13.5 2.5 13.5 2.5ZM13.5 19.5C11.4 19.5 9.8 17.9 9.8 15.8C9.8 14.6 10.5 13.6 11.6 12.9C11.6 12.9 11.4 14.6 12.9 15.2C12.5 13.5 13.8 11.7 13.8 11.7C13.8 11.7 16.8 13.7 16.8 16.1C16.8 18 15.3 19.5 13.5 19.5Z" fill="currentColor"/></svg>'
        ];
    }

    if (str_contains($key, 'player') || str_contains($key, 'uid') || str_contains($key, 'user id') || str_contains($key, 'direct')) {
        return [
            'theme'    => 'sec-theme-cyan',
            'tag'      => 'INSTANT UID',
            'tag_icon' => 'bi-lightning-charge-fill',
            'pill_icon'=> 'bi-lightning-charge-fill',
            'subtitle' => $sub !== '' ? $sub : 'No password required — direct, lightning-fast top-up using your Game ID',
            'svg'      => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="5" width="20" height="14" rx="4" fill="currentColor" opacity="0.22"/><path d="M6 9H10M8 7V11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="15.5" cy="8.5" r="1.3" fill="currentColor"/><circle cx="17.5" cy="11" r="1.3" fill="currentColor"/><path d="M13 15.5L15.2 12.5H12.5L14.2 9.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 15H9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        ];
    }

    if (str_contains($key, 'login') || str_contains($key, 'account') || str_contains($key, 'in-game')) {
        return [
            'theme'    => 'sec-theme-violet',
            'tag'      => 'VERIFIED SAFE',
            'tag_icon' => 'bi-shield-check',
            'pill_icon'=> 'bi-shield-lock-fill',
            'subtitle' => $sub !== '' ? $sub : '100% secure official in-game packages fulfilled by verified agents',
            'svg'      => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L4 5.5V11.5C4 16.8 7.4 21.6 12 23C16.6 21.6 20 16.8 20 11.5V5.5L12 2Z" fill="currentColor" opacity="0.24"/><path d="M12 2L4 5.5V11.5C4 16.8 7.4 21.6 12 23C16.6 21.6 20 16.8 20 11.5V5.5L12 2ZM10.8 16.2L7.3 12.7L8.7 11.3L10.8 13.4L15.3 8.9L16.7 10.3L10.8 16.2Z" fill="currentColor"/></svg>'
        ];
    }

    if (str_contains($key, 'voucher') || str_contains($key, 'gift') || str_contains($key, 'card') || str_contains($key, 'pin') || str_contains($key, 'code')) {
        return [
            'theme'    => 'sec-theme-emerald',
            'tag'      => 'INSTANT CODE',
            'tag_icon' => 'bi-ticket-perforated-fill',
            'pill_icon'=> 'bi-gift-fill',
            'subtitle' => $sub !== '' ? $sub : 'Instant digital gift cards, vouchers & redeemable PIN codes',
            'svg'      => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="5" width="20" height="14" rx="3" fill="currentColor" opacity="0.24"/><path d="M20 5H4C2.9 5 2 5.9 2 7V9.5C3.1 9.5 4 10.4 4 11.5C4 12.6 3.1 13.5 2 13.5V17C2 18.1 2.9 19 4 19H20C21.1 19 22 18.1 22 17V13.5C20.9 13.5 20 12.6 20 11.5C20 10.4 20.9 9.5 22 9.5V7C22 5.9 21.1 5 20 5ZM10 15H8V13H10V15ZM10 11H8V9H10V11ZM16 14.5L13.5 13L11 14.5L11.7 11.7L9.5 9.8L12.4 9.5L13.5 6.8L14.6 9.5L17.5 9.8L15.3 11.7L16 14.5Z" fill="currentColor"/></svg>'
        ];
    }

    return [
        'theme'    => 'sec-theme-amber',
        'tag'      => 'OFFICIAL',
        'tag_icon' => 'bi-stars',
        'pill_icon'=> ($icon ?: 'bi-controller'),
        'subtitle' => $sub !== '' ? $sub : 'Select your favorite game package for instant top-up delivery',
        'svg'      => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 6H18C20.2 6 22 7.8 22 10V14C22 16.8 19.8 19 17 19C15.6 19 14.3 18.4 13.4 17.4L12 16H12L10.6 17.4C9.7 18.4 8.4 19 7 19C4.2 19 2 16.8 2 14V10C2 7.8 3.8 6 6 6Z" fill="currentColor" opacity="0.25"/><path d="M17.5 6H6.5C3.5 6 1 8.5 1 11.5V14.5C1 17.5 3.5 20 6.5 20C8.1 20 9.6 19.3 10.6 18.1L12 16.5L13.4 18.1C14.4 19.3 15.9 20 17.5 20C20.5 20 23 17.5 23 14.5V11.5C23 8.5 20.5 6 17.5 6ZM9 13H7V15H5V13H3V11H5V9H7V11H9V13ZM16.5 14C15.7 14 15 13.3 15 12.5C15 11.7 15.7 11 16.5 11C17.3 11 18 11.7 18 12.5C18 13.3 17.3 14 16.5 14ZM19.5 11C18.7 11 18 10.3 18 9.5C18 8.7 18.7 8 19.5 8C20.3 8 21 8.7 21 9.5C21 10.3 20.3 11 19.5 11Z" fill="currentColor"/></svg>'
    ];
}

$showCategoryNav = (getSetting('category_nav_enabled', '1') === '1');
if ($showCategoryNav && !empty($homepageSections)): 
  // Filter sections that have items and are marked visible in the navigation bar
  $navPillSections = array_filter($homepageSections, function($sec) {
    if (empty($sec['items'])) return false;
    if (isset($sec['show_in_nav']) && (string)$sec['show_in_nav'] === '0') return false;
    return true;
  });
  if (!empty($navPillSections)):
?>
<nav class="rx-cat-nav-sticky" id="rxCatNav" aria-label="Game categories navigation">
  <div class="container">
    <div class="rx-cat-nav-inner">
      <div class="rx-cat-scroll" id="rxCatScroll">
        <?php 
        $activePillCount = 0;
        foreach ($navPillSections as $sec): 
          $isFirst = ($activePillCount === 0);
          $activePillCount++;
          $secMeta = resolveSectionModernMeta($sec);
        ?>
          <a href="#section-<?= e($sec['section_key']) ?>" 
             class="rx-cat-pill <?= e($secMeta['theme']) ?> <?= $isFirst ? 'active' : '' ?>" 
             data-target="section-<?= e($sec['section_key']) ?>"
             id="catPill-<?= e($sec['section_key']) ?>">
            <span class="rx-cat-pill-icon-box">
              <i class="bi <?= e($secMeta['pill_icon']) ?> rx-cat-pill-icon"></i>
            </span>
            <span class="rx-cat-pill-text"><?= e(!empty($sec['nav_title']) ? $sec['nav_title'] : $sec['title']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</nav>
  <?php endif; ?>
<?php endif; ?>

<!-- ============================================================
     FEATURED SECTIONS & GAMES
     ============================================================ -->
<div class="rx-home-sections-container">
  <?php 
  if (!empty($homepageSections)):
    foreach ($homepageSections as $sec): 
      if (empty($sec['items'])) continue;
      $deskCols = (int)($sec['desktop_cols'] ?? 6);
      $mobCols  = (int)($sec['mobile_cols'] ?? 2);
      $viewMoreUrl = !empty($sec['view_more_url']) ? $sec['view_more_url'] : '/shop.php';
      if (!str_starts_with($viewMoreUrl, 'http://') && !str_starts_with($viewMoreUrl, 'https://') && !str_starts_with($viewMoreUrl, '//')) {
        $viewMoreUrl = APP_URL . (str_starts_with($viewMoreUrl, '/') ? $viewMoreUrl : '/' . $viewMoreUrl);
      }
      $secMeta = resolveSectionModernMeta($sec);
  ?>
  <section class="rx-home-section <?= e($secMeta['theme']) ?>" id="section-<?= e($sec['section_key']) ?>" data-section-key="<?= e($sec['section_key']) ?>">
    <div class="container">
      <!-- Modern Section Header -->
      <div class="rx-sec-header">
        <div class="rx-sec-title-group">
          <div class="rx-sec-icon-wrap" aria-hidden="true">
            <?= $secMeta['svg'] ?>
          </div>
          <div class="rx-sec-heading-text">
            <div class="rx-sec-title-row">
              <h2 class="rx-sec-title"><?= e($sec['title']) ?></h2>
              <span class="rx-sec-tag-pill">
                <i class="bi <?= e($secMeta['tag_icon']) ?>"></i>
                <span><?= e($secMeta['tag']) ?></span>
              </span>
            </div>
            <p class="rx-sec-sub">
              <i class="bi bi-check2-circle rx-sec-sub-icon"></i>
              <span><?= e($secMeta['subtitle']) ?></span>
            </p>
          </div>
        </div>

        <?php if (!empty($sec['show_view_more'])): ?>
          <a href="<?= e($viewMoreUrl) ?>" class="rx-sec-view-more">
            <span class="rx-sec-view-more-label"><?= e($sec['view_more_text'] ?: 'View All') ?></span>
            <span class="rx-sec-view-more-arrow" aria-hidden="true">
              <i class="bi bi-arrow-right"></i>
            </span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Section Items Grid -->
      <div class="rx-dynamic-grid" style="--desk-cols: <?= $deskCols ?>; --mob-cols: <?= $mobCols ?>;">
        <?php foreach ($sec['items'] as $item): 
          $badgeColor = $item['badge_color'] ?? 'hot';
          $badgeClass = 'badge-' . $badgeColor;
          $hasImg = !empty($item['image']);
        ?>
          <a href="<?= e($item['link']) ?>" class="rx-card-item rx-game-card <?= $item['is_stock_out'] ? 'stock-out' : '' ?>" data-game-slug="<?= e($item['slug'] ?? '') ?>" data-game-id="<?= e($item['id'] ?? '') ?>">
            <div class="rx-card-media">
              <?php if ($hasImg): ?>
                <img src="<?= APP_URL ?>/<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" class="rx-card-img" loading="lazy" decoding="async">
              <?php else: ?>
                <!-- Clean Generic RechaX Dark Gaming Fallback Card (No random emojis) -->
                <div class="rx-card-fallback">
                  <div class="rx-fallback-glow"></div>
                  <div class="rx-fallback-art">
                    <div class="rx-fallback-icon-wrap">
                      <i class="bi bi-controller"></i>
                    </div>
                    <span class="rx-fallback-brand"><?= e(getSetting('site_name', defined('APP_NAME') ? APP_NAME : 'TopUpers')) ?></span>
                  </div>
                </div>
              <?php endif; ?>

              <!-- Subtle bottom gradient overlay for readability & natural blending -->
              <div class="rx-card-gradient-overlay"></div>

              <!-- Featured Star Indicator (Top-Left) -->
              <?php if (!empty($item['is_featured'])): ?>
                <span class="rx-card-featured-star" title="Featured">
                  <i class="bi bi-star-fill"></i>
                </span>
              <?php endif; ?>

              <!-- Badge Pill (Top-Right) -->
              <?php if (!empty($item['badge'])): ?>
                <span class="rx-badge-pill <?= e($badgeClass) ?>"><?= e($item['badge']) ?></span>
              <?php endif; ?>

              <!-- Stock Out Overlay -->
              <?php if ($item['is_stock_out']): ?>
                <div class="rx-card-stockout-overlay">
                  <span class="rx-stockout-badge">Stock Out</span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Card Footer (Title) -->
            <div class="rx-card-footer">
              <h3 class="rx-card-name" title="<?= e($item['name']) ?>"><?= e($item['name']) ?></h3>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php 
    endforeach; 
  else: 
  ?>
    <div class="container py-5 text-center text-muted">
      <p>No active homepage sections to display.</p>
    </div>
  <?php endif; ?>
</div>

<script>
// Dynamic Horizontal Category Navigation & Smooth Scroll Spy
(function(){
  const pills = document.querySelectorAll('.rx-cat-pill');
  const scrollContainer = document.getElementById('rxCatScroll');
  const sections = document.querySelectorAll('.rx-home-section');
  if (!pills.length || !sections.length) return;

  // Smooth scroll on click
  pills.forEach(pill => {
    pill.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('href');
      const targetEl = document.querySelector(targetId);
      if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth' });
        setActivePill(this);
      }
    });
  });

  function setActivePill(activePill) {
    pills.forEach(p => p.classList.remove('active'));
    activePill.classList.add('active');

    // Auto-scroll the pill navigation bar horizontally so the active pill is in view
    if (scrollContainer) {
      const scrollLeft = activePill.offsetLeft - (scrollContainer.clientWidth / 2) + (activePill.clientWidth / 2);
      scrollContainer.scrollTo({ left: Math.max(0, scrollLeft), behavior: 'smooth' });
    }
  }

  // IntersectionObserver for scroll spy
  if ('IntersectionObserver' in window) {
    const observerOptions = {
      root: null,
      rootMargin: '-20% 0px -60% 0px',
      threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const secKey = entry.target.dataset.sectionKey;
          const matchingPill = document.getElementById('catPill-' + secKey);
          if (matchingPill) {
            setActivePill(matchingPill);
          }
        }
      });
    }, observerOptions);

    sections.forEach(sec => observer.observe(sec));
  }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
