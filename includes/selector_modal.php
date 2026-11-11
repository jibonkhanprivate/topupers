<!-- TOPUPERS Game Region & Category Selector Modal -->
<div class="selector-modal-backdrop" id="topupersSelectorModal" aria-hidden="true">
  <div class="selector-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="selectorModalTitle">
    <!-- Modal Header -->
    <div class="selector-modal-header">
      <div class="selector-header-left">
        <img src="<?= APP_URL ?>/assets/img/game-placeholder.svg" alt="Game Icon" class="selector-game-icon" id="selectorGameIcon">
        <div class="selector-header-titles">
          <h3 class="selector-modal-title" id="selectorModalTitle">SELECT REGION</h3>
          <p class="selector-modal-subtitle" id="selectorModalSubtitle">
            <i class="bi bi-globe" id="selectorModalIcon"></i> 
            <span id="selectorModalSubtitleText">Select your region</span>
          </p>
        </div>
      </div>
      <button type="button" class="selector-modal-close" id="selectorModalClose" aria-label="Close modal">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="selector-modal-body" id="selectorModalBody">
      <div class="selector-options-grid" id="selectorOptionsGrid">
        <!-- Option Cards rendered via JS -->
      </div>
    </div>

    <!-- Modal Footer with TOPUPERS Watermark -->
    <div class="selector-modal-footer">
      <div class="selector-brand-watermark">
        <i class="bi bi-lightning-charge-fill"></i> TOPUPERS
      </div>
    </div>
  </div>
</div>
