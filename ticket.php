<?php
/**
 * NovaServe AI - Support Ticket Submission Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/uploader.php';
require_once __DIR__ . '/includes/notification.php';
require_once __DIR__ . '/includes/logger.php';

// Must be signed in to submit tickets
require_login();

$currentUser = current_user();
$db = Database::getInstance();
$errors = [];

$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token invalid or expired. Please resubmit.';
    }

    $categoryId = clean_int($_POST['category_id'] ?? 0);
    $priority = strtolower(trim($_POST['priority'] ?? 'medium'));
    $subject = clean_string($_POST['subject'] ?? '', 200);
    $description = clean_string($_POST['description'] ?? '', 4000);

    if ($categoryId <= 0) $errors[] = 'Please select a service category.';
    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) $priority = 'medium';
    if (mb_strlen($subject) < 5) $errors[] = 'Subject must contain at least 5 characters.';
    if (mb_strlen($description) < 15) $errors[] = 'Detailed description must contain at least 15 characters.';

    // Process optional attachment
    $uploadedFile = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = SecureUploader::upload($_FILES['attachment'], 'tickets');
        if (!$uploadResult['success']) {
            $errors[] = 'Attachment Error: ' . $uploadResult['error'];
        } else {
            $uploadedFile = $uploadResult;
        }
    }

    if (empty($errors)) {
        try {
            // Generate unique ticket number: NS-YYYYMM-00000X
            $yearMonth = date('Ym');
            $latestCountRow = $db->fetchOne("SELECT COUNT(*) as cnt FROM support_tickets");
            $nextSeq = (int)($latestCountRow['cnt'] ?? 0) + 1;
            $ticketNumber = sprintf("NS-%s-%06d", $yearMonth, $nextSeq);

            // Insert Ticket
            $ticketId = $db->insert('support_tickets', [
                'ticket_number' => $ticketNumber,
                'customer_id' => $currentUser['id'],
                'category_id' => $categoryId,
                'assigned_staff_id' => null,
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority,
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Insert first message thread
            $messageId = $db->insert('ticket_messages', [
                'ticket_id' => $ticketId,
                'sender_id' => $currentUser['id'],
                'message_text' => $description,
                'is_internal_note' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Insert attachment record if present
            if ($uploadedFile) {
                $db->insert('ticket_attachments', [
                    'ticket_id' => $ticketId,
                    'message_id' => $messageId,
                    'original_filename' => $uploadedFile['original_filename'],
                    'stored_filename' => $uploadedFile['stored_filename'],
                    'file_size' => $uploadedFile['file_size'],
                    'mime_type' => $uploadedFile['mime_type'],
                    'file_path' => $uploadedFile['file_path'],
                    'uploaded_by' => $currentUser['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Internal notification
            create_notification(
                null,
                'support_staff',
                "New Support Ticket #{$ticketNumber}",
                "Customer {$currentUser['full_name']} opened ticket: {$subject}",
                'new_ticket',
                $ticketId
            );

            log_activity($currentUser['id'], 'Ticket Created', "Submitted ticket {$ticketNumber} ({$priority}).");

            $_SESSION['flash_success'] = "Support ticket {$ticketNumber} created successfully! Our engineering team will review it shortly.";
            header('Location: ' . BASE_URL . 'ticket_view.php?number=' . urlencode($ticketNumber));
            exit;

        } catch (Exception $e) {
            error_log("Ticket creation error: " . $e->getMessage());
            $errors[] = 'An internal database error occurred while creating your ticket. Please retry.';
        }
    }
}

$pageTitle = 'Submit Support Ticket';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-tech p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-secondary-subtle">
                    <div class="brand-logo-icon">
                        <i class="fa-solid fa-ticket-simple text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-white mb-0"><?php echo __('nav_tickets'); ?></h3>
                        <small class="text-secondary">Submit a formal inquiry or technical escalation to our support desk.</small>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="ticket.php" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Service Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none" required>
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none">
                                <option value="low">Low - General Question / Feedback</option>
                                <option value="medium" selected>Medium - Minor Operational Inconvenience</option>
                                <option value="high">High - Important Feature / API Failure</option>
                                <option value="urgent">Urgent - Mission-Critical Outage</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Ticket Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Brief, concise summary of the issue..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Detailed Description <span class="text-danger">*</span></label>
                        <textarea name="description" rows="6" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Provide complete details, steps to reproduce, error codes, and impact..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white small fw-bold"><?php echo __('attach_files'); ?></label>
                        <input type="file" name="attachment" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
                        <small class="text-muted d-block mt-1">Supported formats: JPG, PNG, PDF, DOC, DOCX, TXT. Max size: 5 MB.</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">
                        <a href="my_tickets.php" class="text-secondary text-decoration-none small">&larr; View My Active Tickets</a>
                        <button type="submit" class="btn btn-primary-gradient px-4">
                            Submit Support Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
