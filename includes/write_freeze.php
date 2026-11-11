<?php
/**
 * includes/write_freeze.php
 *
 * TopUpers Gate 2B / B2 — Application-level ORDER WRITE-FREEZE (runbook §2.3 "Option A").
 *
 * Purpose
 * -------
 * Pause every code path that writes to `orders` (customer checkout, admin status changes/refunds, auto-dispatch,
 * admin API) during a future, separately-authorized DDL window, so the inspect->ALTER gap (runbook §1.6) and the
 * preflight->ADD UNIQUE KEY gap (runbook §2.1) cannot be hit by a concurrent write.
 *
 * Design
 * ------
 *  - The flag is a FILE (config/write_freeze.lock), not a database row:
 *      * enabling/disabling it performs ZERO database writes (no live-data change);
 *      * it keeps working while `orders` is metadata-locked or the DB is slow/unavailable;
 *      * `.htaccess` already denies `*.lock` files and the whole `config/` directory.
 *  - The flag is read only from the filesystem. Reading it never touches `orders` or any other table.
 *  - FAIL-CLOSED: the freeze is active whenever the flag file exists (even if it is empty/corrupt/unreadable).
 *  - Guards are placed BEFORE any beginTransaction()/INSERT/UPDATE. Reads (browsing, order history, admin lists)
 *    are NOT frozen.
 *  - Nothing in this file weakens CSRF/auth/validation; guards only add an early refusal.
 *
 * Test override: a test harness may define('RX_WRITE_FREEZE_FILE', '/abs/path') BEFORE this file loads so tests never
 * touch the real flag path. Production code never defines it.
 */

if (!function_exists('rxWriteFreezeFile')) {

    /** Absolute path of the freeze flag file. */
    function rxWriteFreezeFile(): string
    {
        if (defined('RX_WRITE_FREEZE_FILE') && is_string(RX_WRITE_FREEZE_FILE) && RX_WRITE_FREEZE_FILE !== '') {
            return RX_WRITE_FREEZE_FILE;
        }
        return dirname(__DIR__) . '/config/write_freeze.lock';
    }

    /** True while order writes must be refused. Fail-closed: existence of the flag is sufficient. */
    function rxWriteFreezeActive(): bool
    {
        $file = rxWriteFreezeFile();
        clearstatcache(true, $file);
        return file_exists($file);
    }

    /** Parsed flag details (best effort; a corrupt/empty flag still counts as active). */
    function rxWriteFreezeInfo(): array
    {
        $info = ['active' => false, 'file' => rxWriteFreezeFile(), 'enabled_at' => null, 'reason' => null, 'retry_after' => 300];
        if (!rxWriteFreezeActive()) {
            return $info;
        }
        $info['active'] = true;
        $raw = @file_get_contents($info['file']);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data)) {
            $info['enabled_at'] = isset($data['enabled_at']) ? (string)$data['enabled_at'] : null;
            $info['reason']     = isset($data['reason']) ? (string)$data['reason'] : null;
            if (isset($data['retry_after']) && is_numeric($data['retry_after'])) {
                $info['retry_after'] = max(30, min(3600, (int)$data['retry_after']));
            }
        }
        return $info;
    }

    /** Customer-safe message (no internals disclosed). */
    function rxWriteFreezeCustomerMessage(): string
    {
        return 'We are performing a short scheduled maintenance. Orders are temporarily paused — nothing was submitted or charged. Please try again in a few minutes.';
    }

    /** Admin-facing message. */
    function rxWriteFreezeAdminMessage(): string
    {
        return 'Order write-freeze is ACTIVE (scheduled database maintenance). Order, payment-status and refund changes are temporarily disabled. No change was made.';
    }

    /** Send 503 + Retry-After (safe to call before any output; silently skipped if headers already sent). */
    function rxWriteFreezeSendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        $info = rxWriteFreezeInfo();
        http_response_code(503);
        header('Retry-After: ' . (int)$info['retry_after']);
    }

    /**
     * Enable the freeze by atomically creating the flag file. Returns false if it already exists.
     * Used by database/write_freeze.php only.
     */
    function rxWriteFreezeEnable(string $reason = 'scheduled database maintenance', int $retryAfter = 300): bool
    {
        $file = rxWriteFreezeFile();
        $payload = json_encode([
            'enabled_at'  => date('c'),
            'reason'      => $reason,
            'retry_after' => max(30, min(3600, $retryAfter)),
        ]);
        $h = @fopen($file, 'x'); // exclusive create: fails if it already exists
        if ($h === false) {
            return false;
        }
        fwrite($h, (string)$payload);
        fclose($h);
        return true;
    }

    /** Disable the freeze by removing the flag file. Returns true if the freeze is now inactive. */
    function rxWriteFreezeDisable(): bool
    {
        $file = rxWriteFreezeFile();
        if (file_exists($file)) {
            @unlink($file);
        }
        return !rxWriteFreezeActive();
    }
}
