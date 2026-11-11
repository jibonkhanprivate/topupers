<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/currency.php';

$pageTitle = 'Checkout';
startUserSession();

// Get params from game.php form
$gameId       = (int)($_GET['game_id'] ?? 0);
$productId    = (int)($_GET['product_id'] ?? 0);
$productName  = sanitize($_GET['product_name'] ?? '');
$productPrice = (float)($_GET['product_price'] ?? 0);
$playerId     = sanitize($_GET['player_id'] ?? '');
$serverId     = sanitize($_GET['server_id'] ?? '');
$playerIgn    = sanitize($_GET['player_ign'] ?? '');
$custName     = sanitize($_GET['customer_name'] ?? '');
$custContact  = sanitize($_GET['customer_contact'] ?? '');

// Validate product exists
$product = null;
$game    = null;
if ($productId && $gameId) {
    $stmt = db()->prepare('SELECT p.*, g.name AS game_name, g.slug AS game_slug, g.requires_server_id FROM products p JOIN games g ON g.id = p.game_id WHERE p.id = ? AND p.game_id = ? AND p.is_active = 1');
    $stmt->execute([$productId, $gameId]);
    $product = $stmt->fetch();
    if ($product) {
        $game = getGame($gameId);
        if (empty($playerIgn) && !empty($playerId)) {
            $valCheck = validatePlayerId((int)$gameId, $playerId, $serverId);
            if (!empty($valCheck['success']) && !empty($valCheck['ign'])) {
                $playerIgn = $valCheck['ign'];
            }
        }
    }
}

$error   = '';
$success = '';

// AJAX Promo Code Validator
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

// Process order POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken     = $_POST['csrf_token'] ?? '';
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');
    $paymentNumber = sanitize($_POST['payment_number'] ?? '');
    $paymentTrx    = sanitize($_POST['payment_trx'] ?? '');
    $pId           = sanitize($_POST['player_id'] ?? '');
    $sId           = sanitize($_POST['server_id'] ?? '');
    $pIgn          = sanitize($_POST['player_ign'] ?? '');
    $pName         = sanitize($_POST['customer_name'] ?? '');
    $pContact      = sanitize($_POST['customer_contact'] ?? '');
    $pProdId       = (int)($_POST['product_id'] ?? 0);
    $pGameId       = (int)($_POST['game_id'] ?? 0);
    $appliedCoupon = sanitize(strtoupper($_POST['applied_coupon'] ?? ''));

    // Auto-resolve IGN if empty and game/player ID provided
    if (empty($pIgn) && !empty($pId) && $pGameId > 0) {
        $valCheck = validatePlayerId($pGameId, $pId, $sId);
        if (!empty($valCheck['success']) && !empty($valCheck['ign'])) {
            $pIgn = $valCheck['ign'];
        }
    }

    if (rxWriteFreezeActive()) {
        // Gate B2 write-freeze: refuse before any validation, transaction, coupon increment or order INSERT.
        rxWriteFreezeSendHeaders();
        $error = rxWriteFreezeCustomerMessage();
    } elseif (!verifyCsrf($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } elseif (!isLoggedIn()) {
        $error = 'Please log in or create an account to complete checkout.';
    } elseif (empty($pId)) {
        $error = 'Player ID is required.';
    } else {
        $pdo = db();
        $detectedCountry = detectCustomerCountry();
        $orderCurrency   = ($detectedCountry === 'BD') ? 'BDT' : 'USD';

        $validation = validateAndPrepareOrder(
            $pdo,
            $pProdId,
            $pGameId,
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
                        (order_number, user_id, product_id, game_id, player_id, server_id, player_ign, customer_name, customer_phone,
                         unit_price, total_price, coupon_code, discount_amount, payment_method, payment_number, payment_trx, ip_address,
                         currency, base_amount_bdt, exchange_rate, rate_buffer_percent, rate_mode, rate_source, charge_amount)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ');
                    $stmt->execute([
                        $orderNum, $userId, $pProdId, $pGameId, $pId, $sId, $pIgn ?: null,
                        $pName ?: ($userId ? '' : 'Guest'),
                        $pContact ?: '',
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
                        (order_number, user_id, product_id, game_id, player_id, server_id, player_ign, customer_name, customer_phone,
                         unit_price, total_price, coupon_code, discount_amount, payment_method, payment_number, payment_trx, ip_address)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ');
                    $stmt->execute([
                        $orderNum, $userId, $pProdId, $pGameId, $pId, $sId, $pIgn ?: null,
                        $pName ?: ($userId ? '' : 'Guest'),
                        $pContact ?: '',
                        $verifiedProduct['amount'], $snapshot['total_price'], $usedCoupon, $discountAmt,
                        $paymentMethod, $paymentNumber, $paymentTrx, $ip
                    ]);
                }

                $pdo->commit();

                // Send Telegram Notification to Admin if configured
                if (getSetting('notify_new_order', '1') === '1') {
                    $gameStmt = $pdo->prepare('SELECT name FROM games WHERE id = ?');
                    $gameStmt->execute([$pGameId]);
                    $gName = $gameStmt->fetchColumn() ?: 'Game';

                    $currSymbol = ($snapshot['currency'] === 'USD') ? '$' : '৳';
                    $tgMsg = "🛒 <b>NEW ORDER RECEIVED — TopUpers</b>\n\n"
                           . "<b>Order #:</b> <code>{$orderNum}</code>\n"
                           . "<b>Game:</b> " . htmlspecialchars($gName) . "\n"
                           . "<b>Package:</b> " . htmlspecialchars($verifiedProduct['name']) . "\n"
                           . "<b>Amount:</b> {$currSymbol} " . number_format($snapshot['charge_amount'], ($snapshot['currency'] === 'USD' ? 2 : 0)) . "\n"
                           . "<b>Player ID:</b> <code>" . htmlspecialchars($pId) . "</code>" . ($sId ? " (Server: {$sId})" : "") . ($pIgn ? " [IGN: " . htmlspecialchars($pIgn) . "]" : "") . "\n"
                           . "<b>Payment:</b> " . strtoupper($paymentMethod) . " (" . htmlspecialchars($paymentNumber) . ")\n"
                           . "<b>TrxID:</b> <code>" . htmlspecialchars($paymentTrx) . "</code>\n"
                           . "<b>Status:</b> Pending Verification\n"
                           . "<b>Time:</b> " . date('d M Y, h:i A');

                    @sendTelegramNotification($tgMsg);
                }

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
                        error_log('Database error during checkout order creation: ' . $errorMsg);
                    }
                } elseif ($e instanceof RuntimeException) {
                    $error = $e->getMessage();
                } else {
                    $error = 'Failed to process order. Please try again.';
                    error_log('Unexpected error during checkout order creation: ' . $e->getMessage());
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';

$detectedCountry = detectCustomerCountry();
$activeCurrency  = getActiveCurrency();
$userLoggedIn    = isLoggedIn();
$currentUrl      = $_SERVER['REQUEST_URI'] ?? '/checkout.php';
$currencySymbol  = ($activeCurrency === 'USD') ? '$' : '৳';
$rateDetails     = function_exists('resolveExchangeRate') ? resolveExchangeRate() : [];
$binanceCfg      = function_exists('getExchangeRateConfig') ? getExchangeRateConfig() : [];

$isBinanceAvailable = !empty($rateDetails['is_valid']) 
    && !empty($binanceCfg['binance_active'])
    && (!empty($binanceCfg['binance_pay_id']) || !empty($binanceCfg['binance_qr_image']));

$availableMethodsList = getAvailablePaymentMethods($activeCurrency, db());
$payMethods = [];
foreach ($availableMethodsList as $pmRow) {
    $k = $pmRow['method_key'] ?? $pmRow['key'] ?? '';
    if (empty($k)) continue;
    $payMethods[$k] = [
        'key'            => $k,
        'method_key'     => $k,
        'label'          => $pmRow['method_label'] ?? $pmRow['label'] ?? ucfirst($k),
        'method_label'   => $pmRow['method_label'] ?? $pmRow['label'] ?? ucfirst($k),
        'icon'           => $pmRow['icon'] ?? '',
        'color'          => $pmRow['color'] ?? '#3c4962',
        'account_number' => $pmRow['account_number'] ?? '',
        'instructions'   => $pmRow['instructions'] ?? '',
    ];
}
?>

<!-- Checkout Header -->
<div style="background:var(--bg-card);border-bottom:1px solid var(--border-light);padding:2rem 0;">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>" style="color:var(--text-muted);">Home</a></li>
        <?php if ($game): ?>
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/<?= e($game['slug']) ?>" style="color:var(--text-muted);"><?= e($game['name']) ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active" style="color:var(--text-primary);">Checkout</li>
      </ol>
    </nav>
    <h1 class="section-title" style="font-size:1.8rem;">Secure <span>Checkout</span></h1>
  </div>
</div>

<div class="container py-5">

  <?php if ($error): ?>
  <div class="rx-alert rx-alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?>
  </div>
  <?php endif; ?>

  <?php if (!$product): ?>
  <div class="rx-alert rx-alert-warning">
    <i class="bi bi-info-circle-fill"></i>
    No package selected. <a href="<?= APP_URL ?>/shop.php" style="color:var(--warning);">← Go back to shop</a>
  </div>
  <?php else: ?>

  <div class="row g-4">
    <!-- Left: Payment Form -->
    <div class="col-lg-8">
      <form method="POST" class="form-rx">
        <?= csrfField() ?>
        <input type="hidden" name="product_id"     value="<?= (int)$productId ?>">
        <input type="hidden" name="game_id"        value="<?= (int)$gameId ?>">
        <input type="hidden" name="product_price"  value="<?= e($product['amount']) ?>">
        <input type="hidden" name="player_id"      value="<?= e($playerId) ?>">
        <input type="hidden" name="server_id"      value="<?= e($serverId) ?>">
        <input type="hidden" name="player_ign"      value="<?= e($playerIgn) ?>">
        <input type="hidden" name="customer_name"  value="<?= e($custName) ?>">
        <input type="hidden" name="customer_contact" value="<?= e($custContact) ?>">
        <input type="hidden" name="applied_coupon" id="appliedCouponInput" value="">

        <!-- 1. Delivery Info -->
        <div class="rx-card mb-4">
          <h5 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin-bottom:1.2rem;">
            <span style="color:var(--primary);">01</span> Delivery Details
          </h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?= e($game['player_label'] ?? 'Player ID') ?></label>
              <input type="text" class="form-control" value="<?= e($playerId) ?>" readonly
                     style="opacity:.7;cursor:not-allowed;">
            </div>
            <?php if ($game['requires_server_id'] && $serverId): ?>
            <div class="col-md-6">
              <label class="form-label"><?= e($game['server_label'] ?? 'Server ID') ?></label>
              <input type="text" class="form-control" value="<?= e($serverId) ?>" readonly
                     style="opacity:.7;cursor:not-allowed;">
            </div>
            <?php endif; ?>

            <?php if (!empty($playerIgn)): ?>
            <div class="col-12">
              <div style="background:rgba(0,229,255,0.08);border:1px solid rgba(0,229,255,0.3);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:12px;">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(0,229,255,0.18);display:flex;align-items:center;justify-content:center;color:#00e5ff;font-size:1.1rem;flex-shrink:0;">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div>
                  <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">Verified In-Game Name</div>
                  <div style="font-size:1.02rem;color:#fff;font-weight:700;"><span style="color:#00e5ff;"><?= e($playerIgn) ?></span></div>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>
          <div class="rx-alert rx-alert-info mt-3" style="font-size:.82rem;">
            <i class="bi bi-info-circle"></i>
            Please verify your Player ID is correct. Wrong ID = No refund possible.
          </div>
        </div>

        <!-- 2. Payment Method (Logged In) OR Checkout Login Required (Guest) -->
        <?php if ($userLoggedIn): ?>
        <div class="rx-card mb-4" style="background:#161d2d;border:none;">
          <div class="d-flex align-items-center mb-4">
            <span style="background:var(--primary);color:#fff;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;margin-right:14px;">3</span>
            <h5 style="font-family:'Outfit',sans-serif;font-weight:700;margin:0;font-size:1.4rem;color:#fff;">Select Payment</h5>
          </div>
          
          <div class="d-flex flex-column gap-3" id="payMethodGrid">
            <?php if (empty($payMethods)): ?>
              <div class="alert alert-warning" style="border-radius:12px; background:rgba(234,179,8,0.1); border:1px solid rgba(234,179,8,0.3); color:#fef08a;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= ($activeCurrency === 'USD') 
                    ? 'International checkout (Binance Pay) is temporarily undergoing maintenance. Please check back shortly.' 
                    : 'No payment methods are currently active. Please contact support.' ?>
              </div>
            <?php endif; ?>
            <?php foreach ($payMethods as $key => $pm): 
              $isBinance = ($key === 'binance');
            ?>
            <div class="payment-card-vertical" id="pay-<?= $key ?>" onclick="selectPayment('<?= $key ?>')" 
                 style="background:#0f1521;border:1px solid #232c40;border-radius:12px;padding:16px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;transition:border-color 0.2s ease;">
              
              <div class="d-flex align-items-center gap-3">
                <div style="background:#fff;border-radius:6px;width:75px;height:48px;display:flex;align-items:center;justify-content:center;">
                  <?php if ($key === 'bkash'): ?>
                    <strong style="color:#E2136E;font-size:1.2rem;margin:0;">bKash</strong>
                  <?php elseif ($key === 'nagad'): ?>
                    <div style="display:flex;align-items:center;gap:4px;">
                      <span style="display:inline-block;width:14px;height:14px;border:3px solid #F6921E;border-radius:50%;border-top-color:transparent;transform:rotate(45deg);"></span>
                      <strong style="color:#e41b25;font-size:1.2rem;margin:-2px 0 0 0;">নগদ</strong>
                    </div>
                  <?php elseif ($key === 'rocket'): ?>
                    <strong style="color:#8B0DBA;font-size:1.3rem;margin:0;">রকেট</strong>
                  <?php elseif ($key === 'upay'): ?>
                    <strong style="color:#0055A5;font-size:1.1rem;margin:0;">Upay</strong>
                  <?php elseif ($key === 'binance'): ?>
                    <strong style="color:#F3BA2F;font-size:1rem;margin:0;"><i class="bi bi-currency-bitcoin"></i> Binance</strong>
                  <?php else: ?>
                    <strong style="color:#333;font-size:1rem;margin:0;"><?= e($pm['label']) ?></strong>
                  <?php endif; ?>
                </div>
                <div style="font-size:1.2rem;font-weight:700;color:#fff;">
                  <?= e($pm['label']) ?>
                  <?php if ($isBinance): ?>
                    <span class="badge" style="font-size:0.75rem; background:rgba(243,186,47,0.15); color:#F3BA2F; border:1px solid rgba(243,186,47,0.3); vertical-align:middle; margin-left:6px;">USD</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="d-flex align-items-center gap-3">
                <div style="color:var(--primary);font-weight:700;font-size:1.1rem;">
                  <?php if ($isBinance): ?>
                    <?php 
                      $usdConv = function_exists('convertBdtToUsd') ? convertBdtToUsd((float)$product['amount']) : null;
                    ?>
                    <?php if ($usdConv !== null && ($usdConv['usd_amount'] ?? 0) > 0 && $isBinanceAvailable): ?>
                      <span class="pm-amount" data-method="binance">$ <?= number_format($usdConv['usd_amount'], 2) ?> USD</span>
                    <?php else: ?>
                      <span class="pm-amount text-muted" data-method="binance" style="font-size:0.85rem;">(Unavailable)</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="pm-amount" data-method="<?= $key ?>">৳ <?= number_format($product['amount'], 0) ?></span>
                  <?php endif; ?>
                </div>
                <div class="radio-indicator" id="radio-indicator-<?= $key ?>" style="width:24px;height:24px;border:2px solid #3c4962;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                  <div class="radio-dot" id="radio-dot-<?= $key ?>" style="width:14px;height:14px;background:var(--primary);border-radius:50%;opacity:0;transform:scale(0);transition:all 0.2s ease;"></div>
                </div>
              </div>
              <input type="radio" name="payment_method" value="<?= $key ?>" id="radio-<?= $key ?>" style="display:none;">
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Payment instructions (shown after selection) -->
          <div id="payInstructions" style="display:none;margin-top:1.5rem;">
            <hr class="neon-divider">
            
            <!-- Standard Local BDT Instructions -->
            <div id="localPayAlert" class="rx-alert rx-alert-info mb-3">
              <i class="bi bi-send-fill"></i>
              Send <strong id="payAmount">৳ <?= number_format($product['amount'], 0) ?></strong> to our
              <strong id="payMethodName"></strong> number: <strong id="payNumber" style="font-size:1.1rem;color:var(--secondary);">Loading...</strong>
            </div>

            <!-- Binance Pay USD Instructions -->
            <div id="binancePayAlert" class="rx-alert mb-3" style="display:none; border-radius:12px; padding:16px; background:rgba(243,186,47,0.08); border:1px solid rgba(243,186,47,0.3); color:#fff;">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-currency-bitcoin" style="color:#F3BA2F; font-size:1.3rem;"></i>
                <strong style="color:#F3BA2F; font-size:1.1rem;">Binance Pay (International USD)</strong>
              </div>
              <div id="binanceRateUnavailable" class="text-danger mb-2" style="display:none; font-size:0.92rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>Binance Pay is currently unavailable:</strong> <span id="binanceErrorMsg">Exchange rate is unconfigured or stale.</span> Please select a local payment method.
              </div>
              <div id="binanceRateAvailable">
                <p class="mb-2" style="font-size:0.95rem; line-height:1.6;">
                  Payable Amount: <strong id="binanceUsdAmount" style="color:#F3BA2F; font-size:1.25rem;">$ 0.00 USD</strong>
                  <span class="text-muted ms-1" style="font-size:0.82rem;">(Exchange Rate: 1 USD = <?= number_format((float)($rateDetails['raw_rate'] ?? 0), 2) ?> BDT<?= !empty($rateDetails['buffer_percent']) ? ' + ' . $rateDetails['buffer_percent'] . '% buffer' : '' ?>)</span>
                </p>
                <?php if (!empty($binanceCfg['binance_pay_id'])): ?>
                <p class="mb-2" style="font-size:0.92rem;">
                  Binance Pay ID: <strong style="color:#fff; font-size:1.1rem; background:rgba(255,255,255,0.12); padding:2px 8px; border-radius:6px; user-select:all;"><?= e($binanceCfg['binance_pay_id']) ?></strong>
                </p>
                <?php endif; ?>
                <?php if (!empty($binanceCfg['binance_qr_image'])): ?>
                <div class="my-2">
                  <img src="<?= APP_URL ?>/<?= e($binanceCfg['binance_qr_image']) ?>" alt="Binance Pay QR Code" style="max-width:180px; border-radius:8px; border:1px solid #3c4962;">
                </div>
                <?php endif; ?>
                <p class="mb-0 text-muted" style="font-size:0.82rem;">
                  <?= e(getBinanceInstructions()) ?>
                </p>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" id="payNumberLabel">Your Account Number *</label>
                <input type="text" name="payment_number" id="paymentNumberInput" class="form-control"
                       placeholder="e.g. 01XXXXXXXXX" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" id="payTrxLabel">Transaction ID *</label>
                <input type="text" name="payment_trx" id="paymentTrxInput" class="form-control"
                       placeholder="e.g. TXN1234567890" required>
                <small id="payTrxHelp" style="font-size:.75rem;color:var(--text-muted);">
                  <i class="bi bi-info-circle me-1"></i>Find it in your payment app under transaction history.
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-rx" id="submitBtn" disabled
                style="font-size:1.05rem;padding:.85rem 2.5rem;width:100%;justify-content:center;opacity:.5;">
          <i class="bi bi-lock-fill"></i> Confirm Order — <span id="btnAmt"><?= function_exists('formatPriceByCurrency') ? formatPriceByCurrency((float)$product['amount']) : money($product['amount']) ?></span>
        </button>
        <?php else: ?>
        <!-- Checkout Login Required (Guest) -->
        <div class="rx-card mb-4" style="background:#161d2d;border:none;">
          <div class="d-flex align-items-center mb-4">
            <span class="rx-step-checkout-number" style="background:linear-gradient(135deg,#ef4444,#f43f5e);color:#fff;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;margin-right:14px;box-shadow:0 4px 14px rgba(239,68,68,0.35);">3</span>
            <h5 style="font-family:'Outfit',sans-serif;font-weight:700;margin:0;font-size:1.4rem;color:#fff;">CHECKOUT</h5>
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
                 class="rx-btn-login-continue">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Login to Continue</span>
              </a>
              <a href="<?= APP_URL ?>/auth/register.php?redirect=<?= urlencode($currentUrl) ?>" 
                 class="rx-btn-create-account">
                <i class="bi bi-person-plus"></i>
                <span>Create New Account</span>
              </a>
            </div>
            <div class="rx-login-prompt-footer">
              Join us to enjoy exclusive offers and faster checkout
            </div>
            <div class="rx-login-box-summary">
              <div class="d-flex justify-content-between align-items-center mb-1 text-muted" style="font-size:0.92rem;">
                <span>Subtotal:</span>
                <span><?= function_exists('formatPriceByCurrency') ? formatPriceByCurrency((float)$product['amount']) : money($product['amount']) ?></span>
              </div>
              <div class="d-flex justify-content-between align-items-center fw-bold fs-5 mb-3">
                <span style="color:var(--text-primary, #ffffff);">Total:</span>
                <span style="color:#ef4444;"><?= function_exists('formatPriceByCurrency') ? formatPriceByCurrency((float)$product['amount']) : money($product['amount']) ?></span>
              </div>
              <div class="rx-ssl-secured-badge">
                <i class="bi bi-shield-lock-fill text-success"></i>
                <span>Secured by SSL</span>
              </div>
            </div>
          </div>
        </div>

        <a href="<?= APP_URL ?>/auth/login.php?redirect=<?= urlencode($currentUrl) ?>" 
           class="rx-btn-login-continue text-center text-decoration-none d-flex align-items-center justify-content-center" 
           style="font-size:1.05rem;padding:.85rem 2.5rem;width:100%;">
          <i class="bi bi-box-arrow-in-right me-2"></i> Login to Continue
        </a>
        <?php endif; ?>
        <p class="text-center mt-3" style="font-size:.8rem;color:var(--text-dim);">
          <i class="bi bi-shield-check-fill me-1" style="color:var(--success);"></i>
          Your order is protected. Read our <a href="<?= APP_URL ?>/terms.php" style="color:var(--text-muted);">Terms of Service</a>.
        </p>
      </form>
    </div>

    <!-- Right: Order Summary -->
    <div class="col-lg-4">
      <div class="rx-card" style="position:sticky;top:90px;">
        <h6 style="font-family:'Rajdhani',sans-serif;font-weight:700;margin-bottom:1.2rem;">
          <i class="bi bi-receipt me-2" style="color:var(--primary);"></i>Order Summary
        </h6>

        <!-- Game info -->
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="font-size:2rem;width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:var(--bg-card2);border-radius:10px;flex-shrink:0;">
            <?php
            $gameIcons = ['mobile-legends'=>'⚔️','free-fire'=>'🔥','pubg-mobile'=>'🪖','valorant'=>'🎯','genshin-impact'=>'✨','codm'=>'🔫','honor-of-kings'=>'👑','clash-of-clans'=>'🏰','clash-royale'=>'🃏'];
            echo $gameIcons[$product['game_slug']] ?? '🎮';
            ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:.95rem;"><?= e($product['game_name']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);"><?= e($product['name']) ?></div>
          </div>
        </div>

        <hr style="border-color:var(--border-light);">

        <div class="d-flex justify-content-between mb-2" style="font-size:.88rem;">
          <span style="color:var(--text-muted);">Player ID</span>
          <span style="font-weight:500;"><?= e($playerId) ?></span>
        </div>
        <?php if ($serverId): ?>
        <div class="d-flex justify-content-between mb-2" style="font-size:.88rem;">
          <span style="color:var(--text-muted);">Server ID</span>
          <span style="font-weight:500;"><?= e($serverId) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between mb-2" style="font-size:.88rem;">
          <span style="color:var(--text-muted);">Package</span>
          <span style="font-weight:500;"><?= e($product['name']) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2" style="font-size:.88rem;">
          <span style="color:var(--text-muted);">Delivery</span>
          <span style="color:var(--success);font-weight:600;"><i class="bi bi-lightning-charge-fill"></i> Instant</span>
        </div>

        <hr style="border-color:var(--border-light);">

        <!-- Promo Code Input -->
        <div class="mb-3">
          <label style="font-size:.82rem;font-weight:600;margin-bottom:.4rem;color:var(--text-muted);"><i class="bi bi-tag-fill me-1"></i>Promo Code</label>
          <div class="input-group">
            <input type="text" id="promoCodeVal" class="form-control" placeholder="Enter coupon code" style="background:var(--bg-input);border:1px solid var(--border);color:var(--text-primary);text-transform:uppercase;">
            <button type="button" class="btn btn-rx" id="applyPromoBtn" onclick="applyPromo()">Apply</button>
          </div>
          <div id="promoMsg" class="mt-1" style="font-size:.78rem;font-weight:600;display:none;"></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-1">
          <div style="font-weight:600;color:var(--text-dim);">Subtotal</div>
          <div style="font-weight:700;" id="subtotalDisplay"><?= function_exists('formatPriceByCurrency') ? formatPriceByCurrency((float)$product['amount']) : money($product['amount']) ?></div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2" id="discountRow" style="display:none !important;">
          <div style="font-weight:600;color:var(--success);">Discount</div>
          <div style="font-weight:700;color:var(--success);" id="discountDisplay">- ৳0.00</div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top:1px dashed var(--border-light);">
          <div style="font-weight:700;">Total</div>
          <div style="font-size:1.6rem;font-weight:800;color:var(--secondary);" id="totalDisplay"><?= function_exists('formatPriceByCurrency') ? formatPriceByCurrency((float)$product['amount']) : money($product['amount']) ?></div>
        </div>

        <?php if ($product['original_price'] > $product['amount']): ?>
        <div class="rx-alert rx-alert-success mt-3" style="font-size:.8rem;">
          <i class="bi bi-tag-fill"></i>
          You save <?= function_exists('formatPriceByCurrency') ? formatPriceByCurrency((float)($product['original_price'] - $product['amount'])) : money($product['original_price'] - $product['amount']) ?>!
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
  <?php endif; ?>
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
const activeCurrency     = <?= json_encode($activeCurrency) ?>;
const usdPrecision       = <?= (int)($binanceCfg['decimal_precision'] ?? 2) ?>;

const payNumbers = {
  bkash:  <?= json_encode(getSetting("bkash_number", "")) ?>,
  nagad:  <?= json_encode(getSetting("nagad_number", "")) ?>,
  rocket: <?= json_encode(getSetting("rocket_number", "")) ?>,
  upay:   <?= json_encode(getSetting("upay_number", "")) ?>,
};

const baseTotal = <?= (float)($product['amount'] ?? 0) ?>;
let currentTotal = baseTotal;
let currentMethod = null;

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

function formatBdt(v) {
  return money(v);
}

function updateAmountsDisplay() {
  document.querySelectorAll('.pm-amount').forEach(el => {
    const m = el.getAttribute('data-method');
    if (m === 'binance') {
      if (isBinanceAvailable) {
        const usd = convertBdtToUsdJs(currentTotal);
        el.innerText = usd !== null ? ('$ ' + usd.toFixed(usdPrecision) + ' USD') : '(Unavailable)';
      } else {
        el.innerText = '(Unavailable)';
      }
    } else {
      el.innerText = formatBdt(currentTotal);
    }
  });

  if (currentMethod) {
    selectPayment(currentMethod);
  }
}

function applyPromo() {
  const code = document.getElementById('promoCodeVal').value.trim();
  const msgEl = document.getElementById('promoMsg');
  if(!code) return;

  msgEl.style.display = 'block';
  msgEl.style.color = 'var(--text-dim)';
  msgEl.innerText = 'Applying...';

  fetch(`<?= APP_URL ?>/checkout.php?api=coupon&code=${encodeURIComponent(code)}&total=${baseTotal}`)
    .then(r => r.json())
    .then(data => {
      if(data.success) {
        msgEl.style.color = 'var(--success)';
        msgEl.innerText = 'Coupon applied successfully!';
        
        document.getElementById('discountRow').style.setProperty('display', 'flex', 'important');
        document.getElementById('discountDisplay').innerText = '- ' + formatBdt(data.discount);
        
        currentTotal = data.new_total;
        document.getElementById('totalDisplay').innerText = formatBdt(currentTotal);
        document.getElementById('appliedCouponInput').value = code;
        
        updateAmountsDisplay();
      } else {
        msgEl.style.color = 'var(--danger)';
        msgEl.innerText = data.msg;
        // Reset
        document.getElementById('discountRow').style.setProperty('display', 'none', 'important');
        currentTotal = baseTotal;
        document.getElementById('totalDisplay').innerText = formatBdt(currentTotal);
        document.getElementById('appliedCouponInput').value = '';
        updateAmountsDisplay();
      }
    });
}

function selectPayment(method) {
  currentMethod = method;

  // Reset all
  document.querySelectorAll('.payment-card-vertical').forEach(c => {
    c.style.borderColor = '#232c40';
  });
  document.querySelectorAll('.radio-dot').forEach(dot => {
    dot.style.opacity = '0';
    dot.style.transform = 'scale(0)';
  });
  document.querySelectorAll('.radio-indicator').forEach(ind => {
    ind.style.borderColor = '#3c4962';
  });

  // Select target
  const targetCard = document.getElementById('pay-' + method);
  const targetDot = document.getElementById('radio-dot-' + method);
  const targetIndicator = document.getElementById('radio-indicator-' + method);

  if (targetCard) targetCard.style.borderColor = 'var(--primary)';
  if (targetDot) {
    targetDot.style.opacity = '1';
    targetDot.style.transform = 'scale(1)';
  }
  if (targetIndicator) targetIndicator.style.borderColor = 'var(--primary)';

  const radio = document.getElementById('radio-' + method);
  if (radio) radio.checked = true;

  document.getElementById('payInstructions').style.display = 'block';

  const btn = document.getElementById('submitBtn');
  const localAlert = document.getElementById('localPayAlert');
  const binanceAlert = document.getElementById('binancePayAlert');
  const numInput = document.getElementById('paymentNumberInput');
  const trxInput = document.getElementById('paymentTrxInput');

  if (method === 'binance') {
    localAlert.style.display = 'none';
    binanceAlert.style.display = 'block';

    if (!isBinanceAvailable || !exchangeRateInfo.is_valid || exchangeRateInfo.raw_rate <= 0.0001) {
      document.getElementById('binanceRateUnavailable').style.display = 'block';
      document.getElementById('binanceRateAvailable').style.display = 'none';
      document.getElementById('binanceErrorMsg').textContent = exchangeRateInfo.error_reason || 'Exchange rate is unconfigured or stale.';
      btn.disabled = true;
      btn.style.opacity = '0.5';
      document.getElementById('btnAmt').innerText = 'Rate Unavailable';
    } else {
      document.getElementById('binanceRateUnavailable').style.display = 'none';
      document.getElementById('binanceRateAvailable').style.display = 'block';
      const usdAmount = convertBdtToUsdJs(currentTotal);
      const usdStr = (usdAmount !== null && usdAmount > 0) ? ('$ ' + usdAmount.toFixed(usdPrecision) + ' USD') : 'Unavailable';
      document.getElementById('binanceUsdAmount').innerText = usdStr;
      document.getElementById('btnAmt').innerText = usdStr;
      btn.disabled = false;
      btn.style.opacity = '1';
    }

    document.getElementById('payNumberLabel').innerText = 'Your Binance Account / UID / Email *';
    numInput.placeholder = 'e.g. Binance UID (12345678) or Email';
    document.getElementById('payTrxLabel').innerText = 'Binance Order ID / TrxID *';
    trxInput.placeholder = 'e.g. 239847192847192';
    document.getElementById('payTrxHelp').innerHTML = '<i class="bi bi-info-circle me-1"></i>Found in Binance Pay transaction history details.';
  } else {
    localAlert.style.display = 'flex';
    binanceAlert.style.display = 'none';

    const labels = { bkash: 'bKash', nagad: 'Nagad', rocket: 'Rocket', upay: 'Upay' };
    const mName = labels[method] || method;
    document.getElementById('payNumber').textContent    = payNumbers[method] || 'N/A';
    document.getElementById('payMethodName').textContent = mName;
    document.getElementById('payAmount').textContent     = formatBdt(currentTotal);
    document.getElementById('btnAmt').innerText          = formatBdt(currentTotal);

    document.getElementById('payNumberLabel').innerText = `Your ${mName} Account Number *`;
    numInput.placeholder = 'e.g. 01XXXXXXXXX';
    document.getElementById('payTrxLabel').innerText = 'Transaction ID *';
    trxInput.placeholder = 'e.g. TXN1234567890';
    document.getElementById('payTrxHelp').innerHTML = '<i class="bi bi-info-circle me-1"></i>Find it in your payment app under transaction history.';

    btn.disabled = false;
    btn.style.opacity = '1';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  <?php if ($activeCurrency === 'USD' && $isBinanceAvailable): ?>
  selectPayment('binance');
  <?php endif; ?>
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
