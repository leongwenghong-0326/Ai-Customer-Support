<?php
/**
 * NovaServe AI - Knowledge Base Management Console
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

require_role(['super_admin', 'content_manager']);

$db = Database::getInstance();
$currentUser = current_user();

// Handle Delete Request
if (isset($_GET['delete'])) {
    $delId = clean_int($_GET['delete']);
    if ($delId > 0 && verify_csrf_token($_GET['token'] ?? '')) {
        $db->query("DELETE FROM knowledge_articles WHERE id = :id", ['id' => $delId]);
        log_activity($currentUser['id'], 'Knowledge Article Deleted', "Deleted article #{$delId}.");
        $_SESSION['flash_success'] = 'Knowledge article deleted successfully.';
        header('Location: knowledge.php');
        exit;
    }
}

$adminTitle = 'Knowledge Articles';
require_once __DIR__ . '/../includes/admin_header.php';

$search = clean_string($_GET['q'] ?? '');
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(a.title LIKE :q1 OR a.summary LIKE :q2 OR a.tags LIKE :q3)";
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = '%' . $search . '%';
    $params['q3'] = '%' . $search . '%';
}

$whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
$articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name, u.full_name as author_name 
     FROM knowledge_articles a
     LEFT JOIN categories c ON a.category_id = c.id
     LEFT JOIN users u ON a.created_by = u.id
     {$whereSql}
     ORDER BY a.id DESC",
    $params
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">Knowledge Base Articles</h4>
        <p class="text-secondary small mb-0">Publish technical guides, policies, and certified documentation for RAG AI retrieval.</p>
    </div>
    <a href="knowledge_form.php" class="btn btn-primary-gradient d-flex align-items-center gap-2">
        <i class="fa-solid fa-plus"></i> New Knowledge Article
    </a>
</div>

<!-- Search Bar -->
<div class="card card-tech p-3 mb-4">
    <form action="knowledge.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-card-dark border-secondary-subtle text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" name="q" value="<?php echo e($search); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-2" placeholder="Search by title, summary, tags...">
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-light-soft">Filter</button>
            <?php if (!empty($search)): ?>
                <a href="knowledge.php" class="btn btn-link text-secondary text-decoration-none small">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Articles Table -->
<div class="table-dark-custom shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Title & Summary</th>
                    <th>Category</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th>Author</th>
                    <th>Last Updated</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            <i class="fa-solid fa-book-open fs-2 d-block mb-2 text-muted"></i>
                            No knowledge articles found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($articles as $art): 
                        $statusBadge = match($art['status']) {
                            'published' => 'bg-success-soft text-success',
                            'draft' => 'bg-warning-soft text-warning',
                            'archived' => 'bg-secondary text-white',
                            default => 'bg-secondary text-white'
                        };
                    ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-primary">#<?php echo $art['id']; ?></td>
                            <td style="max-width: 320px;">
                                <strong class="text-white d-block text-truncate"><?php echo e($art['title']); ?></strong>
                                <small class="text-muted d-block text-truncate"><?php echo e($art['summary']); ?></small>
                            </td>
                            <td><span class="small text-secondary"><?php echo e($art['category_name']); ?></span></td>
                            <td class="font-monospace small"><?php echo number_format($art['views_count']); ?></td>
                            <td>
                                <span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst($art['status']); ?></span>
                            </td>
                            <td class="small text-secondary"><?php echo e($art['author_name'] ?? 'System'); ?></td>
                            <td class="small text-secondary"><?php echo date('M d, Y', strtotime($art['updated_at'])); ?></td>
                            <td class="pe-4 text-end">
                                <a href="knowledge_form.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-light-soft me-1" title="Edit Article">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="knowledge.php?delete=<?php echo $art['id']; ?>&token=<?php echo generate_csrf_token(); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you certain you want to delete this knowledge article?')" title="Delete">
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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
