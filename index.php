<?php
/**
 * NovaServe AI - Official Public Customer Support Portal Homepage
 */

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();

// Retrieve categories, featured FAQs, and popular knowledge articles
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
$popularFaqs = $db->fetchAll(
    "SELECT f.*, c.name as category_name 
     FROM faq_items f 
     LEFT JOIN categories c ON f.category_id = c.id 
     WHERE f.is_active = 1 
     ORDER BY f.views_count DESC LIMIT 5"
);
$topArticles = $db->fetchAll(
    "SELECT a.*, c.name as category_name 
     FROM knowledge_articles a 
     LEFT JOIN categories c ON a.category_id = c.id 
     WHERE a.status = 'published' 
     ORDER BY a.views_count DESC LIMIT 6"
);
?>

<!-- Hero Section with Deep Glow -->
<section class="hero-wrapper text-center">
    <div class="hero-glow"></div>
    <div class="container position-relative" style="z-index: 1;">
        <span class="badge bg-primary-soft text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
            <i class="fa-solid fa-sparkles me-1 text-primary-gradient"></i> NovaServe 2.0 Intelligent Service Cloud
        </span>
        <h1 class="hero-title text-white mb-3">
            Instant Resolution Powered by <span class="text-primary-gradient">Enterprise AI</span>
        </h1>
        <p class="hero-subtitle mx-auto mb-4">
            <?php echo __('hero_subtitle'); ?>
        </p>

        <!-- Quick Knowledge Search Bar -->
        <div class="mx-auto mb-4" style="max-width: 650px;">
            <form action="<?php echo BASE_URL; ?>knowledge.php" method="GET" class="position-relative">
                <div class="input-group shadow-lg rounded-pill overflow-hidden bg-card-dark border border-secondary-subtle p-1">
                    <span class="input-group-text bg-transparent border-0 text-secondary ps-3">
                        <i class="fa-solid fa-magnifying-glass fs-5"></i>
                    </span>
                    <input type="text" name="q" class="form-control bg-transparent border-0 text-white shadow-none ps-2" placeholder="<?php echo __('search_placeholder'); ?>" required>
                    <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?php echo BASE_URL; ?>chat.php" class="btn btn-primary-gradient btn-lg px-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-comments"></i> <?php echo __('start_chat'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>ticket.php" class="btn btn-outline-light-soft btn-lg px-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-ticket-simple"></i> <?php echo __('nav_tickets'); ?>
            </a>
        </div>
    </div>
</section>

<!-- Core Portal Action Hub -->
<section class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3 col-md-6">
            <div class="card card-tech h-100 p-4 text-center">
                <div class="brand-logo-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                    <i class="fa-solid fa-robot text-primary"></i>
                </div>
                <h5 class="fw-bold text-white mb-2">24/7 AI Assistant</h5>
                <p class="text-secondary small mb-3">Instant answers synthesized from verified organizational knowledge bases with voice support.</p>
                <a href="<?php echo BASE_URL; ?>chat.php" class="btn btn-sm btn-outline-light-soft mt-auto stretched-link">Launch Chat &rarr;</a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card card-tech h-100 p-4 text-center">
                <div class="brand-logo-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                    <i class="fa-solid fa-book-bookmark text-primary"></i>
                </div>
                <h5 class="fw-bold text-white mb-2">Knowledge Library</h5>
                <p class="text-secondary small mb-3">Technical guides, API documentation, billing details, and platform troubleshooting.</p>
                <a href="<?php echo BASE_URL; ?>knowledge.php" class="btn btn-sm btn-outline-light-soft mt-auto stretched-link">Browse Articles &rarr;</a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card card-tech h-100 p-4 text-center">
                <div class="brand-logo-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                    <i class="fa-solid fa-circle-question text-primary"></i>
                </div>
                <h5 class="fw-bold text-white mb-2">Popular FAQs</h5>
                <p class="text-secondary small mb-3">Quick solutions for payments, refunds, account security, and operating schedules.</p>
                <a href="<?php echo BASE_URL; ?>faq.php" class="btn btn-sm btn-outline-light-soft mt-auto stretched-link">View FAQs &rarr;</a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card card-tech h-100 p-4 text-center">
                <div class="brand-logo-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                    <i class="fa-solid fa-ticket text-primary"></i>
                </div>
                <h5 class="fw-bold text-white mb-2">Support Tickets</h5>
                <p class="text-secondary small mb-3">Submit detailed inquiries, upload diagnostic attachments, and track engineering progress.</p>
                <a href="<?php echo BASE_URL; ?>ticket.php" class="btn btn-sm btn-outline-light-soft mt-auto stretched-link">Open Ticket &rarr;</a>
            </div>
        </div>
    </div>
</section>

<!-- Frequently Asked Questions & Popular Inquiries -->
<section class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-4 border-bottom border-secondary-subtle pb-3">
        <div>
            <span class="badge bg-primary-soft text-primary mb-2">Official Help</span>
            <h3 class="fw-bold text-white mb-1"><?php echo __('frequently_asked'); ?></h3>
            <p class="text-secondary small mb-0">Browse certified solutions to high-frequency customer inquiries.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>faq.php" class="btn btn-sm btn-outline-light-soft">View All FAQs</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="accordion accordion-flush" id="faqAccordion">
                <?php foreach ($popularFaqs as $idx => $faq): ?>
                    <div class="accordion-item bg-card-dark border border-secondary-subtle rounded-3 mb-3 overflow-hidden">
                        <h2 class="accordion-header" id="heading<?php echo $faq['id']; ?>">
                            <button class="accordion-button bg-transparent text-white fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $faq['id']; ?>" aria-expanded="false">
                                <i class="fa-solid fa-circle-question text-primary me-2"></i>
                                <?php echo e($faq['question']); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $faq['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary border-top border-secondary-subtle small leading-relaxed">
                                <?php echo nl2br(e($faq['answer'])); ?>
                                <div class="mt-3 pt-2 border-top border-secondary-subtle d-flex justify-content-between align-items-center" style="font-size:0.82rem;">
                                    <span class="text-secondary"><i class="fa-solid fa-folder-open me-1 text-primary"></i> Category: <strong class="text-white"><?php echo e($faq['category_name']); ?></strong></span>
                                    <span class="text-secondary"><i class="fa-regular fa-eye me-1 text-info"></i><?php echo number_format($faq['views_count']); ?> views</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Service Highlights Column -->
        <div class="col-lg-4">
            <div class="card card-tech p-4 mb-4">
                <h5 class="fw-bold text-white mb-3">Service Guarantee</h5>
                <ul class="list-unstyled small text-secondary mb-0">
                    <li class="mb-3 d-flex gap-2">
                        <i class="fa-solid fa-shield-check text-success fs-5"></i>
                        <div>
                            <strong class="text-white d-block">Zero Policy Fabrication</strong>
                            AI strictly references certified knowledge bases to protect customer accuracy.
                        </div>
                    </li>
                    <li class="mb-3 d-flex gap-2">
                        <i class="fa-solid fa-microphone-lines text-primary fs-5"></i>
                        <div>
                            <strong class="text-white d-block">Bidirectional Voice</strong>
                            Speech-to-text input and natural text-to-speech playback in your browser.
                        </div>
                    </li>
                    <li class="d-flex gap-2">
                        <i class="fa-solid fa-user-tie text-info fs-5"></i>
                        <div>
                            <strong class="text-white d-block">Human Tier-2 Escalation</strong>
                            Seamless takeover by senior support engineers whenever you need specialized assistance.
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Operating Schedule Widget -->
            <div class="card card-tech p-4 text-center">
                <i class="fa-solid fa-clock text-primary fs-3 mb-2"></i>
                <h6 class="fw-bold text-white mb-1"><?php echo __('operating_hours'); ?></h6>
                <p class="text-secondary small mb-2">AI Agent: Available 24/7/365</p>
                <p class="text-secondary small mb-0">Human Desk: Mon - Fri, 8:00 AM - 8:00 PM EST</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Knowledge Base Articles -->
<section class="container py-4 mb-5">
    <div class="d-flex justify-content-between align-items-end mb-4 border-bottom border-secondary-subtle pb-3">
        <div>
            <span class="badge bg-primary-soft text-primary mb-2">Knowledge Base</span>
            <h3 class="fw-bold text-white mb-1"><?php echo __('recent_articles'); ?></h3>
            <p class="text-secondary small mb-0">Explore deep-dive technical documents and enterprise procedures.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>knowledge.php" class="btn btn-sm btn-outline-light-soft">Explore Library</a>
    </div>

    <div class="row g-4">
        <?php foreach ($topArticles as $art): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card card-tech h-100 p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary-soft text-primary"><?php echo e($art['category_name']); ?></span>
                        <small class="text-muted"><i class="fa-regular fa-eye me-1"></i><?php echo number_format($art['views_count']); ?></small>
                    </div>
                    <h5 class="fw-bold text-white mb-2 fs-6">
                        <a href="<?php echo BASE_URL; ?>knowledge.php?id=<?php echo $art['id']; ?>" class="text-white text-decoration-none">
                            <?php echo e($art['title']); ?>
                        </a>
                    </h5>
                    <p class="text-secondary small mb-3 flex-grow-1">
                        <?php echo e(mb_substr($art['summary'], 0, 120)) . '...'; ?>
                    </p>
                    <a href="<?php echo BASE_URL; ?>knowledge.php?id=<?php echo $art['id']; ?>" class="text-primary text-decoration-none fw-semibold small mt-auto">
                        Read Guide <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
