<?php
// includes/search_modal.php
// Instant Live Search Modal for Games & Products
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
?>

<!-- ============================================================
     LIVE SEARCH MODAL OVERLAY
     ============================================================ -->
<div class="rx-search-overlay" id="rxSearchOverlay" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Search Games and Products">
  <div class="rx-search-container">
    <div class="rx-search-card">
      
      <!-- Search Input Bar -->
      <div class="rx-search-header">
        <div class="rx-search-input-wrap">
          <i class="bi bi-search rx-search-input-icon"></i>
          <input type="text" 
                 id="rxLiveSearchInput" 
                 class="rx-search-input" 
                 placeholder="Search games, vouchers, diamonds, UC..." 
                 autocomplete="off" 
                 autocapitalize="off" 
                 spellcheck="false">
          
          <!-- Loading Spinner -->
          <div class="rx-search-spinner" id="rxSearchSpinner" style="display: none;">
            <div class="spinner-border spinner-border-sm" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <!-- Clear Input Button -->
          <button type="button" class="rx-search-btn-clear" id="rxSearchClearBtn" aria-label="Clear Search" style="display: none;">
            <i class="bi bi-x-circle-fill"></i>
          </button>
        </div>

        <!-- Close Button -->
        <button type="button" class="rx-search-btn-close" id="rxSearchCloseBtn" aria-label="Close Search">
          <kbd class="d-none d-sm-inline-block">ESC</kbd>
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- Search Results Body (Only displayed when user searches) -->
      <div class="rx-search-body" id="rxSearchBody" style="display: none;">
        
        <!-- Games Results Section -->
        <div class="rx-search-group" id="rxSearchGamesGroup" style="display: none;">
          <div class="rx-search-section-head">
            <span class="rx-search-section-title"><i class="bi bi-controller text-info me-1"></i> Games</span>
            <span class="rx-search-section-meta" id="rxSearchGamesCount">0 found</span>
          </div>
          <div class="rx-search-items-list" id="rxSearchGamesList"></div>
        </div>

        <!-- Products Results Section -->
        <div class="rx-search-group" id="rxSearchProductsGroup" style="display: none;">
          <div class="rx-search-section-head">
            <span class="rx-search-section-title"><i class="bi bi-box-seam text-success me-1"></i> Products & Packages</span>
            <span class="rx-search-section-meta" id="rxSearchProductsCount">0 found</span>
          </div>
          <div class="rx-search-items-list" id="rxSearchProductsList"></div>
        </div>

        <!-- Empty State -->
        <div class="rx-search-empty-state" id="rxSearchEmptyState" style="display: none;">
          <div class="rx-search-empty-icon">
            <i class="bi bi-search"></i>
          </div>
          <h5 class="rx-search-empty-title">No matches found</h5>
          <p class="rx-search-empty-desc">
            We couldn't find any games or products matching "<span class="rx-search-highlight-query" id="rxSearchEmptyQuery"></span>".
          </p>
        </div>

      </div>

    </div>
  </div>
</div>

<style>
/* ============================================================
   LIVE SEARCH MODAL STYLING (Midnight Navy / Clean Glassmorphism)
   ============================================================ */
.rx-search-overlay {
  position: fixed;
  inset: 0;
  z-index: 10050;
  background: rgba(5, 7, 14, 0.78);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 30px 16px;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1),
              visibility 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  overflow-y: auto;
}

.rx-search-overlay.is-open {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}

.rx-search-container {
  width: 100%;
  max-width: 680px;
  margin-top: 20px;
  margin-bottom: 30px;
  transform: translateY(-16px) scale(0.98);
  transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.rx-search-overlay.is-open .rx-search-container {
  transform: translateY(0) scale(1);
}

.rx-search-card {
  background: #0B0F18;
  background: linear-gradient(180deg, #101624 0%, #0B0E17 100%);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85),
              0 0 35px rgba(22, 184, 255, 0.12);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Header & Input */
.rx-search-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background: rgba(255, 255, 255, 0.02);
}

.rx-search-input-wrap {
  display: flex;
  align-items: center;
  flex: 1;
  gap: 12px;
  position: relative;
}

.rx-search-input-icon {
  font-size: 1.25rem;
  color: #16B8FF;
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.rx-search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #F8FAFC;
  font-family: 'Outfit', sans-serif;
  font-size: 1.05rem;
  font-weight: 500;
  line-height: 1.5;
  padding: 4px 0;
}

.rx-search-input::placeholder {
  color: #64748B;
  font-weight: 400;
}

.rx-search-spinner {
  display: flex;
  align-items: center;
  color: #16B8FF;
}

.rx-search-spinner .spinner-border {
  width: 1.1rem;
  height: 1.1rem;
  border-width: 0.15em;
  color: #16B8FF;
}

.rx-search-btn-clear {
  background: transparent;
  border: none;
  padding: 0;
  color: #64748B;
  cursor: pointer;
  display: flex;
  align-items: center;
  font-size: 1.1rem;
  transition: color 0.15s ease;
}

.rx-search-btn-clear:hover {
  color: #E2E8F0;
}

.rx-search-btn-close {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  color: #94A3B8;
  padding: 6px 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.15s ease;
}

.rx-search-btn-close kbd {
  background: rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 5px;
  padding: 2px 5px;
  font-size: 0.72rem;
  color: #CBD5E1;
  font-family: monospace;
}

.rx-search-btn-close:hover {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.3);
  color: #F87171;
}

/* Results Body */
.rx-search-body {
  padding: 18px 20px;
  max-height: 60vh;
  overflow-y: auto;
  overscroll-behavior: contain;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.rx-search-body::-webkit-scrollbar {
  width: 5px;
}
.rx-search-body::-webkit-scrollbar-track {
  background: transparent;
}
.rx-search-body::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.12);
  border-radius: 4px;
}

.rx-search-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.rx-search-section-title {
  font-size: 0.82rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #94A3B8;
  display: flex;
  align-items: center;
}

.rx-search-section-meta {
  font-size: 0.78rem;
  color: #64748B;
  font-weight: 500;
}

/* Results Group */
.rx-search-group {
  margin-bottom: 20px;
}

.rx-search-group:last-child {
  margin-bottom: 0;
}

.rx-search-items-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Result Item (Game or Product) */
.rx-search-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  color: #E2E8F0;
  text-decoration: none;
  transition: all 0.15s ease;
  cursor: pointer;
}

.rx-search-item:hover,
.rx-search-item.is-selected {
  background: rgba(22, 184, 255, 0.08);
  border-color: rgba(22, 184, 255, 0.3);
  color: #FFFFFF;
  transform: translateX(4px);
}

.rx-search-item-thumb {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  object-fit: cover;
  flex-shrink: 0;
  background: #111625;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.rx-search-item-fallback {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.rx-search-item-info {
  flex: 1;
  min-width: 0;
}

.rx-search-item-title {
  font-size: 0.94rem;
  font-weight: 600;
  color: #F1F5F9;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 2px;
}

.rx-search-item:hover .rx-search-item-title,
.rx-search-item.is-selected .rx-search-item-title {
  color: #16B8FF;
}

.rx-search-item-sub {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  color: #64748B;
}

.rx-search-item-game-pill {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 6px;
  padding: 1px 7px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #94A3B8;
}

.rx-search-item-platform-pill {
  background: rgba(22, 184, 255, 0.1);
  color: #16B8FF;
  border-radius: 6px;
  padding: 1px 7px;
  font-size: 0.72rem;
  font-weight: 600;
}

.rx-search-item-price-wrap {
  text-align: right;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.rx-search-item-price {
  font-family: 'Rajdhani', sans-serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #10B981;
  line-height: 1;
}

.rx-search-item-badge {
  background: rgba(16, 185, 129, 0.12);
  color: #34D399;
  border-radius: 6px;
  padding: 2px 7px;
  font-size: 0.72rem;
  font-weight: 600;
}

.rx-search-item-arrow {
  color: #64748B;
  font-size: 1.1rem;
  transition: transform 0.15s ease, color 0.15s ease;
  margin-left: 6px;
}

.rx-search-item:hover .rx-search-item-arrow,
.rx-search-item.is-selected .rx-search-item-arrow {
  color: #16B8FF;
  transform: translateX(3px);
}

/* Empty State */
.rx-search-empty-state {
  text-align: center;
  padding: 30px 16px;
}

.rx-search-empty-icon {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #64748B;
  margin-bottom: 12px;
}

.rx-search-empty-title {
  color: #E2E8F0;
  font-size: 1.05rem;
  font-weight: 600;
  margin-bottom: 4px;
}

.rx-search-empty-desc {
  color: #64748B;
  font-size: 0.85rem;
  max-width: 380px;
  margin: 0 auto;
}

.rx-search-highlight-query {
  color: #16B8FF;
  font-weight: 600;
}

/* Mobile Adjustments */
@media (max-width: 576px) {
  .rx-search-overlay {
    padding: 12px 8px;
  }
  .rx-search-container {
    margin-top: 6px;
  }
  .rx-search-header {
    padding: 12px 14px;
  }
  .rx-search-input {
    font-size: 0.95rem;
  }
  .rx-search-body {
    padding: 14px 14px;
    max-height: 70vh;
  }
}

/* Light Mode Live Search Modal */
html.light-mode .rx-search-card {
  background: #ffffff;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18), 0 0 25px rgba(22, 184, 255, 0.08);
}
html.light-mode .rx-search-header {
  background: rgba(0, 0, 0, 0.02);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
html.light-mode .rx-search-input {
  color: #0f172a;
}
html.light-mode .rx-search-input::placeholder {
  color: #94a3b8;
}
html.light-mode .rx-search-btn-close {
  background: rgba(0, 0, 0, 0.04);
  color: #64748b;
  border-color: rgba(0, 0, 0, 0.08);
}
html.light-mode .rx-search-btn-close:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}
html.light-mode .rx-search-btn-close kbd {
  background: rgba(0, 0, 0, 0.06);
  color: #64748b;
  border-color: rgba(0, 0, 0, 0.1);
}
html.light-mode .rx-search-body {
  border-top: 1px solid rgba(0, 0, 0, 0.06);
}
html.light-mode .rx-search-section-title {
  color: #64748b;
}
html.light-mode .rx-search-section-meta {
  color: #94a3b8;
}
html.light-mode .rx-search-item {
  background: rgba(0, 0, 0, 0.02);
  border: 1px solid rgba(0, 0, 0, 0.04);
}
html.light-mode .rx-search-item:hover,
html.light-mode .rx-search-item.is-selected {
  background: rgba(7, 155, 232, 0.06);
  border-color: rgba(7, 155, 232, 0.25);
}
html.light-mode .rx-search-item-name {
  color: #0f172a;
}
html.light-mode .rx-search-item-sub {
  color: #64748b;
}
html.light-mode .rx-search-empty-icon {
  background: rgba(0, 0, 0, 0.04);
  border-color: rgba(0, 0, 0, 0.08);
  color: #94a3b8;
}
html.light-mode .rx-search-empty-title {
  color: #0f172a;
}
html.light-mode .rx-search-empty-desc {
  color: #64748b;
}
</style>

<script>
// ============================================================
// LIVE SEARCH CLIENT-SIDE LOGIC
// ============================================================
(function() {
  const overlay     = document.getElementById('rxSearchOverlay');
  const input       = document.getElementById('rxLiveSearchInput');
  const spinner     = document.getElementById('rxSearchSpinner');
  const clearBtn    = document.getElementById('rxSearchClearBtn');
  const closeBtn    = document.getElementById('rxSearchCloseBtn');
  const searchBody  = document.getElementById('rxSearchBody');
  const gamesGroup  = document.getElementById('rxSearchGamesGroup');
  const gamesList   = document.getElementById('rxSearchGamesList');
  const gamesCount  = document.getElementById('rxSearchGamesCount');
  const prodsGroup  = document.getElementById('rxSearchProductsGroup');
  const prodsList   = document.getElementById('rxSearchProductsList');
  const prodsCount  = document.getElementById('rxSearchProductsCount');
  const emptyState  = document.getElementById('rxSearchEmptyState');
  const emptyQuery  = document.getElementById('rxSearchEmptyQuery');

  const searchApiUrl = '<?= APP_URL ?>/ajax/query.php';
  let debounceTimer = null;
  let activeFetchAbort = null;
  let selectedIndex = -1;

  // 1. Open / Close Modal
  function openSearch() {
    if (!overlay) return;
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    selectedIndex = -1;
    setTimeout(() => {
      if (input) {
        input.focus();
        input.select();
      }
    }, 50);
  }

  function closeSearch() {
    if (!overlay) return;
    overlay.classList.remove('is-open');
    document.body.style.overflow = '';
    selectedIndex = -1;
  }

  // Attach Open Listeners to all trigger buttons
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.rx-search-open-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        openSearch();
      });
    });
  });

  // Close handlers
  if (closeBtn) closeBtn.addEventListener('click', closeSearch);
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeSearch();
    });
  }

  // Global Keyboard Shortcuts (Esc to close, / or Ctrl+K to open)
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && overlay && overlay.classList.contains('is-open')) {
      closeSearch();
      return;
    }

    // Ctrl+K / Cmd+K to trigger search anywhere
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      if (overlay && overlay.classList.contains('is-open')) {
        closeSearch();
      } else {
        openSearch();
      }
      return;
    }

    // '/' to trigger search if not in another text input
    if (e.key === '/' && !overlay.classList.contains('is-open')) {
      const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
      if (activeTag !== 'input' && activeTag !== 'textarea') {
        e.preventDefault();
        openSearch();
      }
    }
  });

  // 2. Input Listener & Debounce
  if (input) {
    input.addEventListener('input', function() {
      const q = this.value.trim();
      if (clearBtn) clearBtn.style.display = q ? 'flex' : 'none';

      clearTimeout(debounceTimer);
      if (!q) {
        hideResults();
        return;
      }

      debounceTimer = setTimeout(() => {
        handleSearch(q);
      }, 180);
    });

    // Arrow Key Navigation in Results
    input.addEventListener('keydown', function(e) {
      if (!searchBody || searchBody.style.display === 'none') return;
      const items = searchBody.querySelectorAll('.rx-search-item');
      if (!items.length) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex = (selectedIndex + 1) % items.length;
        updateSelection(items);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex = (selectedIndex - 1 + items.length) % items.length;
        updateSelection(items);
      } else if (e.key === 'Enter') {
        if (selectedIndex >= 0 && items[selectedIndex]) {
          e.preventDefault();
          items[selectedIndex].click();
        }
      }
    });
  }

  function updateSelection(items) {
    items.forEach((item, idx) => {
      if (idx === selectedIndex) {
        item.classList.add('is-selected');
        item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      } else {
        item.classList.remove('is-selected');
      }
    });
  }

  // Clear button
  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      if (input) {
        input.value = '';
        input.focus();
        clearBtn.style.display = 'none';
        hideResults();
      }
    });
  }

  function hideResults() {
    selectedIndex = -1;
    if (spinner) spinner.style.display = 'none';
    if (searchBody) searchBody.style.display = 'none';
  }

  // 3. AJAX Search Query
  function handleSearch(query) {
    if (activeFetchAbort) {
      activeFetchAbort.abort();
    }
    activeFetchAbort = new AbortController();

    if (spinner) spinner.style.display = 'flex';
    selectedIndex = -1;

    fetch(searchApiUrl + '?q=' + encodeURIComponent(query), { signal: activeFetchAbort.signal })
      .then(res => res.json())
      .then(data => {
        if (spinner) spinner.style.display = 'none';
        if (!data.success) {
          renderResults([], [], query);
          return;
        }
        renderResults(data.games || [], data.products || [], query);
      })
      .catch(err => {
        if (err.name !== 'AbortError') {
          if (spinner) spinner.style.display = 'none';
          console.error('Search request failed', err);
        }
      });
  }

  // 4. Render Results State
  function renderResults(games, products, query) {
    if (!searchBody) return;
    searchBody.style.display = 'block';

    const hasGames = games.length > 0;
    const hasProds = products.length > 0;

    // Games Section
    if (hasGames) {
      gamesGroup.style.display = 'block';
      gamesCount.textContent = games.length + (games.length === 1 ? ' match' : ' matches');
      gamesList.innerHTML = '';

      games.forEach(g => {
        const item = document.createElement('a');
        item.className = 'rx-search-item';
        item.href = g.url;

        const thumb = g.image_url
          ? `<img src="${g.image_url}" alt="${escapeHtml(g.name)}" class="rx-search-item-thumb">`
          : `<div class="rx-search-item-fallback" style="background:${g.gradient};">${g.icon}</div>`;

        item.innerHTML = `
          ${thumb}
          <div class="rx-search-item-info">
            <div class="rx-search-item-title">${highlightMatch(escapeHtml(g.name), query)}</div>
            <div class="rx-search-item-sub">
              <span class="rx-search-item-platform-pill">${escapeHtml(g.platform)}</span>
              <span>${escapeHtml(g.category)}</span>
            </div>
          </div>
          <div class="rx-search-item-price-wrap">
            <span class="rx-search-item-badge">Instant</span>
          </div>
          <i class="bi bi-chevron-right rx-search-item-arrow"></i>
        `;
        gamesList.appendChild(item);
      });
    } else {
      gamesGroup.style.display = 'none';
      gamesList.innerHTML = '';
    }

    // Products Section
    if (hasProds) {
      prodsGroup.style.display = 'block';
      prodsCount.textContent = products.length + (products.length === 1 ? ' item' : ' items');
      prodsList.innerHTML = '';

      products.forEach(p => {
        const item = document.createElement('a');
        item.className = 'rx-search-item';
        item.href = p.url;

        const thumb = p.image_url
          ? `<img src="${p.image_url}" alt="${escapeHtml(p.name)}" class="rx-search-item-thumb">`
          : `<div class="rx-search-item-fallback" style="background:${p.gradient};">${p.icon}</div>`;

        item.innerHTML = `
          ${thumb}
          <div class="rx-search-item-info">
            <div class="rx-search-item-title">${highlightMatch(escapeHtml(p.name), query)}</div>
            <div class="rx-search-item-sub">
              <span class="rx-search-item-game-pill">${escapeHtml(p.game_name)}</span>
              ${p.diamonds ? `<span><i class="bi bi-gem me-1"></i>${p.diamonds}</span>` : ''}
            </div>
          </div>
          <div class="rx-search-item-price-wrap">
            <div class="rx-search-item-price">${escapeHtml(p.formatted_price)}</div>
            ${p.original_price ? `<small class="text-muted text-decoration-line-through" style="font-size:0.72rem;">${escapeHtml(p.original_price)}</small>` : ''}
          </div>
          <i class="bi bi-chevron-right rx-search-item-arrow"></i>
        `;
        prodsList.appendChild(item);
      });
    } else {
      prodsGroup.style.display = 'none';
      prodsList.innerHTML = '';
    }

    // Empty State
    if (!hasGames && !hasProds) {
      emptyState.style.display = 'block';
      emptyQuery.textContent = query;
    } else {
      emptyState.style.display = 'none';
    }
  }

  // Utilities
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function highlightMatch(text, query) {
    if (!query) return text;
    const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp('(' + escapedQuery + ')', 'gi');
    return text.replace(regex, '<span style="color:#16B8FF; text-decoration:underline; font-weight:700;">$1</span>');
  }

})();
</script>
