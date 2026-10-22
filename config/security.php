<?php
/**
 * NovaServe AI - Security & Sanitization Layer
 * CSRF Protection, XSS Filtering, Safe Output Helpers
 */

// Generate CSRF token if not already in session
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Render hidden CSRF input field for forms
function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// Verify submitted CSRF token
function verify_csrf_token(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// XSS Output Escaping
function e(?string $string): string {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Clean and sanitize string input
function clean_string(?string $input, int $maxLength = 5000): string {
    if ($input === null) return '';
    $clean = trim($input);
    $clean = strip_tags($clean);
    return mb_substr($clean, 0, $maxLength, 'UTF-8');
}

// Clean integer input
function clean_int($val, int $default = 0): int {
    if (!isset($val) || !is_numeric($val)) return $default;
    return (int)$val;
}

// Password hashing wrapper
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

// Password verify wrapper
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// Client IP resolver with proxy handling
function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}
