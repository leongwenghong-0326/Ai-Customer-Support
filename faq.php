<?php
/**
 * NovaServe AI - Official FAQ Knowledge Portal
 */

$pageTitle = 'Frequently Asked Questions';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();

$searchQuery = clean_string($_GET['q'] ?? '');
$categoryId = clean_int($_GET['category_id'] ?? 0);
$targetId = clean_int($_GET['id'] ?? 0);

// Fetch categories
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");

// Fetch FAQs with filter
$where = ["f.is_active = 1"];
$params = [];

if ($categoryId > 0) {
    $where[] = "f.category_id = :cid";
    $params['cid'] = $categoryId;
}

if (!empty($searchQuery)) {
    $where[] = "(f.question LIKE :q1 OR f.answer LIKE :q2)";
    $params['q1'] = '%' . $searchQuery . '%';
    $params['q2'] = '%' . $searchQuery . '%';
}

$whereSql = implode(' AND ', $where);
$faqs = $db->fetchAll(
    "SELECT f.*, c.name as category_name, c.icon as category_icon 
     FROM faq_items f 
     LEFT JOIN categories c ON f.category_id = c.id 
     WHERE {$whereSql} 
     ORDER BY f.sort_order ASC, f.views_count DESC",
    $params
);

// Popular FAQs for sidebar
$popularFaqs = $db->fetchAll(
    "SELECT id, question, views_count FROM faq_items WHERE is_active = 1 ORDER BY views_count DESC LIMIT 5"
);
?>

<div class="container py-5">
    <!-- Header Banner -->
    <div class="text-center mb-5">
        <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill fw-semibold mb-2">Help Center</span>
        <h2 class="fw-bold text-white mb-2"><?php echo __('frequently_asked'); ?></h2>
        <p class="text-secondary small max-w-500 mx-auto">Instant answers to high-frequency operational and technical inquiries.</p>

        <!-- Search Bar -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-7 col-lg-6">
                <form action="faq.php" method="GET" class="position-relative">
                    <?php if ($categoryId > 0): ?>
                        <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
                    <?php endif; ?>
                    <div class="input-group rounded-pill overflow-hidden bg-card-dark border border-secondary-subtle p-1 shadow-sm">
                        <span class="input-group-text bg-transparent border-0 text-secondary ps-3">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" name="q" value="<?php echo e($searchQuery); ?>" class="form-control bg-transparent border-0 text-white shadow-none ps-2" placeholder="Search FAQ topics, keywords...">
                        <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Category Filters -->
        <div class="col-lg-3">
            <div class="card card-tech p-3 mb-4">
                <h6 class="fw-bold text-white mb-3 px-2">Categories</h6>
                <div class="list-group list-group-flush bg-transparent">
                    <a href="faq.php<?php echo !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : ''; ?>" class="list-group-item list-group-item-action bg-transparent border-0 text-secondary d-flex justify-content-between align-items-center py-2 px-2 rounded-2 <?php echo ($categoryId === 0) ? 'text-white fw-bold bg-primary-soft' : ''; ?>">
                        <span><i class="fa-solid fa-layer-group me-2 text-primary"></i> All Topics</span>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="faq.php?category_id=<?php echo $cat['id']; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" class="list-group-item list-group-item-action bg-transparent border-0 text-secondary d-flex justify-content-between align-items-center py-2 px-2 rounded-2 <?php echo ($categoryId === (int)$cat['id']) ? 'text-white fw-bold bg-primary-soft' : ''; ?>">
                            <span><i class="fa-solid <?php echo e($cat['icon']); ?> me-2 text-primary"></i> <?php echo e($cat['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Popular FAQs Sidebar (Pic 1) -->
            <div class="card card-tech p-3">
                <h6 class="fw-bold text-white mb-3 px-2">Most Read FAQs</h6>
                <ul class="list-unstyled mb-0 px-2 small">
                    <?php foreach ($popularFaqs as $pop): ?>
                        <li class="popular-faq-item" data-faq-id="<?php echo $pop['id']; ?>">
                            <a href="javascript:void(0)" class="popular-faq-link text-secondary text-decoration-none d-block text-truncate" data-faq-id="<?php echo $pop['id']; ?>" title="<?php echo e($pop['question']); ?>">
                                <i class="fa-solid fa-fire text-warning me-1"></i> <span class="popular-faq-title"><?php echo e($pop['question']); ?></span>
                            </a>
                            <span class="text-muted" style="font-size:0.75rem;"><i class="fa-regular fa-eye me-1"></i><?php echo number_format($pop['views_count']); ?> views</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- FAQ Items List (Pic 2) -->
        <div class="col-lg-9">
            <?php if (!empty($searchQuery)): ?>
                <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-3 bg-card-dark border border-secondary-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-magnifying-glass text-primary"></i>
                        <span class="text-secondary small">Search results for:</span>
                        <strong class="text-white">"<?php echo e($searchQuery); ?>"</strong>
                        <span class="badge bg-primary-soft text-primary ms-1"><?php echo count($faqs); ?> found</span>
                    </div>
                    <a href="faq.php<?php echo ($categoryId > 0) ? '?category_id=' . $categoryId : ''; ?>" class="btn btn-sm btn-outline-light-soft">
                        <i class="fa-solid fa-xmark me-1"></i> Clear Search
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($faqs)): ?>
                <div class="card card-tech p-5 text-center">
                    <i class="fa-solid fa-circle-question fs-1 text-secondary mb-3"></i>
                    <h5 class="text-white">No FAQ Entries Found</h5>
                    <p class="text-secondary small mb-3">We could not locate any questions matching your current search parameters.</p>
                    <a href="faq.php" class="btn btn-sm btn-outline-light-soft mx-auto">Reset Filters</a>
                </div>
            <?php else: ?>
                <div class="accordion accordion-flush" id="faqListAccordion">
                    <?php foreach ($faqs as $faq): ?>
                        <div class="accordion-item bg-card-dark border border-secondary-subtle rounded-3 mb-3 overflow-hidden" id="faq_<?php echo $faq['id']; ?>">
                            <h2 class="accordion-header" id="h_<?php echo $faq['id']; ?>">
                                <button class="accordion-button bg-transparent text-white fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c_<?php echo $faq['id']; ?>" aria-expanded="false" onclick="trackFaqView(<?php echo $faq['id']; ?>)">
                                    <i class="fa-solid fa-circle-question text-primary me-2"></i>
                                    <?php echo e($faq['question']); ?>
                                </button>
                            </h2>
                            <div id="c_<?php echo $faq['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#faqListAccordion">
                                <div class="accordion-body text-secondary border-top border-secondary-subtle leading-relaxed">
                                    <div class="mb-3"><?php echo nl2br(e($faq['answer'])); ?></div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary-subtle small" style="font-size:0.82rem;">
                                        <span class="text-secondary"><i class="fa-solid <?php echo e($faq['category_icon'] ?? 'fa-folder'); ?> me-1 text-primary"></i> Category: <strong class="text-white"><?php echo e($faq['category_name']); ?></strong></span>
                                        <span class="text-secondary"><i class="fa-regular fa-eye me-1 text-info"></i> <span id="view_count_<?php echo $faq['id']; ?>"><?php echo number_format($faq['views_count']); ?></span> views</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function trackFaqView(faqId) {
    fetch('api/faq.php?id=' + faqId).catch(() => {});
}

function openAndHighlightFaq(faqId) {
    const targetItem = document.getElementById('faq_' + faqId);
    if (!targetItem) return false;

    const collapseEl = document.getElementById('c_' + faqId);
    const triggerBtn = targetItem.querySelector('.accordion-button');

    // 1. Immediately wipe any highlight pulse classes from ALL items
    document.querySelectorAll('#faqListAccordion .accordion-item').forEach(el => {
        el.classList.remove('faq-highlight-pulse');
    });

    // 2. Close any other currently expanded accordion item in the list
    document.querySelectorAll('#faqListAccordion .accordion-collapse.show').forEach(openCol => {
        if (openCol !== collapseEl) {
            if (window.bootstrap && bootstrap.Collapse) {
                const bsInst = bootstrap.Collapse.getInstance(openCol);
                if (bsInst) bsInst.hide();
                else openCol.classList.remove('show');
            } else {
                openCol.classList.remove('show');
            }
            const parentBtn = openCol.closest('.accordion-item')?.querySelector('.accordion-button');
            if (parentBtn) {
                parentBtn.classList.add('collapsed');
                parentBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });

    // 3. Expand target collapse
    if (collapseEl) {
        if (window.bootstrap && bootstrap.Collapse) {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            bsCollapse.show();
        } else {
            collapseEl.classList.add('show');
        }
        if (triggerBtn) {
            triggerBtn.classList.remove('collapsed');
            triggerBtn.setAttribute('aria-expanded', 'true');
        }
    }

    // 4. Smooth scroll with sticky navbar offset so the header is perfectly visible
    const navBar = document.querySelector('.navbar.sticky-top');
    const navHeight = navBar ? navBar.offsetHeight : 80;
    const itemPosition = targetItem.getBoundingClientRect().top + window.pageYOffset;
    const offsetPosition = itemPosition - navHeight - 20;

    window.scrollTo({
        top: Math.max(0, offsetPosition),
        behavior: 'smooth'
    });

    // 5. Trigger temporary pulse glow highlight and auto-remove after 1.2s
    void targetItem.offsetWidth; // Force CSS reflow
    targetItem.classList.add('faq-highlight-pulse');
    setTimeout(() => {
        targetItem.classList.remove('faq-highlight-pulse');
    }, 1250);

    // 6. Track view count
    trackFaqView(faqId);

    return true;
}

// Bind handlers
document.addEventListener('DOMContentLoaded', function() {
    // Clean any residual URL hash so refresh always keeps all items closed
    if (window.location.hash && window.location.hash.startsWith('#faq_')) {
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
        }
    }

    // 1. Sidebar popular items click (Pic 1 -> Pic 2)
    const popularElements = document.querySelectorAll('.popular-faq-link, .popular-faq-item');
    popularElements.forEach(elem => {
        elem.addEventListener('click', function(e) {
            e.preventDefault();
            const faqId = this.getAttribute('data-faq-id');
            if (!faqId) return;

            const inDOM = document.getElementById('faq_' + faqId);
            if (inDOM) {
                openAndHighlightFaq(faqId);
            } else {
                // If filtered out by category, reset filter
                window.location.href = 'faq.php';
            }
        });
    });

    // 2. Direct accordion button clicks in list
    const accordionList = document.getElementById('faqListAccordion');
    if (accordionList) {
        accordionList.addEventListener('show.bs.collapse', function(e) {
            // Clean highlight pulse from all items
            document.querySelectorAll('#faqListAccordion .accordion-item').forEach(el => {
                el.classList.remove('faq-highlight-pulse');
            });
            // Ensure any other accordion is closed
            document.querySelectorAll('#faqListAccordion .accordion-collapse.show').forEach(openCol => {
                if (openCol !== e.target) {
                    if (window.bootstrap && bootstrap.Collapse) {
                        const bsInst = bootstrap.Collapse.getInstance(openCol);
                        if (bsInst) bsInst.hide();
                    } else {
                        openCol.classList.remove('show');
                    }
                    const parentBtn = openCol.closest('.accordion-item')?.querySelector('.accordion-button');
                    if (parentBtn) {
                        parentBtn.classList.add('collapsed');
                        parentBtn.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });

        accordionList.addEventListener('hide.bs.collapse', function(e) {
            const parentItem = e.target.closest('.accordion-item');
            if (parentItem) {
                parentItem.classList.remove('faq-highlight-pulse');
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
