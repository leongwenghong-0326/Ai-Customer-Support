<?php
/**
 * NovaServe AI - Admin FAQ Management Center
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

require_role(['super_admin', 'content_manager']);

$db = Database::getInstance();
$currentUser = current_user();

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = clean_int($_GET['delete']);
    if ($delId > 0 && verify_csrf_token($_GET['token'] ?? '')) {
        $db->query("DELETE FROM faq_items WHERE id = :id", ['id' => $delId]);
        log_activity($currentUser['id'], 'FAQ Deleted', "Deleted FAQ item #{$delId}.");
        $_SESSION['flash_success'] = 'FAQ item deleted.';
        header('Location: faq.php');
        exit;
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $toggleId = clean_int($_GET['toggle_status']);
    if ($toggleId > 0 && verify_csrf_token($_GET['token'] ?? '')) {
        $item = $db->fetchOne("SELECT is_active FROM faq_items WHERE id = :id", ['id' => $toggleId]);
        if ($item) {
            $newStatus = ($item['is_active'] == 1) ? 0 : 1;
            $db->update('faq_items', ['is_active' => $newStatus], 'id = :id', ['id' => $toggleId]);
            log_activity($currentUser['id'], 'FAQ Status Changed', "Toggled status on FAQ #{$toggleId}.");
            $_SESSION['flash_success'] = 'FAQ status updated.';
        }
        header('Location: faq.php');
        exit;
    }
}

// Handle Create / Edit POST
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token invalid.';
    }

    $faqId = clean_int($_POST['faq_id'] ?? 0);
    $categoryId = clean_int($_POST['category_id'] ?? 0);
    $question = clean_string($_POST['question'] ?? '', 280);
    $answer = clean_string($_POST['answer'] ?? '', 4000);
    $sortOrder = clean_int($_POST['sort_order'] ?? 0);
    $isActive = !empty($_POST['is_active']) ? 1 : 0;

    if ($categoryId <= 0) $errors[] = 'Please select a valid category.';
    if (mb_strlen($question) < 5) $errors[] = 'Question must contain at least 5 characters.';
    if (mb_strlen($answer) < 10) $errors[] = 'Answer must contain at least 10 characters.';

    if (empty($errors)) {
        try {
            if ($faqId > 0) {
                // Edit
                $db->update('faq_items', [
                    'category_id' => $categoryId,
                    'question' => $question,
                    'answer' => $answer,
                    'sort_order' => $sortOrder,
                    'is_active' => $isActive,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $faqId]);

                log_activity($currentUser['id'], 'FAQ Updated', "Updated FAQ: {$question}");
                $_SESSION['flash_success'] = 'FAQ item updated successfully.';
            } else {
                // Create
                $db->insert('faq_items', [
                    'category_id' => $categoryId,
                    'question' => $question,
                    'answer' => $answer,
                    'sort_order' => $sortOrder,
                    'views_count' => 0,
                    'is_active' => $isActive,
                    'created_by' => $currentUser['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                log_activity($currentUser['id'], 'FAQ Created', "Created new FAQ: {$question}");
                $_SESSION['flash_success'] = 'New FAQ item created successfully.';
            }
            header('Location: faq.php');
            exit;
        } catch (Exception $e) {
            error_log("FAQ save error: " . $e->getMessage());
            $errors[] = 'Database error saving FAQ item.';
        }
    }
}

$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
$faqs = $db->fetchAll(
    "SELECT f.*, c.name as category_name 
     FROM faq_items f 
     LEFT JOIN categories c ON f.category_id = c.id 
     ORDER BY f.category_id ASC, f.sort_order ASC, f.id DESC"
);

$adminTitle = 'FAQ Management';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">Frequently Asked Questions</h4>
        <p class="text-secondary small mb-0">Manage customer self-service answers and certified RAG retrieval snippets.</p>
    </div>
    <button class="btn btn-primary-gradient d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#faqModal" onclick="resetFaqForm()">
        <i class="fa-solid fa-plus"></i> Add New FAQ
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- FAQ Table Card -->
<div class="table-dark-custom shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-4">Order</th>
                    <th>Question & Answer</th>
                    <th>Category</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($faqs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">No FAQ entries available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($faqs as $f): ?>
                        <tr>
                            <td class="ps-4 font-monospace small"><?php echo $f['sort_order']; ?></td>
                            <td style="max-width: 380px;">
                                <strong class="text-white d-block text-truncate"><?php echo e($f['question']); ?></strong>
                                <small class="text-secondary d-block text-truncate"><?php echo e($f['answer']); ?></small>
                            </td>
                            <td><span class="small text-secondary"><?php echo e($f['category_name']); ?></span></td>
                            <td class="font-monospace small"><?php echo number_format($f['views_count']); ?></td>
                            <td>
                                <a href="faq.php?toggle_status=<?php echo $f['id']; ?>&token=<?php echo generate_csrf_token(); ?>" class="badge <?php echo ($f['is_active'] == 1) ? 'bg-success text-white' : 'bg-secondary text-white'; ?> text-decoration-none">
                                    <?php echo ($f['is_active'] == 1) ? 'Active' : 'Disabled'; ?>
                                </a>
                            </td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-outline-light-soft me-1" onclick='editFaq(<?php echo json_encode($f); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="faq.php?delete=<?php echo $f['id']; ?>&token=<?php echo generate_csrf_token(); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this FAQ entry?')">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form for Add / Edit FAQ -->
<div class="modal fade" id="faqModal" tabindex="-1" aria-labelledby="faqModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-card-dark border border-secondary-subtle text-white shadow-lg">
            <div class="modal-header border-bottom border-dark-subtle">
                <h5 class="modal-title fw-bold" id="faqModalLabel">Add FAQ Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="faq.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="faq_id" id="modalFaqId" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Service Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="modalCatId" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo e($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Display Sort Order</label>
                            <input type="number" name="sort_order" id="modalSortOrder" value="0" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Inquiry Question <span class="text-danger">*</span></label>
                        <input type="text" name="question" id="modalQuestion" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="e.g. How do I request a tax-compliant invoice?" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Certified Answer Response <span class="text-danger">*</span></label>
                        <textarea name="answer" id="modalAnswer" rows="5" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Official answer provided to customers and indexed by AI..." required></textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="modalIsActive" value="1" checked>
                        <label class="form-check-label small" for="modalIsActive">Activate immediately in public FAQ and AI index</label>
                    </div>
                </div>

                <div class="modal-footer border-top border-dark-subtle">
                    <button type="button" class="btn btn-outline-light-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-gradient px-4">Save FAQ Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetFaqForm() {
    document.getElementById('faqModalLabel').innerText = 'Add FAQ Item';
    document.getElementById('modalFaqId').value = '0';
    document.getElementById('modalCatId').value = '';
    document.getElementById('modalSortOrder').value = '0';
    document.getElementById('modalQuestion').value = '';
    document.getElementById('modalAnswer').value = '';
    document.getElementById('modalIsActive').checked = true;
}

function editFaq(item) {
    document.getElementById('faqModalLabel').innerText = 'Edit FAQ Item';
    document.getElementById('modalFaqId').value = item.id;
    document.getElementById('modalCatId').value = item.category_id;
    document.getElementById('modalSortOrder').value = item.sort_order;
    document.getElementById('modalQuestion').value = item.question;
    document.getElementById('modalAnswer').value = item.answer;
    document.getElementById('modalIsActive').checked = (item.is_active == 1);
    const modal = new bootstrap.Modal(document.getElementById('faqModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
