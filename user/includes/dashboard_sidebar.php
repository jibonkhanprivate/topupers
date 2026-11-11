<?php
// user/includes/dashboard_sidebar.php
// Desktop sticky sidebar for TOPUPERS customer section (>= 992px)
if (!isset($activeNav)) $activeNav = 'dashboard';
if (!isset($user)) $user = currentUser();

$sidebarAvatarUrl = userAvatarUrl($user['avatar'] ?? null);
$sidebarInitials  = userInitials($user['full_name'] ?: $user['username']);

$pendingBadgeCount = 0;
try {
    $pStmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status IN ('pending', 'processing', 'payment_pending', 'on_hold')");
    $pStmt->execute([$user['id']]);
    $pendingBadgeCount = (int)$pStmt->fetchColumn();
} catch (Exception $e) {
    $pendingBadgeCount = 0;
}
?>

<!-- Desktop Account Sidebar (>= 992px) -->
<div class="col-lg-3 d-none d-lg-block">
  <div class="sticky-top" style="top: 90px; z-index: 10;">
    <aside class="tp-sidebar" aria-label="Customer Account Navigation">
      <div class="tp-sidebar-header">
        <div class="tp-sidebar-avatar">
          <?php if ($sidebarAvatarUrl): ?>
            <img src="<?= e($sidebarAvatarUrl) ?>" alt="<?= e($user['full_name'] ?: $user['username']) ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
          <?php else: ?>
            <span><?= e($sidebarInitials) ?></span>
          <?php endif; ?>
        </div>
        <div style="overflow:hidden;">
          <div class="tp-sidebar-user-name" title="<?= e($user['full_name'] ?: $user['username']) ?>">
            <?= e($user['full_name'] ?: $user['username']) ?>
          </div>
          <div class="tp-sidebar-user-role">
            @<?= e($user['username']) ?>
          </div>
        </div>
      </div>

      <nav>
        <ul class="tp-nav-list">
          <li class="tp-nav-item">
            <a href="<?= APP_URL ?>/user/dashboard.php" class="tp-nav-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
              <span class="tp-nav-link-content">
                <i class="bi bi-grid-fill"></i>
                <span>Overview</span>
              </span>
            </a>
          </li>

          <li class="tp-nav-item">
            <a href="<?= APP_URL ?>/user/orders.php" class="tp-nav-link <?= $activeNav === 'orders' ? 'active' : '' ?>">
              <span class="tp-nav-link-content">
                <i class="bi bi-receipt"></i>
                <span>My Orders</span>
              </span>
              <?php if ($pendingBadgeCount > 0): ?>
                <span class="tp-nav-badge" title="Pending / In Progress"><?= $pendingBadgeCount ?></span>
              <?php endif; ?>
            </a>
          </li>

          <li class="tp-nav-item">
            <a href="<?= APP_URL ?>/user/add-money.php" class="tp-nav-link <?= $activeNav === 'wallet' ? 'active' : '' ?>">
              <span class="tp-nav-link-content">
                <i class="bi bi-wallet2"></i>
                <span>Wallet & Payments</span>
              </span>
              <span class="tp-nav-badge" style="background:rgba(245,158,11,0.15);color:#FBBF24;">
                <?= money($user['wallet_balance'] ?? 0) ?>
              </span>
            </a>
          </li>

          <li class="tp-nav-item">
            <a href="<?= APP_URL ?>/user/profile.php" class="tp-nav-link <?= $activeNav === 'profile' ? 'active' : '' ?>">
              <span class="tp-nav-link-content">
                <i class="bi bi-person-fill"></i>
                <span>Personal Profile</span>
              </span>
            </a>
          </li>

          <li class="tp-nav-item">
            <a href="<?= APP_URL ?>/user/profile.php#security" class="tp-nav-link <?= $activeNav === 'security' ? 'active' : '' ?>">
              <span class="tp-nav-link-content">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Security & Settings</span>
              </span>
            </a>
          </li>

          <li class="tp-nav-item">
            <a href="<?= APP_URL ?>/contact.php" class="tp-nav-link <?= $activeNav === 'support' ? 'active' : '' ?>">
              <span class="tp-nav-link-content">
                <i class="bi bi-headset"></i>
                <span>Help & Support</span>
              </span>
            </a>
          </li>

          <li style="margin: 0.8rem 0; border-top: 1px solid var(--tp-border);"></li>

          <li class="tp-nav-item">
            <a href="<?= APP_URL ?>/auth/logout.php" class="tp-nav-link nav-logout">
              <span class="tp-nav-link-content">
                <i class="bi bi-box-arrow-right"></i>
                <span>Log Out</span>
              </span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>
  </div>
</div>
