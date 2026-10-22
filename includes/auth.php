<?php
/**
 * NovaServe AI - Authentication & Role-Based Access Control (RBAC)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'role_id' => $_SESSION['user_role_id'] ?? 4,
        'role_key' => $_SESSION['user_role_key'] ?? 'customer',
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? ''
    ];
}

function has_role(array|string $roles): bool {
    if (!is_logged_in()) return false;
    $currentRole = $_SESSION['user_role_key'] ?? '';
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return $currentRole === $roles;
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please sign in to access this page.';
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function require_role(array|string $allowedRoles): void {
    require_login();
    if (!has_role($allowedRoles)) {
        $_SESSION['flash_error'] = 'Access Denied: You do not have sufficient permissions to access this area.';
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

// Perform login and session regeneration
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_role_id'] = (int)$user['role_id'];
    $_SESSION['user_role_key'] = $user['role_key'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';

    // Update last_login_at
    try {
        $db = Database::getInstance();
        $db->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
    } catch (Exception $e) {
        error_log('Error updating last_login_at: ' . $e->getMessage());
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
