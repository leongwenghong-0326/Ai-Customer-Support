<?php
/**
 * NovaServe AI Intelligent Customer Support Platform
 * Global Configuration & Environment Bootstrap
 */

// Strict error reporting for server logs, friendly errors for users
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Define Filesystem Constants
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__));
defined('CONFIG_PATH') or define('CONFIG_PATH', ROOT_PATH . DS . 'config');
defined('INCLUDES_PATH') or define('INCLUDES_PATH', ROOT_PATH . DS . 'includes');
defined('UPLOADS_PATH') or define('UPLOADS_PATH', ROOT_PATH . DS . 'uploads');

// Dynamic Base URL Resolution (Supports localhost, XAMPP subfolders, and cPanel root/subdomains)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Detect if running from /admin, /api, or root
$baseFolder = preg_replace('#/(admin|api)$#', '', rtrim($scriptDir, '/'));
$baseUrl = rtrim($protocol . $host . $baseFolder, '/') . '/';
defined('BASE_URL') or define('BASE_URL', $baseUrl);
defined('ASSETS_URL') or define('ASSETS_URL', BASE_URL . 'assets/');

// Default Database Configuration Constants
defined('DB_HOST') or define('DB_HOST', '127.0.0.1');
defined('DB_PORT') or define('DB_PORT', 3307); // Auto-detected XAMPP port, installer can override
defined('DB_NAME') or define('DB_NAME', 'novaserve_db');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

// System Settings Defaults
defined('APP_NAME') or define('APP_NAME', 'NovaServe AI');
defined('APP_VERSION') or define('APP_VERSION', '1.0.0');

// Lock File Check
define('LOCK_FILE', CONFIG_PATH . DS . 'installed.lock');

// Auto-check installation status
$isInstalled = file_exists(LOCK_FILE);
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!$isInstalled && $currentScript !== 'install.php' && PHP_SAPI !== 'cli') {
    // Redirect to installer if not installed yet
    header('Location: ' . BASE_URL . 'install.php');
    exit;
}
