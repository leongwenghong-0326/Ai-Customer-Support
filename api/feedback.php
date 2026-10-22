<?php
/**
 * NovaServe AI - Message Feedback REST API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$convId = clean_int($payload['conversation_id'] ?? 0);
$msgId = clean_int($payload['message_id'] ?? 0);
$rating = strtolower(trim($payload['rating'] ?? ''));
$comment = clean_string($payload['comment'] ?? '', 450);

if ($convId <= 0 || $msgId <= 0 || !in_array($rating, ['positive', 'negative'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid feedback parameters.']);
    exit;
}

try {
    $db = Database::getInstance();
    $currentUser = current_user();
    $customerId = $currentUser ? (int)$currentUser['id'] : null;

    $db->insert('conversation_feedback', [
        'conversation_id' => $convId,
        'message_id' => $msgId,
        'customer_id' => $customerId,
        'rating' => $rating,
        'comment' => $comment ?: null,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Feedback API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error recording rating.']);
}
