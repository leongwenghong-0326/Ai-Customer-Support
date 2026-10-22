<?php
/**
 * NovaServe AI - Conversation Detail & Live Human Takeover Console
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

require_role(['super_admin', 'support_staff']);

$db = Database::getInstance();
$currentUser = current_user();
$convId = clean_int($_GET['id'] ?? 0);

if ($convId <= 0) {
    header('Location: ' . BASE_URL . 'admin/conversations.php');
    exit;
}

$conv = $db->fetchOne(
    "SELECT c.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
            s.full_name as assigned_staff_name 
     FROM conversations c
     LEFT JOIN users u ON c.customer_id = u.id
     LEFT JOIN users s ON c.assigned_staff_id = s.id
     WHERE c.id = :id",
    ['id' => $convId]
);

if (!$conv) {
    die("Conversation record not found.");
}

// Handle Staff Form Submissions (Takeover, Send Reply, Close)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['flash_error'] = 'Security validation failed.';
    } else {
        $action = trim($_POST['action'] ?? '');

        if ($action === 'takeover') {
            $db->update('conversations', [
                'status' => 'human_taken',
                'assigned_staff_id' => $currentUser['id'],
                'last_activity_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $convId]);

            $db->insert('conversation_messages', [
                'conversation_id' => $convId,
                'sender_type' => 'system',
                'sender_id' => $currentUser['id'],
                'message_text' => "Support Specialist {$currentUser['full_name']} has taken over this conversation.",
                'created_at' => date('Y-m-d H:i:s')
            ]);

            log_activity($currentUser['id'], 'Human Takeover', "Took over conversation #{$convId}.");
            $_SESSION['flash_success'] = 'You have taken over this conversation.';
            header("Location: conversation_view.php?id={$convId}");
            exit;

        } elseif ($action === 'send_staff_reply') {
            $replyText = clean_string($_POST['staff_reply'] ?? '', 3000);
            if (!empty($replyText)) {
                $db->insert('conversation_messages', [
                    'conversation_id' => $convId,
                    'sender_type' => 'staff',
                    'sender_id' => $currentUser['id'],
                    'message_text' => $replyText,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $db->update('conversations', [
                    'status' => 'human_taken',
                    'assigned_staff_id' => $currentUser['id'],
                    'last_activity_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $convId]);

                log_activity($currentUser['id'], 'Staff Chat Reply', "Replied to customer in conversation #{$convId}.");
                $_SESSION['flash_success'] = 'Message sent to customer.';
                header("Location: conversation_view.php?id={$convId}");
                exit;
            }
        } elseif ($action === 'resolve') {
            $db->update('conversations', [
                'status' => 'resolved',
                'last_activity_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $convId]);

            $db->insert('conversation_messages', [
                'conversation_id' => $convId,
                'sender_type' => 'system',
                'sender_id' => $currentUser['id'],
                'message_text' => "Conversation has been marked as Resolved by {$currentUser['full_name']}.",
                'created_at' => date('Y-m-d H:i:s')
            ]);

            log_activity($currentUser['id'], 'Conversation Resolved', "Resolved conversation #{$convId}.");
            $_SESSION['flash_success'] = 'Conversation marked as resolved.';
            header("Location: conversation_view.php?id={$convId}");
            exit;
        }
    }
}

// Fetch Messages
$messages = $db->fetchAll(
    "SELECT m.*, u.full_name as sender_name, f.rating as feedback_rating, f.comment as feedback_comment 
     FROM conversation_messages m
     LEFT JOIN users u ON m.sender_id = u.id
     LEFT JOIN conversation_feedback f ON m.id = f.message_id
     WHERE m.conversation_id = :cid
     ORDER BY m.id ASC",
    ['cid' => $convId]
);

$adminTitle = 'Conversation #' . $convId;
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="conversations.php" class="text-secondary">Conversations</a></li>
                <li class="breadcrumb-item text-primary active">Thread #<?php echo $convId; ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold text-white mb-0">Conversation Transcript #<?php echo $convId; ?></h4>
    </div>

    <div class="d-flex gap-2">
        <?php if ($conv['status'] !== 'human_taken' && $conv['status'] !== 'resolved'): ?>
            <form action="conversation_view.php?id=<?php echo $convId; ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="takeover">
                <button type="submit" class="btn btn-sm btn-primary-gradient px-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-headset"></i> Take Over Conversation
                </button>
            </form>
        <?php endif; ?>

        <?php if ($conv['status'] !== 'resolved' && $conv['status'] !== 'closed'): ?>
            <form action="conversation_view.php?id=<?php echo $convId; ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="resolve">
                <button type="submit" class="btn btn-sm btn-outline-success px-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-check"></i> Mark Resolved
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Chat Transcript Column -->
    <div class="col-lg-8">
        <div class="card card-tech p-4 mb-4">
            <h6 class="fw-bold text-white mb-3 border-bottom border-dark-subtle pb-2">Full Chat Stream</h6>
            
            <div class="d-flex flex-column gap-3 mb-4" style="max-height: 550px; overflow-y: auto;">
                <?php foreach ($messages as $msg): ?>
                    <?php if ($msg['sender_type'] === 'customer'): ?>
                        <div class="chat-message-row user w-100">
                            <div class="user-avatar-circle flex-shrink-0" style="width:28px;height:28px;font-size:0.75rem;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="chat-bubble" style="max-width: 80%;">
                                <div class="fw-bold small text-light-soft mb-1"><?php echo e($conv['customer_name'] ?? 'Customer'); ?></div>
                                <div class="chat-text"><?php echo e($msg['message_text']); ?></div>
                                <div class="chat-bubble-meta text-end mt-1"><?php echo date('H:i:s', strtotime($msg['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php elseif ($msg['sender_type'] === 'system'): ?>
                        <div class="alert alert-info py-2 px-3 small border-0 text-center my-1">
                            <i class="fa-solid fa-circle-info me-1"></i> <?php echo e($msg['message_text']); ?>
                        </div>
                    <?php elseif ($msg['sender_type'] === 'staff'): ?>
                        <div class="chat-message-row user w-100" style="align-self: flex-end; flex-direction: row-reverse;">
                            <div class="user-avatar-circle flex-shrink-0 bg-success" style="width:28px;height:28px;font-size:0.75rem;">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                            <div class="chat-bubble bg-primary-soft border border-primary text-white" style="max-width: 80%;">
                                <div class="fw-bold small text-primary mb-1">Support Specialist (<?php echo e($msg['sender_name'] ?? 'Staff'); ?>)</div>
                                <div class="chat-text"><?php echo e($msg['message_text']); ?></div>
                                <div class="chat-bubble-meta text-end mt-1"><?php echo date('H:i:s', strtotime($msg['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- AI Bubble -->
                        <div class="chat-message-row ai w-100">
                            <div class="brand-logo-icon flex-shrink-0" style="width:28px;height:28px;font-size:0.8rem;">
                                <i class="fa-solid fa-brain text-primary"></i>
                            </div>
                            <div class="chat-bubble" style="max-width: 80%;">
                                <div class="fw-bold small text-primary mb-1">Nova AI Specialist</div>
                                <div class="chat-text" style="white-space: pre-wrap;"><?php echo e($msg['message_text']); ?></div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-dark-subtle small text-muted" style="font-size:0.75rem;">
                                    <span>Confidence: <strong><?php echo $msg['confidence_score'] ?? 'N/A'; ?>%</strong></span>
                                    <span><?php echo date('H:i:s', strtotime($msg['created_at'])); ?></span>
                                </div>

                                <?php if (!empty($msg['feedback_rating'])): ?>
                                    <div class="mt-1 badge bg-card-dark text-warning border border-secondary-subtle">
                                        Feedback: <?php echo ucfirst($msg['feedback_rating']); ?>
                                        <?php if (!empty($msg['feedback_comment'])) echo ' - "' . e($msg['feedback_comment']) . '"'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Staff Live Reply Box -->
            <form action="conversation_view.php?id=<?php echo $convId; ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="send_staff_reply">
                <div class="input-group">
                    <input type="text" name="staff_reply" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="Type a direct human response to customer..." required>
                    <button type="submit" class="btn btn-primary-gradient px-4">
                        Send as Staff
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Telemetry & Customer Sidebar -->
    <div class="col-lg-4">
        <!-- Telemetry Card -->
        <div class="card card-tech p-4 mb-4">
            <h6 class="fw-bold text-white mb-3 border-bottom border-dark-subtle pb-2">AI Diagnostics & Telemetry</h6>
            <dl class="row mb-0 small text-secondary">
                <dt class="col-sm-5 text-white">Status:</dt>
                <dd class="col-sm-7"><span class="badge bg-primary-soft text-primary"><?php echo ucfirst(str_replace('_', ' ', $conv['status'])); ?></span></dd>

                <dt class="col-sm-5 text-white">Sentiment:</dt>
                <dd class="col-sm-7">
                    <span class="badge <?php echo ($conv['overall_sentiment'] === 'Angry') ? 'bg-danger' : 'bg-secondary'; ?>">
                        <?php echo e($conv['overall_sentiment']); ?>
                    </span>
                </dd>

                <dt class="col-sm-5 text-white">Detected Intent:</dt>
                <dd class="col-sm-7 text-white"><?php echo e($conv['primary_intent']); ?></dd>

                <dt class="col-sm-5 text-white">Confidence:</dt>
                <dd class="col-sm-7 font-monospace"><?php echo $conv['confidence_score']; ?>%</dd>

                <dt class="col-sm-5 text-white">Provider Used:</dt>
                <dd class="col-sm-7 text-info"><?php echo e($conv['provider_used']); ?></dd>

                <dt class="col-sm-5 text-white">Staff Assigned:</dt>
                <dd class="col-sm-7"><?php echo $conv['assigned_staff_name'] ? e($conv['assigned_staff_name']) : '<span class="text-muted">Unassigned</span>'; ?></dd>

                <?php if (!empty($conv['escalation_reason'])): ?>
                    <dt class="col-sm-5 text-warning">Escalation:</dt>
                    <dd class="col-sm-7 text-warning"><?php echo e($conv['escalation_reason']); ?></dd>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Customer Profile Card -->
        <div class="card card-tech p-4">
            <h6 class="fw-bold text-white mb-3 border-bottom border-dark-subtle pb-2">Client Information</h6>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="user-avatar-circle" style="width:40px;height:40px;">
                    <?php echo strtoupper(substr($conv['customer_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div>
                    <strong class="text-white d-block"><?php echo e($conv['customer_name'] ?? 'Guest Visitor'); ?></strong>
                    <small class="text-muted"><?php echo e($conv['customer_email'] ?? 'Session ID: ' . substr($conv['session_token'], 0, 8)); ?></small>
                </div>
            </div>
            <div class="small text-secondary">
                <div><strong>Phone:</strong> <?php echo e($conv['customer_phone'] ?? 'Not provided'); ?></div>
                <div class="mt-1"><strong>Language:</strong> <span class="text-uppercase"><?php echo e($conv['language']); ?></span></div>
                <div class="mt-1"><strong>Started:</strong> <?php echo date('M d, H:i:s', strtotime($conv['started_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
