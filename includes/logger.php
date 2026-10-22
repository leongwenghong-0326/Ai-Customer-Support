<?php
/**
 * NovaServe AI - Audit & Security Activity Logger
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

function log_activity(?int $userId, string $action, string $description): bool {
    try {
        $db = Database::getInstance();
        $ip = get_client_ip();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);
        $db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => mb_substr($action, 0, 100),
            'description' => mb_substr($description, 0, 500),
            'ip_address' => $ip,
            'user_agent' => $ua,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}
