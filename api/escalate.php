<?php
/**
 * NovaServe AI - Human Escalation & Live Takeover REST API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification.php';
require_once __DIR__ . '/../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$convId = clean_int($payload['conversation_id'] ?? 0);
$action = trim($payload['action'] ?? 'escalate'); // 'escalate' (by customer) or 'takeover' (by staff)
$reason = clean_string($payload['reason'] ?? 'Customer initiated human takeover');

if ($convId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid conversation identifier.']);
    exit;
}

$db = Database::getInstance();
$currentUser = current_user();

try {
    $conv = $db->fetchOne("SELECT * FROM conversations WHERE id = :id", ['id' => $convId]);
    if (!$conv) {
        echo json_encode(['success' => false, 'error' => 'Conversation record not found.']);
        exit;
    }

    if ($action === 'takeover') {
        // Must be staff or admin
        if (!has_role(['super_admin', 'support_staff'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized action.']);
            exit;
        }

        $staffId = (int)$currentUser['id'];
        $staffName = $currentUser['full_name'];

        $db->update('conversations', [
            'status' => 'human_taken',
            'assigned_staff_id' => $staffId,
            'last_activity_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $convId]);

        // Insert system broadcast message in chat
        $db->insert('conversation_messages', [
            'conversation_id' => $convId,
            'sender_type' => 'system',
            'sender_id' => $staffId,
            'message_text' => "Support Specialist {$staffName} has joined the conversation and taken over human support.",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        log_activity($staffId, 'Human Takeover', "Staff {$staffName} took over conversation #{$convId}.");

        echo json_encode(['success' => true, 'status' => 'human_taken', 'staff_name' => $staffName]);
        exit;
    }

    if ($action === 'resolve') {
        $staffName = $currentUser ? $currentUser['full_name'] : 'Customer';
        $db->update('conversations', [
            'status' => 'resolved',
            'last_activity_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $convId]);

        $db->insert('conversation_messages', [
            'conversation_id' => $convId,
            'sender_type' => 'system',
            'sender_id' => $currentUser ? (int)$currentUser['id'] : null,
            'message_text' => "Conversation and inquiry marked as Resolved by {$staffName}.",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($currentUser) {
            log_activity($currentUser['id'], 'Conversation Resolved', "Marked conversation #{$convId} as resolved.");
        }

        echo json_encode(['success' => true, 'status' => 'resolved']);
        exit;
    }

    if ($action === 'reopen') {
        $customerName = $currentUser ? $currentUser['full_name'] : 'Customer';
        $db->update('conversations', [
            'status' => 'active',
            'last_activity_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $convId]);

        $db->insert('conversation_messages', [
            'conversation_id' => $convId,
            'sender_type' => 'system',
            'sender_id' => $currentUser ? (int)$currentUser['id'] : null,
            'message_text' => "Conversation has been reopened by {$customerName}.",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode(['success' => true, 'status' => 'active']);
        exit;
    }

    // Default: Customer request escalation
    $db->update('conversations', [
        'status' => 'escalated',
        'escalation_reason' => $reason,
        'last_activity_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $convId]);

    // Send internal staff notification
    $customerName = $currentUser ? $currentUser['full_name'] : 'Guest Visitor';
    create_notification(
        null,
        'support_staff',
        "Live Human Takeover Requested",
        "Customer {$customerName} in conversation #{$convId} requested immediate human assistance.",
        'escalation',
        $convId
    );

    echo json_encode(['success' => true, 'status' => 'escalated']);

} catch (Exception $e) {
    error_log("Escalation API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error executing escalation request.']);
}
