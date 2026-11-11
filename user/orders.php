<?php
// user/orders.php
// TOPUPERS — Customer Order History & Tracking
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

requireLogin();
$user = currentUser();
$pageTitle = 'My Orders';
$pageDescription = 'View and track all your TOPUPERS game top-up orders.';

$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$filter = sanitize($_GET['status'] ?? '');
$where  = 'WHERE o.user_id = ?';
$params = [$user['id']];
if ($filter) {
    $where .= ' AND o.order_status = ?';
    $params[] = $filter;
}

$totalStmt = db()->prepare("SELECT COUNT(*) FROM orders o $where");
$totalStmt->execute($params);
$totalRows = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = db()->prepare("
    SELECT o.*, p.name AS product_name, g.name AS game_name, g.slug AS game_slug, g.image AS game_image
    FROM orders o
    LEFT JOIN products p ON p.id = o.product_id
    LEFT JOIN games g ON g.id = o.game_id
    $where
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Overall user order count for badges
$allCountStmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$allCountStmt->execute([$user['id']]);
$allOrdersCount = (int)$allCountStmt->fetchColumn();

$extraCss = '<link rel="stylesheet" href="' . APP_URL . '/assets/css/customer-dashboard.css?v=' . filemtime(__DIR__ . '/../assets/css/customer-dashboard.css') . '">';

include __DIR__ . '/../includes/header.php';

$avatarUrl = userAvatarUrl($user['avatar'] ?? null);
$userInitials = userInitials($user['full_name'] ?: $user['username']);
$activeNav = 'orders';
?>

<div class="tp-dashboard-page">
  <div class="tp-ambient-glow"></div>

  <div class="container py-4 py-lg-5 position-relative" style="z-index: 1;">

    <!-- Page Header -->
    <div class="tp-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="tp-brand-badge mb-2">
          <i class="bi bi-receipt"></i> ORDER MANAGEMENT
        </div>
        <h1 class="tp-page-title">
          My Order History
        </h1>
        <p class="tp-page-subtitle">
          Review all your diamond, UC, and gaming top-up purchases with real-time delivery status.
        </p>
      </div>

      <div class="tp-header-actions">
        <a href="<?= APP_URL ?>/user/dashboard.php" class="tp-btn tp-btn-outline tp-btn-sm">
          <i class="bi bi-grid-fill"></i> Dashboard
        </a>
        <a href="<?= APP_URL ?>/shop.php" class="tp-btn tp-btn-primary tp-btn-sm">
          <i class="bi bi-plus-circle-fill"></i> New Top Up
        </a>
      </div>
    </div>

    <!-- Mobile Navigation Bar (< 992px) -->
    <?php include __DIR__ . '/includes/dashboard_mobile_nav.php'; ?>

    <div class="row g-4 mt-1">

      <!-- Left Sidebar (Desktop) -->
      <?php include __DIR__ . '/includes/dashboard_sidebar.php'; ?>

      <!-- Main Orders Content -->
      <div class="col-lg-9">

        <!-- Status Filter Tabs -->
        <div class="tp-card mb-3 tp-filter-card">
          <div class="tp-filter-bar">
            <div class="tp-filter-group">
              <span class="tp-filter-label">Filter:</span>
              <div class="tp-filter-scroll">
                <?php 
                $statuses = [
                    ''           => 'All Orders',
                    'pending'    => 'Pending',
                    'processing' => 'Processing',
                    'completed'  => 'Completed',
                    'failed'     => 'Failed'
                ];
                foreach ($statuses as $stKey => $stLabel):
                    $isActive = ($filter === $stKey);
                ?>
                  <a href="?status=<?= e($stKey) ?>"
                     class="tp-btn tp-btn-sm <?= $isActive ? 'tp-btn-primary' : 'tp-btn-outline' ?>">
                    <?= $stLabel ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="tp-filter-count">
              Showing <strong><?= count($orders) ?></strong> of <strong><?= $totalRows ?></strong>
            </div>
          </div>
        </div>

        <!-- Orders List Card -->
        <section class="tp-card">
          <?php if (empty($orders)): ?>
            <div class="tp-empty-state">
              <div class="tp-empty-icon">
                <i class="bi bi-receipt"></i>
              </div>
              <h4 class="tp-empty-title">No Orders Found</h4>
              <p class="tp-empty-desc">
                <?= $filter ? 'No orders match the selected "' . e($filter) . '" status filter.' : 'You haven’t placed any game top-up orders yet. Check out our latest deals!' ?>
              </p>
              <?php if ($filter): ?>
                <a href="<?= APP_URL ?>/user/orders.php" class="tp-btn tp-btn-outline tp-btn-sm me-2">Clear Filter</a>
              <?php endif; ?>
              <a href="<?= APP_URL ?>/shop.php" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="bi bi-controller"></i> Explore Games
              </a>
            </div>
          <?php else: ?>

            <!-- Desktop Orders Table -->
            <div class="tp-table-wrap d-none d-md-block">
              <table class="tp-table" aria-label="Customer Orders Table">
                <thead>
                  <tr>
                    <th>Order #</th>
                    <th>Game & Package</th>
                    <th>Player ID</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td>
                        <span class="tp-order-code">
                          <?= e($order['order_number']) ?>
                          <button type="button" class="tp-copy-btn" onclick="copyOrderText('<?= e($order['order_number']) ?>')" title="Copy Order ID">
                            <i class="bi bi-clipboard"></i>
                          </button>
                        </span>
                      </td>
                      <td>
                        <div style="font-weight:700;color:var(--tp-text);">
                          <?= e($order['game_name'] ?: 'Game Top-Up') ?>
                        </div>
                        <div style="font-size:0.78rem;color:var(--tp-text-secondary);">
                          <?= e($order['product_name'] ?: 'Package') ?>
                        </div>
                      </td>
                      <td>
                        <span style="font-family:'Rajdhani',monospace;font-size:0.9rem;font-weight:600;color:var(--tp-text-secondary);">
                          <?= e($order['player_id']) ?>
                          <?php if (!empty($order['server_id'])): ?>
                            <small class="text-muted">(<?= e($order['server_id']) ?>)</small>
                          <?php endif; ?>
                        </span>
                        <?php if (!empty($order['player_ign'])): ?>
                          <div style="font-size:0.75rem;color:#00e5ff;font-weight:600;margin-top:2px;">
                            <i class="fas fa-user-check me-1"></i><?= e($order['player_ign']) ?>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td>
                        <strong style="color:var(--tp-text);font-family:'Rajdhani',sans-serif;font-size:1.05rem;">
                          <?= function_exists('formatOrderAmount') ? formatOrderAmount($order) : money($order['total_price']) ?>
                        </strong>
                      </td>
                      <td>
                        <span style="font-size:0.8rem;text-transform:uppercase;color:var(--tp-text-secondary);font-weight:600;">
                          <?= e($order['payment_method']) ?>
                        </span>
                      </td>
                      <td>
                        <span class="tp-status-badge tp-status-<?= e(strtolower($order['order_status'])) ?>">
                          <?= e($order['order_status']) ?>
                        </span>
                      </td>
                      <td>
                        <span style="font-size:0.8rem;color:var(--tp-text-muted);" title="<?= e($order['created_at']) ?>">
                          <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <button type="button" class="tp-btn tp-btn-outline tp-btn-sm"
                                onclick="openOrderDetailModal(<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>)">
                          <i class="bi bi-eye"></i> Details
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Mobile Orders Cards -->
            <div class="d-md-none">
              <?php foreach ($orders as $order): ?>
                <div class="tp-mobile-order-card">
                  <div class="tp-mobile-order-head">
                    <span class="tp-order-code">
                      <?= e($order['order_number']) ?>
                      <button type="button" class="tp-copy-btn" onclick="copyOrderText('<?= e($order['order_number']) ?>')">
                        <i class="bi bi-clipboard"></i>
                      </button>
                    </span>
                    <span class="tp-status-badge tp-status-<?= e(strtolower($order['order_status'])) ?>">
                      <?= e($order['order_status']) ?>
                    </span>
                  </div>

                  <div class="tp-mobile-order-game">
                    <?= e($order['game_name'] ?: 'Game Top-Up') ?>
                  </div>
                  <div class="tp-mobile-order-pkg">
                    <?= e($order['product_name'] ?: 'Package') ?> &bull; UID: <?= e($order['player_id']) ?>
                    <?php if (!empty($order['player_ign'])): ?>
                      &bull; <span style="color:#00e5ff;font-weight:600;"><i class="fas fa-user-check"></i> <?= e($order['player_ign']) ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="tp-mobile-order-foot">
                    <div>
                      <span style="color:var(--tp-text-secondary);font-size:0.75rem;">Total:</span>
                      <strong style="color:var(--tp-text);font-family:'Rajdhani',sans-serif;font-size:1.05rem;">
                        <?= function_exists('formatOrderAmount') ? formatOrderAmount($order) : money($order['total_price']) ?>
                      </strong>
                    </div>

                    <button type="button" class="tp-btn tp-btn-outline tp-btn-sm"
                            onclick="openOrderDetailModal(<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>)">
                      Details <i class="bi bi-chevron-right"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
              <div class="tp-pagination-wrap mt-4 pt-3 border-top" style="border-color:var(--tp-border)!important;">
                <?php if ($page > 1): ?>
                  <a href="?status=<?= urlencode($filter) ?>&page=<?= $page - 1 ?>" class="tp-btn tp-btn-outline tp-btn-sm">
                    <i class="bi bi-chevron-left"></i> Prev
                  </a>
                <?php endif; ?>

                <div class="tp-pagination-pages">
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?status=<?= urlencode($filter) ?>&page=<?= $i ?>"
                       class="tp-btn tp-btn-sm <?= $i === $page ? 'tp-btn-primary' : 'tp-btn-outline' ?>"
                       style="min-width:38px;">
                      <?= $i ?>
                    </a>
                  <?php endfor; ?>
                </div>

                <?php if ($page < $totalPages): ?>
                  <a href="?status=<?= urlencode($filter) ?>&page=<?= $page + 1 ?>" class="tp-btn tp-btn-outline tp-btn-sm">
                    Next <i class="bi bi-chevron-right"></i>
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>

          <?php endif; ?>
        </section>

      </div>
    </div>

  </div>
</div>

<!-- ============================================================
     MODAL: ORDER DETAILS
     ============================================================ -->
<div class="modal fade" id="tpOrderDetailsModal" tabindex="-1" aria-labelledby="tpOrderDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content tp-modal-content">
      <div class="tp-modal-header">
        <h3 class="tp-modal-title" id="tpOrderDetailsModalLabel">
          <i class="bi bi-receipt text-primary"></i> Order Details
        </h3>
        <button type="button" class="tp-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="tp-modal-body">
        <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded" style="background:var(--tp-surface);border:1px solid var(--tp-border);">
          <div>
            <span style="font-size:0.75rem;color:var(--tp-text-secondary);text-transform:uppercase;">Order ID</span>
            <div id="modalOrderNumber" style="font-family:'Rajdhani',monospace;font-weight:700;font-size:1.15rem;color:var(--tp-cyan);"></div>
          </div>
          <div id="modalOrderStatusBadge"></div>
        </div>

        <!-- Status Progress Timeline -->
        <div class="tp-order-timeline" id="modalOrderTimeline">
          <div class="tp-timeline-step completed" id="stepPlaced">
            <div class="tp-step-dot"><i class="bi bi-check-lg"></i></div>
            <div class="tp-step-text">Placed</div>
          </div>
          <div class="tp-timeline-step" id="stepProcessing">
            <div class="tp-step-dot"><i class="bi bi-arrow-repeat"></i></div>
            <div class="tp-step-text">Processing</div>
          </div>
          <div class="tp-timeline-step" id="stepCompleted">
            <div class="tp-step-dot"><i class="bi bi-check-circle-fill"></i></div>
            <div class="tp-step-text">Delivered</div>
          </div>
        </div>

        <div class="tp-info-grid mb-3">
          <div class="tp-info-item">
            <div class="tp-info-label">Game</div>
            <div class="tp-info-value" id="modalGameName"></div>
          </div>
          <div class="tp-info-item">
            <div class="tp-info-label">Package</div>
            <div class="tp-info-value" id="modalProductName"></div>
          </div>
          <div class="tp-info-item">
            <div class="tp-info-label">Player ID</div>
            <div class="tp-info-value" id="modalPlayerId"></div>
          </div>
          <div class="tp-info-item" id="modalPlayerIgnWrapper" style="display:none;">
            <div class="tp-info-label">In-Game Name</div>
            <div class="tp-info-value" style="color:#00e5ff;font-weight:700;" id="modalPlayerIgn"></div>
          </div>
          <div class="tp-info-item">
            <div class="tp-info-label">Server / Zone ID</div>
            <div class="tp-info-value" id="modalServerId"></div>
          </div>
          <div class="tp-info-item">
            <div class="tp-info-label">Amount Paid</div>
            <div class="tp-info-value" style="color:var(--tp-cyan);" id="modalTotalAmount"></div>
          </div>
          <div class="tp-info-item">
            <div class="tp-info-label">Payment Method</div>
            <div class="tp-info-value" id="modalPaymentMethod"></div>
          </div>
        </div>

        <div class="p-2 rounded mb-2" style="display:none;background:rgba(243,186,47,0.08);font-size:0.8rem;color:#F3BA2F;border:1px solid rgba(243,186,47,0.25);" id="modalRateBreakdownBox">
          <div><i class="bi bi-currency-exchange me-1"></i> Base BDT: <strong id="modalBaseBdt" style="color:#fff;"></strong></div>
          <div class="mt-1 small" style="color:#cbd5e1;"><span id="modalRateDetails"></span></div>
        </div>

        <div class="p-2 rounded mb-2" style="background:var(--tp-surface);font-size:0.8rem;color:var(--tp-text-secondary);border:1px solid var(--tp-border);" id="modalOrderTrxRow">
          <i class="bi bi-hash"></i> Trx ID: <code id="modalPaymentTrx" style="color:var(--tp-text);"></code>
        </div>

        <div style="font-size:0.78rem;color:var(--tp-text-muted);text-align:right;" id="modalOrderDate"></div>
      </div>

      <div class="tp-modal-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="tp-btn tp-btn-outline tp-btn-sm" onclick="trackCurrentOrderInWidget()">
            <i class="bi bi-geo-alt-fill text-info"></i> Track Live
          </button>
          <button type="button" class="tp-btn tp-btn-outline tp-btn-sm" onclick="openTicketForCurrentOrder()">
            <i class="bi bi-headset text-warning"></i> Need Support?
          </button>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="#" id="modalReorderBtn" class="tp-btn tp-btn-primary tp-btn-sm">
            <i class="bi bi-controller"></i> Top Up Again
          </a>
          <button type="button" class="tp-btn tp-btn-outline tp-btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function copyOrderText(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      showCopyToast('Order ID copied: ' + text);
    });
  } else {
    showCopyToast('Order: ' + text);
  }
}

function showCopyToast(msg) {
  let toast = document.getElementById('tpCopyToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'tpCopyToast';
    toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1E293B;color:#fff;border:1px solid #8B5CF6;border-radius:30px;padding:10px 20px;font-size:0.85rem;box-shadow:0 10px 30px rgba(0,0,0,0.5);z-index:99999;transition:opacity 0.3s ease;';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.style.opacity = '1';
  setTimeout(() => { toast.style.opacity = '0'; }, 2500);
}

function openOrderDetailModal(order) {
  document.getElementById('modalOrderNumber').textContent = order.order_number || 'N/A';
  document.getElementById('modalGameName').textContent = order.game_name || 'N/A';
  document.getElementById('modalProductName').textContent = order.product_name || 'N/A';
  document.getElementById('modalPlayerId').textContent = order.player_id || 'N/A';
  const ignWrap = document.getElementById('modalPlayerIgnWrapper');
  const ignEl = document.getElementById('modalPlayerIgn');
  if (order.player_ign && order.player_ign.trim() !== '') {
    if (ignWrap) ignWrap.style.display = 'block';
    if (ignEl) ignEl.textContent = order.player_ign;
  } else {
    if (ignWrap) ignWrap.style.display = 'none';
  }
  document.getElementById('modalServerId').textContent = order.server_id || 'N/A';
  const isUsd = (order.currency || 'BDT').toUpperCase() === 'USD';
  const sym = isUsd ? '$ ' : '৳ ';
  const val = isUsd 
    ? Number(order.charge_amount || order.total_price).toFixed(2)
    : Number(order.base_amount_bdt || order.total_price).toLocaleString();
  document.getElementById('modalTotalAmount').textContent = sym + val;
  document.getElementById('modalPaymentMethod').textContent = (order.payment_method || 'N/A').toUpperCase();
  document.getElementById('modalPaymentTrx').textContent = order.payment_trx || 'N/A';
  document.getElementById('modalOrderDate').textContent = 'Placed on ' + (order.created_at || '');

  const rateBox = document.getElementById('modalRateBreakdownBox');
  if (isUsd && (Number(order.exchange_rate) > 0 || Number(order.base_amount_bdt) > 0)) {
    if (rateBox) {
      rateBox.style.display = 'block';
      const baseVal = Number(order.base_amount_bdt || order.total_price);
      document.getElementById('modalBaseBdt').textContent = '৳ ' + Math.round(baseVal).toLocaleString();
      let rateTxt = 'Exchange rate: 1 USD = ' + Number(order.exchange_rate || 0).toFixed(4) + ' BDT';
      if (Number(order.rate_buffer_percent) > 0) {
        rateTxt += ' (+' + order.rate_buffer_percent + '% buffer)';
      }
      document.getElementById('modalRateDetails').textContent = rateTxt;
    }
  } else if (rateBox) {
    rateBox.style.display = 'none';
  }

  const status = (order.order_status || 'pending').toLowerCase();
  const badgeWrap = document.getElementById('modalOrderStatusBadge');
  badgeWrap.innerHTML = `<span class="tp-status-badge tp-status-${status}">${order.order_status}</span>`;

  const stepPlaced = document.getElementById('stepPlaced');
  const stepProcessing = document.getElementById('stepProcessing');
  const stepCompleted = document.getElementById('stepCompleted');

  stepPlaced.className = 'tp-timeline-step completed';
  stepProcessing.className = 'tp-timeline-step';
  stepCompleted.className = 'tp-timeline-step';

  if (status === 'processing' || status === 'paid') {
    stepProcessing.className = 'tp-timeline-step active';
  } else if (status === 'completed') {
    stepProcessing.className = 'tp-timeline-step completed';
    stepCompleted.className = 'tp-timeline-step completed';
  }

  const reorderBtn = document.getElementById('modalReorderBtn');
  if (order.game_slug) {
    reorderBtn.href = '<?= APP_URL ?>/game.php?slug=' + encodeURIComponent(order.game_slug);
    reorderBtn.style.display = 'inline-flex';
  } else {
    reorderBtn.href = '<?= APP_URL ?>/shop.php';
  }

  const modal = new bootstrap.Modal(document.getElementById('tpOrderDetailsModal'));
  modal.show();
}

function trackCurrentOrderInWidget() {
  const orderNum = document.getElementById('modalOrderNumber').textContent.trim();
  const modalEl = document.getElementById('tpOrderDetailsModal');
  const modalInstance = bootstrap.Modal.getInstance(modalEl);
  if (modalInstance) modalInstance.hide();
  if (window.openTopUpersSupport) {
    window.openTopUpersSupport('track', orderNum);
    setTimeout(() => {
      const trackBtn = document.getElementById('fswTrackBtn');
      if (trackBtn) trackBtn.click();
    }, 250);
  }
}

function openTicketForCurrentOrder() {
  const orderNum = document.getElementById('modalOrderNumber').textContent.trim();
  const modalEl = document.getElementById('tpOrderDetailsModal');
  const modalInstance = bootstrap.Modal.getInstance(modalEl);
  if (modalInstance) modalInstance.hide();
  if (window.openTopUpersSupport) {
    window.openTopUpersSupport('ticket', orderNum);
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
