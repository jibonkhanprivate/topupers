<?php
// user/dashboard.php
// TOPUPERS — Customer Profile & Account Dashboard
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();
$pageTitle = 'Customer Dashboard';
$pageDescription = 'Manage your TOPUPERS account, track top-up orders, and view wallet balance.';

$msg = '';
$error = '';

// Handle Profile Updates & Password Changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf   = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrf)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $phone    = sanitize($_POST['phone'] ?? '');

        // Validation
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $error = 'Full name must be between 2 and 100 characters.';
        } elseif (!empty($phone) && !preg_match('/^[0-9+\s\-()]{7,20}$/', $phone)) {
            $error = 'Please enter a valid phone number.';
        } else {
            // Handle optional avatar upload
            $avatarUpdated = false;
            $newAvatarName = null;

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['avatar'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'File upload failed. Error code: ' . $file['error'];
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $error = 'Avatar file size must not exceed 2MB.';
                } else {
                    $mime = mime_content_type($file['tmp_name']);
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($mime, $allowedMimes)) {
                        $error = 'Invalid image type. Please upload a JPG, PNG, or WebP image.';
                    } else {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            $ext = 'jpg';
                        }
                        $newAvatarName = 'avatar_' . $user['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetDir = APP_ROOT . '/uploads/avatars';
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        $targetPath = $targetDir . '/' . $newAvatarName;

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Remove previous custom avatar if present
                            if (!empty($user['avatar']) && $user['avatar'] !== 'default.png') {
                                $oldPath = $targetDir . '/' . basename($user['avatar']);
                                if (file_exists($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                            $avatarUpdated = true;
                        } else {
                            $error = 'Failed to save avatar image to server.';
                        }
                    }
                }
            }

            if (empty($error)) {
                if ($avatarUpdated) {
                    $stmt = db()->prepare('UPDATE users SET full_name = ?, phone = ?, avatar = ? WHERE id = ?');
                    $stmt->execute([$fullName, $phone, $newAvatarName, $user['id']]);
                } else {
                    $stmt = db()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
                    $stmt->execute([$fullName, $phone, $user['id']]);
                }
                $msg = 'Profile information updated successfully!';
                
                // Refresh user record
                $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$user['id']]);
                $user = $stmt->fetch();
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation password do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hash, $user['id']]);
            $msg = 'Password changed successfully! Keep your account secure.';
            
            // Refresh user record
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
        }
    }
}

// ------------------------------------------------------------
// Real Database Queries for Dashboard Statistics & Recent Orders
// ------------------------------------------------------------
$userId = (int)$user['id'];

// 1. Order Counts
$cntStmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$cntStmt->execute([$userId]);
$totalOrders = (int)$cntStmt->fetchColumn();

$compStmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status = "completed"');
$compStmt->execute([$userId]);
$completedOrders = (int)$compStmt->fetchColumn();

$pendStmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status IN ("pending", "processing", "payment_pending", "on_hold")');
$pendStmt->execute([$userId]);
$pendingOrders = (int)$pendStmt->fetchColumn();

// 2. Total Amount Spent (Completed Orders)
$spentStmt = db()->prepare('SELECT SUM(total_price) FROM orders WHERE user_id = ? AND order_status = "completed"');
$spentStmt->execute([$userId]);
$totalSpent = (float)($spentStmt->fetchColumn() ?: 0);

// 3. Recent Orders (Latest 6)
$orderStmt = db()->prepare('
    SELECT o.*, p.name AS product_name, g.name AS game_name, g.slug AS game_slug, g.image AS game_image
    FROM orders o
    LEFT JOIN products p ON p.id = o.product_id
    LEFT JOIN games g ON g.id = o.game_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 6
');
$orderStmt->execute([$userId]);
$recentOrders = $orderStmt->fetchAll();

// Page-specific CSS for TOPUPERS Dashboard
$extraCss = '<link rel="stylesheet" href="' . APP_URL . '/assets/css/customer-dashboard.css?v=' . filemtime(__DIR__ . '/../assets/css/customer-dashboard.css') . '">';

include __DIR__ . '/../includes/header.php';

$avatarUrl = userAvatarUrl($user['avatar'] ?? null);
$userInitials = userInitials($user['full_name'] ?: $user['username']);
$activeNav = 'dashboard';
?>

<div class="tp-dashboard-page">
  <!-- Subtle Ambient Glow -->
  <div class="tp-ambient-glow"></div>

  <div class="container py-4 py-lg-5 position-relative" style="z-index: 1;">

    <!-- Top Dashboard Header -->
    <div class="tp-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="tp-brand-badge mb-2">
          <i class="bi bi-controller"></i> TOPUPERS GAMING PORTAL
        </div>
        <h1 class="tp-page-title">
          Customer Dashboard
        </h1>
        <p class="tp-page-subtitle">
          Welcome back, <strong><?= e($user['full_name'] ?: $user['username']) ?></strong>! Here is an overview of your top-up account and activities.
        </p>
      </div>

      <div class="tp-header-actions">
        <button type="button" class="tp-btn tp-btn-outline tp-btn-sm" onclick="if(window.openTopUpersSupport){ window.openTopUpersSupport('track'); } else { window.location.href='<?= APP_URL ?>/order-success.php'; }">
          <i class="bi bi-search"></i> Track Order
        </button>
        <a href="<?= APP_URL ?>/user/add-money.php" class="tp-btn tp-btn-warning tp-btn-sm">
          <i class="bi bi-wallet2"></i> Add Funds
        </a>
        <a href="<?= APP_URL ?>/shop.php" class="tp-btn tp-btn-primary tp-btn-sm">
          <i class="bi bi-plus-circle-fill"></i> New Top Up
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
      <div class="tp-alert tp-alert-success" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= e($msg) ?></div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="tp-alert tp-alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= e($error) ?></div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Mobile Navigation Bar (< 992px) -->
    <?php include __DIR__ . '/includes/dashboard_mobile_nav.php'; ?>

    <div class="row g-4 mt-1">

      <!-- Desktop Sidebar (>= 992px) -->
      <?php include __DIR__ . '/includes/dashboard_sidebar.php'; ?>

      <!-- Main Dashboard Column -->
      <div class="col-lg-9">

        <!-- 1. Customer Profile Overview Header -->
        <section class="tp-profile-hero" aria-label="Customer Profile Summary">
          <div class="row align-items-center g-3">
            <div class="col-auto">
              <div class="tp-avatar-wrapper">
                <?php if ($avatarUrl): ?>
                  <img src="<?= e($avatarUrl) ?>" alt="<?= e($user['full_name'] ?: $user['username']) ?>" class="tp-avatar-img">
                <?php else: ?>
                  <div class="tp-avatar-initials"><?= e($userInitials) ?></div>
                <?php endif; ?>
                <button type="button" class="tp-avatar-edit-btn" data-bs-toggle="modal" data-bs-target="#tpEditProfileModal" title="Change Avatar">
                  <i class="bi bi-camera-fill"></i>
                </button>
              </div>
            </div>

            <div class="col">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h2 class="tp-hero-name">
                  <?= e($user['full_name'] ?: $user['username']) ?>
                </h2>

                <?php if ((int)($user['is_verified'] ?? 0) === 1): ?>
                  <span class="tp-badge-verified">
                    <i class="bi bi-patch-check-fill"></i> Verified Gamer
                  </span>
                <?php else: ?>
                  <span class="tp-badge-unverified" title="Account registered">
                    <i class="bi bi-person-check"></i> Standard Member
                  </span>
                <?php endif; ?>
              </div>

              <div class="tp-profile-meta">
                <span class="tp-meta-item">
                  <i class="bi bi-at"></i> <?= e($user['username']) ?>
                </span>
                <span class="tp-meta-item">
                  <i class="bi bi-envelope"></i> <?= e($user['email']) ?>
                </span>
                <?php if (!empty($user['phone'])): ?>
                  <span class="tp-meta-item">
                    <i class="bi bi-telephone"></i> <?= e($user['phone']) ?>
                  </span>
                <?php endif; ?>
                <span class="tp-meta-item">
                  <i class="bi bi-calendar3"></i> Member since <?= date('M Y', strtotime($user['created_at'])) ?>
                </span>
              </div>
            </div>

            <div class="col-12 col-md-auto text-md-end mt-3 mt-md-0 tp-hero-actions">
              <button type="button" class="tp-btn tp-btn-outline tp-btn-sm" data-bs-toggle="modal" data-bs-target="#tpEditProfileModal">
                <i class="bi bi-pencil-square"></i> Edit Profile
              </button>
              <a href="<?= APP_URL ?>/user/profile.php#security" class="tp-btn tp-btn-outline tp-btn-sm">
                <i class="bi bi-gear-fill"></i> Settings
              </a>
            </div>
          </div>
        </section>

        <!-- 2. Dashboard Statistics Cards (4 Cards) -->
        <section class="tp-stats-grid" aria-label="Account Statistics">
          <!-- Card 1: Wallet Balance -->
          <div class="tp-stat-card card-wallet">
            <div class="tp-stat-top">
              <span class="tp-stat-label">Wallet Balance</span>
              <div class="tp-stat-icon icon-wallet">
                <i class="bi bi-wallet2"></i>
              </div>
            </div>
            <div>
              <div class="tp-stat-value" style="color:#FBBF24;">
                <?= money($user['wallet_balance'] ?? 0) ?>
              </div>
              <div class="tp-stat-subtext">
                <span>Instant Top-Up Funds</span>
                <a href="<?= APP_URL ?>/user/add-money.php">+ Deposit</a>
              </div>
            </div>
          </div>

          <!-- Card 2: Total Orders -->
          <div class="tp-stat-card card-total">
            <div class="tp-stat-top">
              <span class="tp-stat-label">Total Orders</span>
              <div class="tp-stat-icon icon-total">
                <i class="bi bi-bag-check-fill"></i>
              </div>
            </div>
            <div>
              <div class="tp-stat-value">
                <?= $totalOrders ?>
              </div>
              <div class="tp-stat-subtext">
                <span>All time orders</span>
                <a href="<?= APP_URL ?>/user/orders.php">History →</a>
              </div>
            </div>
          </div>

          <!-- Card 3: Completed Orders -->
          <div class="tp-stat-card card-completed">
            <div class="tp-stat-top">
              <span class="tp-stat-label">Completed Orders</span>
              <div class="tp-stat-icon icon-completed">
                <i class="bi bi-patch-check-fill"></i>
              </div>
            </div>
            <div>
              <div class="tp-stat-value" style="color:#34D399;">
                <?= $completedOrders ?>
              </div>
              <div class="tp-stat-subtext">
                <span>Total Spent: <?= money($totalSpent) ?></span>
              </div>
            </div>
          </div>

          <!-- Card 4: Pending / Processing Orders -->
          <div class="tp-stat-card card-pending">
            <div class="tp-stat-top">
              <span class="tp-stat-label">Pending / Processing</span>
              <div class="tp-stat-icon icon-pending">
                <i class="bi bi-hourglass-split"></i>
              </div>
            </div>
            <div>
              <div class="tp-stat-value" style="color:var(--tp-blue);">
                <?= $pendingOrders ?>
              </div>
              <div class="tp-stat-subtext">
                <span>Awaiting delivery</span>
                <?php if ($pendingOrders > 0): ?>
                  <a href="<?= APP_URL ?>/user/orders.php?status=pending">Track →</a>
                <?php else: ?>
                  <span class="text-success">All Clear</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <!-- 3. Recent Orders Section -->
        <section class="tp-card" aria-label="Recent Orders">
          <div class="tp-card-header">
            <div class="d-flex align-items-center gap-2">
              <h3 class="tp-card-title">
                <i class="bi bi-clock-history"></i> Recent Top-Up Orders
              </h3>
              <span class="badge" style="background:rgba(139,92,246,0.15);color:var(--tp-purple-light);font-size:0.75rem;">
                Latest <?= count($recentOrders) ?>
              </span>
            </div>

            <a href="<?= APP_URL ?>/user/orders.php" class="tp-btn tp-btn-outline tp-btn-sm">
              View All Orders <i class="bi bi-arrow-right"></i>
            </a>
          </div>

          <?php if (empty($recentOrders)): ?>
            <!-- Empty State -->
            <div class="tp-empty-state">
              <div class="tp-empty-icon">
                <i class="bi bi-controller"></i>
              </div>
              <h4 class="tp-empty-title">No Orders Placed Yet</h4>
              <p class="tp-empty-desc">
                You haven't ordered any game diamonds or top-ups yet. Explore our games catalog and get instant delivery in minutes!
              </p>
              <a href="<?= APP_URL ?>/shop.php" class="tp-btn tp-btn-primary">
                <i class="bi bi-bag-plus"></i> Browse Games Catalog
              </a>
            </div>
          <?php else: ?>
            <!-- Desktop Table View -->
            <div class="tp-table-wrap d-none d-md-block">
              <table class="tp-table" aria-label="Recent Orders Table">
                <thead>
                  <tr>
                    <th>Order #</th>
                    <th>Game & Package</th>
                    <th>Player ID</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Order Status</th>
                    <th>Date</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentOrders as $order): ?>
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
                          <?= e($order['product_name'] ?: 'Top-Up Package') ?>
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
                          <?= money($order['total_price']) ?>
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
                          <?= timeAgo($order['created_at']) ?>
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

            <!-- Mobile Card View (For Phones) -->
            <div class="d-md-none">
              <?php foreach ($recentOrders as $order): ?>
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
                        <?= money($order['total_price']) ?>
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
          <?php endif; ?>
        </section>

        <!-- 4. Quick Actions Grid -->
        <section class="tp-card" aria-label="Quick Actions">
          <div class="tp-card-header">
            <h3 class="tp-card-title">
              <i class="bi bi-lightning-charge-fill" style="color:var(--tp-cyan);"></i> Quick Shortcuts & Services
            </h3>
          </div>

          <div class="tp-actions-grid">
            <a href="<?= APP_URL ?>/shop.php" class="tp-action-card">
              <div class="tp-action-icon">
                <i class="bi bi-controller"></i>
              </div>
              <h4 class="tp-action-title">Start a Top Up</h4>
              <p class="tp-action-desc">Order Diamonds & UC</p>
            </a>

            <a href="#" class="tp-action-card" data-bs-toggle="modal" data-bs-target="#tpTrackOrderModal">
              <div class="tp-action-icon">
                <i class="bi bi-search"></i>
              </div>
              <h4 class="tp-action-title">Track an Order</h4>
              <p class="tp-action-desc">Check live delivery status</p>
            </a>

            <a href="<?= APP_URL ?>/user/orders.php" class="tp-action-card">
              <div class="tp-action-icon">
                <i class="bi bi-receipt"></i>
              </div>
              <h4 class="tp-action-title">Order History</h4>
              <p class="tp-action-desc">View all placed orders</p>
            </a>

            <a href="<?= APP_URL ?>/user/add-money.php" class="tp-action-card">
              <div class="tp-action-icon">
                <i class="bi bi-wallet2"></i>
              </div>
              <h4 class="tp-action-title">Add Wallet Funds</h4>
              <p class="tp-action-desc">bKash, Nagad, Rocket</p>
            </a>

            <a href="#" class="tp-action-card" data-bs-toggle="modal" data-bs-target="#tpEditProfileModal">
              <div class="tp-action-icon">
                <i class="bi bi-person-gear"></i>
              </div>
              <h4 class="tp-action-title">Edit Profile</h4>
              <p class="tp-action-desc">Update name & avatar</p>
            </a>

            <a href="<?= APP_URL ?>/user/profile.php#security" class="tp-action-card">
              <div class="tp-action-icon">
                <i class="bi bi-shield-check"></i>
              </div>
              <h4 class="tp-action-title">Account Security</h4>
              <p class="tp-action-desc">Update secret password</p>
            </a>

            <a href="<?= APP_URL ?>/contact.php" class="tp-action-card">
              <div class="tp-action-icon">
                <i class="bi bi-headset"></i>
              </div>
              <h4 class="tp-action-title">Customer Support</h4>
              <p class="tp-action-desc">Get instant help 24/7</p>
            </a>

            <a href="<?= APP_URL ?>/faq.php" class="tp-action-card">
              <div class="tp-action-icon">
                <i class="bi bi-question-circle"></i>
              </div>
              <h4 class="tp-action-title">Help & FAQs</h4>
              <p class="tp-action-desc">Guides and top-up rules</p>
            </a>
          </div>
        </section>

      </div>
    </div>

  </div>
</div>

<!-- ============================================================
     MODAL: EDIT PROFILE
     ============================================================ -->
<div class="modal fade" id="tpEditProfileModal" tabindex="-1" aria-labelledby="tpEditProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content tp-modal-content">
      <div class="tp-modal-header">
        <h3 class="tp-modal-title" id="tpEditProfileModalLabel">
          <i class="bi bi-person-gear text-primary"></i> Edit Profile Information
        </h3>
        <button type="button" class="tp-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <form method="POST" action="<?= APP_URL ?>/user/dashboard.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        <?= csrfField() ?>

        <div class="tp-modal-body">
          <!-- Avatar Preview & Upload -->
          <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:var(--tp-surface);border:1px solid var(--tp-border);">
            <div class="tp-avatar-wrapper" style="width:64px;height:64px;">
              <?php if ($avatarUrl): ?>
                <img src="<?= e($avatarUrl) ?>" id="avatarPreviewImg" alt="Avatar" class="tp-avatar-img">
              <?php else: ?>
                <div class="tp-avatar-initials" id="avatarPreviewInitials" style="font-size:1.6rem;"><?= e($userInitials) ?></div>
              <?php endif; ?>
            </div>
            <div class="flex-grow-1">
              <label class="tp-form-label mb-1" for="avatarInput">Profile Picture</label>
              <input type="file" id="avatarInput" name="avatar" class="form-control form-control-sm" accept="image/png, image/jpeg, image/webp" onchange="previewAvatar(this)" style="background:var(--tp-card);color:var(--tp-text);border-color:var(--tp-border);font-size:0.8rem;">
              <small style="font-size:0.72rem;color:var(--tp-text-muted);">Max 2MB. JPG, PNG, or WebP.</small>
            </div>
          </div>

          <div class="mb-3">
            <label class="tp-form-label" for="edit_full_name">Full Name <span class="text-danger">*</span></label>
            <input type="text" id="edit_full_name" name="full_name" class="tp-input" value="<?= e($user['full_name'] ?? '') ?>" required maxlength="100">
          </div>

          <div class="mb-3">
            <label class="tp-form-label" for="edit_phone">Phone Number</label>
            <input type="tel" id="edit_phone" name="phone" class="tp-input" value="<?= e($user['phone'] ?? '') ?>" placeholder="01XXXXXXXXX" maxlength="20">
            <small style="font-size:0.75rem;color:var(--tp-text-muted);">Used for order verification & SMS updates.</small>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label class="tp-form-label">Username</label>
              <input type="text" class="tp-input" value="<?= e($user['username']) ?>" readonly>
            </div>
            <div class="col-6">
              <label class="tp-form-label">Email</label>
              <input type="text" class="tp-input" value="<?= e($user['email']) ?>" readonly>
            </div>
          </div>
          <small style="font-size:0.72rem;color:var(--tp-text-dim);display:block;margin-top:0.4rem;">
            <i class="bi bi-info-circle"></i> Username and Email cannot be modified for account security.
          </small>
        </div>

        <div class="tp-modal-footer">
          <button type="button" class="tp-btn tp-btn-outline tp-btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm">
            <i class="bi bi-save"></i> Save Changes
          </button>
        </div>
      </form>
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

<!-- ============================================================
     MODAL: TRACK ORDER
     ============================================================ -->
<div class="modal fade" id="tpTrackOrderModal" tabindex="-1" aria-labelledby="tpTrackOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content tp-modal-content">
      <div class="tp-modal-header">
        <h3 class="tp-modal-title" id="tpTrackOrderModalLabel">
          <i class="bi bi-search text-primary"></i> Track Order Status
        </h3>
        <button type="button" class="tp-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="tp-modal-body">
        <p style="font-size:0.88rem;color:var(--tp-text-secondary);margin-bottom:1.2rem;">
          Enter your Order Number (e.g. <code>TP123456</code>) to quickly look up your diamond or UC top-up progress.
        </p>

        <form onsubmit="handleTrackOrder(event)">
          <div class="mb-3">
            <label class="tp-form-label" for="trackOrderInput">Order Number</label>
            <input type="text" id="trackOrderInput" class="tp-input" placeholder="e.g. TP..." required style="text-transform:uppercase;">
          </div>
          <button type="submit" class="tp-btn tp-btn-primary w-100">
            <i class="bi bi-search"></i> Check Order
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript for Interactive Dashboard Features -->
<script>
function copyOrderText(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      showCopyToast('Order ID copied to clipboard: ' + text);
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
  document.getElementById('modalTotalAmount').textContent = '৳' + Number(order.total_price).toLocaleString();
  document.getElementById('modalPaymentMethod').textContent = (order.payment_method || 'N/A').toUpperCase();
  document.getElementById('modalPaymentTrx').textContent = order.payment_trx || 'N/A';
  document.getElementById('modalOrderDate').textContent = 'Placed on ' + (order.created_at || '');

  // Badges & Timeline
  const status = (order.order_status || 'pending').toLowerCase();
  const badgeWrap = document.getElementById('modalOrderStatusBadge');
  badgeWrap.innerHTML = `<span class="tp-status-badge tp-status-${status}">${order.order_status}</span>`;

  // Step Timeline
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

  // Reorder button link
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

function handleTrackOrder(e) {
  e.preventDefault();
  const val = document.getElementById('trackOrderInput').value.trim();
  if (val) {
    const modalEl = document.getElementById('tpTrackOrderModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (modalInstance) modalInstance.hide();
    if (window.openTopUpersSupport) {
      window.openTopUpersSupport('track', val);
      setTimeout(() => {
        const trackBtn = document.getElementById('fswTrackBtn');
        if (trackBtn) trackBtn.click();
      }, 250);
    } else {
      window.location.href = '<?= APP_URL ?>/order-success.php?order=' + encodeURIComponent(val);
    }
  }
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

function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      let img = document.getElementById('avatarPreviewImg');
      const initials = document.getElementById('avatarPreviewInitials');
      if (!img) {
        img = document.createElement('img');
        img.id = 'avatarPreviewImg';
        img.className = 'tp-avatar-img';
        if (initials) initials.parentNode.replaceChild(img, initials);
      }
      img.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
