<?php
/**
 * NovaServe AI - Notifications REST API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUser = current_user();
$db = Database::getInstance();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        $notifId = clean_int($payload['notification_id'] ?? 0);
        $action = trim($payload['action'] ?? '');

        if ($notifId > 0) {
            $db->query("UPDATE notifications SET is_read = 1 WHERE id = :id", ['id' => $notifId]);
        } else {
            // Mark all read for this user / role
            if ($currentUser['role_key'] === 'super_admin') {
                $db->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND (user_id = :uid OR role_target IN ('super_admin', 'support_staff', 'content_manager') OR (user_id IS NULL AND role_target IS NULL))", ['uid' => $currentUser['id']]);
            } else {
                $db->query(
                    "UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND (user_id = :uid OR role_target = :rtarget OR (user_id IS NULL AND role_target IS NULL))",
                    ['uid' => $currentUser['id'], 'rtarget' => $currentUser['role_key']]
                );
            }
        }

        $newUnread = get_unread_notifications_count($currentUser['id'], $currentUser['role_key']);

        echo json_encode([
            'success' => true,
            'unread_count' => $newUnread
        ]);
        exit;
    }

    // GET notifications list
    $list = get_user_notifications($currentUser['id'], $currentUser['role_key'], 20);
    $unreadCount = get_unread_notifications_count($currentUser['id'], $currentUser['role_key']);

    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount,
        'notifications' => $list
    ]);
} catch (Exception $e) {
    error_log("Notifications API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
