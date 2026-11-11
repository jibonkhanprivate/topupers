<?php
$html = file_get_contents('http://localhost/RechaX%20Official/game.php?slug=mobile-legends');

$keys = [
    'desktopSummaryCard',
    'rx-summary-empty-state',
    'desktopSummarySelected',
    'rx-summary-player-box',
    'rx-summary-coupon-row',
    'rx-payment-grid',
    'rxMobileStickyBar',
    'rxOrderSummaryModal',
    'viewSummaryToggleBtn',
    'rx-modal-backdrop',
    'ORDER SUMMARY',
    'Pay Now'
];

foreach ($keys as $k) {
    echo str_pad($k, 26) . ': ' . (strpos($html, $k) !== false ? 'FOUND' : 'MISSING') . "\n";
}
