<?php
// user/profile.php
// TOPUPERS — Customer Profile & Account Security
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();
$pageTitle = 'Personal Profile';
$pageDescription = 'Manage your TOPUPERS customer profile details and security settings.';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf   = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrf)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $phone    = sanitize($_POST['phone'] ?? '');

        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $error = 'Full name must be between 2 and 100 characters.';
        } elseif (!empty($phone) && !preg_match('/^[0-9+\s\-()]{7,20}$/', $phone)) {
            $error = 'Please enter a valid phone number.';
        } else {
            $avatarUpdated = false;
            $newAvatarName = null;

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['avatar'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'File upload failed. Error code: ' . $file['error'];
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $error = 'Avatar file size must not exceed 2MB.';
                } else {
                    $mime = mime_content_type($file['tmp_name']);
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($mime, $allowedMimes)) {
                        $error = 'Invalid image type. Please upload a JPG, PNG, or WebP image.';
                    } else {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            $ext = 'jpg';
                        }
                        $newAvatarName = 'avatar_' . $user['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetDir = APP_ROOT . '/uploads/avatars';
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        $targetPath = $targetDir . '/' . $newAvatarName;

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            if (!empty($user['avatar']) && $user['avatar'] !== 'default.png') {
                                $oldPath = $targetDir . '/' . basename($user['avatar']);
                                if (file_exists($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                            $avatarUpdated = true;
                        } else {
                            $error = 'Failed to save avatar image to server.';
                        }
                    }
                }
            }

            if (empty($error)) {
                if ($avatarUpdated) {
                    $stmt = db()->prepare('UPDATE users SET full_name = ?, phone = ?, avatar = ? WHERE id = ?');
                    $stmt->execute([$fullName, $phone, $newAvatarName, $user['id']]);
                } else {
                    $stmt = db()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
                    $stmt->execute([$fullName, $phone, $user['id']]);
                }
                $msg = 'Profile details updated successfully!';
                
                // Refresh user
                $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$user['id']]);
                $user = $stmt->fetch();
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hash, $user['id']]);
            $msg = 'Password changed successfully!';

            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
        }
    }
}

$avatarUrl = userAvatarUrl($user['avatar'] ?? null);
$userInitials = userInitials($user['full_name'] ?: $user['username']);
$activeNav = 'profile';

$extraCss = '<link rel="stylesheet" href="' . APP_URL . '/assets/css/customer-dashboard.css?v=' . filemtime(__DIR__ . '/../assets/css/customer-dashboard.css') . '">';

include __DIR__ . '/../includes/header.php';
?>

<div class="tp-dashboard-page">
  <div class="tp-ambient-glow"></div>

  <div class="container py-4 py-lg-5 position-relative" style="z-index: 1;">

    <!-- Page Header -->
    <div class="tp-page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="tp-brand-badge mb-2">
          <i class="bi bi-person-fill"></i> ACCOUNT PROFILE
        </div>
        <h1 class="tp-page-title">
          Personal Profile & Security
        </h1>
        <p class="tp-page-subtitle">
          Manage your personal gamer details, avatar photo, and authentication credentials.
        </p>
      </div>

      <div class="tp-header-actions">
        <a href="<?= APP_URL ?>/user/dashboard.php" class="tp-btn tp-btn-outline tp-btn-sm">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($msg): ?>
      <div class="tp-alert tp-alert-success" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= e($msg) ?></div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="tp-alert tp-alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= e($error) ?></div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Mobile Navigation Bar (< 992px) -->
    <?php include __DIR__ . '/includes/dashboard_mobile_nav.php'; ?>

    <div class="row g-4 mt-1">

      <!-- Left Sidebar (Desktop) -->
      <?php include __DIR__ . '/includes/dashboard_sidebar.php'; ?>

      <!-- Main Profile Column -->
      <div class="col-lg-9">

        <!-- Personal Info Form -->
        <section class="tp-card">
          <div class="tp-card-header">
            <h3 class="tp-card-title">
              <i class="bi bi-person-vcard-fill"></i> Personal Information
            </h3>
            <span class="badge" style="background:rgba(139,92,246,0.15);color:var(--tp-purple-light);font-size:0.75rem;">
              Editable Fields
            </span>
          </div>

          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            <?= csrfField() ?>

            <!-- Avatar Preview & Input -->
            <div class="d-flex flex-column flex-sm-row align-items-center gap-3 mb-4 p-3 rounded" style="background:var(--tp-surface);border:1px solid var(--tp-border);">
              <div class="tp-avatar-wrapper flex-shrink-0" style="width:72px;height:72px;">
                <?php if ($avatarUrl): ?>
                  <img src="<?= e($avatarUrl) ?>" id="profileAvatarPreview" alt="Avatar" class="tp-avatar-img">
                <?php else: ?>
                  <div class="tp-avatar-initials" id="profileAvatarInitials"><?= e($userInitials) ?></div>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1 w-100 text-center text-sm-start">
                <label class="tp-form-label mb-1" for="profileAvatarInput">Profile Picture / Avatar</label>
                <input type="file" id="profileAvatarInput" name="avatar" class="form-control form-control-sm" accept="image/png, image/jpeg, image/webp" onchange="previewProfileAvatar(this)" style="background:var(--tp-card);color:var(--tp-text);border-color:var(--tp-border);font-size:0.85rem;">
                <small style="font-size:0.75rem;color:var(--tp-text-muted);">Max 2MB. Supports JPG, PNG, and WebP.</small>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="tp-form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
                <input type="text" id="full_name" name="full_name" class="tp-input" value="<?= e($user['full_name'] ?? '') ?>" required maxlength="100">
              </div>

              <div class="col-md-6">
                <label class="tp-form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="tp-input" value="<?= e($user['phone'] ?? '') ?>" placeholder="01XXXXXXXXX" maxlength="20">
              </div>

              <div class="col-md-6">
                <label class="tp-form-label">Username</label>
                <input type="text" class="tp-input" value="<?= e($user['username']) ?>" readonly>
                <small style="font-size:0.72rem;color:var(--tp-text-dim);">Username cannot be modified.</small>
              </div>

              <div class="col-md-6">
                <label class="tp-form-label">Email Address</label>
                <input type="text" class="tp-input" value="<?= e($user['email']) ?>" readonly>
                <small style="font-size:0.72rem;color:var(--tp-text-dim);">Email is verified with your account.</small>
              </div>

              <div class="col-md-6">
                <label class="tp-form-label">Member Since</label>
                <input type="text" class="tp-input" value="<?= date('d F Y', strtotime($user['created_at'])) ?>" readonly>
              </div>

              <div class="col-md-6">
                <label class="tp-form-label">Account Verification</label>
                <input type="text" class="tp-input" value="<?= (int)($user['is_verified'] ?? 0) === 1 ? 'Verified Gamer' : 'Standard Member' ?>" readonly>
              </div>
            </div>

            <div class="mt-4">
              <button type="submit" class="tp-btn tp-btn-primary tp-btn-submit-responsive">
                <i class="bi bi-save"></i> Save Profile Changes
              </button>
            </div>
          </form>
        </section>

        <!-- Change Password Section -->
        <section class="tp-card" id="security">
          <div class="tp-card-header">
            <h3 class="tp-card-title">
              <i class="bi bi-shield-lock-fill"></i> Change Account Password
            </h3>
            <span class="badge" style="background:rgba(245,158,11,0.15);color:#FBBF24;font-size:0.75rem;">
              Security Check
            </span>
          </div>

          <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <?= csrfField() ?>

            <div class="row g-3">
              <div class="col-md-12">
                <label class="tp-form-label" for="current_password">Current Password <span class="text-danger">*</span></label>
                <input type="password" id="current_password" name="current_password" class="tp-input" placeholder="Your current password" required autocomplete="current-password">
              </div>

              <div class="col-md-6">
                <label class="tp-form-label" for="new_password">New Password <span class="text-danger">*</span></label>
                <input type="password" id="new_password" name="new_password" class="tp-input" placeholder="Min 6 characters" minlength="6" required autocomplete="new-password">
              </div>

              <div class="col-md-6">
                <label class="tp-form-label" for="confirm_password">Confirm New Password <span class="text-danger">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="tp-input" placeholder="Repeat new password" minlength="6" required autocomplete="new-password">
              </div>
            </div>

            <div class="mt-4">
              <button type="submit" class="tp-btn tp-btn-outline tp-btn-submit-responsive">
                <i class="bi bi-lock-fill"></i> Update Password
              </button>
            </div>
          </form>
        </section>

      </div>
    </div>

  </div>
</div>

<script>
function previewProfileAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      let img = document.getElementById('profileAvatarPreview');
      const initials = document.getElementById('profileAvatarInitials');
      if (!img) {
        img = document.createElement('img');
        img.id = 'profileAvatarPreview';
        img.className = 'tp-avatar-img';
        if (initials) initials.parentNode.replaceChild(img, initials);
      }
      img.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
