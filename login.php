<?php
/**
 * NovaServe AI - User Authentication / Sign In
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/logger.php';

// Redirect if already authenticated
if (is_logged_in()) {
    $current = current_user();
    if (has_role(['super_admin', 'support_staff', 'content_manager'])) {
        header('Location: ' . BASE_URL . 'admin/index.php');
    } else {
        header('Location: ' . BASE_URL . 'index.php');
    }
    exit;
}

$error = '';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $email = clean_string($_POST['email'] ?? '', 150);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please provide both email address and password.';
        } else {
            $user = $db->fetchOne(
                "SELECT u.*, r.role_key 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.email = :email",
                ['email' => $email]
            );

            if ($user && verify_password($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account status is currently ' . $user['status'] . '. Please contact support.';
                    log_activity($user['id'], 'Failed Login', "Attempted login on {$user['status']} account.");
                } else {
                    // Success
                    login_user($user);
                    log_activity($user['id'], 'Login', 'User authenticated successfully.');

                    $redirect = $_SESSION['redirect_after_login'] ?? '';
                    unset($_SESSION['redirect_after_login']);

                    if (!empty($redirect)) {
                        header('Location: ' . $redirect);
                        exit;
                    }

                    if (in_array($user['role_key'], ['super_admin', 'support_staff', 'content_manager'], true)) {
                        header('Location: ' . BASE_URL . 'admin/index.php');
                    } else {
                        header('Location: ' . BASE_URL . 'index.php');
                    }
                    exit;
                }
            } else {
                $error = 'Invalid email address or authentication password.';
                log_activity(null, 'Failed Login', "Failed login attempt for email: {$email}");
            }
        }
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card card-tech p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="brand-logo-icon mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.4rem;">
                        <i class="fa-solid fa-lock text-primary"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Welcome Back</h3>
                    <p class="text-secondary small">Access your NovaServe client or staff account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 small border-0 shadow-sm mb-4" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-card-dark border-secondary-subtle text-secondary"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" id="loginEmail" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-2" placeholder="name@company.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-white small fw-bold mb-0">Password</label>
                            <a href="faq.php?q=password" class="text-secondary small text-decoration-none">Forgot password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-card-dark border-secondary-subtle text-secondary"><i class="fa-solid fa-key"></i></span>
                            <input type="password" name="password" id="loginPassword" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-2" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-gradient w-100 py-2 mb-3">
                        Sign In to NovaServe
                    </button>
                </form>

                <div class="text-center pt-3 border-top border-secondary-subtle small text-secondary">
                    Don't have an account yet? 
                    <a href="register.php" class="text-primary text-decoration-none fw-semibold">Register here</a>
                </div>

                <!-- Interactive Quick Seed Logins Box -->
                <div class="p-3 mt-4 rounded-3 bg-dark-glass border border-secondary-subtle small">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-white"><i class="fa-solid fa-seedling text-primary me-1"></i> Quick Seed Logins:</strong>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="fa-solid fa-hand-pointer me-1"></i>Click to auto-fill</span>
                    </div>

                    <div class="d-flex flex-column gap-1">
                        <!-- Admin Quick Login -->
                        <div class="quick-login-item p-2 rounded-2 d-flex justify-content-between align-items-center" 
                             data-email="admin@novaserve.com" 
                             data-password="Admin@123456" 
                             title="Click to auto-fill Super Admin credentials" 
                             role="button" 
                             tabindex="0">
                            <div>
                                <span class="badge bg-danger-soft text-danger me-1">Admin</span>
                                <code>admin@novaserve.com</code>
                            </div>
                            <span class="text-secondary small fill-icon"><i class="fa-solid fa-arrow-turn-down"></i></span>
                        </div>

                        <!-- Staff Quick Login -->
                        <div class="quick-login-item p-2 rounded-2 d-flex justify-content-between align-items-center" 
                             data-email="staff@novaserve.com" 
                             data-password="Admin@123456" 
                             title="Click to auto-fill Support Staff credentials" 
                             role="button" 
                             tabindex="0">
                            <div>
                                <span class="badge bg-warning-soft text-warning me-1">Staff</span>
                                <code>staff@novaserve.com</code>
                            </div>
                            <span class="text-secondary small fill-icon"><i class="fa-solid fa-arrow-turn-down"></i></span>
                        </div>

                        <!-- Customer Quick Login -->
                        <div class="quick-login-item p-2 rounded-2 d-flex justify-content-between align-items-center" 
                             data-email="customer@example.com" 
                             data-password="Admin@123456" 
                             title="Click to auto-fill Customer credentials" 
                             role="button" 
                             tabindex="0">
                            <div>
                                <span class="badge bg-success-soft text-success me-1">Customer</span>
                                <code>customer@example.com</code>
                            </div>
                            <span class="text-secondary small fill-icon"><i class="fa-solid fa-arrow-turn-down"></i></span>
                        </div>
                    </div>

                    <!-- Shared Password Row -->
                    <div class="mt-2 pt-2 border-top border-secondary-subtle d-flex justify-content-between align-items-center text-secondary" style="font-size: 0.75rem;">
                        <span>Password for all:</span>
                        <code class="text-light quick-password-code" role="button" title="Click to fill password only">Admin@123456</code>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const emailInput = document.getElementById('loginEmail');
                    const passwordInput = document.getElementById('loginPassword');

                    function triggerAutoFill(email, password, roleLabel) {
                        if (emailInput && email) {
                            emailInput.value = email;
                            emailInput.dispatchEvent(new Event('input', { bubbles: true }));
                            // Brief visual flash to show it filled
                            emailInput.classList.add('is-valid');
                            setTimeout(() => emailInput.classList.remove('is-valid'), 800);
                        }
                        if (passwordInput && password) {
                            passwordInput.value = password;
                            passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                            passwordInput.classList.add('is-valid');
                            setTimeout(() => passwordInput.classList.remove('is-valid'), 800);
                        }
                        if (window.showToast) {
                            window.showToast('Filled credentials for ' + (roleLabel || 'seed account') + '!', 'info');
                        }
                    }

                    document.querySelectorAll('.quick-login-item').forEach(item => {
                        const email = item.getAttribute('data-email');
                        const password = item.getAttribute('data-password');
                        const role = item.querySelector('.badge')?.innerText || 'account';

                        const handleFill = () => triggerAutoFill(email, password, role);

                        item.addEventListener('click', handleFill);
                        item.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                handleFill();
                            }
                        });
                    });

                    const passCode = document.querySelector('.quick-password-code');
                    if (passCode) {
                        passCode.addEventListener('click', () => {
                            if (passwordInput) {
                                passwordInput.value = 'Admin@123456';
                                passwordInput.focus();
                                if (window.showToast) {
                                    window.showToast('Default password filled!', 'info');
                                }
                            }
                        });
                    }
                });
                </script>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
