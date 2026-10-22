<?php
/**
 * NovaServe AI - Customer Registration Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/logger.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$errors = [];
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token invalid or expired. Please refresh.';
    }

    $fullName = clean_string($_POST['full_name'] ?? '', 100);
    $email = clean_string($_POST['email'] ?? '', 150);
    $phone = clean_string($_POST['phone'] ?? '', 30);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (mb_strlen($fullName) < 2) $errors[] = 'Full name must contain at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
    if ($password !== $confirmPassword) $errors[] = 'Password and confirmation do not match.';

    // Check if email already registered
    if (empty($errors)) {
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
        if ($existing) {
            $errors[] = 'This email address is already associated with an active account.';
        }
    }

    if (empty($errors)) {
        try {
            $customerRoleId = 4; // customer role
            $hash = hash_password($password);

            $newUserId = $db->insert('users', [
                'role_id' => $customerRoleId,
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => $hash,
                'phone' => $phone ?: null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $newUser = $db->fetchOne(
                "SELECT u.*, r.role_key FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id",
                ['id' => $newUserId]
            );

            log_activity($newUserId, 'Customer Registered', "New customer account registered: {$email}");
            login_user($newUser);

            $_SESSION['flash_success'] = 'Welcome to NovaServe AI! Your customer account has been created.';
            header('Location: ' . BASE_URL . 'index.php');
            exit;

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'An internal database error occurred. Please retry.';
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-md-3">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6 col-xl-5">
            <div class="card card-tech p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="brand-logo-icon mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.4rem;">
                        <i class="fa-solid fa-user-plus text-primary"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Create Account</h3>
                    <p class="text-secondary small">Join NovaServe to submit tickets and manage support requests</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2 px-3 small border-0 shadow-sm mb-4">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" value="<?php echo e($_POST['full_name'] ?? ''); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="Jane Doe" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="jane@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Phone Number (Optional)</label>
                        <input type="text" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="+1-555-0199">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="Min. 8 characters" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="Repeat password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-gradient w-100 py-2 mb-3">
                        Complete Account Registration
                    </button>
                </form>

                <div class="text-center pt-3 border-top border-secondary-subtle small text-secondary">
                    Already registered? 
                    <a href="login.php" class="text-primary text-decoration-none fw-semibold">Sign in here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
