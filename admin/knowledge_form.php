<?php
/**
 * NovaServe AI - Knowledge Base Article Form (Create / Edit)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

require_role(['super_admin', 'content_manager']);

$db = Database::getInstance();
$currentUser = current_user();

$articleId = clean_int($_GET['id'] ?? 0);
$article = null;

if ($articleId > 0) {
    $article = $db->fetchOne("SELECT * FROM knowledge_articles WHERE id = :id", ['id' => $articleId]);
    if (!$article) {
        die("Article record not found.");
    }
}

$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
$errors = [];

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token expired. Please reload.';
    }

    $title = clean_string($_POST['title'] ?? '', 200);
    $categoryId = clean_int($_POST['category_id'] ?? 0);
    $summary = clean_string($_POST['summary'] ?? '', 450);
    $content = trim($_POST['content'] ?? ''); // Allow HTML tags for article body
    $tags = clean_string($_POST['tags'] ?? '', 200);
    $status = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived'], true) ? $_POST['status'] : 'published';

    if (mb_strlen($title) < 5) $errors[] = 'Article title must have at least 5 characters.';
    if ($categoryId <= 0) $errors[] = 'Please select a valid category.';
    if (mb_strlen($summary) < 10) $errors[] = 'Summary must have at least 10 characters.';
    if (mb_strlen($content) < 20) $errors[] = 'Detailed article content must have at least 20 characters.';

    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

    if (empty($errors)) {
        try {
            if ($article) {
                // Update
                $db->update('knowledge_articles', [
                    'category_id' => $categoryId,
                    'title' => $title,
                    'summary' => $summary,
                    'content' => $content,
                    'tags' => $tags,
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $article['id']]);

                log_activity($currentUser['id'], 'Article Updated', "Updated knowledge article: {$title}");
                $_SESSION['flash_success'] = 'Knowledge article updated successfully.';
            } else {
                // Insert
                $db->insert('knowledge_articles', [
                    'category_id' => $categoryId,
                    'title' => $title,
                    'slug' => $slug . '-' . bin2hex(random_bytes(2)),
                    'summary' => $summary,
                    'content' => $content,
                    'tags' => $tags,
                    'status' => $status,
                    'created_by' => $currentUser['id'],
                    'views_count' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                log_activity($currentUser['id'], 'Article Created', "Created new knowledge article: {$title}");
                $_SESSION['flash_success'] = 'New knowledge article created successfully.';
            }

            header('Location: knowledge.php');
            exit;

        } catch (Exception $e) {
            error_log("Article save error: " . $e->getMessage());
            $errors[] = 'Database error saving knowledge article.';
        }
    }
}

$adminTitle = $article ? 'Edit Knowledge Article' : 'New Knowledge Article';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="knowledge.php" class="text-secondary">Knowledge Articles</a></li>
                <li class="breadcrumb-item text-primary active"><?php echo $article ? 'Edit' : 'Create'; ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold text-white mb-0"><?php echo $article ? 'Edit Article: ' . e($article['title']) : 'Publish New Knowledge Article'; ?></h4>
    </div>
</div>

<div class="card card-tech p-4 p-md-5">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="knowledge_form.php<?php echo $article ? '?id=' . $article['id'] : ''; ?>" method="POST">
        <?php echo csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label class="form-label text-white small fw-bold">Article Title <span class="text-danger">*</span></label>
                <input type="text" name="title" value="<?php echo e($_POST['title'] ?? $article['title'] ?? ''); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="e.g. Setting Up REST Webhooks for Ticket Events" required>
            </div>
            <div class="col-md-4">
                <label class="form-label text-white small fw-bold">Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none" required>
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): 
                        $selected = ((int)($_POST['category_id'] ?? $article['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $selected; ?>>
                            <?php echo e($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label text-white small fw-bold">Summary / TL;DR Snippet <span class="text-danger">*</span></label>
            <textarea name="summary" rows="2" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="Brief 1-2 sentence overview indexed by the AI retrieval engine..." required><?php echo e($_POST['summary'] ?? $article['summary'] ?? ''); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label text-white small fw-bold">Article Body Content (Supports HTML formatting) <span class="text-danger">*</span></label>
            <textarea name="content" rows="10" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace" placeholder="<p>Full walkthrough guide, code snippets, policy bullet points...</p>" required><?php echo e($_POST['content'] ?? $article['content'] ?? ''); ?></textarea>
            <small class="text-muted">You can write standard HTML elements such as &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;code&gt;.</small>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <label class="form-label text-white small fw-bold">Tags (Comma-separated keywords)</label>
                <input type="text" name="tags" value="<?php echo e($_POST['tags'] ?? $article['tags'] ?? ''); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" placeholder="api, webhooks, authentication, billing">
            </div>
            <div class="col-md-4">
                <label class="form-label text-white small fw-bold">Publication Status</label>
                <select name="status" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none">
                    <?php 
                        $curStatus = $_POST['status'] ?? $article['status'] ?? 'published';
                    ?>
                    <option value="published" <?php echo ($curStatus === 'published') ? 'selected' : ''; ?>>Published (Live in AI & Public)</option>
                    <option value="draft" <?php echo ($curStatus === 'draft') ? 'selected' : ''; ?>>Draft (Internal Review)</option>
                    <option value="archived" <?php echo ($curStatus === 'archived') ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-dark-subtle">
            <a href="knowledge.php" class="btn btn-outline-light-soft">&larr; Cancel</a>
            <button type="submit" class="btn btn-primary-gradient px-4">
                <?php echo $article ? 'Update Article' : 'Publish Article'; ?>
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
