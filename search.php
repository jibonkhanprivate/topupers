<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Search';
$pageDescription = 'Search for your favorite games and vouchers on TopUpers.';

include __DIR__ . '/includes/header.php';
?>
<!-- Page Header -->
<div style="background:var(--bg-card);border-bottom:1px solid var(--border-light);padding:2.5rem 0;">
  <div class="container text-center">
    <h1 class="section-title">Search <span>Games</span></h1>
  </div>
</div>
<div class="container py-5 text-center" style="min-height: 40vh;">
  <div class="row justify-content-center">
    <div class="col-md-6 mb-4">
      <form action="<?= APP_URL ?>/shop.php" method="GET">
         <div class="input-group">
           <input type="text" name="q" class="form-control form-rx" placeholder="Search games..." style="background: var(--bg-input); border: 1px solid var(--border); color: var(--text-primary);">
           <button type="submit" class="btn btn-rx"><i class="bi bi-search"></i> Search Base</button>
         </div>
      </form>
    </div>
  </div>
  <a href="<?= APP_URL ?>/shop.php" class="btn-rx-outline mt-3">Browse All Games</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
