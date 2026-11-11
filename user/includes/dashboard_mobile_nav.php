<?php
// user/includes/dashboard_mobile_nav.php
// Mobile Horizontal Navigation Bar (< 992px)
if (!isset($activeNav)) $activeNav = 'dashboard';
if (!isset($user)) $user = currentUser();

$pendingBadgeCount = 0;
try {
    $pStmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status IN ('pending', 'processing', 'payment_pending', 'on_hold')");
    $pStmt->execute([$user['id']]);
    $pendingBadgeCount = (int)$pStmt->fetchColumn();
} catch (Exception $e) {
    $pendingBadgeCount = 0;
}
?>

<!-- Mobile Horizontal Navigation Bar (< 992px) -->
<div class="tp-mobile-nav-wrapper d-lg-none">
  <div class="tp-mobile-nav" id="tpMobileNav" aria-label="Customer Navigation">
    <a href="<?= APP_URL ?>/user/dashboard.php" class="tp-mobile-nav-pill <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-grid-fill"></i> Overview
    </a>
    <a href="<?= APP_URL ?>/user/orders.php" class="tp-mobile-nav-pill <?= $activeNav === 'orders' ? 'active' : '' ?>">
      <i class="bi bi-receipt"></i> Orders
      <?php if ($pendingBadgeCount > 0): ?>
        <span class="badge bg-warning text-dark rounded-pill" style="font-size:0.68rem;"><?= $pendingBadgeCount ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= APP_URL ?>/user/add-money.php" class="tp-mobile-nav-pill <?= $activeNav === 'wallet' ? 'active' : '' ?>">
      <i class="bi bi-wallet2"></i> Wallet
    </a>
    <a href="<?= APP_URL ?>/user/profile.php" class="tp-mobile-nav-pill <?= $activeNav === 'profile' ? 'active' : '' ?>">
      <i class="bi bi-person-fill"></i> Profile
    </a>
    <a href="<?= APP_URL ?>/user/profile.php#security" class="tp-mobile-nav-pill <?= $activeNav === 'security' ? 'active' : '' ?>">
      <i class="bi bi-shield-lock-fill"></i> Security
    </a>
    <a href="<?= APP_URL ?>/contact.php" class="tp-mobile-nav-pill <?= $activeNav === 'support' ? 'active' : '' ?>">
      <i class="bi bi-headset"></i> Support
    </a>
    <a href="<?= APP_URL ?>/auth/logout.php" class="tp-mobile-nav-pill text-danger">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const navContainer = document.getElementById('tpMobileNav');
  if (navContainer) {
    const activePill = navContainer.querySelector('.tp-mobile-nav-pill.active');
    if (activePill) {
      // Auto-center the active pill horizontally on mobile viewports
      const pillCenter = activePill.offsetLeft - (navContainer.clientWidth / 2) + (activePill.clientWidth / 2);
      navContainer.scrollTo({ left: Math.max(0, pillCenter), behavior: 'smooth' });
    }
  }
});
</script>
