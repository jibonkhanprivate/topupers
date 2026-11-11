<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Game Shop';
$pageDescription = 'Browse and buy top-up packages for all your favorite mobile and PC games.';

$allGames = getGames(false);
include __DIR__ . '/includes/header.php';

$gameColors = [
  'mobile-legends'=>['bg'=>'linear-gradient(135deg,#1a237e,#283593)'],
  'free-fire'=>['bg'=>'linear-gradient(135deg,#b71c1c,#c62828)'],
  'pubg-mobile'=>['bg'=>'linear-gradient(135deg,#e65100,#ef6c00)'],
  'valorant'=>['bg'=>'linear-gradient(135deg,#880e4f,#ad1457)'],
  'genshin-impact'=>['bg'=>'linear-gradient(135deg,#004d40,#00695c)'],
  'codm'=>['bg'=>'linear-gradient(135deg,#1b5e20,#2e7d32)'],
  'honor-of-kings'=>['bg'=>'linear-gradient(135deg,#4a148c,#6a1b9a)'],
  'clash-of-clans'=>['bg'=>'linear-gradient(135deg,#33691e,#558b2f)'],
  'clash-royale'=>['bg'=>'linear-gradient(135deg,#1565c0,#1976d2)'],
];
$gameIcons = [
  'mobile-legends'=>'⚔️','free-fire'=>'🔥','pubg-mobile'=>'🪖',
  'valorant'=>'🎯','genshin-impact'=>'✨','codm'=>'🔫',
  'honor-of-kings'=>'👑','clash-of-clans'=>'🏰','clash-royale'=>'🃏',
];
?>

<!-- Page Header -->
<div style="background:var(--bg-card);border-bottom:1px solid var(--border-light);padding:2.5rem 0;">
  <div class="container">
    <h1 class="section-title">Game <span>Shop</span></h1>
    <p style="color:var(--text-muted);font-size:.95rem;margin-top:.5rem;">Select a game to view all available top-up packages.</p>
  </div>
</div>

<div class="container py-5">
  <!-- Search & Filter Bar -->
  <div class="rx-card mb-4" style="padding:1rem 1.5rem;">
    <div class="row align-items-center g-3">
      <div class="col-md-6">
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-input);border:1.5px solid var(--border);border-right:0;color:var(--text-muted);">
            <i class="bi bi-search"></i>
          </span>
          <input type="text" class="form-control form-rx" id="gameSearch"
                 placeholder="Search games..." style="border-left:0;">
        </div>
      </div>
      <div class="col-md-3">
        <select class="form-select form-rx" id="platformFilter">
          <option value="">All Platforms</option>
          <option value="Mobile">Mobile</option>
          <option value="PC">PC</option>
          <option value="Mobile/PC">Mobile/PC</option>
        </select>
      </div>
      <div class="col-md-3 text-end">
        <span style="color:var(--text-muted);font-size:.85rem;">
          <i class="bi bi-controller me-1"></i><?= count($allGames) ?> Games Available
        </span>
      </div>
    </div>
  </div>

  <!-- Games Grid -->
  <div class="row g-3" id="gamesGrid">
    <?php 
      $allGames = db()->query("SELECT g.*, p.image as parent_image FROM games g LEFT JOIN games p ON g.parent_id = p.id WHERE g.is_active = 1 ORDER BY g.sort_order ASC")->fetchAll();
      foreach ($allGames as $g): 
      $gc   = $gameColors[$g['slug']] ?? ['bg'=>'linear-gradient(135deg,#111226,#1a1b2e)'];
      $icon = $gameIcons[$g['slug']] ?? '🎮';
    ?>
    <div class="col-6 col-sm-4 col-md-3 col-lg-2 game-item"
         data-name="<?= strtolower(e($g['name'])) ?>"
         data-platform="<?= e($g['platform'] ?? '') ?>">
      <a href="<?= APP_URL ?>/<?= e($g['slug']) ?>" class="game-card rx-game-card <?= ($g['is_stock_out'] ?? 0) ? 'stock-out' : '' ?>" data-game-slug="<?= e($g['slug']) ?>" data-game-id="<?= $g['id'] ?>">
        <div class="game-card-img-wrap">
          <?php 
            $displayImg = !empty($g['image']) ? $g['image'] : (!empty($g['parent_image']) ? $g['parent_image'] : '');
            if (!empty($displayImg)): 
          ?>
          <img src="<?= APP_URL ?>/<?= e($displayImg) ?>" alt="<?= e($g['name']) ?>" class="game-card-img">
          <div class="game-card-overlay"></div>
          <?php if ($g['is_stock_out'] ?? 0): ?>
          <div class="ribbon-wrapper"><div class="ribbon-red">Stock Out</div></div>
          <?php endif; ?>
          <?php if ($g['badge_text'] ?? ''): ?>
          <div class="item-badge"><?= e($g['badge_text']) ?></div>
          <?php endif; ?>
          <?php else: ?>
          <div style="width:100%;height:100%;background:<?= $gc['bg'] ?>;display:flex;align-items:center;justify-content:center;font-size:3.5rem;position:relative;">
            <?= $icon ?>
            <div class="game-card-overlay"></div>
          </div>
          <?php endif; ?>
        </div>
        <div class="game-card-body text-center">
          <div class="game-card-title"><?= e($g['name']) ?></div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- No results -->
  <div id="noResults" style="display:none;text-align:center;padding:4rem 0;">
    <div style="font-size:3rem;margin-bottom:1rem;">🎮</div>
    <h5 style="color:var(--text-muted);">No games found</h5>
    <p style="font-size:.85rem;color:var(--text-dim);">Try a different search term</p>
  </div>
</div>

<script>
const gameSearch = document.getElementById('gameSearch');
const platformFilter = document.getElementById('platformFilter');
const items = document.querySelectorAll('.game-item');
const noResults = document.getElementById('noResults');

function filterGames() {
  const q = gameSearch.value.toLowerCase();
  const p = platformFilter.value.toLowerCase();
  let visible = 0;
  items.forEach(item => {
    const match = item.dataset.name.includes(q) && (!p || item.dataset.platform.toLowerCase().includes(p));
    item.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  noResults.style.display = visible === 0 ? 'block' : 'none';
}

gameSearch.addEventListener('input', filterGames);
platformFilter.addEventListener('change', filterGames);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
