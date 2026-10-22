<?php
/**
 * NovaServe AI - Customer Ticket Conversation & Attachment Thread
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/i18n.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/uploader.php';
require_once __DIR__ . '/includes/notification.php';
require_once __DIR__ . '/includes/logger.php';

require_login();

$currentUser = current_user();
$db = Database::getInstance();
$ticketNumber = clean_string($_GET['number'] ?? '');

if (empty($ticketNumber)) {
    header('Location: ' . BASE_URL . 'my_tickets.php');
    exit;
}

// Fetch ticket record
$ticket = $db->fetchOne(
    "SELECT t.*, c.name as category_name, c.icon as category_icon, 
            u.full_name as customer_name, u.email as customer_email,
            s.full_name as staff_name
     FROM support_tickets t
     LEFT JOIN categories c ON t.category_id = c.id
     LEFT JOIN users u ON t.customer_id = u.id
     LEFT JOIN users s ON t.assigned_staff_id = s.id
     WHERE t.ticket_number = :num",
    ['num' => $ticketNumber]
);

if (!$ticket) {
    die("Ticket not found.");
}

// Authorization: Must be owner or staff/admin
$isStaff = has_role(['super_admin', 'support_staff']);
if (!$isStaff && (int)$ticket['customer_id'] !== (int)$currentUser['id']) {
    die("Access Denied: You do not have permission to view this ticket.");
}

$errors = [];

// Handle Customer Action POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $errors[] = 'Security verification failed. Please try again.';
    } elseif ($_POST['action'] === 'mark_resolved') {
        $db->update('support_tickets', [
            'status' => 'resolved',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $ticket['id']]);

        $db->insert('ticket_messages', [
            'ticket_id' => $ticket['id'],
            'sender_id' => $currentUser['id'],
            'message_text' => 'Customer marked this support problem as Resolved.',
            'is_internal_note' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        log_activity($currentUser['id'], 'Ticket Resolved', "Customer marked ticket #{$ticket['ticket_number']} as resolved.");
        $_SESSION['flash_success'] = 'Problem marked as resolved. Thank you for confirming!';
        header('Location: ticket_view.php?number=' . urlencode($ticket['ticket_number']));
        exit;
    } elseif ($_POST['action'] === 'reply') {
        $replyText = clean_string($_POST['reply_message'] ?? '', 4000);
        if (mb_strlen($replyText) < 2) {
            $errors[] = 'Reply message cannot be empty.';
        }

        // Process optional attachment
        $uploadedFile = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = SecureUploader::upload($_FILES['attachment'], 'tickets');
            if (!$uploadRes['success']) {
                $errors[] = 'Attachment Error: ' . $uploadRes['error'];
            } else {
                $uploadedFile = $uploadRes;
            }
        }

        if (empty($errors)) {
            try {
                // Insert message
                $msgId = $db->insert('ticket_messages', [
                    'ticket_id' => $ticket['id'],
                    'sender_id' => $currentUser['id'],
                    'message_text' => $replyText,
                    'is_internal_note' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if ($uploadedFile) {
                    $db->insert('ticket_attachments', [
                        'ticket_id' => $ticket['id'],
                        'message_id' => $msgId,
                        'original_filename' => $uploadedFile['original_filename'],
                        'stored_filename' => $uploadedFile['stored_filename'],
                        'file_size' => $uploadedFile['file_size'],
                        'mime_type' => $uploadedFile['mime_type'],
                        'file_path' => $uploadedFile['file_path'],
                        'uploaded_by' => $currentUser['id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }

                // Update ticket status
                $newStatus = ($ticket['status'] === 'closed' || $ticket['status'] === 'resolved') ? 'open' : $ticket['status'];
                $db->update('support_tickets', [
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $ticket['id']]);

                // Notify assigned staff or general staff
                create_notification(
                    $ticket['assigned_staff_id'],
                    'support_staff',
                    "Customer Replied: Ticket #{$ticket['ticket_number']}",
                    "{$currentUser['full_name']} sent a new reply on ticket: {$ticket['subject']}",
                    'ticket_reply',
                    $ticket['id']
                );

                log_activity($currentUser['id'], 'Ticket Replied', "Replied to ticket #{$ticket['ticket_number']}.");

                $_SESSION['flash_success'] = 'Your reply has been posted successfully.';
                header('Location: ' . BASE_URL . 'ticket_view.php?number=' . urlencode($ticket['ticket_number']));
                exit;

            } catch (Exception $e) {
                error_log("Ticket reply error: " . $e->getMessage());
                $errors[] = 'Failed to post reply due to database error.';
            }
        }
    }
}

// Fetch Thread Messages (DO NOT DISPLAY INTERNAL NOTES TO CUSTOMERS!)
$internalNoteCondition = $isStaff ? "" : "AND tm.is_internal_note = 0";
$messages = $db->fetchAll(
    "SELECT tm.*, u.full_name as sender_name, u.role_id, r.role_key 
     FROM ticket_messages tm
     JOIN users u ON tm.sender_id = u.id
     JOIN roles r ON u.role_id = r.id
     WHERE tm.ticket_id = :tid {$internalNoteCondition}
     ORDER BY tm.id ASC",
    ['tid' => $ticket['id']]
);

// Fetch Attachments
$attachments = $db->fetchAll(
    "SELECT * FROM ticket_attachments WHERE ticket_id = :tid ORDER BY id ASC",
    ['tid' => $ticket['id']]
);

$attachmentsByMessage = [];
foreach ($attachments as $att) {
    $attachmentsByMessage[$att['message_id']][] = $att;
}

$pageTitle = 'Ticket #' . $ticket['ticket_number'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Ticket Thread Column -->
        <div class="col-lg-8">
            <!-- Ticket Header Info Card -->
            <div class="card card-tech p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <span class="badge bg-primary-soft text-primary font-monospace fs-6 px-3 py-2">
                        <?php echo e($ticket['ticket_number']); ?>
                    </span>
                    <?php 
                        $statusBadgeClass = match($ticket['status']) {
                            'resolved' => 'bg-success text-white border-success',
                            'closed' => 'bg-secondary-subtle text-secondary border-secondary',
                            'in_progress' => 'bg-warning-soft text-warning border-warning-subtle',
                            'pending' => 'bg-info-soft text-info border-info-subtle',
                            default => 'bg-primary-soft text-primary border-primary-subtle'
                        };
                        $statusIcon = match($ticket['status']) {
                            'resolved' => 'fa-circle-check',
                            'closed' => 'fa-lock',
                            'in_progress' => 'fa-spinner fa-spin-pulse',
                            default => 'fa-clock'
                        };
                    ?>
                    <span class="badge <?php echo $statusBadgeClass; ?> px-3 py-2 d-flex align-items-center gap-1 border">
                        <i class="fa-solid <?php echo $statusIcon; ?>"></i>
                        Status: <strong class="text-uppercase ms-1"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></strong>
                    </span>
                </div>
                <h3 class="fw-bold text-white mb-2"><?php echo e($ticket['subject']); ?></h3>
                <div class="d-flex align-items-center gap-3 text-secondary small flex-wrap">
                    <span><i class="fa-solid fa-folder me-1 text-primary"></i> <?php echo e($ticket['category_name']); ?></span>
                    <span>&bull;</span>
                    <span><i class="fa-solid fa-flag me-1 text-warning"></i> Priority: <?php echo ucfirst($ticket['priority']); ?></span>
                    <span>&bull;</span>
                    <span><i class="fa-regular fa-calendar me-1"></i> Opened <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></span>
                </div>
            </div>

            <!-- Problem Resolution Status Banner for Customer -->
            <?php if ($ticket['status'] === 'resolved'): ?>
                <div class="alert alert-success border-0 shadow-sm p-4 mb-4 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="fa-solid fa-circle-check fs-4"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h5 class="fw-bold mb-0 text-white">This Problem Has Been Marked as Resolved</h5>
                                <span class="badge bg-success-soft text-success border border-success-subtle">Solved</span>
                            </div>
                            <p class="text-white-50 small mb-0">Our support specialist has provided a certified solution for this issue. If everything is working to your satisfaction, no further action is required. If the problem persists or you need further help, reply below to reopen this ticket.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($ticket['status'] === 'closed'): ?>
                <div class="alert alert-secondary border-0 shadow-sm p-3 mb-4 rounded-3 d-flex align-items-center gap-3">
                    <i class="fa-solid fa-lock fs-4 text-secondary"></i>
                    <div>
                        <h6 class="fw-bold mb-0 text-white">Ticket Closed</h6>
                        <small class="text-secondary">This support ticket has been closed. Posting a reply below will automatically reopen it.</small>
                    </div>
                </div>
            <?php else: ?>
                <div class="card card-tech p-3 mb-4 border-primary-subtle d-flex flex-row align-items-center justify-content-between flex-wrap gap-3" style="background: rgba(30, 41, 59, 0.4);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-soft text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                            <i class="fa-solid fa-spinner fa-spin-pulse"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-white small">Problem Status: In Progress (Unresolved)</span>
                                <span class="badge bg-warning-soft text-warning border border-warning-subtle small">Awaiting Staff</span>
                            </div>
                            <small class="text-secondary">Our technical team is working on your issue. If you already solved this on your own, you can confirm resolution below.</small>
                        </div>
                    </div>
                    <form method="POST" action="ticket_view.php?number=<?php echo urlencode($ticket['ticket_number']); ?>" class="m-0" onsubmit="return confirm('Confirm that this problem is now resolved?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="mark_resolved">
                        <button type="submit" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1">
                            <i class="fa-solid fa-check"></i> Problem is Solved
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Message Thread -->
            <div class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($messages as $msg): 
                    $isSenderStaff = in_array($msg['role_key'], ['super_admin', 'support_staff']);
                    $cardBorder = $isSenderStaff ? 'border-primary' : 'border-secondary-subtle';
                    $badgeRole = $isSenderStaff ? '<span class="badge bg-primary ms-2">Support Staff</span>' : '<span class="badge bg-secondary ms-2">Customer</span>';
                ?>
                    <div class="card card-tech p-4 <?php echo $cardBorder; ?>" id="msg_<?php echo $msg['id']; ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar-circle" style="width:28px;height:28px;font-size:0.75rem;">
                                    <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                </span>
                                <strong class="text-white"><?php echo e($msg['sender_name']); ?></strong>
                                <?php echo $badgeRole; ?>
                            </div>
                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></small>
                        </div>
                        <div class="text-secondary leading-relaxed" style="white-space: pre-wrap;"><?php echo e($msg['message_text']); ?></div>

                        <!-- Attachments attached to this message -->
                        <?php if (!empty($attachmentsByMessage[$msg['id']])): ?>
                            <div class="mt-3 pt-2 border-top border-dark-subtle">
                                <span class="small text-muted d-block mb-1"><i class="fa-solid fa-paperclip me-1"></i> Attachments:</span>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php foreach ($attachmentsByMessage[$msg['id']] as $att): ?>
                                        <a href="<?php echo BASE_URL . e($att['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-light-soft d-flex align-items-center gap-2" download>
                                            <i class="fa-solid fa-file-arrow-down text-primary"></i>
                                            <span class="small"><?php echo e($att['original_filename']); ?></span>
                                            <span class="text-muted" style="font-size:0.7rem;">(<?php echo round($att['file_size'] / 1024, 1); ?> KB)</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Customer Reply Box -->
            <div class="card card-tech p-4">
                <h5 class="fw-bold text-white mb-3"><i class="fa-solid fa-reply me-2 text-primary"></i> Post a Reply</h5>
                <form action="ticket_view.php?number=<?php echo urlencode($ticket['ticket_number']); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="reply">

                    <div class="mb-3">
                        <textarea name="reply_message" rows="4" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Type your update or provide additional information..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold"><?php echo __('attach_files'); ?></label>
                        <input type="file" name="attachment" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="my_tickets.php" class="text-secondary text-decoration-none small">&larr; Back to Tickets</a>
                        <button type="submit" class="btn btn-primary-gradient px-4">
                            Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Meta Column -->
        <div class="col-lg-4">
            <div class="card card-tech p-4 mb-4">
                <h6 class="fw-bold text-white mb-3 border-bottom border-secondary-subtle pb-2">Ticket Summary</h6>
                <dl class="row mb-0 small text-secondary">
                    <dt class="col-sm-5 text-white">Status:</dt>
                    <dd class="col-sm-7"><span class="badge bg-card-dark text-primary border border-primary-subtle"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></dd>

                    <dt class="col-sm-5 text-white">Assigned To:</dt>
                    <dd class="col-sm-7 text-white"><?php echo $ticket['staff_name'] ? e($ticket['staff_name']) : 'Engineering Queue'; ?></dd>

                    <dt class="col-sm-5 text-white">Created By:</dt>
                    <dd class="col-sm-7"><?php echo e($ticket['customer_name']); ?></dd>

                    <dt class="col-sm-5 text-white">Customer Email:</dt>
                    <dd class="col-sm-7 text-truncate"><?php echo e($ticket['customer_email']); ?></dd>

                    <dt class="col-sm-5 text-white">Last Updated:</dt>
                    <dd class="col-sm-7"><?php echo date('M d, H:i', strtotime($ticket['updated_at'])); ?></dd>
                </dl>
            </div>

            <?php if ($isStaff): ?>
                <div class="card card-tech p-4 text-center">
                    <i class="fa-solid fa-user-shield text-primary fs-3 mb-2"></i>
                    <h6 class="text-white fw-bold mb-1">Staff Administrative Action</h6>
                    <p class="text-secondary small mb-3">View and update internal notes, assign representatives, and change ticket priority in the Staff Console.</p>
                    <a href="<?php echo BASE_URL; ?>admin/ticket_view.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                        Open in Staff Console &rarr;
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
