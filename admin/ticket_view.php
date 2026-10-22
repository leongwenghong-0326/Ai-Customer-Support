<?php
/**
 * NovaServe AI - Staff Ticket Detail & Engineering Action Center
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/uploader.php';
require_once __DIR__ . '/../includes/notification.php';
require_once __DIR__ . '/../includes/logger.php';

require_role(['super_admin', 'support_staff']);

$db = Database::getInstance();
$currentUser = current_user();
$ticketId = clean_int($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    header('Location: ' . BASE_URL . 'admin/tickets.php');
    exit;
}

$ticket = $db->fetchOne(
    "SELECT t.*, c.name as category_name, u.full_name as customer_name, u.email as customer_email,
            u.phone as customer_phone, s.full_name as staff_name 
     FROM support_tickets t
     LEFT JOIN categories c ON t.category_id = c.id
     LEFT JOIN users u ON t.customer_id = u.id
     LEFT JOIN users s ON t.assigned_staff_id = s.id
     WHERE t.id = :id",
    ['id' => $ticketId]
);

if (!$ticket) {
    die("Ticket not found.");
}

// Fetch all staff users for assignment dropdown
$staffList = $db->fetchAll(
    "SELECT id, full_name FROM users WHERE role_id IN (1, 2) AND status = 'active' ORDER BY full_name ASC"
);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['flash_error'] = 'Security verification failed.';
    } else {
        $action = trim($_POST['action'] ?? '');

        if ($action === 'reply_or_note') {
            $msgText = clean_string($_POST['message_text'] ?? '', 4000);
            $isInternalNote = !empty($_POST['is_internal_note']) ? 1 : 0;

            if (mb_strlen($msgText) >= 2) {
                $newMsgId = $db->insert('ticket_messages', [
                    'ticket_id' => $ticketId,
                    'sender_id' => $currentUser['id'],
                    'message_text' => $msgText,
                    'is_internal_note' => $isInternalNote,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                // Update ticket updated_at
                $db->update('support_tickets', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $ticketId]);

                $noteLabel = $isInternalNote ? 'Internal Note added' : 'Public Reply sent';
                log_activity($currentUser['id'], 'Ticket Update', "{$noteLabel} on ticket #{$ticket['ticket_number']}.");

                // If public reply, notify customer
                if (!$isInternalNote) {
                    create_notification(
                        $ticket['customer_id'],
                        'customer',
                        "Staff Replied to Ticket #{$ticket['ticket_number']}",
                        "Support engineer {$currentUser['full_name']} posted a response to your ticket.",
                        'ticket_reply',
                        $ticketId
                    );
                }

                $_SESSION['flash_success'] = "{$noteLabel} successfully.";
                header("Location: ticket_view.php?id={$ticketId}");
                exit;
            }
        } elseif ($action === 'update_meta') {
            $status = clean_string($_POST['status'] ?? $ticket['status']);
            $priority = clean_string($_POST['priority'] ?? $ticket['priority']);
            $assignedStaffId = clean_int($_POST['assigned_staff_id'] ?? 0);

            $updateFields = [
                'status' => in_array($status, ['open', 'pending', 'in_progress', 'resolved', 'closed'], true) ? $status : $ticket['status'],
                'priority' => in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : $ticket['priority'],
                'assigned_staff_id' => ($assignedStaffId > 0) ? $assignedStaffId : null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->update('support_tickets', $updateFields, 'id = :id', ['id' => $ticketId]);
            log_activity($currentUser['id'], 'Ticket Status Modified', "Updated metadata for ticket #{$ticket['ticket_number']}.");

            $_SESSION['flash_success'] = 'Ticket parameters updated.';
            header("Location: ticket_view.php?id={$ticketId}");
            exit;
        }
    }
}

// Fetch all messages (staff sees internal notes!)
$messages = $db->fetchAll(
    "SELECT tm.*, u.full_name as sender_name, r.role_key 
     FROM ticket_messages tm
     JOIN users u ON tm.sender_id = u.id
     JOIN roles r ON u.role_id = r.id
     WHERE tm.ticket_id = :tid 
     ORDER BY tm.id ASC",
    ['tid' => $ticketId]
);

// Fetch attachments
$attachments = $db->fetchAll(
    "SELECT * FROM ticket_attachments WHERE ticket_id = :tid ORDER BY id ASC",
    ['tid' => $ticketId]
);
$attachmentsByMessage = [];
foreach ($attachments as $att) {
    $attachmentsByMessage[$att['message_id']][] = $att;
}

$adminTitle = 'Ticket ' . $ticket['ticket_number'];
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="tickets.php" class="text-secondary">Tickets</a></li>
                <li class="breadcrumb-item text-primary active"><?php echo e($ticket['ticket_number']); ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold text-white mb-0"><?php echo e($ticket['subject']); ?></h4>
    </div>
</div>

<div class="row g-4">
    <!-- Messages Stream -->
    <div class="col-lg-8">
        <div class="card card-tech p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-dark-subtle pb-2">
                <span class="badge bg-primary-soft text-primary font-monospace fs-6">
                    <?php echo e($ticket['ticket_number']); ?>
                </span>
                <span class="badge bg-card-dark text-white border border-secondary-subtle">
                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                </span>
            </div>

            <!-- Thread -->
            <div class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($messages as $msg): 
                    $isInternal = (bool)$msg['is_internal_note'];
                    $isSenderStaff = in_array($msg['role_key'], ['super_admin', 'support_staff']);
                    $cardClass = $isInternal ? 'border-warning bg-card-dark' : ($isSenderStaff ? 'border-primary' : 'border-secondary-subtle');
                ?>
                    <div class="card p-3 <?php echo $cardClass; ?>" style="background: rgba(30, 41, 59, 0.7);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar-circle-sm" style="width:26px;height:26px;font-size:0.75rem;">
                                    <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                </span>
                                <strong class="text-white small"><?php echo e($msg['sender_name']); ?></strong>
                                <?php if ($isInternal): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-lock me-1"></i> Internal Staff Note</span>
                                <?php elseif ($isSenderStaff): ?>
                                    <span class="badge bg-primary">Support Staff</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Client</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></small>
                        </div>
                        <div class="text-secondary small leading-relaxed" style="white-space: pre-wrap;"><?php echo e($msg['message_text']); ?></div>

                        <!-- Attachments attached to this message -->
                        <?php if (!empty($attachmentsByMessage[$msg['id']])): ?>
                            <div class="mt-2 pt-2 border-top border-dark-subtle">
                                <span class="small text-muted d-block mb-1"><i class="fa-solid fa-paperclip me-1"></i> Attachments:</span>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php foreach ($attachmentsByMessage[$msg['id']] as $att): ?>
                                        <a href="<?php echo BASE_URL . e($att['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-light-soft py-1 px-2 d-flex align-items-center gap-1" style="font-size:0.75rem;" download>
                                            <i class="fa-solid fa-file-arrow-down text-primary"></i>
                                            <span><?php echo e($att['original_filename']); ?></span>
                                            <span class="text-muted">(<?php echo round($att['file_size'] / 1024, 1); ?> KB)</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Staff Response & Internal Note Form -->
            <form action="ticket_view.php?id=<?php echo $ticketId; ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="reply_or_note">

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Post Response or Internal Engineering Note</label>
                    <textarea name="message_text" rows="4" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Draft your answer to client or record team troubleshooting steps..." required></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_internal_note" id="internalNoteCheck" value="1">
                        <label class="form-check-label text-warning small fw-bold" for="internalNoteCheck">
                            <i class="fa-solid fa-lock me-1"></i> Make this an Internal Staff Note (Customer will NOT see this)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary-gradient px-4">
                        Post Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ticket Controls Sidebar -->
    <div class="col-lg-4">
        <!-- Ticket Meta Update Card -->
        <div class="card card-tech p-4 mb-4">
            <h6 class="fw-bold text-white mb-3 border-bottom border-dark-subtle pb-2">Ticket Controls</h6>
            <form action="ticket_view.php?id=<?php echo $ticketId; ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_meta">

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Status</label>
                    <select name="status" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none">
                        <option value="open" <?php echo ($ticket['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo ($ticket['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="pending" <?php echo ($ticket['status'] === 'pending') ? 'selected' : ''; ?>>Pending Staff Action</option>
                        <option value="resolved" <?php echo ($ticket['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo ($ticket['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Priority</label>
                    <select name="priority" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none">
                        <option value="low" <?php echo ($ticket['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo ($ticket['priority'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo ($ticket['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo ($ticket['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Assigned Representative</label>
                    <select name="assigned_staff_id" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none">
                        <option value="0">-- Unassigned Queue --</option>
                        <?php foreach ($staffList as $st): ?>
                            <option value="<?php echo $st['id']; ?>" <?php echo ($ticket['assigned_staff_id'] == $st['id']) ? 'selected' : ''; ?>>
                                <?php echo e($st['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-outline-light-soft w-100 py-2">
                    Update Ticket Parameters
                </button>
            </form>
        </div>

        <!-- Client Info Card -->
        <div class="card card-tech p-4">
            <h6 class="fw-bold text-white mb-3 border-bottom border-dark-subtle pb-2">Customer Profile</h6>
            <div class="small text-secondary">
                <div class="mb-2"><strong class="text-white d-block">Full Name:</strong> <?php echo e($ticket['customer_name']); ?></div>
                <div class="mb-2"><strong class="text-white d-block">Email:</strong> <?php echo e($ticket['customer_email']); ?></div>
                <div class="mb-2"><strong class="text-white d-block">Phone:</strong> <?php echo e($ticket['customer_phone'] ?? 'None'); ?></div>
                <div class="mb-2"><strong class="text-white d-block">Category:</strong> <?php echo e($ticket['category_name']); ?></div>
                <div><strong class="text-white d-block">Created:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
