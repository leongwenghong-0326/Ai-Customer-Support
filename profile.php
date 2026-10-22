<?php
/**
 * NovaServe AI - Customer Profile & Security Center
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/logger.php';

require_login();

$currentUser = current_user();
$db = Database::getInstance();

$errors = [];
$successMsg = '';

// Fetch fresh user profile
$user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $currentUser['id']]);

// Aggregate statistics
$ticketCountRow = $db->fetchOne("SELECT COUNT(*) as total FROM support_tickets WHERE customer_id = :id", ['id' => $currentUser['id']]);
$ticketCount = (int)($ticketCountRow['total'] ?? 0);

$convCountRow = $db->fetchOne("SELECT COUNT(*) as total FROM conversations WHERE customer_id = :id", ['id' => $currentUser['id']]);
$convCount = (int)($convCountRow['total'] ?? 0);

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token expired. Please retry.';
    }

    $fullName = clean_string($_POST['full_name'] ?? '', 100);
    $phone = clean_string($_POST['phone'] ?? '', 30);
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (mb_strlen($fullName) < 2) {
        $errors[] = 'Full name must contain at least 2 characters.';
    }

    $updateData = [
        'full_name' => $fullName,
        'phone' => $phone
    ];

    // Password modification logic
    if (!empty($newPass)) {
        if (empty($currentPass)) {
            $errors[] = 'You must enter your current password to authorize a password change.';
        } elseif (!verify_password($currentPass, $user['password_hash'])) {
            $errors[] = 'The current password you entered is incorrect.';
        } elseif (strlen($newPass) < 8) {
            $errors[] = 'New password must contain at least 8 characters.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $updateData['password_hash'] = hash_password($newPass);
        }
    }

    if (empty($errors)) {
        try {
            $db->update('users', $updateData, 'id = :id', ['id' => $currentUser['id']]);
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_phone'] = $phone;
            $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $currentUser['id']]);
            log_activity($currentUser['id'], 'Profile Updated', 'Customer updated account information.');
            $successMsg = 'Profile credentials updated successfully.';
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $errors[] = 'Failed to update profile due to database error.';
        }
    }
}

$pageTitle = 'Profile & Security';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Profile Sidebar Cards -->
        <div class="col-lg-4">
            <div class="card card-tech p-4 text-center mb-4">
                <div class="user-avatar-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 1.75rem;">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <h4 class="fw-bold text-white mb-1"><?php echo e($user['full_name']); ?></h4>
                <p class="text-secondary small mb-3"><?php echo e($user['email']); ?></p>
                <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill font-monospace">
                    ROLE: <?php echo strtoupper($currentUser['role_key']); ?>
                </span>
            </div>

            <!-- Stats KPI Card -->
            <div class="card card-tech p-4">
                <h6 class="fw-bold text-white mb-3 border-bottom border-secondary-subtle pb-2">Engagement Summary</h6>
                <div class="d-flex justify-content-between py-2 border-bottom border-dark-subtle small">
                    <span class="text-secondary">Total Support Tickets:</span>
                    <strong class="text-white"><?php echo $ticketCount; ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-dark-subtle small">
                    <span class="text-secondary">AI Support Conversations:</span>
                    <strong class="text-white"><?php echo $convCount; ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-dark-subtle small">
                    <span class="text-secondary">Member Since:</span>
                    <strong class="text-white"><?php echo date('M Y', strtotime($user['created_at'])); ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 small">
                    <span class="text-secondary">Last Authenticated:</span>
                    <strong class="text-white"><?php echo $user['last_login_at'] ? date('M d, H:i', strtotime($user['last_login_at'])) : 'Recent'; ?></strong>
                </div>
            </div>
        </div>

        <!-- Profile Edit Form -->
        <div class="col-lg-8">
            <div class="card card-tech p-4 p-md-5">
                <h4 class="fw-bold text-white mb-4 border-bottom border-secondary-subtle pb-3">Account Settings</h4>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check me-2 fs-5"></i>
                        <div><?php echo e($successMsg); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4 shadow-sm">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <?php echo csrf_field(); ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo e($user['full_name']); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Email Address (Immutable)</label>
                            <input type="email" value="<?php echo e($user['email']); ?>" class="form-control bg-card-dark border-secondary-subtle text-muted shadow-none" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="+1-555-0199">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Account Status</label>
                            <input type="text" value="<?php echo strtoupper($user['status']); ?>" class="form-control bg-card-dark border-secondary-subtle text-success shadow-none font-monospace" readonly>
                        </div>
                    </div>

                    <h5 class="fw-bold text-white mb-3 pt-3 border-top border-secondary-subtle">Security & Password Update</h5>
                    <p class="text-secondary small mb-3">Leave blank if you do not wish to alter your authentication password.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label text-white small fw-bold">Current Password</label>
                            <input type="password" name="current_password" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Enter current password to verify">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="At least 8 characters">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Repeat new password">
                        </div>
                    </div>

                    <div class="text-end pt-3 border-top border-secondary-subtle">
                        <button type="submit" class="btn btn-primary-gradient px-4">
                            Save Account Updates
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
