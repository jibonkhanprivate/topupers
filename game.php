<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/currency.php';

$slug = sanitize($_GET['slug'] ?? '');
$game = $slug ? getGame($slug) : null;

if (!$game) {
    header('Location: ' . APP_URL . '/shop.php');
    exit;
}

// Check selector options
$selectorConfig = getGameSelectorConfig((int)$game['id']);
$selectorOptions = $selectorConfig ? getSelectorOptions((int)$selectorConfig['id'], true) : [];
$selectedOption = null;

if (!empty($selectorOptions)) {
    $reqOption = sanitize($_GET['option'] ?? $_GET['region'] ?? '');
    if ($reqOption) {
        $selectedOption = getSelectorOptionBySlug((int)$game['id'], $reqOption);
    }
    // Fallback to first active option if none specified
    if (!$selectedOption && !empty($selectorOptions)) {
        $selectedOption = $selectorOptions[0];
    }
}

if ($selectedOption) {
    $products = getOptionProducts((int)$selectedOption['id']);
} else {
    $products = getProducts($game['id']);
}

$pageTitle = 'Buy ' . $game['name'] . ($selectedOption ? ' - ' . $selectedOption['name'] : ' Diamonds');
$pageDescription = 'Top up ' . $game['name'] . ' instantly. Best price guaranteed. Secure and fast delivery.';

// Handle AJAX Promo Code Validator
if (isset($_GET['api']) && $_GET['api'] === 'coupon') {
    header('Content-Type: application/json');
    $code  = sanitize(strtoupper($_GET['code'] ?? ''));
    $total = (float)($_GET['total'] ?? 0);
    
    $stmt = db()->prepare('SELECT * FROM coupons WHERE code=? AND is_active=1');
    $stmt->execute([$code]);
    $c = $stmt->fetch();
    
    if (!$c) { echo json_encode(['success'=>false, 'msg'=>'Invalid promo code.']); exit; }
    if ($c['usage_limit'] > 0 && $c['used_count'] >= $c['usage_limit']) { echo json_encode(['success'=>false, 'msg'=>'Promo code limit reached.']); exit; }
    if ($c['expires_at'] && strtotime($c['expires_at']) < time()) { echo json_encode(['success'=>false, 'msg'=>'Promo code expired.']); exit; }
    if ($c['min_purchase'] > 0 && $c['min_purchase'] > $total) { echo json_encode(['success'=>false, 'msg'=>'Minimum spend: ' . (function_exists('formatPriceByCurrency') ? formatPriceByCurrency((float)$c['min_purchase']) : ('৳ ' . $c['min_purchase']))]); exit; }
    
    $discount = ($c['type'] === 'percent') ? ($total * ($c['value'] / 100)) : $c['value'];
    if ($c['type'] === 'percent' && $c['max_discount'] > 0 && $discount > $c['max_discount']) $discount = $c['max_discount'];
    if ($discount > $total) $discount = $total;
    
    echo json_encode(['success'=>true, 'discount'=>$discount, 'new_total'=>($total - $discount)]);
    exit;
}

$error = '';
$success = '';

// Process Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $csrfToken     = $_POST['csrf_token'] ?? '';
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');
    $paymentNumber = sanitize($_POST['payment_number'] ?? '');
    $paymentTrx    = sanitize($_POST['payment_trx'] ?? '');
    $pId           = sanitize($_POST['player_id'] ?? '');
    $sId           = sanitize($_POST['server_id'] ?? '');
    $playerIgn     = sanitize($_POST['player_ign'] ?? '');
    $pName         = sanitize($_POST['customer_name'] ?? '');
    $pContact      = sanitize($_POST['customer_contact'] ?? '');
    $customerEmail = sanitize($_POST['customer_email'] ?? '');
    $pProdId       = (int)($_POST['product_id'] ?? 0);
    $appliedCoupon = sanitize(strtoupper($_POST['applied_coupon'] ?? ''));

    // If player_ign was not sent from frontend, auto-resolve it
    if (empty($playerIgn) && !empty($pId)) {
        $valCheck = validatePlayerId((int)$game['id'], $pId, $sId);
        if (!empty($valCheck['success']) && !empty($valCheck['ign'])) {
            $playerIgn = $valCheck['ign'];
        }
    }

    if (rxWriteFreezeActive()) {
        // Gate B2 write-freeze: refuse before any validation, transaction, coupon increment or order INSERT.
        rxWriteFreezeSendHeaders();
        $error = rxWriteFreezeCustomerMessage();
    } elseif (!verifyCsrf($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } elseif (!isLoggedIn()) {
        $error = 'Please log in or create an account to proceed with your order.';
    } elseif (empty($pProdId)) {
        $error = 'Please select a package first.';
    } elseif (empty($pId)) {
        $error = 'Player ID is required.';
    } else {
        $pdo = db();
        $detectedCountry = detectCustomerCountry();
        $orderCurrency   = ($detectedCountry === 'BD') ? 'BDT' : 'USD';

        $validation = validateAndPrepareOrder(
            $pdo,
            $pProdId,
            (int)$game['id'],
            $appliedCoupon,
            $orderCurrency,
            $paymentMethod,
            $paymentTrx,
            $paymentNumber
        );

        if (!$validation['valid']) {
            $error = $validation['error'];
        } else {
            $verifiedProduct = $validation['verified_product'];
            $snapshot        = $validation['snapshot'];
            $usedCoupon      = $validation['used_coupon_code'];
            $discountAmt     = $validation['discount_amount'];
            $hasSnapshotCols = hasOrderCurrencySnapshotColumns($pdo);

            $orderNum = generateOrderNumber();
            $userId   = isLoggedIn() ? $_SESSION['user_id'] : null;
            $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $pOptId   = isset($_POST['selector_option_id']) && is_numeric($_POST['selector_option_id']) && $_POST['selector_option_id'] > 0 
                        ? (int)$_POST['selector_option_id'] 
                        : ($selectedOption ? (int)$selectedOption['id'] : null);
            $pRegId   = isset($_POST['region_id']) && is_numeric($_POST['region_id']) && $_POST['region_id'] > 0 
                        ? (int)$_POST['region_id'] 
                        : ($selectedOption && !empty($selectedOption['region_id']) ? (int)$selectedOption['region_id'] : null);

            $pdo->beginTransaction();
            try {
                if ($validation['coupon_id_to_increment']) {
                    $cpStmt = $pdo->prepare('
                        UPDATE coupons
                        SET used_count = used_count + 1
                        WHERE id = ?
                          AND (usage_limit IS NULL OR usage_limit = 0 OR used_count < usage_limit)
                    ');
                    $cpStmt->execute([$validation['coupon_id_to_increment']]);
                    if ($cpStmt->rowCount() !== 1) {
                        throw new RuntimeException('Promo code usage limit has been reached. Please place your order without this promo code.');
                    }
                }

                if ($hasSnapshotCols) {
                    $stmt = $pdo->prepare('
                        INSERT INTO orders
                        (order_number, user_id, product_id, game_id, region_id, selector_option_id, player_id, server_id, player_ign, customer_name, customer_email, customer_phone,
                         unit_price, total_price, coupon_code, discount_amount, payment_method, payment_number, payment_trx, ip_address,
                         currency, base_amount_bdt, exchange_rate, rate_buffer_percent, rate_mode, rate_source, charge_amount)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ');
                    $stmt->execute([
                        $orderNum, $userId, $pProdId, $game['id'], $pRegId, $pOptId, $pId, $sId, $playerIgn,
                        $pName ?: ($userId ? $_SESSION['user_name'] : 'Guest'),
                        $customerEmail ?: ($userId ? $_SESSION['user_email'] ?? '' : ''),
                        $pContact ?: ($userId ? $_SESSION['user_phone'] ?? '' : ''),
                        $verifiedProduct['amount'], $snapshot['total_price'], $usedCoupon, $discountAmt,
                        $paymentMethod, $paymentNumber, $paymentTrx, $ip,
                        $snapshot['currency'], $snapshot['base_amount_bdt'], $snapshot['exchange_rate'],
                        $snapshot['rate_buffer_percent'], $snapshot['rate_mode'], $snapshot['rate_source'],
                        $snapshot['charge_amount']
                    ]);
                } else {
                    // Baseline schema compatibility before Gate 2B migration
                    $stmt = $pdo->prepare('
                        INSERT INTO orders
                        (order_number, user_id, product_id, game_id, region_id, selector_option_id, player_id, server_id, player_ign, customer_name, customer_email, customer_phone,
                         unit_price, total_price, coupon_code, discount_amount, payment_method, payment_number, payment_trx, ip_address)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ');
                    $stmt->execute([
                        $orderNum, $userId, $pProdId, $game['id'], $pRegId, $pOptId, $pId, $sId, $playerIgn,
                        $pName ?: ($userId ? $_SESSION['user_name'] : 'Guest'),
                        $customerEmail ?: ($userId ? $_SESSION['user_email'] ?? '' : ''),
                        $pContact ?: ($userId ? $_SESSION['user_phone'] ?? '' : ''),
                        $verifiedProduct['amount'], $snapshot['total_price'], $usedCoupon, $discountAmt,
                        $paymentMethod, $paymentNumber, $paymentTrx, $ip
                    ]);
                }

                $pdo->commit();

                header("Location: " . APP_URL . "/order-success.php?order=" . $orderNum);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if ($e instanceof PDOException) {
                    $sqlState   = (string)$e->getCode();
                    $driverCode = (int)($e->errorInfo[1] ?? 0);
                    $errorMsg   = $e->getMessage();

                    if (($sqlState === '23000' || $driverCode === 1062) && stripos($errorMsg, 'uq_orders_normalized_payment_trx') !== false) {
                        $error = 'This transaction ID has already been submitted for another order. Please check your TrxID or contact support.';
                    } else {
                        $error = 'Failed to process order. Please try again.';
                        error_log('Database error during game order creation: ' . $errorMsg);
                    }
                } elseif ($e instanceof RuntimeException) {
                    $error = $e->getMessage();
                } else {
                    $error = 'Failed to process order. Please try again.';
                    error_log('Unexpected error during game order creation: ' . $e->getMessage());
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';

$detectedCountry = detectCustomerCountry();
$activeCurrency  = getActiveCurrency();
$userLoggedIn    = isLoggedIn();
$currentUrl      = $_SERVER['REQUEST_URI'] ?? (APP_URL . '/game.php?slug=' . urlencode($game['slug']));
$currencySymbol  = ($activeCurrency === 'USD') ? '$' : '৳';
$rateDetails     = function_exists('resolveExchangeRate') ? resolveExchangeRate() : [];
$binanceCfg      = function_exists('getExchangeRateConfig') ? getExchangeRateConfig() : [];

$isUsdActive     = ($activeCurrency === 'USD');
$isUsdAvailable  = $isUsdActive && !empty($rateDetails['is_valid']);
$isBinanceAvailable = !empty($rateDetails['is_valid']) 
    && !empty($binanceCfg['binance_active'])
    && (!empty($binanceCfg['binance_pay_id']) || !empty($binanceCfg['binance_qr_image']));

$gameColors = [
  'mobile-legends' => ['bg'=>'linear-gradient(135deg,#1a237e,#283593)','accent'=>'#5c6bc0'],
  'free-fire'      => ['bg'=>'linear-gradient(135deg,#b71c1c,#c62828)','accent'=>'#ef5350'],
  'pubg-mobile'    => ['bg'=>'linear-gradient(135deg,#e65100,#ef6c00)','accent'=>'#ffa726'],
  'valorant'       => ['bg'=>'linear-gradient(135deg,#880e4f,#ad1457)','accent'=>'#f06292'],
  'genshin-impact' => ['bg'=>'linear-gradient(135deg,#004d40,#00695c)','accent'=>'#4db6ac'],
  'codm'           => ['bg'=>'linear-gradient(135deg,#1b5e20,#2e7d32)','accent'=>'#66bb6a'],
  'honor-of-kings' => ['bg'=>'linear-gradient(135deg,#4a148c,#6a1b9a)','accent'=>'#ce93d8'],
  'clash-of-clans' => ['bg'=>'linear-gradient(135deg,#33691e,#558b2f)','accent'=>'#aed581'],
  'clash-royale'   => ['bg'=>'linear-gradient(135deg,#1565c0,#1976d2)','accent'=>'#90caf9'],
];
$gameIcons = [
  'mobile-legends'=>'⚔️','free-fire'=>'🔥','pubg-mobile'=>'🪖',
  'valorant'=>'🎯','genshin-impact'=>'✨','codm'=>'🔫',
  'honor-of-kings'=>'👑','clash-of-clans'=>'🏰','clash-royale'=>'🃏',
];
$gc   = $gameColors[$game['slug']] ?? ['bg'=>'linear-gradient(135deg,#111226,#1a1b2e)','accent'=>'#6c63ff'];
$icon = $gameIcons[$game['slug']] ?? '🎮';
?>

<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/package-list.css?v=<?= filemtime(__DIR__ . '/assets/css/package-list.css') ?>">

<style>
.step-header-modern {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.step-number-pill {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    color: #fff;
    flex-shrink: 0;
}
.step-title-modern {
    margin: 0;
    font-weight: 700;
    font-size: 1.4rem;
    color: var(--text-primary);
}

</style>

<!-- MAIN CONTENT -->
<div class="container px-2 px-sm-3 py-2 py-md-4">
  <?php if ($isUsdActive && !$isUsdAvailable): ?>
    <div class="rx-alert mb-3" style="border-radius:12px; padding:12px 16px; background:#3B121E; border:1px solid #EF4444; color:#FEE2E2; display:flex; align-items:center; gap:10px; font-size:0.9rem;">
      <i class="bi bi-exclamation-triangle-fill" style="color:#F87171; font-size:1.1rem; flex-shrink:0;"></i>
      <div>
        <strong>USD pricing is temporarily unavailable.</strong> Displaying standard BDT prices.
      </div>
    </div>
  <?php endif; ?>
  <div class="row g-3 g-lg-4">
    
    <!-- Left Column: Game Media & Order Form (col-lg-8) -->
    <div class="col-lg-8 px-1 px-sm-2 px-lg-3">
      
      <!-- Game Banner Carousel / Cover Media -->
      <div class="game-banner-card mb-3 mb-md-4">
        <?php 
          $banners = [];
          if (!empty($game['cover_image'])) $banners[] = $game['cover_image'];
          if (!empty($game['banner_1']))    $banners[] = $game['banner_1'];
          if (!empty($game['banner_2']))    $banners[] = $game['banner_2'];
          if (!empty($game['banner_3']))    $banners[] = $game['banner_3'];
        ?>
        <?php if (count($banners) > 1): ?>
          <div id="gameBannerCarousel" class="carousel slide carousel-fade rx-game-banner-carousel" data-bs-ride="carousel" data-bs-interval="4000" data-bs-pause="hover">
            <!-- Modern Indicator Pills -->
            <div class="carousel-indicators rx-banner-indicators">
              <?php foreach ($banners as $idx => $b): ?>
                <button type="button" data-bs-target="#gameBannerCarousel" data-bs-slide-to="<?= $idx ?>" <?= $idx === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Slide <?= $idx + 1 ?>"></button>
              <?php endforeach; ?>
            </div>

            <!-- Slides with Gradient Overlay -->
            <div class="carousel-inner">
              <?php foreach ($banners as $idx => $b): ?>
                <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                  <img src="<?= APP_URL ?>/<?= e($b) ?>" class="d-block w-100 game-banner-img" alt="<?= e($game['name']) ?> Banner <?= $idx + 1 ?>">
                  <div class="game-banner-gradient-overlay"></div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Sleek Glassmorphism Navigation Controls -->
            <button class="carousel-control-prev rx-carousel-nav rx-nav-prev" type="button" data-bs-target="#gameBannerCarousel" data-bs-slide="prev" aria-label="Previous Slide">
              <span class="rx-nav-icon"><i class="bi bi-chevron-left"></i></span>
            </button>
            <button class="carousel-control-next rx-carousel-nav rx-nav-next" type="button" data-bs-target="#gameBannerCarousel" data-bs-slide="next" aria-label="Next Slide">
              <span class="rx-nav-icon"><i class="bi bi-chevron-right"></i></span>
            </button>

            <!-- Counter Badge -->
            <div class="rx-carousel-counter-badge">
              <i class="bi bi-images me-1"></i><span id="rxSlideCurrent">1</span> / <span><?= count($banners) ?></span>
            </div>
          </div>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const carouselEl = document.getElementById('gameBannerCarousel');
              if (carouselEl) {
                carouselEl.addEventListener('slide.bs.carousel', function (e) {
                  const cur = document.getElementById('rxSlideCurrent');
                  if (cur) cur.textContent = (e.to + 1);
                });
              }
            });
          </script>
        <?php elseif (count($banners) === 1): ?>
          <div class="position-relative overflow-hidden" style="border-radius:20px;">
            <img src="<?= APP_URL ?>/<?= e($banners[0]) ?>" class="d-block w-100 game-banner-img" alt="<?= e($game['name']) ?> Banner">
            <div class="game-banner-gradient-overlay"></div>
          </div>
        <?php else: ?>
          <div style="width:100%; aspect-ratio:16/7; background:<?= $gc['bg'] ?>; display:flex; align-items:center; justify-content:center; font-size:4rem; border-radius:20px; overflow:hidden;">
            <?= $icon ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Game Identity / Region Header Card -->
      <?php 
        $gameIcon = !empty($game['image']) 
            ? (APP_URL . '/' . ltrim($game['image'], '/')) 
            : (!empty($game['cover_image']) ? (APP_URL . '/' . ltrim($game['cover_image'], '/')) : (APP_URL . '/assets/img/game-placeholder.svg'));
      ?>
      <div class="rx-region-header-card mb-3 mb-md-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rx-region-thumb-wrapper">
              <img src="<?= htmlspecialchars($gameIcon) ?>" 
                   alt="<?= e($game['name']) ?>" 
                   class="rx-region-game-thumb"
                   loading="lazy">
              <?php 
                $thumbBadge = !empty($selectedOption['badge_text']) ? $selectedOption['badge_text'] : ($game['badge_text'] ?? '');
                $thumbColor = !empty($selectedOption['badge_color']) ? $selectedOption['badge_color'] : ($game['badge_color'] ?? 'hot');
              ?>
              <?php if (!empty($thumbBadge)): ?>
                <div class="rx-thumb-ribbon ribbon-<?= e($thumbColor) ?>">
                  <?= e($thumbBadge) ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="rx-region-info-col">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="rx-region-title">
                  <?= e($selectedOption['name'] ?? $game['name']) ?>
                </h4>
              </div>
              <div class="rx-game-badges-row">
                <?php
                  $customBadges = !empty($game['custom_badges']) ? json_decode($game['custom_badges'], true) : [];
                  if (empty($customBadges) || !is_array($customBadges)) {
                      $delTime = !empty($selectedOption['delivery_time']) 
                          ? $selectedOption['delivery_time'] 
                          : (($game['topup_type'] ?? '') === 'login' ? '10-180 Minute' : 'Instant Delivery');
                      
                      $regText = !empty($selectedOption) 
                          ? getSelectorOptionRegionText($selectedOption) 
                          : 'Global Region';

                      $customBadges = [
                          [
                              'text'  => 'Safety Guarantee',
                              'icon'  => 'bi bi-patch-check-fill',
                              'file'  => '',
                              'color' => '#38bdf8'
                          ],
                          [
                              'text'  => $delTime,
                              'icon'  => 'bi bi-lightning-charge-fill',
                              'file'  => '',
                              'color' => '#eab308'
                          ],
                          [
                              'text'  => $regText,
                              'icon'  => 'bi bi-globe',
                              'file'  => '',
                              'color' => '#ef4444',
                              'is_region' => true
                          ]
                      ];
                  } else {
                      foreach ($customBadges as &$cb) {
                          if (!empty($selectedOption['delivery_time']) && (stripos($cb['text'], 'delivery') !== false || stripos($cb['text'], 'minute') !== false || stripos($cb['text'], 'instant') !== false)) {
                              $cb['text'] = $selectedOption['delivery_time'];
                          }
                          if (!empty($selectedOption) && (stripos($cb['text'], 'region') !== false || !empty($cb['is_region']))) {
                              $cb['text'] = getSelectorOptionRegionText($selectedOption);
                              $cb['is_region'] = true;
                          }
                      }
                      unset($cb);
                  }
                ?>
                <?php foreach ($customBadges as $cb): ?>
                  <?php 
                    $bText  = trim($cb['text'] ?? '');
                    if ($bText === '') continue;
                    $bFile  = trim($cb['file'] ?? '');
                    $bIcon  = trim($cb['icon'] ?? 'bi bi-patch-check-fill');
                    $bColor = trim($cb['color'] ?? '#38bdf8');
                  ?>
                  <span class="rx-clean-badge-item">
                    <?php if (!empty($bFile) && file_exists(__DIR__ . '/' . $bFile)): ?>
                      <img src="<?= APP_URL ?>/<?= htmlspecialchars(ltrim($bFile, '/')) ?>" alt="<?= e($bText) ?>" class="rx-clean-badge-img">
                    <?php elseif (!empty($selectedOption) && !empty($cb['is_region']) && function_exists('renderSelectorRegionFlag')): ?>
                      <?= renderSelectorRegionFlag($selectedOption, 16) ?>
                    <?php elseif (!empty($bIcon)): ?>
                      <i class="<?= htmlspecialchars($bIcon) ?> rx-clean-badge-icon" style="color: <?= htmlspecialchars($bColor) ?>;"></i>
                    <?php endif; ?>
                    <span><?= e($bText) ?></span>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        <?php if (!empty($selectedOption['custom_instructions'])): ?>
          <div class="rx-region-instruction-box mt-3">
            <i class="bi bi-info-circle-fill fs-6 flex-shrink-0 text-info"></i>
            <div><?= nl2br(e($selectedOption['custom_instructions'])) ?></div>
          </div>
        <?php endif; ?>
      </div>



      <!-- ORDER FORM -->
      <form class="form-rx" id="orderForm" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="submit_order" value="1">
        <input type="hidden" name="product_id" id="selectedProductId" value="">
        <input type="hidden" name="applied_coupon" id="appliedCouponInput" value="">
        <input type="hidden" name="customer_email" id="customerEmailInput" value="<?= e($_SESSION['user_email'] ?? '') ?>">
        <input type="hidden" name="selector_option_id" value="<?= $selectedOption ? (int)$selectedOption['id'] : '' ?>">
        <input type="hidden" name="region_id" value="<?= $selectedOption && !empty($selectedOption['region_id']) ? (int)$selectedOption['region_id'] : '' ?>">

        <?php if ($error): ?>
        <div class="rx-alert rx-alert-danger mb-4">
          <i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>

        <!-- Step 1: User ID & Server -->
        <div class="step-card mb-4" id="userIdSectionCard">
          <div class="step-header">
            <div class="step-number">1</div>
            <h5 class="step-title">Account Details</h5>
          </div>
          <div class="row g-3">
            <div class="col-md-<?= $game['requires_server_id'] ? '8' : '12' ?>">
              <input type="text" class="form-control form-control-modern" name="player_id" id="playerIdInput"
                     placeholder="Enter your <?= e($game['player_label'] ?: 'User ID') ?>" oninput="syncPlayerDetails()" autocomplete="off" required>
            </div>
            <?php if ($game['requires_server_id']): ?>
            <div class="col-md-4">
              <input type="text" class="form-control form-control-modern" name="server_id" id="serverIdInput"
                     placeholder="<?= e($game['server_label'] ?: 'Server ID / Zone ID') ?>" oninput="syncPlayerDetails()" autocomplete="off" required>
            </div>
            <?php endif; ?>
          </div>

          <!-- Hidden Input for verified IGN -->
          <input type="hidden" name="player_ign" id="playerIgnHiddenInput" value="">

          <!-- Live Player Verification Display Card -->
          <div id="playerVerifyContainer" class="player-verify-container" style="display:none;">
            <!-- Loading Box -->
            <div id="playerVerifyLoading" class="player-verify-card loading" style="display:none;">
              <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              <span>Checking account details...</span>
            </div>

            <!-- Success Box -->
            <div id="playerVerifySuccess" class="player-verify-card success" style="display:none;">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                  <div class="verify-badge-icon">
                    <i class="bi bi-patch-check-fill"></i>
                  </div>
                  <div>
                    <div class="verify-label">In-Game Name (IGN)</div>
                    <div class="verify-ign" id="playerVerifyIgn">—</div>
                  </div>
                </div>
                <div class="text-sm-end">
                  <div class="verify-server-pill" id="playerVerifyServerWrap" style="display:none;">
                    <i class="bi bi-hdd-network"></i> <span id="playerVerifyServerPill">—</span>
                  </div>
                  <div>
                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25" style="font-size:0.72rem;padding:4px 8px;">
                      <i class="bi bi-shield-check me-1"></i> Account Verified
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error Box -->
            <div id="playerVerifyError" class="player-verify-card error" style="display:none;">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"></i>
                <div>
                  <div class="text-warning fw-bold" style="font-size:0.85rem;" id="playerVerifyErrorTitle">Account Not Found</div>
                  <div class="text-muted small" id="playerVerifyErrorMsg">Please check your User ID and Server ID.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php
          // Prepare Featured and Categories
          $featuredProducts = array_values(array_filter($products, fn($p) => !empty($p['is_featured'])));
          $categoryKeys = [];
          foreach ($products as $p) {
              $cat = !empty($p['category']) ? $p['category'] : 'diamonds';
              $categoryKeys[$cat] = true;
          }

          $categoryMeta = [
              'diamonds'       => ['label' => 'Diamonds', 'icon' => '💎'],
              'first_recharge' => ['label' => '1st Recharge', 'icon' => '✨'],
              'recharge_phase' => ['label' => 'Recharge Phase', 'icon' => '📅'],
              'pass'           => ['label' => 'Pass', 'icon' => '🎟️'],
              'exclusive'      => ['label' => 'Exclusive', 'icon' => '🪄'],
          ];
        ?>

        <!-- Featured Section (Matching Screenshot) -->
        <?php if (!empty($featuredProducts)): ?>
        <div class="rx-section-box mb-4" id="featuredSectionBox">
          <div class="rx-section-title">
            <i class="bi bi-star-fill rx-star-icon"></i>
            <span>Featured</span>
          </div>
          <div class="rx-pkg-grid-2col" id="featuredGrid">
            <?php foreach ($featuredProducts as $fp): ?>
              <?= renderModernPackageCard($fp, true) ?>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Step 2: Select Package Section (Matching Screenshot) -->
        <div class="rx-section-box mb-4" id="packageSelectionBox">
          <div class="rx-section-title">
            <i class="bi bi-grid-fill" style="color:#00c07f;"></i>
            <span>Select Package</span>
          </div>

          <!-- Category Filter Pills -->
          <?php if (count($categoryKeys) > 1 || isset($categoryKeys['diamonds'])): ?>
          <div class="rx-category-tabs-wrapper">
            <div class="rx-category-pills" id="categoryPills">
              <?php 
                $firstCat = true;
                $orderedCats = ['diamonds', 'first_recharge', 'recharge_phase', 'pass', 'exclusive'];
                foreach ($orderedCats as $ck) {
                  if (isset($categoryKeys[$ck])) {
                    $meta = $categoryMeta[$ck];
                    $isActive = $firstCat ? 'active' : '';
                    $firstCat = false;
                    echo '<button type="button" class="rx-cat-btn ' . $isActive . '" data-category="' . $ck . '" onclick="filterCategory(\'' . $ck . '\', this)">';
                    echo '<span class="rx-cat-icon">' . $meta['icon'] . '</span> ' . e($meta['label']);
                    echo '</button>';
                  }
                }
                foreach ($categoryKeys as $ck => $v) {
                  if (!in_array($ck, $orderedCats)) {
                    $isActive = $firstCat ? 'active' : '';
                    $firstCat = false;
                    $lbl = ucwords(str_replace(['_', '-'], ' ', $ck));
                    echo '<button type="button" class="rx-cat-btn ' . $isActive . '" data-category="' . e($ck) . '" onclick="filterCategory(\'' . e($ck) . '\', this)">';
                    echo '<span class="rx-cat-icon">⚡</span> ' . e($lbl);
                    echo '</button>';
                  }
                }
              ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Package Cards Grid (2 Columns) -->
          <div class="rx-pkg-grid-2col" id="packageGrid">
            <?php foreach ($products as $p): ?>
              <?= renderModernPackageCard($p, false) ?>
            <?php endforeach; ?>
          </div>

          <!-- Show More / Show Less Toggle -->
          <div class="rx-show-more-wrap" id="showMoreWrap" style="display:none;">
            <button type="button" class="rx-show-more-btn" id="showMoreToggle" onclick="toggleShowMore()">
              <i class="bi bi-chevron-down me-1"></i> Show More
            </button>
          </div>
        </div>

        <!-- Step 3: Payment Method (Logged In) OR Checkout Login Required (Guest) -->
        <?php if ($userLoggedIn): ?>
        <div class="step-card mb-4" id="paymentSectionCard">
          <div class="step-header">
            <div class="step-number" style="background:#00c07f;"><i class="bi bi-wallet2"></i></div>
            <h5 class="step-title">Payment Method</h5>
          </div>

          <div class="rx-payment-grid" id="paymentMethodsGrid">
            <?php 
              $pMethods = getAvailablePaymentMethods($activeCurrency, db());
              if (empty($pMethods)):
            ?>
              <div class="alert alert-warning w-100" style="border-radius:12px; background:#422607; border:1px solid #F59E0B; color:#FEF3C7;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= ($activeCurrency === 'USD') 
                    ? 'International checkout (Binance Pay) is temporarily undergoing maintenance. Please check back shortly.' 
                    : 'No payment methods are currently active. Please contact support.' ?>
              </div>
            <?php 
              endif;
              foreach ($pMethods as $pm): 
                $key = $pm['method_key'] ?? $pm['key'] ?? '';
                $label = $pm['method_label'] ?? $pm['label'] ?? ucfirst($key);
                $accountNum = $pm['account_number'] ?? '';
                $icon = $pm['icon'] ?? '';
                $isBinance = ($key === 'binance');
            ?>
            <div class="rx-payment-card" id="pay-<?= $key ?>" onclick="selectPayment('<?= $key ?>', '<?= e((string)$label) ?>', '<?= e((string)$accountNum) ?>')">
              <div class="rx-pm-left">
                <div class="rx-pm-logo-wrap">
                  <?php if (!empty($icon) && strpos($icon, 'bi-') === false): ?>
                    <img src="<?= APP_URL ?>/<?= e((string)$icon) ?>" alt="<?= e((string)$label) ?>" loading="lazy">
                  <?php elseif ($isBinance): ?>
                    <strong style="color:#F3BA2F; font-size:1.15rem;"><i class="bi bi-currency-bitcoin"></i></strong>
                  <?php else: ?>
                    <span style="color:#000; font-weight:800; font-size:0.75rem;"><?= e((string)$label) ?></span>
                  <?php endif; ?>
                </div>
                <div class="rx-pm-title-col">
                  <div class="rx-pm-name">
                    <?= e((string)$label) ?>
                    <?php if ($isBinance): ?>
                      <span class="badge" style="font-size:0.68rem; background:#422607; color:#FDE68A; border:1px solid #F59E0B; vertical-align:middle; margin-left:4px;">USD</span>
                    <?php endif; ?>
                  </div>
                  <div class="rx-pm-subprice pm-subprice-disp" data-method="<?= $key ?>">—</div>
                </div>
              </div>
              <div class="rx-pm-indicator" id="indicator-<?= $key ?>"></div>
              <input type="radio" name="payment_method" value="<?= $key ?>" id="radio-<?= $key ?>" style="display:none;" required>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Payment Instructions (Shown upon payment selection) -->
          <div id="payInstructions" style="display:none;" class="mb-3">
            <!-- Local BDT Payment Alert -->
            <div id="localPayAlert" class="rx-alert rx-alert-info" style="border-radius:14px; padding:16px 18px; background:#092E24; border:1px solid #10B981;">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-info-circle-fill" style="font-size:1.2rem; color:#34D399;"></i>
                <span style="font-weight:800; font-size:0.95rem; color:#ffffff;">Payment Instructions</span>
              </div>
              <p class="mb-0" style="font-size:0.92rem; line-height:1.6; color:#F8FAFC;">
                Send <strong id="payAmountDisp" style="color:#34D399;">0</strong> to our <strong id="payMethodDisp" style="color:#34D399;"></strong> number: 
                <strong id="payNoDisp" style="color:#FFFFFF; font-size:1.15rem; background:#064E3B; border:1px solid #10B981; padding:2px 8px; border-radius:6px; margin-left:5px;"></strong>
              </p>
            </div>

            <!-- Binance Pay USD Alert -->
            <div id="binancePayAlert" class="rx-alert" style="display:none; border-radius:14px; padding:16px 18px; background:#3B2306; border:1px solid #F59E0B;">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-currency-bitcoin" style="font-size:1.3rem; color:#FBBF24;"></i>
                <span style="font-weight:800; font-size:0.95rem; color:#ffffff;">Binance Pay (International USD)</span>
              </div>
              <div id="binanceRateUnavailable" class="text-danger mb-2" style="display:none; font-size:0.92rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>Binance Pay is currently unavailable:</strong> <span id="binanceErrorMsg">Exchange rate is unconfigured or stale.</span> Please select a local payment method.
              </div>
              <div id="binanceRateAvailable">
                <p class="mb-2" style="font-size:0.92rem; line-height:1.6; color:#F8FAFC;">
                  Send <strong id="binanceUsdAmountDisp" style="color:#FDE68A; font-size:1.15rem;">$ 0.00 USD</strong> to our Binance Pay ID: 
                  <strong id="binancePayIdDisp" style="color:#FFFFFF; font-size:1.15rem; background:#78350F; border:1px solid #F59E0B; padding:2px 8px; border-radius:6px; margin-left:5px; user-select:all;"><?= e($binanceCfg['binance_pay_id'] ?? '') ?></strong>
                </p>
                <?php if (!empty($binanceCfg['binance_qr_image'])): ?>
                <div class="my-2">
                  <img src="<?= APP_URL ?>/<?= e($binanceCfg['binance_qr_image']) ?>" alt="Binance Pay QR Code" style="max-width:160px; border-radius:8px; border:1px solid #3c4962;">
                </div>
                <?php endif; ?>
                <p class="mb-0 text-muted" style="font-size:0.82rem;">
                  <?= e(getBinanceInstructions()) ?>
                </p>
              </div>
            </div>

            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label" id="payNumberLabel" style="font-size:0.82rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Payment Number</label>
                <input type="text" name="payment_number" class="form-control form-control-modern" placeholder="01XXXXXXXXX" id="payNumInput">
              </div>
              <div class="col-md-6">
                <label class="form-label" id="payTrxLabel" style="font-size:0.82rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Transaction ID</label>
                <input type="text" name="payment_trx" class="form-control form-control-modern" placeholder="TRX ID" id="payTrxInput">
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <!-- Step 3: Checkout (Login Required) -->
        <div class="step-card mb-4" id="paymentSectionCard">
          <div class="step-header">
            <div class="step-number rx-step-checkout-number">3</div>
            <h5 class="step-title">CHECKOUT</h5>
          </div>

          <div class="rx-login-required-box text-center">
            <div class="rx-login-icon-circle mx-auto">
              <i class="bi bi-box-arrow-in-right"></i>
            </div>
            <h3 class="rx-login-required-title">Login Required</h3>
            <p class="rx-login-required-desc">
              Please login or create an account to continue with your purchase
            </p>
            <div class="rx-login-actions-wrap">
              <a href="<?= APP_URL ?>/auth/login.php?redirect=<?= urlencode($currentUrl) ?>" 
                 class="rx-btn-login-continue" 
                 onclick="preserveOrderSelections(event, this.href)">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Login to Continue</span>
              </a>
              <a href="<?= APP_URL ?>/auth/register.php?redirect=<?= urlencode($currentUrl) ?>" 
                 class="rx-btn-create-account" 
                 onclick="preserveOrderSelections(event, this.href)">
                <i class="bi bi-person-plus"></i>
                <span>Create New Account</span>
              </a>
            </div>
            <div class="rx-login-prompt-footer">
              Join us to enjoy exclusive offers and faster checkout
            </div>

            <!-- Live Subtotal & Total Preview inside checkout box matching screenshot -->
            <div class="rx-login-box-summary">
              <div class="d-flex justify-content-between align-items-center mb-1 text-muted" style="font-size:0.92rem;">
                <span>Subtotal:</span>
                <span class="rx-login-subtotal-disp"><?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
              </div>
              <div class="d-flex justify-content-between align-items-center fw-bold fs-5 mb-3">
                <span style="color:var(--text-primary, #ffffff);">Total:</span>
                <span class="rx-login-total-disp" style="color:#ef4444;"><?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
              </div>
              <div class="rx-ssl-secured-badge">
                <i class="bi bi-shield-lock-fill text-success"></i>
                <span>Secured by SSL</span>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </form>

      <!-- About Game Section (Accordion) -->
      <?php if (!empty($game['description'])): ?>
      <div class="rx-section-box mb-4">
        <div class="d-flex justify-content-between align-items-center" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#aboutGameCollapse">
          <div class="rx-section-title mb-0">
            <i class="bi bi-book"></i>
            <span>About <?= e($game['name']) ?></span>
          </div>
          <i class="bi bi-chevron-down text-muted"></i>
        </div>
        <div class="collapse show mt-3" id="aboutGameCollapse">
          <div style="font-size:0.9rem; line-height:1.6; color:var(--text-dim);">
            <?= nl2br(e($game['description'])) ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Mobile How to Top-Up (Visible only on mobile) -->
      <div class="how-to-section d-block d-lg-none mt-4">
        <h2 class="how-to-title">
          <span class="how-to-title-icon"><i class="bi bi-info-circle-fill"></i></span>
          How to Top-Up
        </h2>
        <div class="how-to-steps">
          <div class="how-to-step">
            <div class="step-indicator">1</div>
            <div class="step-content">
              <h4>Enter Your ID</h4>
              <p>Provide your User ID & Zone ID for verification.</p>
            </div>
          </div>
          <div class="how-to-step">
            <div class="step-indicator">2</div>
            <div class="step-content">
              <h4>Choose the Package</h4>
              <p>Select the top-up package you want.</p>
            </div>
          </div>
          <div class="how-to-step">
            <div class="step-indicator">3</div>
            <div class="step-content">
              <h4>Make Payment</h4>
              <p>Choose your preferred payment method.</p>
            </div>
          </div>
          <div class="how-to-step">
            <div class="step-indicator">4</div>
            <div class="step-content">
              <h4>Confirmation</h4>
              <p>Items are added instantly after payment.</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Right Sidebar Column: Desktop Order Summary (col-lg-4, visible on desktop) -->
    <div class="col-lg-4 d-none d-lg-block">
      <div class="rx-desktop-sidebar-sticky">

        <!-- ORDER SUMMARY CARD (Screenshot 1 & 2) -->
        <div class="rx-order-summary-card" id="desktopSummaryCard">
          <div class="rx-summary-header">
            <span class="rx-summary-header-title">ORDER SUMMARY</span>
          </div>

          <div class="rx-summary-body">
            <!-- Unselected State (Screenshot 1) -->
            <div class="rx-summary-empty-state" id="desktopSummaryEmpty">
              <div class="rx-summary-cart-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="9" cy="21" r="1"></circle>
                  <circle cx="20" cy="21" r="1"></circle>
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
              </div>
              <p class="rx-summary-empty-text">Select a package to continue</p>
            </div>

            <!-- Selected State (Screenshot 2) -->
            <div class="rx-summary-selected-state" id="desktopSummarySelected" style="display:none;">
              <!-- Package Row -->
              <div class="rx-summary-pkg-row">
                <div class="rx-summary-pkg-icon-wrap">
                  <img src="" class="rx-summary-pkg-img pkg-icon-disp" alt="Package">
                </div>
                <div class="rx-summary-pkg-info">
                  <h4 class="rx-summary-pkg-name pkg-name-disp">—</h4>
                  <span class="rx-summary-game-name"><?= e($game['name']) ?></span>
                </div>
              </div>

              <!-- Player Details Box (Mint Card) -->
              <div class="rx-summary-player-box">
                <div class="rx-summary-info-row">
                  <span class="rx-info-lbl">User ID</span>
                  <span class="rx-info-val user-id-disp">—</span>
                </div>
                <?php if ($game['requires_server_id']): ?>
                <div class="rx-summary-info-row">
                  <span class="rx-info-lbl">Zone ID</span>
                  <span class="rx-info-val zone-id-disp">—</span>
                </div>
                <?php endif; ?>
                <div class="rx-summary-info-row">
                  <span class="rx-info-lbl">IGN</span>
                  <span class="rx-info-val is-verified ign-disp"><i class="bi bi-check2"></i> <?= e(!empty($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Smily Queen') ?></span>
                </div>
                <div class="rx-summary-info-row">
                  <span class="rx-info-lbl">Region</span>
                  <span class="rx-info-val region-disp"><?= e($selectedOption ? ($selectedOption['region_code'] ?? $selectedOption['name']) : 'BD') ?></span>
                </div>
              </div>

              <!-- Coupon Code Row -->
              <div class="rx-summary-coupon-row">
                <div class="rx-coupon-input-wrap">
                  <span class="rx-coupon-icon"><i class="bi bi-ticket-perforated"></i></span>
                  <input type="text" class="rx-coupon-input coupon-input-field" placeholder="Coupon code" autocomplete="off">
                </div>
                <button type="button" class="rx-coupon-apply-btn" onclick="applySummaryCoupon(this)">Apply</button>
              </div>
              <div class="rx-coupon-msg coupon-msg-disp" style="display:none;"></div>

              <!-- Pricing Breakdown -->
              <div class="rx-summary-breakdown">
                <div class="rx-breakdown-row">
                  <span class="rx-bd-lbl">Package price</span>
                  <span class="rx-bd-val pkg-orig-price-disp"><?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
                </div>
                <div class="rx-breakdown-row shop-discount-row" style="display:none;">
                  <span class="rx-bd-lbl">Shop discount</span>
                  <span class="rx-bd-val is-discount shop-discount-disp">- <?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
                </div>
                <div class="rx-breakdown-row coupon-discount-row" style="display:none;">
                  <span class="rx-bd-lbl">Coupon discount</span>
                  <span class="rx-bd-val is-discount coupon-discount-disp">- <?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
                </div>
                <div class="rx-breakdown-total-row">
                  <span class="rx-total-lbl">Total</span>
                  <span class="rx-total-val total-price-disp"><?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
                </div>
                <div class="rx-savings-subtext savings-disp" style="display:none;">
                  You save <span><?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
                </div>
              </div>

              <!-- Email Receipt Input -->
              <div class="rx-summary-email-box">
                <div class="rx-email-header">
                  <span class="rx-email-lbl"><i class="bi bi-envelope me-1"></i> Email receipt</span>
                  <span class="rx-email-opt">optional</span>
                </div>
                <div class="rx-email-input-wrap">
                  <input type="email" class="rx-email-input email-input-field" placeholder="your@email.com" value="<?= e($_SESSION['user_email'] ?? '') ?>" oninput="syncEmail(this.value)">
                  <span class="rx-email-icon"><i class="bi bi-send-fill"></i></span>
                </div>
              </div>

              <!-- Pay Now / Login to Continue Button -->
              <?php if ($userLoggedIn): ?>
              <button type="button" class="rx-summary-pay-btn" onclick="handlePayNow()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                  <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
                <span>Pay Now</span>
              </button>
              <?php else: ?>
              <button type="button" class="rx-summary-pay-btn rx-summary-login-btn" onclick="handleLoginRequiredClick()">
                <i class="bi bi-box-arrow-in-right me-2" style="font-size:1.1rem;"></i>
                <span>Login to Continue</span>
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Desktop How to Top-Up (below summary) -->
        <div class="how-to-section">
          <h2 class="how-to-title">
            <span class="how-to-title-icon"><i class="bi bi-info-circle-fill"></i></span>
            How to Top-Up
          </h2>
          <div class="how-to-steps">
            <div class="how-to-step">
              <div class="step-indicator">1</div>
              <div class="step-content">
                <h4>Enter Your ID</h4>
                <p>Provide your User ID & Zone ID for verification.</p>
              </div>
            </div>
            <div class="how-to-step">
              <div class="step-indicator">2</div>
              <div class="step-content">
                <h4>Choose the Package</h4>
                <p>Select the top-up package you want.</p>
              </div>
            </div>
            <div class="how-to-step">
              <div class="step-indicator">3</div>
              <div class="step-content">
                <h4>Make Payment</h4>
                <p>Choose your preferred payment method.</p>
              </div>
            </div>
            <div class="how-to-step">
              <div class="step-indicator">4</div>
              <div class="step-content">
                <h4>Confirmation</h4>
                <p>Items are added instantly after payment.</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<!-- MOBILE STICKY BOTTOM BAR (Matches Screenshot 4 & 1) -->
<div class="rx-mobile-sticky-bar" id="rxMobileStickyBar">
  <!-- Unselected State (Screenshot 1) -->
  <div class="rx-sticky-unselected" id="stickyUnselected">
    <i class="bi bi-arrow-up-circle"></i>
    <span>Select a package above</span>
  </div>

  <!-- Selected State (Screenshot 4) -->
  <div class="rx-sticky-selected" id="stickySelected" style="display:none;">
    <div class="rx-sticky-left-info">
      <div class="rx-sticky-pkg-name" id="stickyPkgName">—</div>
      <div class="rx-sticky-price-row">
        <span class="rx-sticky-pkg-price" id="stickyPkgPrice"><?= $isUsdAvailable ? '$ 0.00' : '৳ 0' ?></span>
      </div>
      <a href="javascript:void(0)" class="rx-view-summary-toggle" id="viewSummaryToggleBtn" onclick="toggleOrderSummaryModal()">
        View summary ▴
      </a>
    </div>
    <?php if ($userLoggedIn): ?>
    <button type="button" class="rx-sticky-pay-btn" onclick="handlePayNow()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:-2px; margin-right:3px;">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        <polyline points="9 12 11 14 15 10"></polyline>
      </svg>
      <span>Pay Now</span>
    </button>
    <?php else: ?>
    <button type="button" class="rx-sticky-pay-btn rx-sticky-login-btn" onclick="handleLoginRequiredClick()">
      <i class="bi bi-box-arrow-in-right me-1" style="font-size:0.95rem;"></i>
      <span>Login to Continue</span>
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- MOBILE ORDER SUMMARY MODAL / BOTTOM SHEET (Matches Screenshot 3) -->
<div class="rx-modal-backdrop" id="rxModalBackdrop" onclick="closeOrderSummaryModal()"></div>
<div class="rx-summary-modal" id="rxOrderSummaryModal">
  <div class="rx-summary-modal-dialog">
    <div class="rx-summary-header rx-modal-header-flex">
      <span class="rx-summary-header-title">ORDER SUMMARY</span>
      <button type="button" class="rx-modal-close-btn" onclick="closeOrderSummaryModal()" aria-label="Close">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="rx-summary-modal-body">
      <!-- Selected Package Row -->
      <div class="rx-summary-pkg-row">
        <div class="rx-summary-pkg-icon-wrap">
          <img src="" class="rx-summary-pkg-img pkg-icon-disp" alt="Package">
        </div>
        <div class="rx-summary-pkg-info">
          <h4 class="rx-summary-pkg-name pkg-name-disp">—</h4>
          <span class="rx-summary-game-name"><?= e($game['name']) ?></span>
        </div>
      </div>

      <!-- Player Details Box -->
      <div class="rx-summary-player-box">
        <div class="rx-summary-info-row">
          <span class="rx-info-lbl">User ID</span>
          <span class="rx-info-val user-id-disp">—</span>
        </div>
        <?php if ($game['requires_server_id']): ?>
        <div class="rx-summary-info-row">
          <span class="rx-info-lbl">Zone ID</span>
          <span class="rx-info-val zone-id-disp">—</span>
        </div>
        <?php endif; ?>
        <div class="rx-summary-info-row">
          <span class="rx-info-lbl">IGN</span>
          <span class="rx-info-val is-verified ign-disp"><i class="bi bi-check2"></i> <?= e(!empty($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Smily Queen') ?></span>
        </div>
        <div class="rx-summary-info-row">
          <span class="rx-info-lbl">Region</span>
          <span class="rx-info-val region-disp"><?= e($selectedOption ? ($selectedOption['region_code'] ?? $selectedOption['name']) : 'BD') ?></span>
        </div>
      </div>

      <!-- Coupon Code Row -->
      <div class="rx-summary-coupon-row">
        <div class="rx-coupon-input-wrap">
          <span class="rx-coupon-icon"><i class="bi bi-ticket-perforated"></i></span>
          <input type="text" class="rx-coupon-input coupon-input-field" placeholder="Coupon code" autocomplete="off">
        </div>
        <button type="button" class="rx-coupon-apply-btn" onclick="applySummaryCoupon(this)">Apply</button>
      </div>
      <div class="rx-coupon-msg coupon-msg-disp" style="display:none;"></div>

      <!-- Pricing Breakdown -->
      <div class="rx-summary-breakdown">
        <div class="rx-breakdown-row">
          <span class="rx-bd-lbl">Package price</span>
          <span class="rx-bd-val pkg-orig-price-disp">৳ 0</span>
        </div>
        <div class="rx-breakdown-row shop-discount-row" style="display:none;">
          <span class="rx-bd-lbl">Shop discount</span>
          <span class="rx-bd-val is-discount shop-discount-disp">- ৳ 0</span>
        </div>
        <div class="rx-breakdown-row coupon-discount-row" style="display:none;">
          <span class="rx-bd-lbl">Coupon discount</span>
          <span class="rx-bd-val is-discount coupon-discount-disp">- ৳ 0</span>
        </div>
        <div class="rx-breakdown-total-row">
          <span class="rx-total-lbl">Total</span>
          <span class="rx-total-val total-price-disp">৳ 0</span>
        </div>
        <div class="rx-savings-subtext savings-disp" style="display:none;">
          You save <span>৳ 0</span>
        </div>
      </div>

      <!-- Email Receipt -->
      <div class="rx-summary-email-box">
        <div class="rx-email-header">
          <span class="rx-email-lbl"><i class="bi bi-envelope me-1"></i> Email receipt</span>
          <span class="rx-email-opt">optional</span>
        </div>
        <div class="rx-email-input-wrap">
          <input type="email" class="rx-email-input email-input-field" placeholder="your@email.com" value="<?= e($_SESSION['user_email'] ?? '') ?>" oninput="syncEmail(this.value)">
          <span class="rx-email-icon"><i class="bi bi-send-fill"></i></span>
        </div>
      </div>

      <!-- Pay Now / Login to Continue Button -->
      <?php if ($userLoggedIn): ?>
      <button type="button" class="rx-summary-pay-btn" onclick="handlePayNow()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          <polyline points="9 12 11 14 15 10"></polyline>
        </svg>
        <span>Pay Now</span>
      </button>
      <?php else: ?>
      <button type="button" class="rx-summary-pay-btn rx-summary-login-btn" onclick="handleLoginRequiredClick()">
        <i class="bi bi-box-arrow-in-right me-2" style="font-size:1.1rem;"></i>
        <span>Login to Continue</span>
      </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const exchangeRateInfo   = <?= json_encode([
    'raw_rate'           => (float)($rateDetails['raw_rate'] ?? 0),
    'buffer_percent'     => (float)($rateDetails['buffer_percent'] ?? 0),
    'is_valid'           => (bool)($rateDetails['is_valid'] ?? false),
    'error_reason'       => (string)($rateDetails['error_reason'] ?? ''),
    'binance_active'     => (bool)($binanceCfg['binance_active'] ?? false),
    'binance_configured' => (!empty($binanceCfg['binance_pay_id']) || !empty($binanceCfg['binance_qr_image'])),
]) ?>;

const isBinanceAvailable = Boolean(exchangeRateInfo.is_valid && exchangeRateInfo.binance_active && exchangeRateInfo.binance_configured);
const isUserLoggedIn     = <?= $userLoggedIn ? 'true' : 'false' ?>;
const activeCurrency     = <?= json_encode($activeCurrency) ?>;
const usdPrecision       = <?= (int)($binanceCfg['decimal_precision'] ?? 2) ?>;
let selectedPackageId    = null;
let selectedPackageName  = '';
let basePrice            = 0; // Selling price before coupon (canonical BDT)
let originalPrice        = 0; // Strike-through regular price (canonical BDT)
let currentPrice         = 0; // Final total after all discounts (canonical BDT)
let couponDiscountAmount = 0; // Coupon discount in BDT
let appliedCouponCode    = '';
let selectedPackageIcon  = '';
let activeCategory       = 'diamonds';
let isExpanded           = false;
const SHOW_LIMIT         = 10;

function roundHalfUp(num, decimals) {
  const d = (typeof decimals === 'number') ? decimals : 2;
  const absVal = Math.abs(num);
  const shifted = Number(absVal + 'e' + d);
  const rounded = Math.round(shifted);
  const result = Number(rounded + 'e-' + d);
  return (num < 0 ? -1 : 1) * result;
}

function convertBdtToUsdJs(bdtAmount) {
  const bdt = parseFloat(bdtAmount) || 0;
  if (bdt <= 0) return 0.00;
  if (!exchangeRateInfo || !exchangeRateInfo.is_valid || exchangeRateInfo.raw_rate <= 0.0001) {
    return null;
  }
  const rawRate = exchangeRateInfo.raw_rate;
  const buffer = exchangeRateInfo.buffer_percent || 0;
  const rawUsd = bdt / rawRate;
  const withBuffer = rawUsd * (1 + (buffer / 100));
  const rounded = roundHalfUp(withBuffer, usdPrecision);
  return Math.max(0.01, rounded);
}

function money(v) {
  const bdt = parseFloat(v) || 0;
  if (activeCurrency === 'USD') {
    const usd = convertBdtToUsdJs(bdt);
    if (usd !== null) {
      return '$ ' + usd.toFixed(usdPrecision);
    }
  }
  return '৳ ' + Math.round(bdt).toLocaleString('en-BD');
}

// Select Package Trigger
function selectPackage(id, name, price, origPrice, iconUrl) {
  selectedPackageId   = id;
  selectedPackageName = name;
  basePrice           = parseFloat(price) || 0;
  originalPrice       = parseFloat(origPrice) || basePrice;
  currentPrice        = basePrice;
  couponDiscountAmount = 0;
  appliedCouponCode   = '';
  selectedPackageIcon = iconUrl || '';

  // 1. Highlight clicked card across all instances (featured & main grid)
  document.querySelectorAll('.rx-modern-card').forEach(c => c.classList.remove('selected'));
  document.querySelectorAll('.rx-pkg-item-' + id).forEach(c => c.classList.add('selected'));

  // 2. Set hidden form values
  document.getElementById('selectedProductId').value = id;
  document.getElementById('appliedCouponInput').value = '';
  document.querySelectorAll('.coupon-input-field').forEach(input => input.value = '');
  document.querySelectorAll('.coupon-msg-disp').forEach(msg => msg.style.display = 'none');

  // 3. Reveal desktop selected state
  const desktopEmpty = document.getElementById('desktopSummaryEmpty');
  const desktopSel   = document.getElementById('desktopSummarySelected');
  if (desktopEmpty && desktopSel) {
    desktopEmpty.style.display = 'none';
    desktopSel.style.display = 'block';
  }

  // 4. Update mobile sticky bar
  const stickyUnsel = document.getElementById('stickyUnselected');
  const stickySel   = document.getElementById('stickySelected');
  const stickyName  = document.getElementById('stickyPkgName');
  const stickyPrice = document.getElementById('stickyPkgPrice');

  if (stickyUnsel && stickySel) {
    stickyUnsel.style.display = 'none';
    stickySel.style.display   = 'flex';
    if (stickyName)  stickyName.textContent  = name;
    if (stickyPrice) stickyPrice.textContent = money(currentPrice);
  }

  // 5. Update icon and display
  if (selectedPackageIcon) {
    document.querySelectorAll('.pkg-icon-disp').forEach(img => {
      img.src = selectedPackageIcon;
    });
  }

  // 6. Refresh User ID and Zone ID in summary
  syncPlayerDetails();

  // 7. Recalculate totals and breakdown
  updateTotals();
}

// Synchronize User ID & Zone ID from input fields
function syncPlayerDetails() {
  const pInput = document.getElementById('playerIdInput');
  const sInput = document.getElementById('serverIdInput');

  const pVal = pInput && pInput.value.trim() ? pInput.value.trim() : '—';
  const sVal = sInput && sInput.value.trim() ? sInput.value.trim() : '—';

  document.querySelectorAll('.user-id-disp').forEach(el => el.textContent = pVal);
  document.querySelectorAll('.zone-id-disp').forEach(el => el.textContent = sVal);
}

// Synchronize Email input
function syncEmail(val) {
  const hiddenEmail = document.getElementById('customerEmailInput');
  if (hiddenEmail) hiddenEmail.value = val;
  document.querySelectorAll('.email-input-field').forEach(el => {
    if (el.value !== val) el.value = val;
  });
}

// Update Totals and Breakdown Rows
function updateTotals() {
  if (!selectedPackageId) return;

  const shopDiscount = (originalPrice > basePrice) ? (originalPrice - basePrice) : 0;
  currentPrice = Math.max(0, basePrice - couponDiscountAmount);
  const totalSavings = shopDiscount + couponDiscountAmount;

  // Sync Package Name
  document.querySelectorAll('.pkg-name-disp').forEach(el => el.textContent = selectedPackageName);

  // Sync Package Price (show strike price if higher, otherwise base price)
  const displayPkgPrice = (originalPrice > basePrice) ? originalPrice : basePrice;
  document.querySelectorAll('.pkg-orig-price-disp').forEach(el => el.textContent = money(displayPkgPrice));

  // Shop Discount Row
  document.querySelectorAll('.shop-discount-row').forEach(row => {
    row.style.display = shopDiscount > 0 ? 'flex' : 'none';
  });
  document.querySelectorAll('.shop-discount-disp').forEach(el => {
    el.textContent = '- ' + money(shopDiscount);
  });

  // Coupon Discount Row
  document.querySelectorAll('.coupon-discount-row').forEach(row => {
    row.style.display = couponDiscountAmount > 0 ? 'flex' : 'none';
  });
  document.querySelectorAll('.coupon-discount-disp').forEach(el => {
    el.textContent = '- ' + money(couponDiscountAmount);
  });

  // Total
  document.querySelectorAll('.total-price-disp').forEach(el => {
    el.textContent = money(currentPrice);
  });
  document.querySelectorAll('.rx-login-subtotal-disp').forEach(el => {
    el.textContent = money(currentPrice);
  });
  document.querySelectorAll('.rx-login-total-disp').forEach(el => {
    el.textContent = money(currentPrice);
  });

  // Savings Subtext
  document.querySelectorAll('.savings-disp').forEach(el => {
    if (totalSavings > 0) {
      el.style.display = 'block';
      el.innerHTML = 'You save <span>' + money(totalSavings) + '</span>';
    } else {
      el.style.display = 'none';
    }
  });

  // Mobile Sticky Bar Price
  const stickyPrice = document.getElementById('stickyPkgPrice');
  if (stickyPrice) stickyPrice.textContent = money(currentPrice);

  // Payment Methods subtitle prices
  document.querySelectorAll('.pm-subprice-disp').forEach(el => {
    const m = el.getAttribute('data-method');
    if (m === 'binance') {
      if (isBinanceAvailable) {
        const usd = convertBdtToUsdJs(currentPrice);
        el.textContent = usd !== null ? ('$ ' + usd.toFixed(2) + ' USD') : 'Unavailable';
      } else {
        el.textContent = 'Unavailable';
      }
    } else {
      el.textContent = money(currentPrice);
    }
  });

  // Update instruction amount if open
  const payAmtDisp = document.getElementById('payAmountDisp');
  if (payAmtDisp) payAmtDisp.textContent = money(currentPrice);

  const binanceUsdDisp = document.getElementById('binanceUsdAmountDisp');
  if (binanceUsdDisp) {
    const usd = convertBdtToUsdJs(currentPrice);
    binanceUsdDisp.textContent = (usd !== null && usd > 0) ? ('$ ' + usd.toFixed(2) + ' USD') : 'Unavailable';
  }
}

// Apply Coupon Function (works from desktop and modal)
function applySummaryCoupon(triggerBtn) {
  if (!selectedPackageId || basePrice === 0) {
    alert('Please select a package first.');
    return;
  }

  // Find the input corresponding to the clicked button
  const row = triggerBtn ? triggerBtn.closest('.rx-summary-coupon-row') : null;
  const inputEl = row ? row.querySelector('.coupon-input-field') : document.querySelector('.coupon-input-field');
  const code = inputEl ? inputEl.value.trim() : '';

  if (!code) return;

  // Show applying state
  document.querySelectorAll('.coupon-msg-disp').forEach(el => {
    el.style.display = 'block';
    el.style.color = '#94a3b8';
    el.textContent = 'Applying coupon...';
  });

  fetch(`<?= APP_URL ?>/game.php?slug=<?= $game['slug'] ?>&api=coupon&code=${encodeURIComponent(code)}&total=${basePrice}`)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        couponDiscountAmount = parseFloat(data.discount) || 0;
        appliedCouponCode    = code;
        document.getElementById('appliedCouponInput').value = code;

        // Sync input values
        document.querySelectorAll('.coupon-input-field').forEach(inp => inp.value = code);

        document.querySelectorAll('.coupon-msg-disp').forEach(el => {
          el.style.display = 'block';
          el.style.color   = '#00c07f';
          el.textContent   = '✓ Coupon applied: - ' + money(couponDiscountAmount);
        });

        updateTotals();
      } else {
        couponDiscountAmount = 0;
        appliedCouponCode    = '';
        document.getElementById('appliedCouponInput').value = '';

        document.querySelectorAll('.coupon-msg-disp').forEach(el => {
          el.style.display = 'block';
          el.style.color   = '#ef4444';
          el.textContent   = '✕ ' + (data.msg || 'Invalid coupon code');
        });

        updateTotals();
      }
    })
    .catch(() => {
      document.querySelectorAll('.coupon-msg-disp').forEach(el => {
        el.style.display = 'block';
        el.style.color   = '#ef4444';
        el.textContent   = 'Failed to validate coupon.';
      });
    });
}

// Payment Selection
function selectPayment(method, label, number) {
  // Deselect all
  document.querySelectorAll('.rx-payment-card').forEach(c => c.classList.remove('selected'));
  
  // Select active card
  const payCard = document.getElementById('pay-' + method);
  if (payCard) payCard.classList.add('selected');

  const radio = document.getElementById('radio-' + method);
  if (radio) radio.checked = true;

  document.getElementById('payInstructions').style.display = 'block';

  const localAlert = document.getElementById('localPayAlert');
  const binanceAlert = document.getElementById('binancePayAlert');
  const numInp = document.getElementById('payNumInput');
  const trxInp = document.getElementById('payTrxInput');

  if (method === 'binance') {
    if (localAlert) localAlert.style.display = 'none';
    if (binanceAlert) binanceAlert.style.display = 'block';

    if (!isBinanceAvailable || !exchangeRateInfo.is_valid || exchangeRateInfo.raw_rate <= 0.0001) {
      document.getElementById('binanceRateUnavailable').style.display = 'block';
      document.getElementById('binanceRateAvailable').style.display = 'none';
      document.getElementById('binanceErrorMsg').textContent = exchangeRateInfo.error_reason || 'Exchange rate is unconfigured or stale.';
    } else {
      document.getElementById('binanceRateUnavailable').style.display = 'none';
      document.getElementById('binanceRateAvailable').style.display = 'block';
      const usd = convertBdtToUsdJs(currentPrice || basePrice || 0);
      document.getElementById('binanceUsdAmountDisp').textContent = (usd !== null && usd > 0) ? ('$ ' + usd.toFixed(2) + ' USD') : 'Unavailable';
    }

    document.getElementById('payNumberLabel').textContent = 'Binance Account / UID / Email';
    if (numInp) numInp.placeholder = 'e.g. Binance UID or Email';
    document.getElementById('payTrxLabel').textContent = 'Binance Order ID / TrxID';
    if (trxInp) trxInp.placeholder = 'e.g. 239847192847192';
  } else {
    if (localAlert) localAlert.style.display = 'block';
    if (binanceAlert) binanceAlert.style.display = 'none';

    document.getElementById('payMethodDisp').textContent = label;
    document.getElementById('payNoDisp').textContent     = number || '';
    document.getElementById('payAmountDisp').textContent = money(currentPrice || basePrice || 0);

    if (number && number.trim() !== '') {
      document.getElementById('payNoDisp').parentElement.style.display = 'block';
    } else {
      document.getElementById('payNoDisp').parentElement.style.display = 'none';
    }

    document.getElementById('payNumberLabel').textContent = 'Payment Number';
    if (numInp) numInp.placeholder = '01XXXXXXXXX';
    document.getElementById('payTrxLabel').textContent = 'Transaction ID';
    if (trxInp) trxInp.placeholder = 'TRX ID';
  }

  if (numInp) numInp.required = true;
  if (trxInp) trxInp.required = true;
}

// Mobile Summary Modal Controls
function openOrderSummaryModal() {
  const backdrop = document.getElementById('rxModalBackdrop');
  const modal    = document.getElementById('rxOrderSummaryModal');
  if (backdrop && modal) {
    backdrop.classList.add('active');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeOrderSummaryModal() {
  const backdrop = document.getElementById('rxModalBackdrop');
  const modal    = document.getElementById('rxOrderSummaryModal');
  if (backdrop && modal) {
    backdrop.classList.remove('active');
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

function toggleOrderSummaryModal() {
  const modal = document.getElementById('rxOrderSummaryModal');
  if (modal && modal.classList.contains('active')) {
    closeOrderSummaryModal();
  } else {
    openOrderSummaryModal();
  }
}

// Category Tabs
function filterCategory(cat, btn) {
  document.querySelectorAll('.rx-cat-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');

  activeCategory = cat;
  isExpanded     = false;

  const cards = document.querySelectorAll('#packageGrid .rx-modern-card');
  cards.forEach(card => {
    const cardCat = card.getAttribute('data-category');
    if (!cat || cat === 'all' || cardCat === cat) {
      card.setAttribute('data-matches-cat', '1');
    } else {
      card.setAttribute('data-matches-cat', '0');
    }
  });

  applyShowMore();
}

function applyShowMore() {
  const cards = document.querySelectorAll('#packageGrid .rx-modern-card');
  let matchingCards = [];

  cards.forEach(card => {
    if (card.getAttribute('data-matches-cat') !== '0') {
      matchingCards.push(card);
    } else {
      card.classList.add('rx-hidden-pkg');
    }
  });

  const total = matchingCards.length;
  matchingCards.forEach((card, idx) => {
    if (!isExpanded && idx >= SHOW_LIMIT) {
      card.classList.add('rx-hidden-pkg');
    } else {
      card.classList.remove('rx-hidden-pkg');
    }
  });

  const wrap = document.getElementById('showMoreWrap');
  const btn  = document.getElementById('showMoreToggle');
  if (wrap && btn) {
    if (total > SHOW_LIMIT) {
      wrap.style.display = 'block';
      btn.innerHTML = isExpanded 
        ? '<i class="bi bi-chevron-up me-1"></i> Show Less' 
        : '<i class="bi bi-chevron-down me-1"></i> Show More';
    } else {
      wrap.style.display = 'none';
    }
  }
}

function toggleShowMore() {
  isExpanded = !isExpanded;
  applyShowMore();
}

// ============================================================
// AUTOMATIC LIVE PLAYER VERIFICATION (IGN & SERVER LOOKUP)
// ============================================================
let playerVerifyTimer = null;
let lastVerifiedId = '';
let lastVerifiedServer = '';

function syncPlayerDetails() {
  const pInput = document.getElementById('playerIdInput');
  const sInput = document.getElementById('serverIdInput');
  if (!pInput) return;

  const pId = pInput.value.trim();
  const sId = sInput ? sInput.value.trim() : '';

  // Clear debounce timer
  if (playerVerifyTimer) {
    clearTimeout(playerVerifyTimer);
  }

  const container = document.getElementById('playerVerifyContainer');
  const boxLoading = document.getElementById('playerVerifyLoading');
  const boxSuccess = document.getElementById('playerVerifySuccess');
  const boxError   = document.getElementById('playerVerifyError');
  const ignHidden  = document.getElementById('playerIgnHiddenInput');

  // If user cleared the input, reset verification
  if (!pId || pId.length < 3) {
    if (container) container.style.display = 'none';
    if (ignHidden) ignHidden.value = '';
    lastVerifiedId = '';
    lastVerifiedServer = '';
    return;
  }

  // If server ID is required for this game and not filled yet, wait
  const requiresServer = <?= !empty($game['requires_server_id']) ? 'true' : 'false' ?>;
  if (requiresServer && (!sId || sId.length < 2)) {
    if (container) container.style.display = 'none';
    if (ignHidden) ignHidden.value = '';
    return;
  }

  // If already verified with identical values, do nothing
  if (pId === lastVerifiedId && sId === lastVerifiedServer) {
    return;
  }

  // Wait 450ms after user stops typing to trigger lookup
  playerVerifyTimer = setTimeout(() => {
    runPlayerVerification(pId, sId);
  }, 450);
}

function runPlayerVerification(pId, sId) {
  const container = document.getElementById('playerVerifyContainer');
  const boxLoading = document.getElementById('playerVerifyLoading');
  const boxSuccess = document.getElementById('playerVerifySuccess');
  const boxError   = document.getElementById('playerVerifyError');
  const ignHidden  = document.getElementById('playerIgnHiddenInput');
  const ignText    = document.getElementById('playerVerifyIgn');
  const srvPill    = document.getElementById('playerVerifyServerPill');
  const srvWrap    = document.getElementById('playerVerifyServerWrap');
  const errMsg     = document.getElementById('playerVerifyErrorMsg');

  if (!container) return;

  container.style.display = 'block';
  if (boxLoading) boxLoading.style.display = 'flex';
  if (boxSuccess) boxSuccess.style.display = 'none';
  if (boxError)   boxError.style.display   = 'none';

  const gameId = <?= (int)$game['id'] ?>;
  const url = '<?= APP_URL ?>/ajax/verify_player.php?game_id=' + encodeURIComponent(gameId) + 
              '&player_id=' + encodeURIComponent(pId) + 
              (sId ? '&server_id=' + encodeURIComponent(sId) : '');

  fetch(url)
    .then(r => r.json())
    .then(data => {
      if (boxLoading) boxLoading.style.display = 'none';

      if (data.skipped) {
        container.style.display = 'none';
        return;
      }

      if (data.success && data.ign) {
        lastVerifiedId = pId;
        lastVerifiedServer = sId;

        if (ignText) ignText.textContent = data.ign;
        if (ignHidden) ignHidden.value = data.ign;

        if (data.server_name) {
          if (srvPill) srvPill.textContent = data.server_name;
          if (srvWrap) srvWrap.style.display = 'inline-flex';
        } else if (sId) {
          if (srvPill) srvPill.textContent = 'Server ' + sId;
          if (srvWrap) srvWrap.style.display = 'inline-flex';
        } else {
          if (srvWrap) srvWrap.style.display = 'none';
        }

        if (boxSuccess) boxSuccess.style.display = 'flex';
      } else {
        if (ignHidden) ignHidden.value = '';
        if (errMsg) errMsg.textContent = data.message || 'Player not found. Please verify your User ID / Server ID.';
        if (boxError) boxError.style.display = 'flex';
      }
    })
    .catch(() => {
      if (boxLoading) boxLoading.style.display = 'none';
      if (container) container.style.display = 'none';
    });
}

// Preserve user selection & redirect to login/register
function preserveOrderSelections(e, targetUrl) {
  if (e && e.preventDefault) {
    e.preventDefault();
  }
  const pInput = document.getElementById('playerIdInput');
  const sInput = document.getElementById('serverIdInput');
  const state = {
    slug: '<?= e($game['slug']) ?>',
    productId: selectedPackageId,
    productName: selectedPackageName,
    playerId: pInput ? pInput.value.trim() : '',
    serverId: sInput ? sInput.value.trim() : '',
    timestamp: Date.now()
  };
  try {
    sessionStorage.setItem('rx_pending_checkout', JSON.stringify(state));
  } catch (err) {}

  window.location.href = targetUrl;
}

function handleLoginRequiredClick() {
  if (!selectedPackageId) {
    closeOrderSummaryModal();
    const pkgBox = document.getElementById('packageSelectionBox') || document.getElementById('featuredSectionBox');
    if (pkgBox) pkgBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert('Please select a top-up package first.');
    return;
  }

  // Smooth scroll to the checkout card in Step 3 so customer sees it clearly
  const checkoutCard = document.getElementById('paymentSectionCard');
  if (checkoutCard) {
    closeOrderSummaryModal();
    checkoutCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
  } else {
    preserveOrderSelections(null, '<?= APP_URL ?>/auth/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search));
  }
}

// Master Checkout / Pay Now Trigger
function handlePayNow() {
  if (!isUserLoggedIn) {
    handleLoginRequiredClick();
    return;
  }

  // 1. Verify Package is chosen
  if (!selectedPackageId) {
    closeOrderSummaryModal();
    const pkgBox = document.getElementById('packageSelectionBox') || document.getElementById('featuredSectionBox');
    if (pkgBox) pkgBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert('Please select a top-up package first.');
    return;
  }

  // 2. Verify Player ID
  const pIdInput = document.getElementById('playerIdInput');
  if (pIdInput && !pIdInput.value.trim()) {
    closeOrderSummaryModal();
    pIdInput.focus();
    pIdInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert('Please enter your Player / User ID.');
    return;
  }

  // 3. Verify Server ID if required
  const sIdInput = document.getElementById('serverIdInput');
  if (sIdInput && !sIdInput.value.trim()) {
    closeOrderSummaryModal();
    sIdInput.focus();
    sIdInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert('Please enter your Server ID / Zone ID.');
    return;
  }

  // 4. Verify Payment Method is chosen
  const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
  if (!selectedPayment) {
    closeOrderSummaryModal();
    const payBox = document.getElementById('paymentSectionCard');
    if (payBox) payBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert('Please choose a payment method.');
    return;
  }

  if (selectedPayment.value === 'binance' && (!isBinanceAvailable || !exchangeRateInfo.is_valid || exchangeRateInfo.raw_rate <= 0.0001)) {
    closeOrderSummaryModal();
    alert('International USD checkout is currently unavailable because the exchange rate or payment gateway is undergoing maintenance.');
    return;
  }

  // 5. Verify Payment Account Number & TRX ID
  const isBinance = (selectedPayment.value === 'binance');
  const payNumInput = document.getElementById('payNumInput');
  if (payNumInput && !payNumInput.value.trim()) {
    closeOrderSummaryModal();
    payNumInput.focus();
    payNumInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert(isBinance ? 'Please enter your Binance Account, UID, or Email.' : 'Please enter your payment sender phone number.');
    return;
  }

  const payTrxInput = document.getElementById('payTrxInput');
  if (payTrxInput && !payTrxInput.value.trim()) {
    closeOrderSummaryModal();
    payTrxInput.focus();
    payTrxInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert(isBinance ? 'Please enter your Binance Order ID / TrxID.' : 'Please enter your transaction ID (TRX ID).');
    return;
  }

  // 6. All valid -> Submit Order
  closeOrderSummaryModal();
  document.getElementById('orderForm').submit();
}

// Initial bootstrap on load
document.addEventListener('DOMContentLoaded', function() {
  // Auto-restore preserved selection if user just logged in or registered
  try {
    const saved = sessionStorage.getItem('rx_pending_checkout');
    if (saved) {
      const data = JSON.parse(saved);
      if (data && data.slug === '<?= e($game['slug']) ?>' && (Date.now() - (data.timestamp || 0) < 1800000)) {
        if (data.playerId && document.getElementById('playerIdInput')) {
          document.getElementById('playerIdInput').value = data.playerId;
        }
        if (data.serverId && document.getElementById('serverIdInput')) {
          document.getElementById('serverIdInput').value = data.serverId;
        }
        syncPlayerDetails();
        if (data.productId) {
          const card = document.querySelector('.rx-pkg-item-' + data.productId);
          if (card) {
            setTimeout(() => card.click(), 120);
          }
        }
      }
      sessionStorage.removeItem('rx_pending_checkout');
    }
  } catch(e) {}

  const activeBtn = document.querySelector('.rx-cat-btn.active');
  if (activeBtn) {
    const cat = activeBtn.getAttribute('data-category');
    filterCategory(cat, activeBtn);
  } else {
    filterCategory('all', null);
  }

  <?php if ($activeCurrency === 'USD' && $isBinanceAvailable): ?>
  const binanceCard = document.getElementById('pay-binance');
  if (binanceCard) {
    binanceCard.click();
  }
  <?php endif; ?>
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

