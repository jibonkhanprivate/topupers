<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

requireLogin();
$user = currentUser();
$pageTitle = 'Add Funds to Wallet';

$error = '';
$success = '';

// Handle POST Deposit Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (rxWriteFreezeActive()) {
        rxWriteFreezeSendHeaders();
        $error = rxWriteFreezeCustomerMessage();
    } else {
        $csrfToken     = $_POST['csrf_token'] ?? '';
        $amount        = (float)($_POST['amount'] ?? 0);
        $paymentMethod = sanitize($_POST['payment_method'] ?? '');
        $paymentNumber = sanitize($_POST['payment_number'] ?? '');
        $paymentTrx    = sanitize($_POST['payment_trx'] ?? '');

        if (!verifyCsrf($csrfToken)) {
            $error = 'Invalid request. Please try again.';
        } elseif ($amount < 10 || $amount > 999999) {
            $error = 'Deposit amount must be between ৳10 and ৳999,999.';
        } elseif (empty($paymentMethod) || !isPaymentMethodAllowedForCurrency($paymentMethod, 'BDT')) {
            $error = 'Please select a valid local payment method.';
        } elseif (empty($paymentNumber)) {
            $error = 'Please enter your payment account number.';
        } elseif (empty($paymentTrx)) {
            $error = 'Please enter the Transaction ID.';
        } else {
            try {
                // Check duplicate transaction ID
                $checkStmt = db()->prepare("SELECT id FROM wallet_deposits WHERE payment_trx = ?");
                $checkStmt->execute([$paymentTrx]);
                if ($checkStmt->fetch()) {
                    $error = 'This Transaction ID has already been submitted.';
                } else {
                    $depositNumber = 'DEP-' . strtoupper(uniqid()) . rand(10, 99);
                    $stmt = db()->prepare("
                        INSERT INTO wallet_deposits 
                        (deposit_number, user_id, amount, payment_method, payment_number, payment_trx, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([$depositNumber, $user['id'], $amount, $paymentMethod, $paymentNumber, $paymentTrx]);

                    logActivity('wallet_deposit', "Submitted deposit request of " . money($amount) . " via " . strtoupper($paymentMethod));
                    $success = 'Deposit request submitted successfully! Our verification team will review and credit your wallet shortly.';
                    
                    // Alert Admin via Telegram if enabled
                    if (getSetting('notify_new_deposit', '1') === '1') {
                        $currency = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '৳';
                        $tgDepMsg = "💳 <b>NEW WALLET DEPOSIT REQUEST — TopUpers</b>\n\n"
                                  . "<b>Deposit #:</b> <code>{$depositNumber}</code>\n"
                                  . "<b>User:</b> " . htmlspecialchars($user['username']) . " (ID: #{$user['id']})\n"
                                  . "<b>Amount:</b> {$currency} " . number_format($amount, 2) . "\n"
                                  . "<b>Method:</b> " . strtoupper($paymentMethod) . " (" . htmlspecialchars($paymentNumber) . ")\n"
                                  . "<b>TrxID:</b> <code>" . htmlspecialchars($paymentTrx) . "</code>\n"
                                  . "<b>Status:</b> Pending Review\n"
                                  . "<b>Time:</b> " . date('d M Y, h:i A');
                        @sendTelegramNotification($tgDepMsg);
                    }

                    // Refresh user balance in current context
                    $user = currentUser();
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch active payment methods
$pMethods = getAvailablePaymentMethods('BDT', db());

$extraCss = '<link rel="stylesheet" href="' . APP_URL . '/assets/css/customer-dashboard.css?v=' . filemtime(__DIR__ . '/../assets/css/customer-dashboard.css') . '">';

include __DIR__ . '/../includes/header.php';
?>

<style>
    .add-funds-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 180px);
        padding: 2rem 1rem;
    }

    .add-funds-card {
        background: #0f131a;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        width: 100%;
        max-width: 480px;
        padding: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        position: relative;
    }

    .add-funds-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .icon-box-gradient {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #a78bfa, #f472b6);
        color: #fff;
        font-size: 1.4rem;
    }

    .title-area h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: #fff;
        font-family: 'Rajdhani', sans-serif;
    }

    .title-area span {
        font-size: 0.85rem;
        color: #8892b0;
    }

    .close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        border: none;
        color: #8892b0;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .close-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        transform: scale(1.05);
    }

    /* Step Content Area */
    .amount-display-container {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
    }

    .bdt-symbol {
        font-size: 2.5rem;
        font-weight: 800;
        color: #4f5d75;
        margin-right: 8px;
        font-family: 'Rajdhani', sans-serif;
    }

    .amount-input-large {
        background: transparent;
        border: none;
        font-size: 3.5rem;
        font-weight: 800;
        color: #fff;
        text-align: center;
        width: 200px;
        outline: none;
        font-family: 'Rajdhani', sans-serif;
    }

    .amount-input-large::-webkit-outer-spin-button,
    .amount-input-large::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .preset-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }

    .amount-preset-btn {
        background: #151922;
        border: 1px solid rgba(255, 255, 255, 0.02);
        border-radius: 10px;
        color: #8892b0;
        padding: 12px 6px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
        font-family: 'Rajdhani', sans-serif;
    }

    .amount-preset-btn:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
    }

    .amount-preset-btn.active {
        border-color: #00d4ff;
        background: rgba(0, 212, 255, 0.1);
        color: #00d4ff;
    }

    .limit-label {
        font-size: 0.78rem;
        color: #4b5563;
        text-align: center;
        margin-bottom: 24px;
    }

    .section-title {
        font-size: 0.82rem;
        font-weight: 600;
        color: #8892b0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }

    .methods-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 250px;
        overflow-y: auto;
        margin-bottom: 24px;
        padding-right: 4px;
    }

    .methods-list::-webkit-scrollbar {
        width: 6px;
    }
    .methods-list::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.01);
        border-radius: 3px;
    }
    .methods-list::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.08);
        border-radius: 3px;
    }

    .payment-method-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #151922;
        border: 1px solid rgba(255, 255, 255, 0.03);
        border-radius: 14px;
        padding: 12px 16px;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .payment-method-row:hover {
        border-color: rgba(255, 255, 255, 0.1);
        background: #181d27;
    }

    .payment-method-row.selected {
        border-color: #00d4ff;
        background: rgba(0, 212, 255, 0.05);
    }

    .pm-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .pm-logo-box {
        width: 64px;
        height: 40px;
        background: #fff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    .pm-logo-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .pm-info {
        display: flex;
        flex-direction: column;
    }

    .pm-name {
        font-weight: 700;
        color: #fff;
        font-size: 1.05rem;
    }

    .pm-type {
        font-size: 0.8rem;
        color: #8892b0;
    }

    .select-indicator {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 2px solid #3c4962;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .payment-method-row.selected .select-indicator {
        background: #00d4ff;
        border-color: #00d4ff;
        color: #0b1329;
    }

    .select-indicator i {
        font-size: 0.9rem;
        font-weight: 900;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s ease;
    }

    .payment-method-row.selected .select-indicator i {
        opacity: 1;
        transform: scale(1);
    }

    /* Action Buttons */
    .btn-action-large {
        width: 100%;
        height: 54px;
        border-radius: 12px;
        border: none;
        background: #1b202e;
        color: #4b5563;
        font-size: 1rem;
        font-weight: 700;
        cursor: not-allowed;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-action-large.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(108, 99, 255, 0.35);
    }

    .btn-action-large.active:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    /* Step 2 Elements */
    .instruction-box {
        background: rgba(0, 212, 255, 0.08);
        border: 1px dashed rgba(0, 212, 255, 0.3);
        border-radius: 14px;
        padding: 16px;
        margin-bottom: 20px;
        font-size: 0.92rem;
        line-height: 1.5;
        color: rgba(255,255,255,0.9);
    }

    .instruction-box strong {
        color: #00d4ff;
    }

    .back-nav {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #8892b0;
        cursor: pointer;
        font-size: 0.9rem;
        margin-bottom: 16px;
        transition: color 0.2s;
        width: fit-content;
    }

    .back-nav:hover {
        color: #fff;
    }

    .form-group-custom {
        margin-bottom: 18px;
    }

    .form-group-custom label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #8892b0;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    .form-control-custom {
        width: 100%;
        background: #151922;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 12px 16px;
        color: #fff;
        outline: none;
        font-size: 0.95rem;
        transition: all 0.25s ease;
    }

    .form-control-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 10px rgba(108,99,255,0.25);
    }
</style>

<div class="add-funds-wrapper">
    <div class="add-funds-card">
        
        <!-- Header -->
        <div class="add-funds-header">
            <div class="header-left">
                <div class="icon-box-gradient">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="title-area">
                    <h3>Add Funds</h3>
                    <span>Balance: <?= money($user['wallet_balance']) ?></span>
                </div>
            </div>
            <a href="<?= APP_URL ?>/user/dashboard.php" class="close-btn">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>

        <?php if ($error): ?>
            <div class="rx-alert rx-alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="rx-alert rx-alert-success mb-4">
                <i class="bi bi-check-circle-fill"></i> <?= e($success) ?>
            </div>
            <div class="text-center mt-3">
                <a href="<?= APP_URL ?>/user/dashboard.php" class="btn btn-rx py-2 px-4">Go to Dashboard</a>
            </div>
        <?php else: ?>

            <form method="POST" id="addMoneyForm">
                <?= csrfField() ?>
                <input type="hidden" name="payment_method" id="selectedMethodInput" value="">
                
                <!-- STEP 1 CONTAINER -->
                <div id="step-1-container">
                    <!-- Amount Input -->
                    <div class="amount-display-container">
                        <span class="bdt-symbol">৳</span>
                        <input type="number" name="amount" id="amountInput" class="amount-input-large" placeholder="0" min="10" max="999999" required autocomplete="off">
                    </div>

                    <!-- Presets Grid -->
                    <div class="preset-grid">
                        <div class="amount-preset-btn" data-val="100">৳100</div>
                        <div class="amount-preset-btn" data-val="200">৳200</div>
                        <div class="amount-preset-btn" data-val="500">৳500</div>
                        <div class="amount-preset-btn" data-val="1000">৳1,000</div>
                        <div class="amount-preset-btn" data-val="2000">৳2,000</div>
                        <div class="amount-preset-btn" data-val="5000">৳5,000</div>
                    </div>
                    <div class="limit-label">Minimum ৳10 • Maximum ৳999,999</div>

                    <!-- Payment Methods Section -->
                    <div class="section-title">Payment Method</div>
                    <div class="methods-list">
                        <?php if (empty($pMethods)): ?>
                            <div class="text-center text-muted py-3">No payment methods available.</div>
                        <?php else: ?>
                            <?php foreach ($pMethods as $pm): 
                                $key = $pm['method_key'];
                                $number = $pm['account_number'] ?: getSetting($key . "_number", 'N/A');
                            ?>
                                <div class="payment-method-row" data-key="<?= e($key) ?>" data-label="<?= e($pm['method_label']) ?>" data-number="<?= e($number) ?>">
                                    <div class="pm-left">
                                        <div class="pm-logo-box">
                                            <?php if ($pm['icon']): ?>
                                                <img src="<?= APP_URL ?>/<?= e($pm['icon']) ?>" alt="<?= e($pm['method_label']) ?>">
                                            <?php else: ?>
                                                <!-- Fallback styled logo -->
                                                <?php if ($key === 'bkash'): ?>
                                                    <span style="color:#E2136E; font-weight:800; font-size:1rem;">bKash</span>
                                                <?php elseif ($key === 'nagad'): ?>
                                                    <span style="color:#F6921E; font-weight:800; font-size:1rem;">Nagad</span>
                                                <?php elseif ($key === 'rocket'): ?>
                                                    <span style="color:#8B0DBA; font-weight:800; font-size:1rem;">Rocket</span>
                                                <?php else: ?>
                                                    <span style="color:#333; font-weight:800; font-size:0.9rem;"><?= e($pm['method_label']) ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pm-info">
                                            <span class="pm-name"><?= e($pm['method_label']) ?></span>
                                            <span class="pm-type">Mobile Banking</span>
                                        </div>
                                    </div>
                                    <div class="select-indicator">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Next Step Action -->
                    <button type="button" id="nextStepBtn" class="btn-action-large" disabled>
                        Enter Amount
                    </button>
                </div>

                <!-- STEP 2 CONTAINER (Initially Hidden) -->
                <div id="step-2-container" style="display: none;">
                    <div class="back-nav" id="backToStep1">
                        <i class="bi bi-arrow-left"></i> Change Amount / Payment Method
                    </div>

                    <!-- Payment Instructions -->
                    <div class="instruction-box">
                        <i class="bi bi-info-circle-fill me-1"></i> Send <strong id="dispAmount">৳0</strong> to our <strong id="dispMethod">Method</strong> number: <strong id="dispNumber" style="font-size:1.1rem; letter-spacing:0.5px;">01XXXXXXXXX</strong>. After sending, enter your details below.
                    </div>

                    <div class="form-group-custom">
                        <label id="numberFieldLabel">Your Mobile Account Number *</label>
                        <input type="text" name="payment_number" id="payNumberInput" class="form-control-custom" placeholder="e.g. 01XXXXXXXXX">
                    </div>

                    <div class="form-group-custom">
                        <label>Transaction ID *</label>
                        <input type="text" name="payment_trx" id="payTrxInput" class="form-control-custom" placeholder="e.g. TRX12345678">
                    </div>

                    <button type="submit" id="submitDepositBtn" class="btn-action-large active">
                        <i class="bi bi-shield-lock-fill"></i> Confirm Deposit
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const amountInput = document.getElementById('amountInput');
    const presets = document.querySelectorAll('.amount-preset-btn');
    const methodRows = document.querySelectorAll('.payment-method-row');
    const selectedMethodInput = document.getElementById('selectedMethodInput');
    const nextStepBtn = document.getElementById('nextStepBtn');
    
    const step1Container = document.getElementById('step-1-container');
    const step2Container = document.getElementById('step-2-container');
    
    const backToStep1 = document.getElementById('backToStep1');
    const dispAmount = document.getElementById('dispAmount');
    const dispMethod = document.getElementById('dispMethod');
    const dispNumber = document.getElementById('dispNumber');
    const numberFieldLabel = document.getElementById('numberFieldLabel');
    
    const payNumberInput = document.getElementById('payNumberInput');
    const payTrxInput = document.getElementById('payTrxInput');

    let selectedMethod = '';
    let selectedMethodLabel = '';
    let selectedMethodNumber = '';

    // Update form elements state
    function validateStep1() {
        const amt = parseFloat(amountInput.value) || 0;
        const methodSelected = selectedMethod !== '';
        
        if (amt < 10) {
            nextStepBtn.disabled = true;
            nextStepBtn.classList.remove('active');
            nextStepBtn.textContent = 'Enter Amount';
        } else if (!methodSelected) {
            nextStepBtn.disabled = true;
            nextStepBtn.classList.remove('active');
            nextStepBtn.textContent = 'Select Payment Method';
        } else {
            nextStepBtn.disabled = false;
            nextStepBtn.classList.add('active');
            nextStepBtn.textContent = 'Proceed to Pay ৳' + Number(amt).toLocaleString('en-BD');
        }
    }

    // Amount Input change
    amountInput.addEventListener('input', () => {
        // Remove preset active classes if input manual value is not matching
        const val = parseInt(amountInput.value) || 0;
        presets.forEach(p => {
            if (parseInt(p.dataset.val) === val) {
                p.classList.add('active');
            } else {
                p.classList.remove('active');
            }
        });
        validateStep1();
    });

    // Preset select
    presets.forEach(p => {
        p.addEventListener('click', () => {
            presets.forEach(btn => btn.classList.remove('active'));
            p.classList.add('active');
            amountInput.value = p.dataset.val;
            validateStep1();
        });
    });

    // Payment Method select
    methodRows.forEach(row => {
        row.addEventListener('click', () => {
            methodRows.forEach(r => r.classList.remove('selected'));
            row.classList.add('selected');
            selectedMethod = row.dataset.key;
            selectedMethodLabel = row.dataset.label;
            selectedMethodNumber = row.dataset.number;
            selectedMethodInput.value = selectedMethod;
            validateStep1();
        });
    });

    // Next Step
    nextStepBtn.addEventListener('click', () => {
        if (nextStepBtn.classList.contains('active')) {
            const amt = parseFloat(amountInput.value) || 0;
            
            dispAmount.textContent = '৳' + amt.toLocaleString('en-BD');
            dispMethod.textContent = selectedMethodLabel;
            dispNumber.textContent = selectedMethodNumber;
            numberFieldLabel.textContent = 'Your ' + selectedMethodLabel + ' Account Number *';
            payNumberInput.placeholder = 'Your ' + selectedMethodLabel + ' Wallet Number';

            payNumberInput.required = true;
            payTrxInput.required = true;

            step1Container.style.display = 'none';
            step2Container.style.display = 'block';
        }
    });

    // Back to Step 1
    backToStep1.addEventListener('click', () => {
        payNumberInput.required = false;
        payTrxInput.required = false;
        
        step2Container.style.display = 'none';
        step1Container.style.display = 'block';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
