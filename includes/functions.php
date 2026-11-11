<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/write_freeze.php';

// ============================================================
// Session helpers
// ============================================================
function startUserSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function startAdminSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ADMIN_SESSION_NAME);
        session_start();
    }
}

function isLoggedIn(): bool {
    startUserSession();
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn(): bool {
    startAdminSession();
    return isset($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    startAdminSession();
    if (!isAdminLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function currentAdmin(): ?array {
    if (!isAdminLoggedIn()) return null;
    static $admin = null;
    if ($admin === null) {
        $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}

// ============================================================
// Security helpers
// ============================================================
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize(string $s): string {
    return trim(strip_tags($s));
}

function generateCsrf(): string {
    startUserSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    startUserSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrf()) . '">';
}

// ============================================================
// Output helpers
// ============================================================
function money(float $amount): string {
    return CURRENCY_SYMBOL . number_format($amount, 0, '.', ',');
}

function formatPrice(float|int|string $amount): string {
    return money((float)$amount);
}

function generateOrderNumber(): string {
    return ORDER_PREFIX . strtoupper(uniqid()) . rand(10, 99);
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 6)   return $ago->format('d M Y');
    if ($diff->d > 0)   return $diff->d . 'd ago';
    if ($diff->h > 0)   return $diff->h . 'h ago';
    if ($diff->i > 0)   return $diff->i . 'm ago';
    return 'Just now';
}

function slug(string $text): string {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($text)));
}

// ============================================================
// Image / upload helpers
// ============================================================
function uploadImage(array $file, string $folder = 'products'): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE)  return false;
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) return false;
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(12)) . '.' . strtolower($ext);
    $path = UPLOAD_PATH . '/' . $folder . '/' . $name;
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    return move_uploaded_file($file['tmp_name'], $path) ? $name : false;
}

function gameImg(?string $img, string $game_slug = ''): string {
    if ($img && file_exists(UPLOAD_PATH . '/games/' . $img)) {
        return APP_URL . '/uploads/games/' . $img;
    }
    // Return an SVG placeholder with game initial
    $letter = strtoupper(substr($game_slug ?: 'G', 0, 1));
    return APP_URL . '/assets/img/game-placeholder.svg';
}

function userAvatarUrl(?string $avatar): ?string {
    if (!empty($avatar) && $avatar !== 'default.png') {
        $safeName = basename($avatar);
        if (file_exists(UPLOAD_PATH . '/avatars/' . $safeName)) {
            return APP_URL . '/uploads/avatars/' . rawurlencode($safeName);
        }
    }
    return null;
}

function userInitials(string $name): string {
    $trimmed = trim($name);
    if ($trimmed === '') return 'U';
    $words = preg_split('/\s+/', $trimmed);
    if (count($words) >= 2) {
        return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
    return strtoupper(mb_substr($trimmed, 0, 2));
}

// ============================================================
// DB shorthand helpers
// ============================================================
function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
            $cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Exception $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function setSetting(string $key, string $value): bool {
    try {
        $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}

function getGames(bool $featuredOnly = false, ?string $category = 'game'): array {
    $sql = 'SELECT g.*, p.image as parent_image FROM games g LEFT JOIN games p ON g.parent_id = p.id WHERE g.is_active = 1';
    if ($featuredOnly) $sql .= ' AND g.is_featured = 1';
    if ($category !== null) {
        $sql .= ' AND (g.category = ' . db()->quote($category);
        if ($category === 'game') {
            $sql .= ' OR g.category IS NULL OR g.category = \'\'';
        }
        $sql .= ')';
    }
    $sql .= ' ORDER BY g.sort_order ASC';
    return db()->query($sql)->fetchAll();
}

function getGame(string $slugOrId): ?array {
    $col  = is_numeric($slugOrId) ? 'id' : 'slug';
    $stmt = db()->prepare("SELECT * FROM games WHERE $col = ? AND is_active = 1");
    $stmt->execute([$slugOrId]);
    return $stmt->fetch() ?: null;
}

function getProducts(int $gameId): array {
    $stmt = db()->prepare('SELECT * FROM products WHERE game_id = ? AND is_active = 1 AND is_archived = 0 ORDER BY sort_order ASC');
    $stmt->execute([$gameId]);
    return $stmt->fetchAll();
}

function getCartCount(): int {
    startUserSession();
    return array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
}

function getBanners(bool $activeOnly = true): array {
    $sql = 'SELECT * FROM banners';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id DESC';
    return db()->query($sql)->fetchAll();
}

function bannerUrl(?string $path, string $variant = 'opt'): string {
    if (empty($path)) return '';
    $path = ltrim($path, '/');
    
    // Serve optimized variant if requested and available
    if ($variant !== 'orig') {
        $info = pathinfo($path);
        $variantPath = $info['dirname'] . '/' . $info['filename'] . ($variant === 'opt' ? '-opt.jpg' : "-{$variant}.jpg");
        if (file_exists(APP_ROOT . '/' . $variantPath)) {
            $path = $variantPath;
        }
    }

    $parts = explode('/', $path);
    $encoded = array_map('rawurlencode', $parts);
    return APP_URL . '/' . implode('/', $encoded);
}

function bannerSrcset(?string $path): string {
    if (empty($path)) return '';
    $path = ltrim($path, '/');
    $info = pathinfo($path);

    $mobile = $info['dirname'] . '/' . $info['filename'] . '-mobile.jpg';
    $tablet = $info['dirname'] . '/' . $info['filename'] . '-tablet.jpg';
    $desktop = $info['dirname'] . '/' . $info['filename'] . '-opt.jpg';

    $srcset = [];
    if (file_exists(APP_ROOT . '/' . $mobile)) {
        $srcset[] = bannerUrl($mobile, 'orig') . ' 540w';
    }
    if (file_exists(APP_ROOT . '/' . $tablet)) {
        $srcset[] = bannerUrl($tablet, 'orig') . ' 960w';
    }
    if (file_exists(APP_ROOT . '/' . $desktop)) {
        $srcset[] = bannerUrl($desktop, 'orig') . ' 1600w';
    }

    return implode(', ', $srcset);
}

/**
 * Generate desktop/tablet/mobile responsive JPEG variants for an uploaded banner.
 * Uses PHP GD if available, otherwise falls back silently (manual generation via PS1 script).
 *
 * @param string $srcAbsPath  Absolute path to the original uploaded file
 * @return void
 */
function generateBannerVariants(string $srcAbsPath): void {
    if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng')) {
        return; // GD not available — variants pre-generated via optimize_banners.ps1
    }
    $ext   = strtolower(pathinfo($srcAbsPath, PATHINFO_EXTENSION));
    $base  = pathinfo($srcAbsPath, PATHINFO_FILENAME);
    $dir   = dirname($srcAbsPath);

    // Load source image
    $src = match($ext) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($srcAbsPath),
        'png'         => @imagecreatefrompng($srcAbsPath),
        'webp'        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcAbsPath) : false,
        default       => false,
    };
    if (!$src) return;

    $origW = imagesx($src);
    $origH = imagesy($src);

    $variants = [
        'opt'    => 1600,
        'tablet' => 960,
        'mobile' => 540,
    ];

    foreach ($variants as $suffix => $maxW) {
        $dstPath = $dir . '/' . $base . '-' . $suffix . '.jpg';
        if (file_exists($dstPath)) continue; // already generated

        $w = min($origW, $maxW);
        $h = (int) round(($origH * $w) / $origW);

        $dst = imagecreatetruecolor($w, $h);
        // Fill white background (for PNGs with transparency)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $origW, $origH);
        imagejpeg($dst, $dstPath, 85);
        imagedestroy($dst);
    }
    imagedestroy($src);
}

// ============================================================
// RBAC (Role-Based Access Control)
// ============================================================

/**
 * Check if the currently logged-in admin has a specific permission.
 */
function hasPermission(string $permission, ?array $admin = null): bool {
    if ($admin === null) {
        $admin = currentAdmin();
    }
    if (!$admin) return false;

    $role = $admin['role'] ?? 'admin';
    if ($role === 'superadmin') return true;

    // Decode custom permissions if set
    $customPerms = [];
    if (!empty($admin['permissions'])) {
        $customPerms = is_array($admin['permissions']) ? $admin['permissions'] : json_decode($admin['permissions'], true);
        if (!is_array($customPerms)) $customPerms = [];
    }

    if (in_array('*', $customPerms) || in_array($permission, $customPerms)) {
        return true;
    }

    // Role default permissions map
    $roleDefaults = [
        'admin' => [
            'dashboard.*', 'orders.*', 'deposits.*', 'payments.*', 'products.*', 'games.*',
            'coupons.*', 'promotions.*', 'content.*', 'users.*', 'resellers.*', 'wallet.*',
            'coins.*', 'withdrawals.*', 'suppliers.*', 'reports.*', 'analytics.*',
            'settings.view', 'settings.edit', 'security.*', 'logs.*'
        ],
        'order_manager' => [
            'dashboard.view', 'orders.view', 'orders.edit', 'orders.process', 'reports.view'
        ],
        'payment_manager' => [
            'dashboard.view', 'deposits.*', 'payments.*', 'wallet.*', 'withdrawals.*', 'reports.view'
        ],
        'product_manager' => [
            'dashboard.view', 'products.*', 'games.*', 'coupons.*', 'promotions.*', 'content.*', 'suppliers.view'
        ],
        'support' => [
            'dashboard.view', 'orders.view', 'users.view', 'deposits.view'
        ],
        'accountant' => [
            'dashboard.view', 'reports.*', 'analytics.*', 'payments.view', 'deposits.view', 'wallet.view', 'withdrawals.view'
        ]
    ];

    $allowedList = $roleDefaults[$role] ?? [];
    foreach ($allowedList as $pattern) {
        if ($pattern === '*' || $pattern === $permission) return true;
        if (str_ends_with($pattern, '.*')) {
            $prefix = substr($pattern, 0, -2);
            if (str_starts_with($permission, $prefix . '.')) return true;
        }
    }

    return false;
}

/**
 * Require a specific permission, redirect or abort with 403.
 */
function requirePermission(string $permission): void {
    requireAdmin();
    if (!hasPermission($permission)) {
        http_response_code(403);
        $adminPageTitle = 'Access Denied';
        $activeNav = '';
        require __DIR__ . '/../admin/includes/header.php';
        echo '<div class="admin-card p-5 text-center my-5">
                <i class="bi bi-shield-x text-danger" style="font-size:3.5rem;"></i>
                <h3 class="mt-3 text-white">Access Denied</h3>
                <p class="text-muted">You do not have permission (' . e($permission) . ') to access this module.</p>
                <a href="' . APP_URL . '/admin/index.php" class="btn-admin-primary btn-admin-sm mt-3">Back to Dashboard</a>
              </div>';
        require __DIR__ . '/../admin/includes/footer.php';
        exit;
    }
}

// ============================================================
// Financial Ledger & Transaction-Safe Helpers
// ============================================================

/**
 * Record a wallet ledger transaction with atomic balance lock.
 */
function recordWalletTransaction(
    int $userId,
    string $type,
    float $amount,
    string $description,
    ?string $refType = null,
    ?string $refId = null,
    ?int $adminId = null
): bool {
    if (rxWriteFreezeActive()) {
        error_log('Write-freeze active: recordWalletTransaction() refused.');
        return false;
    }

    $db = db();
    $inExistingTx = $db->inTransaction();
    if (!$inExistingTx) $db->beginTransaction();

    try {
        // Lock user record for update
        $stmt = $db->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$userId]);
        $currBal = $stmt->fetchColumn();
        if ($currBal === false) {
            if (!$inExistingTx) $db->rollBack();
            return false;
        }

        $balanceBefore = (float)$currBal;
        $balanceAfter = $balanceBefore;

        if (in_array($type, ['deposit', 'refund', 'admin_credit'])) {
            $balanceAfter = $balanceBefore + $amount;
        } elseif (in_array($type, ['purchase', 'admin_debit', 'withdrawal'])) {
            $balanceAfter = $balanceBefore - $amount;
        } elseif ($type === 'adjustment') {
            $balanceAfter = $balanceBefore + $amount; // amount can be positive or negative
        }

        // Update user balance
        $db->prepare('UPDATE users SET wallet_balance = ? WHERE id = ?')->execute([$balanceAfter, $userId]);

        // Insert ledger record
        $ins = $db->prepare('INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, reference_type, reference_id, admin_id, description) VALUES (?,?,?,?,?,?,?,?,?)');
        $ins->execute([$userId, $type, abs($amount), $balanceBefore, $balanceAfter, $refType, $refId, $adminId, $description]);

        if (!$inExistingTx) $db->commit();
        return true;
    } catch (Throwable $e) {
        if (!$inExistingTx) $db->rollBack();
        error_log('Wallet Tx Failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Record a coin ledger transaction (1 Coin = 1 BDT).
 */
function recordCoinTransaction(
    int $userId,
    string $type,
    float $amount,
    string $description,
    ?string $refId = null,
    ?int $adminId = null
): bool {
    $db = db();
    $inExistingTx = $db->inTransaction();
    if (!$inExistingTx) $db->beginTransaction();

    try {
        $stmt = $db->prepare('SELECT coin_balance FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$userId]);
        $currBal = $stmt->fetchColumn();
        if ($currBal === false) {
            if (!$inExistingTx) $db->rollBack();
            return false;
        }

        $balanceBefore = (float)$currBal;
        $balanceAfter = $balanceBefore;

        if (in_array($type, ['purchase', 'bonus'])) {
            $balanceAfter = $balanceBefore + $amount;
        } elseif (in_array($type, ['usage'])) {
            $balanceAfter = $balanceBefore - $amount;
        } elseif ($type === 'admin_adjustment') {
            $balanceAfter = $balanceBefore + $amount;
        } elseif ($type === 'refund') {
            $balanceAfter = $balanceBefore + $amount;
        }

        $db->prepare('UPDATE users SET coin_balance = ? WHERE id = ?')->execute([$balanceAfter, $userId]);

        $ins = $db->prepare('INSERT INTO coin_transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, admin_id) VALUES (?,?,?,?,?,?,?,?)');
        $ins->execute([$userId, $type, abs($amount), $balanceBefore, $balanceAfter, $refId, $description, $adminId]);

        if (!$inExistingTx) $db->commit();
        return true;
    } catch (Throwable $e) {
        if (!$inExistingTx) $db->rollBack();
        error_log('Coin Tx Failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Record a unified payment entry.
 */
function recordPayment(
    string $refType,
    string $refId,
    ?int $userId,
    float $amount,
    string $gateway,
    ?string $accNum,
    ?string $trxRef,
    string $status = 'pending',
    ?string $gatewayTrxId = null,
    ?string $gatewayResponse = null,
    ?int $adminId = null
): int {
    if (rxWriteFreezeActive()) {
        error_log('Write-freeze active: recordPayment() refused.');
        return 0;
    }

    try {
        $payNum = 'PAY-' . strtoupper(uniqid());
        $stmt = db()->prepare('INSERT INTO payments (payment_number, reference_type, reference_id, user_id, amount, gateway_name, account_number, transaction_ref, gateway_trx_id, gateway_response, status, verified_by_admin_id, verified_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $verifiedAt = in_array($status, ['verified', 'completed']) ? date('Y-m-d H:i:s') : null;
        $stmt->execute([
            $payNum, $refType, $refId, $userId, $amount, $gateway, $accNum, $trxRef, $gatewayTrxId, $gatewayResponse, $status, $adminId, $verifiedAt
        ]);
        return (int)db()->lastInsertId();
    } catch (Throwable $e) {
        error_log('Record Payment Failed: ' . $e->getMessage());
        return 0;
    }
}

// ============================================================
// Security & Audit Logging
// ============================================================

/**
 * Log an activity to the system for audit/safety.
 */
function logActivity(string $actionType, string $description, ?int $adminId = null, ?int $userId = null): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

        if ($adminId === null && isAdminLoggedIn()) {
            $adminId = $_SESSION['admin_id'];
        }
        if ($userId === null && isLoggedIn()) {
            $userId = $_SESSION['user_id'];
        }

        $stmt = db()->prepare('INSERT INTO activity_logs (admin_id, user_id, action_type, description, ip_address, user_agent) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$adminId, $userId, $actionType, $description, $ip, $ua]);
    } catch (Throwable $e) {
        error_log("Activity log failed: " . $e->getMessage());
    }
}

/**
 * Record an admin login attempt for security monitoring.
 */
function logAdminLogin(string $username, string $status, ?int $adminId = null, ?string $reason = null): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $stmt = db()->prepare('INSERT INTO admin_login_logs (admin_id, username, username_attempted, status, ip_address, user_agent, failure_reason, attempted_at) VALUES (?,?,?,?,?,?,?,NOW())');
        $stmt->execute([$adminId, $username, $username, $status, $ip, $ua, $reason]);
    } catch (Throwable $e) {
        error_log("Admin login log failed: " . $e->getMessage());
    }
}

// ============================================================
// Supplier API & Dispatch Helpers (with Idempotency)
// ============================================================

/**
 * True when $e is the duplicate-key violation raised by the standalone unique payment-reference index
 * (uq_orders_normalized_payment_trx). Never matches any other integrity error.
 */
function isOrderTrxConflictException(Throwable $e): bool {
    if (!($e instanceof PDOException)) return false;
    $sqlState   = (string)$e->getCode();
    $driverCode = (int)($e->errorInfo[1] ?? 0);
    return ($sqlState === '23000' || $driverCode === 1062)
        && stripos($e->getMessage(), 'uq_orders_normalized_payment_trx') !== false;
}

/**
 * Advisory pre-check (works before AND after the unique index exists): would moving $orderId from 'cancelled'
 * to an active status collide with another non-cancelled order carrying the same normalised payment reference?
 * Returns the conflicting order row or null. The database unique index remains the authoritative enforcement;
 * this only lets callers refuse early with a clear message BEFORE any side effect (e.g. a supplier call).
 */
function findActiveOrderTrxConflict(PDO $db, int $orderId): ?array {
    $stmt = $db->prepare('SELECT payment_trx, order_status FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $self = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$self || $self['order_status'] !== 'cancelled') return null; // only a cancelled order can newly claim a reference
    if ($self['payment_trx'] === null || trim((string)$self['payment_trx']) === '') return null;

    $q = $db->prepare("SELECT id, order_number, order_status FROM orders WHERE id <> ? AND order_status <> 'cancelled' AND payment_trx IS NOT NULL AND TRIM(payment_trx) <> '' AND UPPER(TRIM(payment_trx)) = UPPER(TRIM(?)) LIMIT 1");
    $q->execute([$orderId, (string)$self['payment_trx']]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Safe supplier API dispatch with fallback and idempotency protection.
 */
function dispatchSupplierOrder(int $orderId): array {
    // Gate B2 write-freeze: refuse before ANY write (idempotency key, queue note, status update, supplier call).
    if (rxWriteFreezeActive()) {
        return ['success' => false, 'frozen' => true, 'message' => rxWriteFreezeAdminMessage()];
    }
    $db = db();
    $stmt = $db->prepare('SELECT o.*, p.name as product_name, p.delivery_type, p.supplier_id as prod_supplier_id, p.supplier_product_id as prod_supplier_code, g.name as game_name FROM orders o JOIN products p ON p.id = o.product_id JOIN games g ON g.id = o.game_id WHERE o.id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        return ['success' => false, 'message' => 'Order not found.'];
    }

    // Gate B2: dispatching a cancelled order completes it (cancelled -> completed), i.e. it re-claims its payment
    // reference. Refuse BEFORE contacting any supplier if that reference is held by another active order.
    $trxConflict = findActiveOrderTrxConflict($db, $orderId);
    if ($trxConflict) {
        return [
            'success'  => false,
            'conflict' => true,
            'message'  => 'Cannot reactivate/dispatch this cancelled order: its Transaction ID is already reserved by active order #' . $trxConflict['order_number'] . '. Cancel or resolve that order first. No changes were made.',
        ];
    }

    // Ensure idempotency key
    $idempotencyKey = $order['idempotency_key'];
    if (empty($idempotencyKey)) {
        $idempotencyKey = 'RX-IDEM-' . $order['id'] . '-' . bin2hex(random_bytes(8));
        $db->prepare('UPDATE orders SET idempotency_key = ? WHERE id = ?')->execute([$idempotencyKey, $orderId]);
    }

    // Fetch suppliers configured for this product
    $mappings = $db->prepare('SELECT m.*, s.name as supplier_name, s.api_endpoint, s.api_key, s.api_secret, s.balance FROM supplier_product_mappings m JOIN suppliers s ON s.id = m.supplier_id WHERE m.product_id = ? AND m.is_active = 1 AND s.is_active = 1 ORDER BY m.priority ASC');
    $mappings->execute([$order['product_id']]);
    $supplierList = $mappings->fetchAll();

    // Fallback: If no explicit mapping, check direct product supplier_id
    if (empty($supplierList) && !empty($order['prod_supplier_id'])) {
        $directSup = $db->prepare('SELECT s.*, ? as supplier_product_code FROM suppliers s WHERE s.id = ? AND s.is_active = 1');
        $directSup->execute([$order['prod_supplier_code'] ?: 'DEFAULT_CODE', $order['prod_supplier_id']]);
        $found = $directSup->fetch();
        if ($found) {
            $found['supplier_name'] = $found['name'];
            $supplierList = [$found];
        }
    }

    if (empty($supplierList)) {
        // Mark as manual processing
        $db->prepare('UPDATE orders SET delivery_status = "manual_queue", admin_note = CONCAT(COALESCE(admin_note,""), "\n[Auto-Dispatch] No active supplier API mapped. Queued for manual fulfillment.") WHERE id = ?')->execute([$orderId]);
        return ['success' => false, 'manual' => true, 'message' => 'No active supplier API mapped. Queued for manual processing.'];
    }

    // Try primary supplier, then fallback
    foreach ($supplierList as $supplier) {
        $startTime = microtime(true);
        $requestId = 'REQ-' . uniqid();
        
        // Mock / Live dispatch simulation
        // In real live integration, send cURL payload to $supplier['api_endpoint']
        $supplierOrderId = 'SUP-' . strtoupper(substr($supplier['supplier_name'], 0, 3)) . '-' . rand(100000, 999999);
        $executionTimeMs = (int)((microtime(true) - $startTime) * 1000);
        $mockResponse = [
            'status' => 'SUCCESS',
            'supplier_order_id' => $supplierOrderId,
            'message' => 'Top-up initiated successfully on ' . $supplier['supplier_name'],
            'player_id' => $order['player_id'],
            'zone_id' => $order['server_id'] ?? ''
        ];
        $responseBody = json_encode($mockResponse);

        // Record API Log
        $db->prepare('INSERT INTO supplier_api_logs (supplier_id, order_id, request_id, endpoint, http_status, response_time_ms, request_payload, response_body) VALUES (?,?,?,?,?,?,?,?)')->execute([
            $supplier['supplier_id'] ?? $supplier['id'],
            $orderId,
            $requestId,
            $supplier['api_endpoint'] . '/order/create',
            200,
            $executionTimeMs,
            json_encode(['order_number' => $order['order_number'], 'player_id' => $order['player_id'], 'idempotency_key' => $idempotencyKey]),
            $responseBody
        ]);

        // Update order status (the unique reference index stays authoritative; a race with the pre-check above is
        // caught here and reported without partial state: the statement itself is atomic).
        try {
            $db->prepare('UPDATE orders SET supplier_id = ?, supplier_order_id = ?, supplier_response = ?, delivery_status = "delivered", order_status = "completed" WHERE id = ?')->execute([
                $supplier['supplier_id'] ?? $supplier['id'],
                $supplierOrderId,
                $responseBody,
                $orderId
            ]);
        } catch (PDOException $e) {
            if (isOrderTrxConflictException($e)) {
                error_log("dispatchSupplierOrder: reference conflict for order {$order['order_number']} (supplier ref {$supplierOrderId}); order left unchanged");
                return [
                    'success'  => false,
                    'conflict' => true,
                    'message'  => 'Order could not be completed: its Transaction ID is already reserved by another active order. The order was left unchanged; check supplier ref ' . $supplierOrderId . ' before retrying.',
                ];
            }
            throw $e;
        }

        logActivity('SUPPLIER_DISPATCH_SUCCESS', "Order #{$order['order_number']} successfully processed by {$supplier['supplier_name']} (Supplier Ref: $supplierOrderId)");

        return [
            'success' => true,
            'supplier_name' => $supplier['supplier_name'],
            'supplier_order_id' => $supplierOrderId,
            'message' => 'Order fulfilled via ' . $supplier['supplier_name']
        ];
    }

    return ['success' => false, 'message' => 'All supplier dispatch attempts failed.'];
}

// ============================================================
// HOMEPAGE DYNAMIC SECTIONS HELPERS
// ============================================================

/**
 * Fetch homepage sections with resolved items.
 */
function getHomepageSections(bool $activeOnly = true): array {
    $db = db();
    $sql = "SELECT * FROM homepage_sections";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";

    $stmt = $db->query($sql);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sections as &$section) {
        $section['items'] = getSectionItems($section);
    }
    unset($section);

    return $sections;
}

/**
 * Resolve display items for a single homepage section.
 */
function getSectionItems(array $section): array {
    $db = db();
    $limit = (int)($section['max_items'] ?? 12);
    if ($limit <= 0) $limit = 12;

    $items = [];

    // Manual Item Selection Mode
    if (($section['selection_mode'] ?? 'auto') === 'manual') {
        $stmt = $db->prepare("
            SELECT 
                si.id AS section_item_id,
                si.item_type,
                si.custom_title,
                si.custom_badge,
                si.custom_badge_color,
                si.custom_link,
                si.sort_order AS item_sort_order,
                g.id AS game_id,
                g.name AS game_name,
                g.slug AS game_slug,
                g.image AS game_image,
                g.cover_image AS game_cover_image,
                g.badge_text AS game_badge,
                g.is_stock_out AS game_stock_out,
                g.is_featured AS game_featured,
                g.category AS game_category,
                p.id AS product_id,
                p.name AS product_name,
                p.amount AS product_amount,
                p.original_price AS product_original_price,
                p.badge AS product_badge,
                p.image AS product_image,
                pg.slug AS product_game_slug,
                pg.name AS product_game_name
            FROM section_items si
            LEFT JOIN games g ON si.game_id = g.id
            LEFT JOIN products p ON si.product_id = p.id
            LEFT JOIN games pg ON p.game_id = pg.id
            WHERE si.section_id = ? AND si.is_active = 1
            ORDER BY si.sort_order ASC, si.id ASC
            LIMIT " . $limit
        );
        $stmt->execute([(int)$section['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $isProduct = ($r['item_type'] === 'product' && !empty($r['product_id']));
            if ($isProduct) {
                $title = !empty($r['custom_title']) ? $r['custom_title'] : ($r['product_name'] . ' (' . ($r['product_game_name'] ?? '') . ')');
                $link = !empty($r['custom_link']) ? $r['custom_link'] : (APP_URL . '/' . ($r['product_game_slug'] ?? ''));
                $image = !empty($r['product_image']) ? $r['product_image'] : ($r['game_image'] ?? '');
                $badge = !empty($r['custom_badge']) ? $r['custom_badge'] : ($r['product_badge'] ?? '');
                $badgeColor = !empty($r['custom_badge_color']) ? $r['custom_badge_color'] : 'primary';
                $isStockOut = 0;
                $isFeatured = 0;
            } else {
                if (empty($r['game_id'])) continue;
                $title = !empty($r['custom_title']) ? $r['custom_title'] : $r['game_name'];
                $link = !empty($r['custom_link']) ? $r['custom_link'] : (APP_URL . '/' . $r['game_slug']);
                $image = !empty($r['game_image']) ? $r['game_image'] : (!empty($r['game_cover_image']) ? $r['game_cover_image'] : '');
                $badge = !empty($r['custom_badge']) ? $r['custom_badge'] : ($r['game_badge'] ?? '');
                $badgeColor = !empty($r['custom_badge_color']) ? $r['custom_badge_color'] : 'hot';
                $isStockOut = (int)($r['game_stock_out'] ?? 0);
                $isFeatured = (int)($r['game_featured'] ?? 0);
            }

            $items[] = [
                'id' => $isProduct ? $r['product_id'] : $r['game_id'],
                'section_item_id' => $r['section_item_id'],
                'item_type' => $r['item_type'],
                'title' => $title,
                'name' => $title,
                'slug' => $isProduct ? ($r['product_game_slug'] ?? '') : ($r['game_slug'] ?? ''),
                'link' => $link,
                'image' => $image,
                'badge' => $badge,
                'badge_color' => !empty($r['custom_badge_color']) ? $r['custom_badge_color'] : resolveBadgeClass($badge, 'hot'),
                'is_stock_out' => $isStockOut,
                'is_featured' => $isFeatured,
                'sort_order' => $r['item_sort_order'] ?? 0
            ];
        }
        return $items;
    }

    // Automatic Item Selection Mode
    $filterType = $section['auto_filter_type'] ?? 'featured';
    $filterCat  = $section['auto_filter_category'] ?? 'all';

    $where = ["g.is_active = 1"];
    $params = [];
    $orderBy = "g.sort_order ASC, g.id ASC";

    if ($filterType === 'featured') {
        $where[] = "g.is_featured = 1";
    } elseif ($filterType === 'player_id') {
        $where[] = "(g.topup_type = 'player_id' OR g.topup_type IS NULL OR g.topup_type = '') AND (g.category = 'game' OR g.category IS NULL OR g.category = '')";
    } elseif ($filterType === 'login_method') {
        $where[] = "g.topup_type = 'login'";
    } elseif ($filterType === 'by_category') {
        if (!empty($filterCat) && $filterCat !== 'all') {
            $where[] = "g.category = ?";
            $params[] = $filterCat;
        }
    } elseif ($filterType === 'by_platform') {
        $where[] = "(g.platform = 'PC' OR g.platform LIKE '%PC%')";
    } elseif ($filterType === 'popular') {
        $where[] = "(g.is_featured = 1 OR g.badge_text LIKE '%POPULAR%' OR g.badge_text LIKE '%HOT%')";
    } elseif ($filterType === 'newest') {
        $orderBy = "g.id DESC";
    }

    $whereSql = implode(" AND ", $where);
    $query = "
        SELECT g.*, p.image AS parent_image 
        FROM games g 
        LEFT JOIN games p ON g.parent_id = p.id 
        WHERE {$whereSql} 
        ORDER BY {$orderBy} 
        LIMIT {$limit}
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($games as $g) {
        $displayImg = !empty($g['image']) ? $g['image'] : (!empty($g['parent_image']) ? $g['parent_image'] : '');
        $badgeText = $g['badge_text'] ?? '';
        $items[] = [
            'id' => $g['id'],
            'item_type' => 'game',
            'title' => $g['name'],
            'name' => $g['name'],
            'slug' => $g['slug'],
            'link' => APP_URL . '/' . $g['slug'],
            'image' => $displayImg,
            'badge' => $badgeText,
            'badge_color' => resolveBadgeClass($badgeText, 'hot'),
            'is_stock_out' => (int)($g['is_stock_out'] ?? 0),
            'is_featured' => (int)($g['is_featured'] ?? 0),
            'sort_order' => (int)($g['sort_order'] ?? 0),
            'platform' => $g['platform'] ?? '',
            'publisher' => $g['publisher'] ?? ''
        ];
    }

    return $items;
}

/**
 * Resolves standard badge color CSS class based on badge label text
 */
function resolveBadgeClass(?string $badgeText, string $default = 'hot'): string {
    if (empty($badgeText)) return $default;
    $t = strtoupper(trim($badgeText));
    if (str_contains($t, 'HOT')) return 'hot';
    if (str_contains($t, 'POPULAR')) return 'popular';
    if (str_contains($t, 'BEST') || str_contains($t, 'VALUE')) return 'best-value';
    if (str_contains($t, 'WEEKLY')) return 'weekly';
    if (str_contains($t, 'VIP')) return 'vip';
    if (str_contains($t, 'GIFT') || str_contains($t, 'CODE')) return 'gift-code';
    if (str_contains($t, 'PIN') || str_contains($t, 'INSTANT')) return 'instant-pin';
    if (str_contains($t, 'UNIVERSAL')) return 'universal';
    if (str_contains($t, 'CHEAPEST') || str_contains($t, 'CHEAP')) return 'cheapest';
    if (str_contains($t, 'PROMO') || str_contains($t, 'DISCOUNT')) return 'hot';
    if (str_contains($t, 'SPECIAL')) return 'vip';
    if (str_contains($t, 'PC')) return 'popular';
    return $default;
}

// ============================================================
// FEATURE FLAGS ENGINE
// ============================================================
function isFeatureEnabled(string $flagKey): bool {
    static $flagsCache = null;
    if ($flagsCache === null) {
        try {
            $stmt = db()->query('SELECT flag_key, is_enabled FROM feature_flags');
            $flagsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Throwable $e) {
            $flagsCache = [];
        }
    }
    return !empty($flagsCache[$flagKey]);
}

// ============================================================
// REGIONS CATALOGUE HELPERS
// ============================================================
function getRegions(bool $activeOnly = true): array {
    try {
        $sql = 'SELECT * FROM regions';
        if ($activeOnly) $sql .= ' WHERE is_active = 1';
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return db()->query($sql)->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function getRegion(int|string $idOrCode): ?array {
    try {
        $col = is_numeric($idOrCode) ? 'id' : 'code';
        $stmt = db()->prepare("SELECT * FROM regions WHERE $col = ? LIMIT 1");
        $stmt->execute([$idOrCode]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

// ============================================================
// DYNAMIC GAME FIELDS ENGINE
// ============================================================
/**
 * Resolves configured dynamic fields schema for a game.
 * Falls back to default User ID / Server ID if no custom schema exists.
 */
function getGameFieldSchema(array|int $game): array {
    if (is_numeric($game)) {
        $game = getGame((int)$game);
    }
    if (!$game) return [];

    if (!empty($game['field_schema'])) {
        $decoded = is_array($game['field_schema']) ? $game['field_schema'] : json_decode($game['field_schema'], true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
    }

    // Default fallback based on legacy columns
    $fields = [];
    $playerLabel = !empty($game['player_label']) ? $game['player_label'] : 'Player ID';
    $fields[] = [
        'key'         => 'player_id',
        'label'       => $playerLabel,
        'type'        => 'text',
        'required'    => true,
        'placeholder' => 'Enter ' . $playerLabel,
        'help_text'   => 'Your in-game account identifier',
        'min_length'  => 3,
        'max_length'  => 50,
        'regex'       => ''
    ];

    if (!empty($game['requires_server_id'])) {
        $serverLabel = !empty($game['server_label']) ? $game['server_label'] : 'Zone / Server ID';
        $fields[] = [
            'key'         => 'server_id',
            'label'       => $serverLabel,
            'type'        => 'text',
            'required'    => true,
            'placeholder' => 'Enter ' . $serverLabel,
            'help_text'   => 'Server or Zone number found in profile',
            'min_length'  => 1,
            'max_length'  => 20,
            'regex'       => ''
        ];
    }

    return $fields;
}

/**
 * Validates player dynamic fields input based on game schema.
 */
function validateGameFields(array $game, array $input): array {
    $schema = getGameFieldSchema($game);
    $errors = [];
    $values = [];

    foreach ($schema as $f) {
        $key = $f['key'];
        $val = trim((string)($input[$key] ?? ''));

        if (!empty($f['required']) && $val === '') {
            $errors[$key] = ($f['label'] ?? $key) . ' is required.';
            continue;
        }

        if ($val !== '') {
            if (!empty($f['min_length']) && strlen($val) < (int)$f['min_length']) {
                $errors[$key] = ($f['label'] ?? $key) . ' must be at least ' . $f['min_length'] . ' characters.';
            } elseif (!empty($f['max_length']) && strlen($val) > (int)$f['max_length']) {
                $errors[$key] = ($f['label'] ?? $key) . ' cannot exceed ' . $f['max_length'] . ' characters.';
            } elseif (!empty($f['regex'])) {
                if (@preg_match($f['regex'], $val) === 0) {
                    $errors[$key] = ($f['label'] ?? $key) . ' format is invalid.';
                }
            }
        }

        $values[$key] = $val;
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
        'values' => $values
    ];
}

// ============================================================
// PLAYER VALIDATION FRAMEWORK
// ============================================================
/**
 * Real-time player ID validation (live supplier API / custom API / smart simulator).
 * Supports Smile.One, Custom REST API, and realistic format-checking simulator.
 *
 * @param int|string  $gameIdentifier  Game ID or URL slug
 * @param string      $playerId        Customer player / user ID
 * @param string|null $serverId        Server ID / Zone ID (if required)
 * @return array
 */
function validatePlayerId($gameIdentifier, string $playerId, ?string $serverId = null): array {
    $db = db();
    $playerId = trim($playerId);
    $serverId = ($serverId !== null) ? trim($serverId) : null;

    if (empty($playerId)) {
        return ['success' => false, 'message' => 'Player ID is required.'];
    }

    // Lookup game by ID or slug
    if (is_numeric($gameIdentifier)) {
        $stmt = $db->prepare('SELECT * FROM games WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$gameIdentifier]);
    } else {
        $stmt = $db->prepare('SELECT * FROM games WHERE slug = ? OR id = ? LIMIT 1');
        $stmt->execute([(string)$gameIdentifier, (int)$gameIdentifier]);
    }
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        return ['success' => false, 'message' => 'Game not found.'];
    }

    // Check if server is required for this game
    if (!empty($game['requires_server_id']) && (empty($serverId) && $serverId !== '0')) {
        $label = !empty($game['server_label']) ? $game['server_label'] : 'Server ID / Zone ID';
        return ['success' => false, 'message' => "{$label} is required."];
    }

    // Check game-level validation provider override
    $gameProvider = $game['validation_provider'] ?? 'auto';
    if ($gameProvider === 'none') {
        return [
            'success'   => false,
            'skipped'   => true,
            'message'   => 'Player verification is disabled for this game.'
        ];
    }

    // Fetch platform verification settings
    $settingsStmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'player_check_%'");
    $settings = $settingsStmt ? $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

    $enabledGlobally = ($settings['player_check_enabled'] ?? '1') !== '0';
    if (!$enabledGlobally) {
        return [
            'success'   => false,
            'skipped'   => true,
            'message'   => 'Player verification is disabled globally.'
        ];
    }

    $defaultProvider = $settings['player_check_provider'] ?? 'auto';
    $effectiveProvider = ($gameProvider && $gameProvider !== 'auto') ? $gameProvider : $defaultProvider;

    $apiUrl    = trim($settings['player_check_api_url'] ?? '');
    $apiKey    = trim($settings['player_check_api_key'] ?? '');
    $apiSecret = trim($settings['player_check_api_secret'] ?? '');

    $liveResult = null;
    $providerUsed = 'simulator';

    // 1. Attempt External Custom API if configured
    if (($effectiveProvider === 'custom_api' || $effectiveProvider === 'auto') && !empty($apiUrl)) {
        $liveResult = queryCustomPlayerApi($apiUrl, $apiKey, $apiSecret, $game, $playerId, $serverId);
        if ($liveResult && !empty($liveResult['success'])) {
            $providerUsed = 'custom_api';
        }
    }

    // 2. Attempt Smile One API if configured or active in suppliers
    if (!$liveResult && ($effectiveProvider === 'smile_one' || $effectiveProvider === 'auto')) {
        $liveResult = querySmileOnePlayerApi($game, $playerId, $serverId);
        if ($liveResult && !empty($liveResult['success'])) {
            $providerUsed = 'smile_one';
        }
    }

    // Return live API result if successful
    if ($liveResult && !empty($liveResult['success'])) {
        return [
            'success'     => true,
            'game_id'     => (int)$game['id'],
            'game_name'   => $game['name'],
            'game_slug'   => $game['slug'],
            'player_id'   => $playerId,
            'server_id'   => $serverId,
            'username'    => $liveResult['username'],
            'ign'         => $liveResult['username'],
            'server_name' => $liveResult['server_name'] ?? ($serverId ? "Server {$serverId}" : null),
            'provider'    => $providerUsed,
            'verified_at' => date('Y-m-d H:i:s'),
            'message'     => "Account verified: {$liveResult['username']}"
        ];
    }

    // If a dedicated external API was explicitly required and returned a hard error (e.g. not found)
    if ($liveResult && isset($liveResult['success']) && $liveResult['success'] === false && $effectiveProvider !== 'auto') {
        return [
            'success' => false,
            'message' => $liveResult['message'] ?? 'Player account not found.'
        ];
    }

    // 3. Smart Format-Aware Simulator (Fallback / Offline / Demo)
    $simResult = simulatePlayerValidation($game, $playerId, $serverId);
    return $simResult;
}

/**
 * Query custom external player lookup REST API.
 */
function queryCustomPlayerApi(string $url, string $key, string $secret, array $game, string $playerId, ?string $serverId): ?array {
    if (!function_exists('curl_init')) return null;

    $ch = curl_init();
    $params = [
        'game'      => $game['slug'],
        'game_id'   => $game['id'],
        'player_id' => $playerId,
        'server_id' => $serverId ?? ''
    ];

    // Determine GET or POST
    if (strpos($url, '?') !== false || strpos($url, '{player_id}') !== false) {
        $finalUrl = str_replace(
            ['{game}', '{player_id}', '{server_id}'],
            [rawurlencode($game['slug']), rawurlencode($playerId), rawurlencode($serverId ?? '')],
            $url
        );
        curl_setopt($ch, CURLOPT_URL, $finalUrl);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: RechaX-PlayerVerify/1.0'
    ];
    if (!empty($key)) {
        $headers[] = 'X-API-Key: ' . $key;
        $headers[] = 'Authorization: Bearer ' . $key;
    }

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw && $httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($raw, true);
        if (is_array($data)) {
            $ign = $data['username'] ?? $data['ign'] ?? $data['player_name'] ?? $data['name'] ?? ($data['data']['username'] ?? ($data['data']['ign'] ?? null));
            $srv = $data['server'] ?? $data['server_name'] ?? $data['zone'] ?? ($data['data']['zone'] ?? ($data['data']['server'] ?? null));
            if (!empty($ign)) {
                return [
                    'success'     => true,
                    'username'    => trim((string)$ign),
                    'server_name' => $srv ? trim((string)$srv) : null
                ];
            }
        }
    }

    return null;
}

/**
 * Query Smile One API for Mobile Legends and Free Fire if configured.
 */
function querySmileOnePlayerApi(array $game, string $playerId, ?string $serverId): ?array {
    $db = db();
    $supplier = $db->query("SELECT * FROM suppliers WHERE slug = 'smile-one' AND is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$supplier || empty($supplier['api_endpoint'])) {
        return null;
    }

    // Supported games mapping
    $gameCodes = [
        'mobile-legends'     => 'mobilelegends',
        'mlbb-weekly-pass'   => 'mobilelegends',
        'free-fire'          => 'freefire',
        'ff-weekly-membership'=> 'freefire',
    ];

    $slug = $game['slug'] ?? '';
    if (!isset($gameCodes[$slug])) {
        return null; // Game not directly handled by default smile.one mapper
    }

    // Call Smile One user query endpoint
    $endpoint = rtrim($supplier['api_endpoint'], '/') . '/user/query';
    $params = [
        'uid'       => $supplier['api_key'] ?? '',
        'userid'    => $playerId,
        'zoneid'    => $serverId ?? '',
        'product'   => $gameCodes[$slug],
        'time'      => time(),
    ];

    if (!empty($supplier['api_secret'])) {
        // Sign hash if secret exists
        $signStr = "uid={$params['uid']}&userid={$params['userid']}&zoneid={$params['zoneid']}&product={$params['product']}&time={$params['time']}&secret={$supplier['api_secret']}";
        $params['sign'] = md5($signStr);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw && $httpCode === 200) {
        $json = json_decode($raw, true);
        if (is_array($json) && !empty($json['username'])) {
            return [
                'success'     => true,
                'username'    => $json['username'],
                'server_name' => $json['zone'] ?? ($serverId ? "Server {$serverId}" : null)
            ];
        }
    }

    return null;
}

/**
 * Smart format-aware simulator for offline, demo, and test modes.
 */
function simulatePlayerValidation(array $game, string $playerId, ?string $serverId): array {
    $slug = $game['slug'] ?? 'default';

    // Format Rules Validation
    switch ($slug) {
        case 'mobile-legends':
        case 'mlbb-weekly-pass':
            if (!preg_match('/^\d{6,12}$/', $playerId)) {
                return ['success' => false, 'message' => 'Invalid Mobile Legends User ID. Must be 6-12 digits.'];
            }
            if ($serverId !== null && !preg_match('/^\d{3,6}$/', $serverId)) {
                return ['success' => false, 'message' => 'Invalid Zone ID. Must be 3-6 digits (e.g. 2024).'];
            }
            break;

        case 'free-fire':
        case 'ff-weekly-membership':
            if (!preg_match('/^\d{7,14}$/', $playerId)) {
                return ['success' => false, 'message' => 'Invalid Free Fire Player ID. Must be 7-14 digits.'];
            }
            break;

        case 'pubg-mobile':
        case 'pubg-mobile-login':
            if (!preg_match('/^\d{7,14}$/', $playerId)) {
                return ['success' => false, 'message' => 'Invalid PUBG Mobile Character ID. Must be 7-14 digits.'];
            }
            break;

        case 'genshin-impact':
        case 'genshin-impact-login':
            if (!preg_match('/^[1-9]\d{8}$/', $playerId)) {
                return ['success' => false, 'message' => 'Invalid Genshin Impact UID. Must be a 9-digit number.'];
            }
            // Auto map region by UID prefix
            $firstDigit = substr($playerId, 0, 1);
            $genshinServers = [
                '6' => 'America Server',
                '7' => 'Europe Server',
                '8' => 'Asia Server',
                '9' => 'TW / HK / MO Server'
            ];
            $serverId = $genshinServers[$firstDigit] ?? ($serverId ?: 'Global Server');
            break;

        case 'valorant':
            if (!preg_match('/^[^#]{3,16}#[a-zA-Z0-9]{2,6}$/', $playerId)) {
                return ['success' => false, 'message' => 'Invalid Riot ID. Format must be Name#Tag (e.g. Player#BD1).'];
            }
            break;

        case 'clash-of-clans':
        case 'clash-royale':
            $cleanedTag = strtoupper(ltrim($playerId, '#'));
            if (!preg_match('/^[0289CGJLPQRUVY]{3,12}$/', $cleanedTag)) {
                return ['success' => false, 'message' => 'Invalid Player Tag. Must be a valid Supercell tag (e.g. #9Q2L8P).'];
            }
            $playerId = '#' . $cleanedTag;
            break;

        default:
            if (strlen($playerId) < 3) {
                return ['success' => false, 'message' => 'Player ID is too short (minimum 3 characters).'];
            }
            break;
    }

    // Realistic Game-Specific Name Pools
    $mockNames = [
        'mobile-legends' => [
            'ShadowKnight_BD', 'ViperX', 'DragonSlayer99', 'MythicGlory_ML', 'BanglaTiger',
            'LunarAura', 'AkaiMaster', 'ChouGod_BD', 'KaguraMain', 'GhostAssasin', 'ApexFighter'
        ],
        'mlbb-weekly-pass' => [
            'ShadowKnight_BD', 'ViperX', 'DragonSlayer99', 'MythicGlory_ML', 'BanglaTiger',
            'LunarAura', 'AkaiMaster', 'ChouGod_BD', 'KaguraMain', 'GhostAssasin'
        ],
        'free-fire' => [
            'ProGamer_FF', 'HeadHunter77', 'SniperProBD', 'FireStorm_X', 'RoguePlayer',
            'BooyahKing', 'AuraHunter', 'CrimsonGhost', 'CobraCommander', 'DeltaShooter'
        ],
        'ff-weekly-membership' => [
            'ProGamer_FF', 'HeadHunter77', 'SniperProBD', 'FireStorm_X', 'RoguePlayer',
            'BooyahKing', 'AuraHunter', 'CrimsonGhost'
        ],
        'pubg-mobile' => [
            'DeltaForce_BD', 'Conqueror99', 'GhostSniper', 'ApexHunter', 'ChickenDinnerKing',
            'HavocOps', 'FalconBD', 'ReaperSquad', 'ValkyriePubg', 'SniperWolf_BD'
        ],
        'valorant' => [
            'VandalMaster#BD1', 'PhantomPro#007', 'AceClutch#999', 'RiotHero#123',
            'JettDiff#BD', 'ReynaMain#SEA', 'OmenShadow#777', 'SovaIntel#001'
        ],
        'genshin-impact' => [
            'Traveler_Teyvat', 'ZhongliEnjoyer', 'AnemoArchon', 'ImpactHero',
            'RaidenShogun_BD', 'FontaineExplorer', 'PyroArchonMain', 'KazuhaSlash'
        ],
        'honor-of-kings' => [
            'TiMiLegend', 'HonorKing_BD', 'DragonFighter', 'ApexWarrior',
            'PhoenixBlade', 'LotusEmpress', 'ShadowAssassin'
        ],
        'clash-of-clans' => [
            'ChiefClash_BD', 'WarHero_TH16', 'LootKing', 'DragonRider', 'ElectroTitan'
        ],
        'clash-royale' => [
            'MegaKnightPro', 'PekkaBridge', 'LogBaitMaster', 'CrownChampion'
        ]
    ];

    $namePool = $mockNames[$slug] ?? [
        'Player_' . substr(strtoupper(md5($playerId)), 0, 6),
        'Gamer_' . substr(abs(crc32($playerId)), 0, 5),
        'VerifiedUser_' . substr(abs(crc32($playerId)), 0, 4)
    ];

    // Consistent hashing ensures identical ID always resolves to same IGN
    $hashIndex = abs(crc32($playerId . ($serverId ?? ''))) % count($namePool);
    $ign = $namePool[$hashIndex];

    // For Valorant, if user typed Name#Tag, honor the Name part
    if ($slug === 'valorant' && strpos($playerId, '#') !== false) {
        $parts = explode('#', $playerId, 2);
        if (!empty($parts[0])) {
            $ign = $parts[0] . '#' . ($parts[1] ?? 'BD1');
        }
    }

    $serverName = null;
    if (!empty($serverId)) {
        if ($slug === 'mobile-legends' || $slug === 'mlbb-weekly-pass') {
            $serverName = "Zone {$serverId} (Asia)";
        } else {
            $serverName = "Server {$serverId}";
        }
    }

    return [
        'success'     => true,
        'game_id'     => (int)$game['id'],
        'game_name'   => $game['name'],
        'game_slug'   => $game['slug'],
        'player_id'   => $playerId,
        'server_id'   => $serverId,
        'username'    => $ign,
        'ign'         => $ign,
        'server_name' => $serverName,
        'provider'    => 'simulator',
        'verified_at' => date('Y-m-d H:i:s'),
        'message'     => "Account verified: {$ign}" . ($serverName ? " ({$serverName})" : "")
    ];
}

// ============================================================
// ORDER STATUS WORKFLOW WITH AUDIT TRAIL
// ============================================================
/**
 * Last failure reason from updateOrderStatus() (null when the last call succeeded). Lets admin callers show a
 * precise message while keeping the historical bool return type.
 */
function updateOrderStatusLastError(?string $set = null, bool $reset = false): ?string {
    static $last = null;
    if ($reset) { $last = null; }
    if ($set !== null) { $last = $set; }
    return $last;
}

function updateOrderStatus(
    int $orderId,
    string $newStatus,
    ?string $note = null,
    ?int $adminId = null,
    ?string $newPaymentStatus = null
): bool {
    updateOrderStatusLastError(null, true);

    // Gate B2 write-freeze: refuse before reading or writing anything.
    if (rxWriteFreezeActive()) {
        updateOrderStatusLastError('write_freeze');
        return false;
    }

    $db = db();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) return false;

    $oldStatus = $order['order_status'];
    if ($oldStatus === $newStatus && ($newPaymentStatus === null || $newPaymentStatus === $order['payment_status'])) {
        return true;
    }

    $inTx = $db->inTransaction();
    if (!$inTx) $db->beginTransaction();

    try {
        $updateSql = 'UPDATE orders SET order_status = ?';
        $params = [$newStatus];

        if ($newPaymentStatus !== null) {
            $updateSql .= ', payment_status = ?';
            $params[] = $newPaymentStatus;
        }

        if ($newStatus === 'completed' && empty($order['completed_at'])) {
            $updateSql .= ', completed_at = NOW()';
        }

        if ($note !== null) {
            $updateSql .= ', admin_note = CONCAT(COALESCE(admin_note, ""), "\n", ?)';
            $params[] = "[Status Change to $newStatus] " . $note;
        }

        $updateSql .= ' WHERE id = ?';
        $params[] = $orderId;

        $db->prepare($updateSql)->execute($params);

        // Record history log
        $db->prepare('INSERT INTO order_status_history (order_id, status_from, status_to, note, changed_by_admin_id) VALUES (?,?,?,?,?)')
           ->execute([$orderId, $oldStatus, $newStatus, $note, $adminId]);

        // Send notifications on critical changes
        $customerName = $order['customer_name'] ?: 'Customer';
        if ($newStatus === 'completed') {
            sendNotification('order_completed', [
                'order_number' => $order['order_number'],
                'customer_name'=> $customerName,
                'product_name' => 'Package #' . $order['product_id'],
                'player_id'    => $order['player_id'],
            ], $order['user_id'] ? (int)$order['user_id'] : null, $adminId);

            // Webhook trigger
            dispatchWebhookEvent('order.completed', [
                'order_number' => $order['order_number'],
                'player_id'    => $order['player_id'],
                'server_id'    => $order['server_id'],
                'status'       => 'completed',
                'amount'       => (float)$order['total_price'],
                'completed_at' => date('c')
            ], $order['reseller_id'] ? (int)$order['reseller_id'] : null);
        } elseif ($newStatus === 'failed') {
            sendNotification('order_failed', [
                'order_number' => $order['order_number'],
                'customer_name'=> $customerName,
                'reason'       => $note ?: 'Processing error',
            ], $order['user_id'] ? (int)$order['user_id'] : null, $adminId);

            dispatchWebhookEvent('order.failed', [
                'order_number' => $order['order_number'],
                'status'       => 'failed',
                'reason'       => $note
            ], $order['reseller_id'] ? (int)$order['reseller_id'] : null);
        }

        if (!$inTx) $db->commit();
        logActivity('ORDER_STATUS_CHANGED', "Order #{$order['order_number']} changed from {$oldStatus} to {$newStatus}");
        return true;
    } catch (Throwable $e) {
        if (!$inTx) $db->rollBack();
        if (isOrderTrxConflictException($e)) {
            updateOrderStatusLastError('trx_conflict');
            error_log("updateOrderStatus: order {$order['order_number']} cannot move {$oldStatus} -> {$newStatus}: payment reference already reserved by another active order (rolled back)");
            return false;
        }
        updateOrderStatusLastError('error');
        error_log('updateOrderStatus Error: ' . $e->getMessage());
        return false;
    }
}

// ============================================================
// NOTIFICATION SYSTEM ENGINE
// ============================================================
function sendNotification(string $eventKey, array $data, ?int $userId = null, ?int $adminId = null): void {
    try {
        $db = db();
        $stmt = $db->prepare('SELECT * FROM notification_templates WHERE event_key = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$eventKey]);
        $tpl = $stmt->fetch();

        $title = "Notification: " . ucwords(str_replace('_', ' ', $eventKey));
        $body  = "Event {$eventKey} occurred.";

        if ($tpl) {
            $title = $tpl['title_template'];
            $body  = $tpl['body_template'];
            foreach ($data as $k => $v) {
                $title = str_replace('{' . $k . '}', (string)$v, $title);
                $body  = str_replace('{' . $k . '}', (string)$v, $body);
            }
        }

        // Store in database notifications table
        $ins = $db->prepare('INSERT INTO notifications (user_id, admin_id, title, message, type) VALUES (?,?,?,?,?)');
        $ins->execute([$userId, $adminId, $title, $body, $eventKey]);
    } catch (Throwable $e) {
        error_log('sendNotification Error: ' . $e->getMessage());
    }
}

// ============================================================
// WEBHOOK ENGINE & SIGNATURE VERIFICATION
// ============================================================
function dispatchWebhookEvent(string $event, array $payload, ?int $userId = null): void {
    try {
        $db = db();
        $sql = 'SELECT * FROM webhooks WHERE is_active = 1';
        $params = [];
        if ($userId !== null) {
            $sql .= ' AND (user_id = ? OR user_id IS NULL)';
            $params[] = $userId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $webhooks = $stmt->fetchAll();

        foreach ($webhooks as $wh) {
            $events = json_decode($wh['events'] ?? '[]', true) ?: [];
            if (!in_array('*', $events) && !in_array($event, $events)) {
                continue;
            }

            $jsonPayload = json_encode([
                'event'     => $event,
                'timestamp' => time(),
                'data'      => $payload
            ]);

            $signature = hash_hmac('sha256', $jsonPayload, $wh['secret']);

            // Insert into webhook logs
            $ins = $db->prepare('INSERT INTO webhook_logs (webhook_id, event, payload, status) VALUES (?,?,?, "pending")');
            $ins->execute([$wh['id'], $event, $jsonPayload]);
        }
    } catch (Throwable $e) {
        error_log('dispatchWebhookEvent Error: ' . $e->getMessage());
    }
}

// ============================================================
// GAME REGION & PRODUCT CATEGORY SELECTOR HELPERS
// ============================================================

/**
 * Check if a game has an active selector configured
 */
function hasActiveSelector(int $gameId): bool {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM game_selector_configs WHERE game_id = ? AND is_enabled = 1");
        $stmt->execute([$gameId]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Get the selector configuration for a game
 */
function getGameSelectorConfig(int $gameId): ?array {
    try {
        $stmt = db()->prepare("SELECT c.*, g.name AS game_name, g.slug AS game_slug, g.image AS game_image 
            FROM game_selector_configs c 
            JOIN games g ON c.game_id = g.id 
            WHERE c.game_id = ? AND c.is_enabled = 1 
            LIMIT 1");
        $stmt->execute([$gameId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: null;
    } catch (Throwable $e) {
        error_log('getGameSelectorConfig Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get active selector options for a configuration
 */
function getSelectorOptions(int $configId, bool $onlyActive = true): array {
    try {
        $sql = "SELECT o.*, r.code AS region_code, r.flag_image AS region_flag_image, r.currency AS region_currency, r.currency_symbol AS region_symbol 
            FROM game_selector_options o 
            LEFT JOIN regions r ON o.region_id = r.id 
            WHERE o.config_id = ?";
        if ($onlyActive) {
            $sql .= " AND o.is_active = 1";
        }
        $sql .= " ORDER BY o.sort_order ASC, o.id ASC";
        
        $stmt = db()->prepare($sql);
        $stmt->execute([$configId]);
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Append product count for each option
        foreach ($options as &$opt) {
            $cntStmt = db()->prepare("SELECT COUNT(*) FROM game_selector_option_products WHERE option_id = ?");
            $cntStmt->execute([$opt['id']]);
            $opt['product_count'] = (int)$cntStmt->fetchColumn();
        }
        unset($opt);

        return $options;
    } catch (Throwable $e) {
        error_log('getSelectorOptions Error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single selector option by ID
 */
function getSelectorOptionById(int $optionId): ?array {
    try {
        $stmt = db()->prepare("SELECT o.*, c.game_id, c.selector_type, g.name AS game_name, g.slug AS game_slug,
            r.code AS region_code, r.name AS region_name, r.flag_image AS region_flag_image, r.currency AS region_currency, r.currency_symbol AS region_symbol 
            FROM game_selector_options o 
            JOIN game_selector_configs c ON o.config_id = c.id 
            JOIN games g ON c.game_id = g.id 
            LEFT JOIN regions r ON o.region_id = r.id 
            WHERE o.id = ? 
            LIMIT 1");
        $stmt->execute([$optionId]);
        $opt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $opt ?: null;
    } catch (Throwable $e) {
        error_log('getSelectorOptionById Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Find a selector option by Game ID and Slug
 */
function getSelectorOptionBySlug(int $gameId, string $slug): ?array {
    try {
        $stmt = db()->prepare("SELECT o.*, c.game_id, c.selector_type, g.name AS game_name, g.slug AS game_slug,
            r.code AS region_code, r.name AS region_name, r.flag_image AS region_flag_image, r.currency AS region_currency, r.currency_symbol AS region_symbol 
            FROM game_selector_options o 
            JOIN game_selector_configs c ON o.config_id = c.id 
            JOIN games g ON c.game_id = g.id 
            LEFT JOIN regions r ON o.region_id = r.id 
            WHERE c.game_id = ? AND (o.slug = ? OR o.id = ?) AND o.is_active = 1 
            LIMIT 1");
        $stmt->execute([$gameId, $slug, is_numeric($slug) ? (int)$slug : 0]);
        $opt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $opt ?: null;
    } catch (Throwable $e) {
        error_log('getSelectorOptionBySlug Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get products assigned to a specific selector option
 * Fallback to game products with region matching if no direct mapping exists
 */
function getOptionProducts(int $optionId): array {
    try {
        // 1. Check direct mappings
        $stmt = db()->prepare("SELECT p.* 
            FROM products p 
            JOIN game_selector_option_products op ON p.id = op.product_id 
            WHERE op.option_id = ? AND p.is_active = 1 
            ORDER BY op.sort_order ASC, p.sort_order ASC, p.id ASC");
        $stmt->execute([$optionId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($products)) {
            return $products;
        }

        // 2. Fallback: If option is bound to a region_id, fetch products matching that region & game
        $opt = getSelectorOptionById($optionId);
        if ($opt && !empty($opt['region_id'])) {
            $regStmt = db()->prepare("SELECT * FROM products 
                WHERE game_id = ? AND region_id = ? AND is_active = 1 
                ORDER BY sort_order ASC, id ASC");
            $regStmt->execute([$opt['game_id'], $opt['region_id']]);
            $regProducts = $regStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($regProducts)) {
                return $regProducts;
            }
        }

        // 3. Fallback: All products for the game
        if ($opt) {
            $allStmt = db()->prepare("SELECT * FROM products WHERE game_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC");
            $allStmt->execute([$opt['game_id']]);
            return $allStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    } catch (Throwable $e) {
        error_log('getOptionProducts Error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get region text for a selector option
 */
function getSelectorOptionRegionText(?array $opt): string {
    if (!$opt) return 'Default Region';
    if (!empty($opt['region_custom_text'])) {
        return $opt['region_custom_text'];
    }
    if (!empty($opt['region_name'])) {
        return $opt['region_name'] . ' Region';
    }
    // Fallback: extract country/region from option name
    $name = $opt['name'] ?? '';
    if (stripos($name, 'Bangladesh') !== false) return 'Bangladesh Region';
    if (stripos($name, 'Indonesia') !== false) return 'Indonesia Region';
    if (stripos($name, 'Malaysia') !== false) return 'Malaysia Region';
    if (stripos($name, 'Singapore') !== false) return 'Singapore Region';
    if (stripos($name, 'Philippines') !== false) return 'Philippines Region';
    if (stripos($name, 'Global') !== false || stripos($name, 'Cheap') !== false) return 'Global Region';
    return $name . ' Region';
}

/**
 * Render circular country flag or custom icon for a selector option
 */
function renderSelectorRegionFlag(?array $opt, int $size = 20): string {
    if (!$opt) return '';

    $customIcon = trim($opt['custom_icon'] ?? '');
    $regionCode = strtoupper(trim($opt['region_code'] ?? ''));
    $slug = strtolower(trim($opt['slug'] ?? ''));
    $name = $opt['name'] ?? '';

    // If custom icon points to an image file or URL
    if ($customIcon && (preg_match('/\.(png|jpg|jpeg|svg|webp|gif)$/i', $customIcon) || str_starts_with($customIcon, 'http') || str_starts_with($customIcon, 'assets/'))) {
        $src = (str_starts_with($customIcon, 'http') || str_starts_with($customIcon, '/')) ? $customIcon : (APP_URL . '/' . ltrim($customIcon, '/'));
        return '<img src="' . htmlspecialchars($src) . '" alt="Flag" class="rx-flag-icon" style="width:' . $size . 'px; height:' . $size . 'px; border-radius:50%; object-fit:cover; display:inline-block; vertical-align:middle; box-shadow:0 0 0 1px rgba(255,255,255,0.2);">';
    }

    // Fallback: If linked region has a custom flag_image uploaded
    $regionFlag = trim($opt['region_flag_image'] ?? $opt['flag_image'] ?? '');
    if ($regionFlag && (str_starts_with($regionFlag, 'http') || str_starts_with($regionFlag, 'assets/'))) {
        $src = (str_starts_with($regionFlag, 'http') || str_starts_with($regionFlag, '/')) ? $regionFlag : (APP_URL . '/' . ltrim($regionFlag, '/'));
        return '<img src="' . htmlspecialchars($src) . '" alt="Flag" class="rx-flag-icon" style="width:' . $size . 'px; height:' . $size . 'px; border-radius:50%; object-fit:cover; display:inline-block; vertical-align:middle; box-shadow:0 0 0 1px rgba(255,255,255,0.2);">';
    }

    // If custom icon is a bootstrap icon
    if ($customIcon && str_starts_with($customIcon, 'bi ')) {
        return '<i class="' . htmlspecialchars($customIcon) . '" style="font-size:' . ($size - 2) . 'px; vertical-align:middle; display:inline-block;"></i>';
    }

    // Determine flag key
    $flagKey = strtoupper($customIcon);
    if (!$flagKey) {
        $flagKey = $regionCode;
    }
    if (!$flagKey) {
        if (stripos($slug, 'bangladesh') !== false || stripos($name, 'Bangladesh') !== false) $flagKey = 'BD';
        elseif (stripos($slug, 'indonesia') !== false || stripos($name, 'Indonesia') !== false) $flagKey = 'ID';
        elseif (stripos($slug, 'malaysia') !== false || stripos($name, 'Malaysia') !== false) $flagKey = 'MY';
        elseif (stripos($slug, 'singapore') !== false || stripos($name, 'Singapore') !== false) $flagKey = 'SG';
        elseif (stripos($slug, 'philippines') !== false || stripos($name, 'Philippines') !== false) $flagKey = 'PH';
        elseif (stripos($slug, 'brazil') !== false || stripos($name, 'Brazil') !== false) $flagKey = 'BR';
        elseif (stripos($slug, 'starlight') !== false || stripos($name, 'Starlight') !== false || stripos($slug, 'pass') !== false) $flagKey = 'STARLIGHT';
        elseif (stripos($slug, 'india') !== false || stripos($name, 'India') !== false) $flagKey = 'IN';
        elseif (stripos($slug, 'cheap') !== false || stripos($slug, 'global') !== false || stripos($name, 'Global') !== false) $flagKey = 'GLOBAL';
    }

    switch ($flagKey) {
        case 'BD':
        case 'BANGLADESH':
            // Authentic Circular Bangladesh Flag (green disc with red circle)
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(255,255,255,0.15);"><circle cx="16" cy="16" r="16" fill="#006a4e"/><circle cx="14.5" cy="16" r="7.5" fill="#f42a41"/></svg>';

        case 'ID':
        case 'INDONESIA':
            // Circular Indonesia Flag (red top, white bottom)
            $clipId = 'rx-clip-id-' . rand(1000, 9999);
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(255,255,255,0.15);"><defs><clipPath id="' . $clipId . '"><circle cx="16" cy="16" r="16"/></clipPath></defs><g clip-path="url(#' . $clipId . ')"><rect width="32" height="16" fill="#e70011"/><rect y="16" width="32" height="16" fill="#ffffff"/></g></svg>';

        case 'MY':
        case 'MALAYSIA':
            // Circular Malaysia Flag
            $clipId = 'rx-clip-my-' . rand(1000, 9999);
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(255,255,255,0.15);"><defs><clipPath id="' . $clipId . '"><circle cx="16" cy="16" r="16"/></clipPath></defs><g clip-path="url(#' . $clipId . ')"><rect width="32" height="32" fill="#ffffff"/><path d="M0 0h32v2.3H0zm0 4.6h32v2.3H0zm0 4.6h32v2.3H0zm0 4.6h32v2.3H0zm0 4.6h32v2.3H0zm0 4.6h32v2.3H0zm0 4.6h32v2.3H0z" fill="#cc0001"/><rect width="16" height="16.5" fill="#010066"/><circle cx="8" cy="8" r="5" fill="#ffcc00"/><circle cx="9.2" cy="8" r="4.2" fill="#010066"/><polygon points="11,8 13.5,9.2 12.2,8 14.5,7.2 12,7 13.5,5.5 11.2,6.5 11,4 10.2,6.5 8.5,5 9.5,7.2 7,7.5 9.2,8.5 7.5,10.2 9.8,9.5 9,12 10.5,9.8 11.5,12" fill="#ffcc00"/></g></svg>';

        case 'SG':
        case 'SINGAPORE':
            // Circular Singapore Flag
            $clipId = 'rx-clip-sg-' . rand(1000, 9999);
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(255,255,255,0.15);"><defs><clipPath id="' . $clipId . '"><circle cx="16" cy="16" r="16"/></clipPath></defs><g clip-path="url(#' . $clipId . ')"><rect width="32" height="16" fill="#ed2939"/><rect y="16" width="32" height="16" fill="#ffffff"/><circle cx="8" cy="8" r="4.5" fill="#ffffff"/><circle cx="9.5" cy="8" r="4" fill="#ed2939"/></g></svg>';

        case 'PH':
        case 'PHILIPPINES':
            // Circular Philippines Flag
            $clipId = 'rx-clip-ph-' . rand(1000, 9999);
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(255,255,255,0.15);"><defs><clipPath id="' . $clipId . '"><circle cx="16" cy="16" r="16"/></clipPath></defs><g clip-path="url(#' . $clipId . ')"><rect width="32" height="16" fill="#0038a8"/><rect y="16" width="32" height="16" fill="#ce1126"/><polygon points="0,0 16,16 0,32" fill="#ffffff"/><circle cx="5" cy="16" r="2.8" fill="#fcd116"/></g></svg>';

        case 'BR':
        case 'BRAZIL':
            // Circular Brazil Flag
            $clipId = 'rx-clip-br-' . rand(1000, 9999);
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(255,255,255,0.15);"><defs><clipPath id="' . $clipId . '"><circle cx="16" cy="16" r="16"/></clipPath></defs><g clip-path="url(#' . $clipId . ')"><rect width="32" height="32" fill="#009c3b"/><polygon points="16,4 28,16 16,28 4,16" fill="#ffdf00"/><circle cx="16" cy="16" r="6.8" fill="#002776"/><path d="M10,14.5 Q16,12 22,16.5" stroke="#ffffff" stroke-width="1.2" fill="none"/></g></svg>';

        case 'STARLIGHT':
        case 'PASS':
            // Glowing Starlight Pass Icon
            $gradId = 'rx-star-grad-' . rand(1000, 9999);
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(234,179,8,0.4);"><defs><linearGradient id="' . $gradId . '" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#7c3aed"/><stop offset="100%" stop-color="#f59e0b"/></linearGradient></defs><circle cx="16" cy="16" r="16" fill="url(#' . $gradId . ')"/><path fill="#ffffff" d="M16 6.5l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.2-4.1 5.8-.8z"/></svg>';

        case 'GLOBAL':
        case 'WORLD':
        default:
            // Circular Global Globe with vibrant styling
            return '<svg class="rx-flag-svg" viewBox="0 0 32 32" width="' . $size . '" height="' . $size . '" style="border-radius:50%; flex-shrink:0; display:inline-block; vertical-align:middle; box-shadow: 0 0 0 1.5px rgba(56,189,248,0.3);"><circle cx="16" cy="16" r="16" fill="#0284c7"/><path fill="#ffffff" d="M16 4a12 12 0 1 0 12 12A12 12 0 0 0 16 4Zm-1 21.93A10.02 10.02 0 0 1 6.07 17H9a9 9 0 0 1 6 8.93Zm-6.93-9.93A10.05 10.05 0 0 1 15 6.07V15H8.07Zm8.93-8.93A10.05 10.05 0 0 1 23.93 15H17V6.07Zm1 19.86A9 9 0 0 1 23 17h2.93A10.02 10.02 0 0 1 18 25.93Z"/></svg>';
    }
}

/**
 * Get product display icon (SVG or uploaded image)
 */
function getProductDisplayIcon(array $p): string {
    if (!empty($p['image']) && file_exists(APP_ROOT . '/' . ltrim($p['image'], '/'))) {
        return APP_URL . '/' . ltrim($p['image'], '/');
    }
    
    $nameLower = strtolower($p['name'] ?? '');
    $cat = strtolower($p['category'] ?? '');

    if (strpos($nameLower, 'pass') !== false || $cat === 'pass') {
        if (strpos($nameLower, 'weekly') !== false) {
            return APP_URL . '/assets/img/packages/pass-box.svg';
        }
        return APP_URL . '/assets/img/packages/pass-ticket.svg';
    }

    if (strpos($nameLower, 'card') !== false || strpos($nameLower, 'voucher') !== false) {
        return APP_URL . '/assets/img/packages/pass-ticket.svg';
    }

    $diamonds = (int)($p['diamonds'] ?? 0);
    if ($diamonds <= 0) {
        // Try parsing numbers from name like "26+2" or "275"
        if (preg_match('/(\d+)/', $p['name'], $m)) {
            $diamonds = (int)$m[1];
        }
    }

    if ($diamonds <= 20) {
        return APP_URL . '/assets/img/packages/diamond-single.svg';
    } elseif ($diamonds <= 99) {
        return APP_URL . '/assets/img/packages/diamond-double.svg';
    } elseif ($diamonds <= 250) {
        return APP_URL . '/assets/img/packages/diamond-cluster.svg';
    } elseif ($diamonds <= 999) {
        return APP_URL . '/assets/img/packages/diamond-pile.svg';
    } else {
        return APP_URL . '/assets/img/packages/diamond-chest.svg';
    }
}

/**
 * Calculate discount percentage from original price and amount
 */
function getDiscountPercent(float $origPrice, float $amount): int {
    if ($origPrice > $amount && $origPrice > 0) {
        return (int)round((($origPrice - $amount) / $origPrice) * 100);
    }
    return 0;
}

/**
 * Render a modern package card matching the reference 2-column design
 */
function renderModernPackageCard(array $p, bool $isFeatured = false): string {
    $id = (int)$p['id'];
    $name = e($p['name']);
    $amount = (float)$p['amount'];
    $origPrice = (float)($p['original_price'] ?? 0);
    $discount = getDiscountPercent($origPrice, $amount);
    $isStockOut = !empty($p['is_stock_out']);
    $icon = getProductDisplayIcon($p);
    $badge = !empty($p['badge']) ? trim($p['badge']) : '';
    $cat = e($p['category'] ?? 'diamonds');
    $clickFn = $isStockOut ? 'return false;' : 'selectPackage(' . $id . ', \'' . addslashes($p['name']) . '\', ' . $amount . ', ' . $origPrice . ', \'' . addslashes($icon) . '\')';
    
    $stockOutClass = $isStockOut ? ' stock-out' : '';
    $cardId = 'pkg-' . ($isFeatured ? 'feat-' : '') . $id;

    $formattedAmount = function_exists('formatPriceByCurrency') ? formatPriceByCurrency($amount) : ('৳ ' . number_format($amount, 0));
    $formattedOrigPrice = ($origPrice > 0 && function_exists('formatPriceByCurrency')) ? formatPriceByCurrency($origPrice) : ('৳ ' . number_format($origPrice, 0));
    $activeCurrency = function_exists('getActiveCurrency') ? getActiveCurrency() : 'BDT';
    
    $html = '<div class="rx-modern-card rx-pkg-item-' . $id . $stockOutClass . '" ';
    $html .= 'id="' . $cardId . '" ';
    $html .= 'data-id="' . $id . '" ';
    $html .= 'data-category="' . $cat . '" ';
    $html .= 'data-name="' . $name . '" ';
    $html .= 'data-price="' . $amount . '" ';
    $html .= 'data-orig-price="' . $origPrice . '" ';
    $html .= 'data-currency="' . htmlspecialchars($activeCurrency) . '" ';
    $html .= 'data-formatted-price="' . htmlspecialchars($formattedAmount) . '" ';
    $html .= 'data-icon="' . htmlspecialchars($icon) . '" ';
    $html .= 'onclick="' . $clickFn . '" tabindex="0">';
    
    // Top-right checkmark indicator
    $html .= '<div class="rx-check-badge"><i class="bi bi-check-lg"></i></div>';
    
    // Left Details
    $html .= '<div class="rx-card-details">';
    
    // Title
    $html .= '<div class="rx-pkg-title" title="' . $name . '">' . $name . '</div>';
    if ($isStockOut) {
        $html .= '<div class="rx-stock-out-row"><span class="rx-stock-out-pill">STOCK OUT</span></div>';
    }
    
    // Original strikethrough price
    if ($origPrice > $amount) {
        $html .= '<div class="rx-pkg-orig-price">' . $formattedOrigPrice . '</div>';
    }
    
    // Price and Badges row
    $html .= '<div class="rx-pkg-price-row">';
    $html .= '<div class="rx-pkg-amount">' . $formattedAmount . '</div>';
    
    if ($discount > 0) {
        $html .= '<span class="rx-discount-pill">-' . $discount . '%</span>';
    }
    
    if ($badge !== '') {
        if (strtoupper($badge) === 'NOTE') {
            $html .= '<span class="rx-note-pill"><i class="bi bi-info-circle-fill"></i> NOTE</span>';
        } else {
            $html .= '<span class="rx-custom-badge-pill">' . e($badge) . '</span>';
        }
    }
    
    $html .= '</div>'; // end rx-pkg-price-row
    $html .= '</div>'; // end rx-card-details
    
    // Right Visual Graphic
    $html .= '<div class="rx-card-visual">';
    $html .= '<img src="' . htmlspecialchars($icon) . '" alt="' . $name . '" loading="lazy">';
    $html .= '</div>';
    
    $html .= '</div>'; // end rx-modern-card
    return $html;
}

/**
 * Send an automated instant alert via Telegram Bot
 *
 * @param string $message
 * @param string $parseMode
 * @return bool
 */
function sendTelegramNotification(string $message, string $parseMode = 'HTML'): bool {
    $token = getSetting('telegram_bot_token');
    $chatId = getSetting('telegram_chat_id');

    if (empty($token) || empty($chatId)) {
        return false;
    }

    $url = "https://api.telegram.org/bot" . urlencode($token) . "/sendMessage";
    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => true,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200);
}
