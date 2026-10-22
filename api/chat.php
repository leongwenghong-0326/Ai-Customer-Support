<?php
/**
 * NovaServe AI - Chat Conversation REST API Endpoint
 * Handles customer inputs, RAG contextualization, multi-tier AI inference, and state persistence
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_service.php';
require_once __DIR__ . '/../includes/notification.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!$payload || empty(trim($payload['message'] ?? ''))) {
    echo json_encode(['success' => false, 'error' => 'Inbound message cannot be empty.']);
    exit;
}

$userMessage = clean_string($payload['message']);
$conversationId = clean_int($payload['conversation_id'] ?? 0);
$rawLang = $payload['language'] ?? 'en';
$clientLang = in_array($rawLang, ['en', 'ms', 'zh'], true) ? $rawLang : 'en';

// Automatically detect message language (e.g., Chinese characters -> 'zh', Malay tokens -> 'ms')
$detectedLang = TextAnalyzer::detectLanguage($userMessage, $clientLang);
$lang = $detectedLang;

$db = Database::getInstance();
$currentUser = current_user();
$customerId = $currentUser ? (int)$currentUser['id'] : null;

try {
    // 1. Resolve or Create Active Conversation
    $conversation = null;
    if ($conversationId > 0) {
        $conversation = $db->fetchOne("SELECT * FROM conversations WHERE id = :id", ['id' => $conversationId]);
    }

    if (!$conversation) {
        $sessionToken = bin2hex(random_bytes(16));
        $newConvId = $db->insert('conversations', [
            'customer_id' => $customerId,
            'session_token' => $sessionToken,
            'status' => 'active',
            'channel' => 'web_chat',
            'language' => $lang,
            'primary_intent' => 'General Question',
            'overall_sentiment' => 'Neutral',
            'confidence_score' => 90.0,
            'provider_used' => 'Primary AI',
            'started_at' => date('Y-m-d H:i:s')
        ]);
        $conversation = $db->fetchOne("SELECT * FROM conversations WHERE id = :id", ['id' => $newConvId]);
    }

    $activeConvId = (int)$conversation['id'];
    $_SESSION['active_conversation_id'] = $activeConvId;
    $wasResolved = ($conversation['status'] === 'resolved');

    if ($wasResolved) {
        $db->update('conversations', ['status' => 'active'], 'id = :id', ['id' => $activeConvId]);
        $conversation['status'] = 'active';
        $db->insert('conversation_messages', [
            'conversation_id' => $activeConvId,
            'sender_type' => 'system',
            'sender_id' => null,
            'message_text' => 'Inquiry reopened with customer follow-up message.',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    // 2. Fetch Recent Conversation History for Context Continuity
    $history = $db->fetchAll(
        "SELECT sender_type, message_text FROM conversation_messages WHERE conversation_id = :cid ORDER BY id ASC LIMIT 10",
        ['cid' => $activeConvId]
    );

    // 3. Save User Message
    $userMsgId = $db->insert('conversation_messages', [
        'conversation_id' => $activeConvId,
        'sender_type' => 'customer',
        'sender_id' => $customerId,
        'message_text' => $userMessage,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // 4. Generate AI Inference with Multi-Tier Fallback & RAG
    $aiService = new AIService();
    $aiResult = $aiService->generateAnswer($userMessage, $history, $lang);

    $answer = $aiResult['answer'];
    $sources = $aiResult['sources'];
    $providerUsed = $aiResult['provider_used'];
    $confidence = $aiResult['confidence'];
    $intent = $aiResult['intent'];
    $sentiment = $aiResult['sentiment'];
    $isEscalated = $aiResult['is_escalated'];
    $escalationReason = $aiResult['escalation_reason'];

    // 5. Store AI Response
    $aiMsgId = $db->insert('conversation_messages', [
        'conversation_id' => $activeConvId,
        'sender_type' => 'ai',
        'sender_id' => null,
        'message_text' => $answer,
        'sources_json' => !empty($sources) ? json_encode($sources, JSON_UNESCAPED_UNICODE) : null,
        'intent' => $intent,
        'sentiment' => $sentiment,
        'confidence_score' => $confidence,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // 6. Update Conversation Aggregate Metadata
    $updateData = [
        'language' => $detectedLang,
        'primary_intent' => $intent,
        'overall_sentiment' => $sentiment,
        'confidence_score' => $confidence,
        'provider_used' => $providerUsed,
        'last_activity_at' => date('Y-m-d H:i:s')
    ];

    if ($isEscalated && $conversation['status'] === 'active') {
        $updateData['status'] = 'escalated';
        $updateData['escalation_reason'] = $escalationReason;

        // Disclose real-time notification to support staff
        $customerName = $currentUser ? $currentUser['full_name'] : 'Guest Visitor';
        create_notification(
            null,
            'support_staff',
            "Escalation: {$intent} ({$sentiment})",
            "Conversation #{$activeConvId} from {$customerName} requires human attention: {$escalationReason}",
            ($sentiment === 'Angry') ? 'angry_customer' : 'escalation',
            $activeConvId
        );
    }

    $db->update('conversations', $updateData, 'id = :id', ['id' => $activeConvId]);

    echo json_encode([
        'success' => true,
        'conversation_id' => $activeConvId,
        'message_id' => $aiMsgId,
        'answer' => $answer,
        'sources' => $sources,
        'confidence' => $confidence,
        'intent' => $intent,
        'sentiment' => $sentiment,
        'provider_used' => $providerUsed,
        'detected_language' => $detectedLang,
        'is_escalated' => $isEscalated,
        'conversation_status' => $updateData['status'] ?? $conversation['status']
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Chat API Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'A service processing exception occurred. Please try again.'
    ]);
}
