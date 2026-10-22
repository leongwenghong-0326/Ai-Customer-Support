<?php
/**
 * NovaServe AI - Immersive AI Customer Service & Support Chat Interface
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/i18n.php';
require_once __DIR__ . '/includes/auth.php';

$db = Database::getInstance();
$currentUser = current_user();
$customerId = $currentUser ? (int)$currentUser['id'] : null;

// Handle explicit new conversation request before any HTML output
if (isset($_GET['new'])) {
    if (!empty($_SESSION['active_conversation_id'])) {
        $db->update(
            'conversations',
            ['status' => 'resolved'],
            'id = :id',
            ['id' => (int)$_SESSION['active_conversation_id']]
        );
    } elseif ($customerId) {
        // Also close any lingering active conversation for this customer
        $db->update(
            'conversations',
            ['status' => 'resolved'],
            'customer_id = :cid AND status IN ("active", "escalated", "human_taken")',
            ['cid' => $customerId]
        );
    }
    unset($_SESSION['active_conversation_id']);
    $_SESSION['force_fresh_chat'] = true;
    header('Location: ' . BASE_URL . 'chat.php');
    exit;
}

$pageTitle = 'AI Support Chat';
require_once __DIR__ . '/includes/header.php';

// Resolve or retrieve recent active conversation
$activeConv = null;

if (!empty($_SESSION['force_fresh_chat'])) {
    // Explicit fresh session requested by clicking restart button (Pic 1)
    unset($_SESSION['force_fresh_chat']);
    unset($_SESSION['active_conversation_id']);
    $activeConv = null;
} elseif (isset($_GET['conv']) || isset($_GET['conversation_id'])) {
    // Explicit conversation specified via URL parameter
    $convId = isset($_GET['conv']) ? (int)$_GET['conv'] : (int)$_GET['conversation_id'];
    if ($convId > 0) {
        if ($currentUser && in_array($currentUser['role'], ['admin', 'agent', 'support_staff', 'super_admin'])) {
            $activeConv = $db->fetchOne("SELECT * FROM conversations WHERE id = :id", ['id' => $convId]);
        } elseif ($customerId) {
            $activeConv = $db->fetchOne("SELECT * FROM conversations WHERE id = :id AND customer_id = :cid", [
                'id' => $convId,
                'cid' => $customerId
            ]);
        }
    }
} elseif (!empty($_SESSION['active_conversation_id'])) {
    // KEEP current ongoing conversation when refreshed from browser!
    $activeConv = $db->fetchOne("SELECT * FROM conversations WHERE id = :id", ['id' => (int)$_SESSION['active_conversation_id']]);
    if ($activeConv && $customerId && !empty($activeConv['customer_id']) && (int)$activeConv['customer_id'] !== $customerId) {
        $activeConv = null;
        unset($_SESSION['active_conversation_id']);
    }
} elseif ($customerId) {
    // If user has an unfinished active or escalated conversation, auto-resume it
    $activeConv = $db->fetchOne(
        "SELECT * FROM conversations WHERE customer_id = :cid AND status IN ('active', 'escalated', 'human_taken') ORDER BY id DESC LIMIT 1",
        ['cid' => $customerId]
    );
}

$conversationMessages = [];
if ($activeConv) {
    $_SESSION['active_conversation_id'] = (int)$activeConv['id'];
    $conversationMessages = $db->fetchAll(
        "SELECT m.*, f.rating as user_rating 
         FROM conversation_messages m
         LEFT JOIN conversation_feedback f ON m.id = f.message_id
         WHERE m.conversation_id = :cid 
         ORDER BY m.id ASC",
        ['cid' => $activeConv['id']]
    );
}

$convStatus = $activeConv['status'] ?? 'new';
$isResolved = ($convStatus === 'resolved');
$isEscalated = in_array($convStatus, ['escalated', 'human_taken']);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <!-- Chat Card Container -->
            <div class="chat-window-card">
                <!-- Chat Window Header -->
                <div class="chat-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-logo-icon">
                            <i class="fa-solid fa-robot text-primary"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="mb-0 fw-bold text-white">Nova AI Specialist</h5>
                                <?php if ($isResolved): ?>
                                    <span class="badge bg-success text-white border border-success d-flex align-items-center gap-1" id="chatStatusBadge">
                                        <i class="fa-solid fa-circle-check"></i> Problem Resolved
                                    </span>
                                <?php elseif ($isEscalated): ?>
                                    <span class="badge bg-warning-soft text-warning border border-warning-subtle d-flex align-items-center gap-1" id="chatStatusBadge">
                                        <i class="fa-solid fa-headset"></i> Live Specialist
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success-soft text-success border border-success-subtle d-flex align-items-center gap-1" id="chatStatusBadge">
                                        <span class="status-indicator-dot me-1"></span> 24/7 Online
                                    </span>
                                <?php endif; ?>
                            </div>
                            <small class="text-secondary">Official Certified Knowledge Base Assistant</small>
                        </div>
                    </div>

                    <!-- Header Action Buttons (Only Pic 1 Restart button, Pic 2 removed) -->
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($activeConv && !$isResolved && count($conversationMessages) > 1): ?>
                            <button class="btn btn-sm btn-outline-success d-flex align-items-center gap-1" id="resolveChatBtn" title="Mark this problem as resolved">
                                <i class="fa-solid fa-circle-check"></i>
                                <span class="d-none d-sm-inline">Problem Solved</span>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1" id="escalateToHumanBtn" title="Request Human Escalation">
                            <i class="fa-solid fa-headset"></i>
                            <span class="d-none d-sm-inline"><?php echo __('talk_to_human'); ?></span>
                        </button>
                        <!-- Pic 1: Restart / Refresh Conversation Button -->
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center" id="clearChatBtn" title="Restart / Start New Conversation" style="width: 34px; height: 34px;">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>

                <!-- Chat Scrollable Message Body -->
                <div class="chat-messages-container" id="chatMessagesContainer">
                    <?php if ($isResolved): ?>
                        <!-- Prominent Problem Resolved Banner -->
                        <div class="chat-status-banner alert alert-success border-0 shadow-sm p-3 mb-3 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2" id="chatResolvedBanner">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="fa-solid fa-check fs-5"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold mb-0 text-white">Problem Marked as Resolved</h6>
                                        <span class="badge bg-success-soft text-success border border-success-subtle small">Completed</span>
                                    </div>
                                    <small class="text-white-50">Our support specialist has marked this case as solved. Need more help? You can continue messaging below to reopen, or click the restart button <i class="fa-solid fa-rotate-left mx-1"></i> above to start a fresh inquiry.</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- Default Welcome AI Message if conversation is fresh -->
                    <?php if (empty($conversationMessages)): ?>
                        <div class="chat-message-row ai">
                            <div class="brand-logo-icon flex-shrink-0" style="width:30px;height:30px;font-size:0.9rem;">
                                <i class="fa-solid fa-brain text-primary"></i>
                            </div>
                            <div class="chat-bubble">
                                <div class="chat-text">
                                    Hello<?php echo $currentUser ? ' ' . e($currentUser['full_name']) : ''; ?>! I am Nova, your official intelligent support specialist.
                                    <br><br>
                                    I can provide certified answers regarding our platform, subscription plans, 30-day refund policy, API keys, and technical troubleshooting. How may I assist you today?
                                </div>
                                <div class="chat-bubble-meta text-end mt-2">Just now</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Loop through saved history -->
                        <?php foreach ($conversationMessages as $msg): ?>
                            <?php if ($msg['sender_type'] === 'customer'): ?>
                                <div class="chat-message-row user">
                                    <div class="user-avatar-circle flex-shrink-0">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div class="chat-bubble">
                                        <div class="chat-text"><?php echo e($msg['message_text']); ?></div>
                                        <div class="chat-bubble-meta text-end"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                                    </div>
                                </div>
                            <?php elseif ($msg['sender_type'] === 'system'): ?>
                                <div class="alert alert-info py-2 px-3 small border-0 shadow-sm my-2 text-center">
                                    <i class="fa-solid fa-circle-info me-2"></i> <?php echo e($msg['message_text']); ?>
                                </div>
                            <?php else: ?>
                                <div class="chat-message-row ai" data-message-id="<?php echo $msg['id']; ?>">
                                    <div class="brand-logo-icon flex-shrink-0" style="width:30px;height:30px;font-size:0.9rem;">
                                        <i class="fa-solid fa-brain text-primary"></i>
                                    </div>
                                    <div class="chat-bubble">
                                        <div class="chat-text" style="white-space: pre-wrap;"><?php echo e($msg['message_text']); ?></div>
                                        
                                        <?php if (!empty($msg['sources_json'])): 
                                            $sources = json_decode($msg['sources_json'], true);
                                            if (!empty($sources)): ?>
                                                <div class="sources-card">
                                                    <div class="fw-bold mb-1 text-primary"><i class="fa-solid fa-check-double me-1"></i> Verified References:</div>
                                                    <ul class="mb-0 ps-3">
                                                        <?php foreach ($sources as $s): ?>
                                                            <li><i class="fa-solid fa-bookmark me-1 text-primary"></i> <strong><?php echo e($s['title']); ?></strong></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                        <?php endif; endif; ?>

                                        <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-secondary-subtle">
                                            <div class="feedback-actions">
                                                <?php if (!empty($msg['user_rating'])): ?>
                                                    <span class="text-success small"><i class="fa-solid fa-check"></i> Rated (<?php echo e($msg['user_rating']); ?>)</span>
                                                <?php else: ?>
                                                    <button class="btn-feedback" onclick="rateMessage(<?php echo $activeConv['id']; ?>, <?php echo $msg['id']; ?>, 'positive', this)" title="Helpful">
                                                        <i class="fa-regular fa-thumbs-up"></i> Helpful
                                                    </button>
                                                    <button class="btn-feedback" onclick="rateMessage(<?php echo $activeConv['id']; ?>, <?php echo $msg['id']; ?>, 'negative', this)" title="Not helpful">
                                                        <i class="fa-regular fa-thumbs-down"></i> Not Helpful
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn-feedback ms-1" onclick="speakAIMessage(this)" title="Listen">
                                                    <i class="fa-solid fa-volume-high"></i>
                                                </button>
                                            </div>
                                            <div class="chat-bubble-meta d-flex align-items-center">
                                                <span><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                                                <?php if (!empty($msg['confidence_score'])): ?>
                                                    <span class="badge bg-card-dark text-success border border-secondary-subtle ms-2">
                                                        <i class="fa-solid fa-gauge-simple me-1"></i><?php echo $msg['confidence_score']; ?>%
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Suggested Questions Chips Bar -->
                <div class="suggested-chips-container">
                    <span class="small text-muted align-self-center me-1"><i class="fa-solid fa-lightbulb"></i></span>
                    <button type="button" class="chip-btn">What is the 30-day refund policy?</button>
                    <button type="button" class="chip-btn">How do I reset my password?</button>
                    <button type="button" class="chip-btn">Where can I locate API keys?</button>
                    <button type="button" class="chip-btn">What are official operating hours?</button>
                </div>

                <!-- Chat Input Controls -->
                <div class="p-3 bg-dark-glass border-top border-secondary-subtle">
                    <input type="hidden" id="activeConversationId" value="<?php echo $activeConv ? $activeConv['id'] : ''; ?>">
                    <div class="input-group">
                        <!-- Voice Input Microphone Button with Dynamic Language Indicator -->
                        <button class="btn btn-outline-light-soft px-3 d-flex align-items-center gap-1" type="button" id="voiceInputBtn" title="Voice Input (Click to speak - Auto Detects EN/ZH/MS)">
                            <i class="fa-solid fa-microphone"></i>
                            <span class="badge bg-primary-soft text-primary border border-primary-subtle" id="voiceLangBadge" style="font-size: 0.65rem; padding: 2px 4px;">AUTO</span>
                        </button>
                        <!-- Message Text Input -->
                        <input type="text" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" id="chatMessageInput" placeholder="<?php echo __('type_message'); ?>" autocomplete="off">
                        <!-- Send Button -->
                        <button class="btn btn-primary-gradient px-4 d-flex align-items-center gap-2" type="button" id="chatSendBtn">
                            <span><?php echo __('send'); ?></span>
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Voice & Chat Interaction Scripts -->
<script src="<?php echo ASSETS_URL; ?>js/voice.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo ASSETS_URL; ?>js/chat.js?v=<?php echo time(); ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
