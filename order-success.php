<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/currency.php';

$orderNum = sanitize($_GET['order'] ?? '');
$order = null;

if ($orderNum) {
    $stmt = db()->prepare('
        SELECT o.*, p.name AS product_name, g.name AS game_name, g.slug AS game_slug
        FROM orders o
        JOIN products p ON p.id = o.product_id
        JOIN games g ON g.id = o.game_id
        WHERE o.order_number = ?
    ');
    $stmt->execute([$orderNum]);
    $order = $stmt->fetch();
}

$pageTitle = 'Order Confirmed!';
include __DIR__ . '/includes/header.php';
?>

<div style="min-height:70vh;display:flex;align-items:center;padding:3rem 0;">
  <div class="container">
    <?php if ($order): ?>
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div class="rx-card text-center" style="padding:3rem 2rem;">
          <!-- Success Animation -->
          <div style="width:90px;height:90px;background:rgba(0,230,118,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 1.5rem;border:2px solid rgba(0,230,118,0.3);animation:pulse-neon 2s ease-in-out infinite;">
            ✅
          </div>
          <h2 style="font-family:'Rajdhani',sans-serif;font-weight:800;font-size:2rem;color:var(--success);">
            Order Confirmed!
          </h2>
          <p style="color:var(--text-muted);margin-bottom:2rem;">
            Your top-up request has been received. We'll process it shortly.
          </p>

          <!-- Order details card -->
          <div style="background:var(--bg-card2);border-radius:var(--radius-sm);padding:1.5rem;text-align:left;margin-bottom:2rem;">
            <div class="d-flex justify-content-between mb-2">
              <span style="color:var(--text-muted);font-size:.85rem;">Order #</span>
              <span style="font-weight:700;font-family:'Rajdhani',sans-serif;color:var(--secondary);"><?= e($order['order_number']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span style="color:var(--text-muted);font-size:.85rem;">Game</span>
              <span style="font-size:.85rem;"><?= e($order['game_name']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span style="color:var(--text-muted);font-size:.85rem;">Package</span>
              <span style="font-size:.85rem;"><?= e($order['product_name']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span style="color:var(--text-muted);font-size:.85rem;">Player ID</span>
              <span style="font-size:.85rem;font-weight:600;"><?= e($order['player_id']) ?><?= !empty($order['server_id']) ? ' (' . e($order['server_id']) . ')' : '' ?></span>
            </div>
            <?php if (!empty($order['player_ign'])): ?>
            <div class="d-flex justify-content-between mb-2">
              <span style="color:var(--text-muted);font-size:.85rem;">In-Game Name</span>
              <span style="font-size:.85rem;font-weight:700;color:var(--primary);"><i class="bi bi-patch-check-fill text-success me-1"></i><?= e($order['player_ign']) ?></span>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-2">
              <span style="color:var(--text-muted);font-size:.85rem;">Amount Paid</span>
              <span style="font-size:.9rem;font-weight:700;color:var(--secondary);"><?= function_exists('formatOrderAmount') ? formatOrderAmount($order) : money($order['total_price']) ?></span>
            </div>
            <?php if (strtoupper((string)($order['currency'] ?? 'BDT')) === 'USD' && !empty($order['base_amount_bdt'])): ?>
            <div class="d-flex justify-content-between mb-2" style="font-size:0.8rem; color:var(--text-dim);">
              <span>Base BDT / Rate</span>
              <span>
                ৳ <?= number_format((float)$order['base_amount_bdt'], 0) ?> 
                <span class="text-muted">(Rate: ৳<?= number_format((float)($order['exchange_rate'] ?? 0), 2) ?><?= !empty($order['rate_buffer_percent']) ? ' +' . $order['rate_buffer_percent'] . '%' : '' ?>)</span>
              </span>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between">
              <span style="color:var(--text-muted);font-size:.85rem;">Status</span>
              <span class="status-badge status-pending"><?= e($order['order_status']) ?></span>
            </div>
          </div>

          <div class="rx-alert rx-alert-info" style="font-size:.85rem;text-align:left;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <i class="bi bi-info-circle-fill text-info me-1"></i>
                Order Number: <strong class="font-monospace text-white" id="orderNumText"><?= e($order['order_number']) ?></strong>
              </div>
              <button type="button" class="btn btn-sm btn-outline-light" onclick="copyOrderNum()" id="copyOrderBtn" style="font-size:0.75rem;border-radius:6px;">
                <i class="bi bi-clipboard me-1"></i> Copy
              </button>
            </div>
            <div class="text-muted mt-2 small">
              Delivery is processed typically within 1–5 minutes after payment verification. You can track status anytime.
            </div>
          </div>

          <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
            <button type="button" class="btn-rx" onclick="trackThisOrder('<?= e($order['order_number']) ?>')">
              <i class="bi bi-geo-alt-fill me-1"></i> Track Order Live
            </button>
            <a href="<?= APP_URL ?>/game.php?slug=<?= e($order['game_slug']) ?>" class="btn-rx-outline">
              <i class="bi bi-controller-fill me-1"></i> Top Up Again
            </a>
            <a href="<?= APP_URL ?>" class="btn-rx-outline">
              <i class="bi bi-house-fill me-1"></i> Home
            </a>
          </div>

          <script>
          function copyOrderNum() {
            const num = document.getElementById('orderNumText').innerText.trim();
            navigator.clipboard.writeText(num).then(() => {
              const btn = document.getElementById('copyOrderBtn');
              btn.innerHTML = '<i class="bi bi-check2 text-success me-1"></i> Copied!';
              setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copy'; }, 2000);
            });
          }

          function trackThisOrder(num) {
            if (window.openTopUpersSupport) {
              window.openTopUpersSupport('track', num);
              setTimeout(() => {
                const trackBtn = document.getElementById('fswTrackBtn');
                if (trackBtn) trackBtn.click();
              }, 250);
            }
          }
          </script>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="text-center">
      <h3 style="color:var(--text-muted);">Order not found.</h3>
      <a href="<?= APP_URL ?>/shop.php" class="btn-rx mt-3">← Browse Games</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
