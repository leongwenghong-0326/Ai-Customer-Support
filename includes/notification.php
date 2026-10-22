<?php
/**
 * NovaServe AI - Internal Real-Time Notification Service
 */

require_once __DIR__ . '/../config/database.php';

function create_notification(?int $userId, ?string $roleTarget, string $title, string $message, string $type, ?int $referenceId = null): bool {
    try {
        $db = Database::getInstance();
        $db->insert('notifications', [
            'user_id' => $userId,
            'role_target' => $roleTarget,
            'title' => mb_substr($title, 0, 150),
            'message' => mb_substr($message, 0, 500),
            'type' => $type,
            'reference_id' => $referenceId,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

function get_unread_notifications_count(?int $userId, ?string $roleTarget = null): int {
    try {
        $db = Database::getInstance();
        $params = [];
        $where = "is_read = 0";
        if ($roleTarget === 'super_admin') {
            $where .= " AND (user_id = :uid OR role_target IN ('super_admin', 'support_staff', 'content_manager') OR (user_id IS NULL AND role_target IS NULL))";
            $params['uid'] = $userId;
        } elseif ($userId !== null && $roleTarget !== null) {
            $where .= " AND (user_id = :uid OR role_target = :rtarget OR (user_id IS NULL AND role_target IS NULL))";
            $params['uid'] = $userId;
            $params['rtarget'] = $roleTarget;
        } elseif ($userId !== null) {
            $where .= " AND user_id = :uid";
            $params['uid'] = $userId;
        } elseif ($roleTarget !== null) {
            $where .= " AND role_target = :rtarget";
            $params['rtarget'] = $roleTarget;
        }
        $row = $db->fetchOne("SELECT COUNT(*) as total FROM notifications WHERE {$where}", $params);
        return (int)($row['total'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

function get_user_notifications(?int $userId, ?string $roleTarget = null, int $limit = 15): array {
    try {
        $db = Database::getInstance();
        $params = [];
        $where = "1=1";
        if ($roleTarget === 'super_admin') {
            $where .= " AND (user_id = :uid OR role_target IN ('super_admin', 'support_staff', 'content_manager') OR (user_id IS NULL AND role_target IS NULL))";
            $params['uid'] = $userId;
        } elseif ($userId !== null && $roleTarget !== null) {
            $where .= " AND (user_id = :uid OR role_target = :rtarget OR (user_id IS NULL AND role_target IS NULL))";
            $params['uid'] = $userId;
            $params['rtarget'] = $roleTarget;
        } elseif ($userId !== null) {
            $where .= " AND user_id = :uid";
            $params['uid'] = $userId;
        } elseif ($roleTarget !== null) {
            $where .= " AND role_target = :rtarget";
            $params['rtarget'] = $roleTarget;
        }

        $items = $db->fetchAll("SELECT * FROM notifications WHERE {$where} ORDER BY id DESC LIMIT {$limit}", $params);
        foreach ($items as &$item) {
            $item = format_notification_meta($item);
        }
        return $items;
    } catch (Exception $e) {
        return [];
    }
}

function format_notification_meta(array $item): array {
    $refId = clean_int($item['reference_id'] ?? 0);
    $type = $item['type'] ?? 'system';
    
    // Default fallback
    $link = BASE_URL . 'admin/conversations.php?filter=escalated';
    $icon = 'fa-solid fa-bell text-secondary';
    $badgeBg = 'bg-secondary-subtle text-secondary';

    switch ($type) {
        case 'escalation':
            $icon = 'fa-solid fa-headset text-warning';
            $badgeBg = 'bg-warning-subtle text-warning';
            $link = $refId > 0 ? (BASE_URL . "admin/conversation_view.php?id={$refId}") : (BASE_URL . 'admin/conversations.php?filter=escalated');
            break;
        case 'angry_customer':
            $icon = 'fa-solid fa-triangle-exclamation text-danger';
            $badgeBg = 'bg-danger-subtle text-danger';
            $link = $refId > 0 ? (BASE_URL . "admin/conversation_view.php?id={$refId}") : (BASE_URL . 'admin/conversations.php?filter=escalated');
            break;
        case 'new_ticket':
            $icon = 'fa-solid fa-ticket text-info';
            $badgeBg = 'bg-info-subtle text-info';
            $link = $refId > 0 ? (BASE_URL . "admin/ticket_view.php?id={$refId}") : (BASE_URL . 'admin/tickets.php');
            break;
        case 'ticket_reply':
            $icon = 'fa-solid fa-reply text-success';
            $badgeBg = 'bg-success-subtle text-success';
            $link = $refId > 0 ? (BASE_URL . "admin/ticket_view.php?id={$refId}") : (BASE_URL . 'admin/tickets.php');
            break;
        case 'system_alert':
            $icon = 'fa-solid fa-gear text-primary';
            $badgeBg = 'bg-primary-subtle text-primary';
            $link = BASE_URL . 'admin/ai_settings.php';
            break;
    }

    // Time difference calculation
    $ts = strtotime($item['created_at'] ?? 'now');
    $diff = max(1, time() - $ts);
    if ($diff < 60) {
        $timeAgo = 'Just now';
    } elseif ($diff < 3600) {
        $timeAgo = floor($diff / 60) . 'm ago';
    } elseif ($diff < 86400) {
        $timeAgo = floor($diff / 3600) . 'h ago';
    } else {
        $timeAgo = floor($diff / 86400) . 'd ago';
    }

    $item['link'] = $link;
    $item['icon'] = $icon;
    $item['badge_bg'] = $badgeBg;
    $item['time_ago'] = $timeAgo;
    return $item;
}
