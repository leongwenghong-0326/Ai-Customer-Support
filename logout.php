<?php
/**
 * NovaServe AI - Logout Endpoint
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/logger.php';

$currentUser = current_user();
if ($currentUser) {
    log_activity($currentUser['id'], 'Logout', 'User signed out gracefully.');
}

logout_user();

header('Location: ' . BASE_URL . 'index.php');
exit;
