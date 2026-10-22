<?php
/**
 * NovaServe AI - Official Knowledge Base Hub & Article Detail
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';

$db = Database::getInstance();
$articleId = clean_int($_GET['id'] ?? 0);
$articleSlug = clean_string($_GET['slug'] ?? '');
$article = null;

if ($articleId > 0) {
    // Increment view count
    $db->query("UPDATE knowledge_articles SET views_count = views_count + 1 WHERE id = :id", ['id' => $articleId]);
    
    // Fetch article with category and author
    $article = $db->fetchOne(
        "SELECT a.*, c.name as category_name, c.icon as category_icon, u.full_name as author_name 
         FROM knowledge_articles a
         LEFT JOIN categories c ON a.category_id = c.id
         LEFT JOIN users u ON a.created_by = u.id
         WHERE a.id = :id AND a.status = 'published'",
        ['id' => $articleId]
    );
} elseif (!empty($articleSlug)) {
    // Fetch article by unique slug
    $article = $db->fetchOne(
        "SELECT a.*, c.name as category_name, c.icon as category_icon, u.full_name as author_name 
         FROM knowledge_articles a
         LEFT JOIN categories c ON a.category_id = c.id
         LEFT JOIN users u ON a.created_by = u.id
         WHERE a.slug = :slug AND a.status = 'published'",
        ['slug' => $articleSlug]
    );
    if ($article) {
        $db->query("UPDATE knowledge_articles SET views_count = views_count + 1 WHERE id = :id", ['id' => $article['id']]);
    }
}

$pageTitle = $article ? $article['title'] : 'Knowledge Base';
require_once __DIR__ . '/includes/header.php';

if ($article): 
    // Related articles in same category
    $relatedArticles = $db->fetchAll(
        "SELECT id, title, views_count FROM knowledge_articles WHERE category_id = :cid AND id != :id AND status = 'published' LIMIT 5",
        ['cid' => $article['category_id'], 'id' => $article['id']]
    );
?>
    <!-- Article Detail View -->
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-secondary">Home</a></li>
                <li class="breadcrumb-item"><a href="knowledge.php" class="text-secondary">Knowledge Base</a></li>
                <li class="breadcrumb-item text-primary active" aria-current="page"><?php echo e($article['category_name']); ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <div class="card card-tech p-4 p-md-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-soft text-primary">
                            <i class="fa-solid <?php echo e($article['category_icon'] ?? 'fa-folder'); ?> me-1"></i>
                            <?php echo e($article['category_name']); ?>
                        </span>
                        <span class="text-secondary small">&bull;</span>
                        <span class="text-secondary small"><i class="fa-regular fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($article['updated_at'])); ?></span>
                        <span class="text-secondary small">&bull;</span>
                        <span class="text-secondary small"><i class="fa-regular fa-eye me-1"></i><?php echo number_format($article['views_count']); ?> views</span>
                    </div>

                    <h1 class="fw-bold text-white mb-3 fs-3"><?php echo e($article['title']); ?></h1>
                    
                    <div class="p-3 rounded-3 bg-dark-glass border-start border-primary border-3 mb-4 text-secondary small leading-relaxed">
                        <?php echo e($article['summary']); ?>
                    </div>

                    <div class="article-content text-secondary leading-relaxed mb-4">
                        <?php echo $article['content']; // Safe rich text from author ?>
                    </div>

                    <!-- Tags & Author Metadata -->
                    <?php if (!empty($article['tags'])): 
                        $tags = explode(',', $article['tags']); ?>
                        <div class="d-flex flex-wrap gap-2 align-items-center pt-3 border-top border-secondary-subtle">
                            <span class="small text-muted me-1"><i class="fa-solid fa-tags"></i> Tags:</span>
                            <?php foreach ($tags as $tag): ?>
                                <a href="knowledge.php?q=<?php echo urlencode(trim($tag)); ?>" class="chip-btn text-decoration-none" style="font-size:0.75rem;">
                                    <?php echo e(trim($tag)); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary-subtle small text-secondary">
                        <span>Authored by: <strong class="text-white"><?php echo e($article['author_name'] ?? 'Support Team'); ?></strong></span>
                        <a href="knowledge.php" class="text-primary text-decoration-none">&larr; Back to Knowledge Base</a>
                    </div>
                </div>
            </div>

            <!-- Related Articles Sidebar -->
            <div class="col-lg-4">
                <div class="card card-tech p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3">Related Guides</h5>
                    <?php if (empty($relatedArticles)): ?>
                        <p class="text-secondary small mb-0">No other articles in this category yet.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0 small">
                            <?php foreach ($relatedArticles as $rel): ?>
                                <li class="mb-3">
                                    <a href="knowledge.php?id=<?php echo $rel['id']; ?>" class="text-white text-decoration-none d-block fw-semibold mb-1">
                                        <?php echo e($rel['title']); ?>
                                    </a>
                                    <span class="text-muted" style="font-size:0.75rem;"><i class="fa-regular fa-eye me-1"></i><?php echo number_format($rel['views_count']); ?> views</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="card card-tech p-4 text-center">
                    <i class="fa-solid fa-headset text-primary fs-2 mb-2"></i>
                    <h6 class="fw-bold text-white mb-1">Still need guidance?</h6>
                    <p class="text-secondary small mb-3">Our AI assistant and engineering specialists are ready to help you 24/7.</p>
                    <a href="chat.php" class="btn btn-sm btn-primary-gradient w-100">Open AI Chat</a>
                </div>
            </div>
        </div>
    </div>
<?php else: 
    // Directory / Listing View
    $searchQuery = clean_string($_GET['q'] ?? '');
    $categoryId = clean_int($_GET['category_id'] ?? 0);

    $categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");

    $where = ["a.status = 'published'"];
    $params = [];

    if ($categoryId > 0) {
        $where[] = "a.category_id = :cid";
        $params['cid'] = $categoryId;
    }

    if (!empty($searchQuery)) {
        $where[] = "(a.title LIKE :q1 OR a.summary LIKE :q2 OR a.tags LIKE :q3)";
        $params['q1'] = '%' . $searchQuery . '%';
        $params['q2'] = '%' . $searchQuery . '%';
        $params['q3'] = '%' . $searchQuery . '%';
    }

    $whereSql = implode(' AND ', $where);
    $articles = $db->fetchAll(
        "SELECT a.*, c.name as category_name, c.icon as category_icon 
         FROM knowledge_articles a
         LEFT JOIN categories c ON a.category_id = c.id
         WHERE {$whereSql}
         ORDER BY a.views_count DESC, a.id DESC",
        $params
    );
?>
    <div class="container py-5">
        <!-- Header & Search -->
        <div class="text-center mb-5">
            <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill fw-semibold mb-2">Knowledge Library</span>
            <h2 class="fw-bold text-white mb-2"><?php echo __('nav_kb'); ?></h2>
            <p class="text-secondary small max-w-500 mx-auto">Explore verified platform architectures, API reference materials, and troubleshooting.</p>

            <div class="row justify-content-center mt-4">
                <div class="col-md-7 col-lg-6">
                    <form action="knowledge.php" method="GET" class="position-relative">
                        <?php if ($categoryId > 0): ?>
                            <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
                        <?php endif; ?>
                        <div class="input-group rounded-pill overflow-hidden bg-card-dark border border-secondary-subtle p-1 shadow-sm">
                            <span class="input-group-text bg-transparent border-0 text-secondary ps-3">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" name="q" value="<?php echo e($searchQuery); ?>" class="form-control bg-transparent border-0 text-white shadow-none ps-2" placeholder="Search knowledge articles, tags...">
                            <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="d-flex justify-content-center gap-2 flex-wrap mb-5">
            <a href="knowledge.php<?php echo !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm <?php echo ($categoryId === 0) ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
                All Topics
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="knowledge.php?category_id=<?php echo $cat['id']; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm <?php echo ($categoryId === (int)$cat['id']) ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3 d-flex align-items-center gap-2">
                    <i class="fa-solid <?php echo e($cat['icon']); ?> text-primary"></i>
                    <span><?php echo e($cat['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Articles Grid -->
        <div class="row g-4">
            <?php if (empty($articles)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-book-open fs-1 text-secondary mb-3"></i>
                    <h5 class="text-white">No Articles Located</h5>
                    <p class="text-secondary small mb-3">Try adjusting your keyword filter or browse all categories.</p>
                    <a href="knowledge.php" class="btn btn-sm btn-outline-light-soft">Reset Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($articles as $art): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card card-tech h-100 p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-soft text-primary">
                                    <i class="fa-solid <?php echo e($art['category_icon'] ?? 'fa-folder'); ?> me-1"></i>
                                    <?php echo e($art['category_name']); ?>
                                </span>
                                <small class="text-muted"><i class="fa-regular fa-eye me-1"></i><?php echo number_format($art['views_count']); ?></small>
                            </div>
                            <h5 class="fw-bold text-white mb-2 fs-6">
                                <a href="knowledge.php?id=<?php echo $art['id']; ?>" class="text-white text-decoration-none">
                                    <?php echo e($art['title']); ?>
                                </a>
                            </h5>
                            <p class="text-secondary small mb-3 flex-grow-1">
                                <?php echo e(mb_substr($art['summary'], 0, 130)) . '...'; ?>
                            </p>
                            <a href="knowledge.php?id=<?php echo $art['id']; ?>" class="text-primary text-decoration-none fw-semibold small mt-auto">
                                Read Article &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
