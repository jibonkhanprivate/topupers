<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "============================================================\n";
echo "TESTING FORGOT PASSWORD MODAL & ENDPOINTS\n";
echo "============================================================\n\n";

$passCount = 0;
function it(string $desc, bool $cond) {
    global $passCount;
    if ($cond) {
        echo "  [PASS] $desc\n";
        $passCount++;
    } else {
        echo "  [FAIL] $desc\n";
    }
}

// 1. Verify modal markup in auth/login.php
$loginHtml = file_get_contents('http://localhost/RechaX%20Official/auth/login.php');
it("login.php contains modern-modal-backdrop", strpos($loginHtml, 'modern-modal-backdrop') !== false);
it("login.php contains modern-modal-card", strpos($loginHtml, 'modern-modal-card') !== false);
it("login.php contains modern-modal-icon-wrap", strpos($loginHtml, 'modern-modal-icon-wrap') !== false);
it("login.php contains 'Forgot Password?' title", strpos($loginHtml, 'Forgot Password?</h3>') !== false);
it("login.php contains 'We\'ll send a code to your email'", strpos($loginHtml, "We'll send a code to your email") !== false);
it("login.php contains 'Email Address' input label", strpos($loginHtml, 'for="forgotEmailInput">Email Address</label>') !== false);
it("login.php contains placeholder 'your@email.com'", strpos($loginHtml, 'placeholder="your@email.com"') !== false);
it("login.php contains Cancel button", strpos($loginHtml, 'onclick="closeForgotModal()">Cancel</button>') !== false);
it("login.php contains 'Send Code' button with arrow icon", strpos($loginHtml, 'id="btnSendForgotCode"') !== false && strpos($loginHtml, 'bi-arrow-right') !== false);
it("Forgot Password link calls openForgotModal(event)", strpos($loginHtml, 'onclick="openForgotModal(event)"') !== false);

// 2. Test AJAX Endpoint: send_code with invalid email
$ch = curl_init('http://localhost/RechaX%20Official/ajax/forgot_password.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'send_code',
    'email'  => 'invalid-email-address'
]));
$res1 = json_decode(curl_exec($ch), true);
curl_close($ch);

it("ajax/forgot_password.php rejects invalid email", ($res1['success'] ?? null) === false);

// 3. Test AJAX Endpoint: send_code with non-existent user
$ch = curl_init('http://localhost/RechaX%20Official/ajax/forgot_password.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'send_code',
    'email'  => 'nonexistent_user_99999@gmail.com'
]));
$res2 = json_decode(curl_exec($ch), true);
curl_close($ch);

it("ajax/forgot_password.php handles non-existent user safely", ($res2['success'] ?? null) === false);
it("ajax/forgot_password.php error explains no account found", strpos($res2['message'] ?? '', 'No account found') !== false);

// 4. Test CSS rules in assets/css/auth-modern.css
$css = file_get_contents(__DIR__ . '/../assets/css/auth-modern.css');
it("auth-modern.css has .modern-modal-backdrop with blur", strpos($css, '.modern-modal-backdrop') !== false && strpos($css, 'backdrop-filter: blur(8px)') !== false);
it("auth-modern.css has .modern-modal-card styling", strpos($css, '.modern-modal-card') !== false && strpos($css, 'border-radius: 22px') !== false);
it("auth-modern.css has .modern-modal-btn-send with cyan gradient", strpos($css, '.modern-modal-btn-send') !== false && strpos($css, 'linear-gradient(135deg, #00c7e5, #00b4d8)') !== false);
it("auth-modern.css has .modern-modal-btn-cancel with rounded pill", strpos($css, '.modern-modal-btn-cancel') !== false && strpos($css, 'border-radius: 9999px') !== false);

echo "\n============================================================\n";
echo "SUMMARY: $passCount / 16 PASSED\n";
echo "============================================================\n";
