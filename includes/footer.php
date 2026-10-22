<?php
/**
 * NovaServe AI - Public Portal Footer Component
 */

require_once __DIR__ . '/../config/database.php';
$systemDb = Database::getInstance();
$systemConfigs = [];
try {
    $rows = $systemDb->fetchAll("SELECT config_key, config_value FROM system_settings");
    foreach ($rows as $r) {
        $systemConfigs[$r['config_key']] = $r['config_value'];
    }
} catch (Exception $e) {}

$companyName = $systemConfigs['company_name'] ?? 'NovaServe AI Technologies';
$contactEmail = $systemConfigs['contact_email'] ?? 'support@novaserve.com';
$contactPhone = $systemConfigs['contact_phone'] ?? '+1 (800) 555-NOVA (6682)';
$businessHours = $systemConfigs['business_hours'] ?? 'Monday - Friday: 8:00 AM - 8:00 PM EST';
?>

<!-- Public Modern Footer -->
<footer class="footer-modern mt-5 pt-5 pb-4 bg-dark-glass text-light-soft">
    <div class="container">
        <div class="row g-4 pb-4 border-bottom border-secondary-subtle">
            <!-- Brand & Tagline -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="brand-logo-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a4 4 0 0 0-4 4v1H6a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2H6a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v1a4 4 0 0 0 8 0v-1h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-2v-2h2a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2V6a4 4 0 0 0-4-4z"></path>
                            <circle cx="9" cy="10" r="1.5" fill="#3b82f6"></circle>
                            <circle cx="15" cy="14" r="1.5" fill="#3b82f6"></circle>
                        </svg>
                    </div>
                    <span class="fs-4 fw-bold text-white">Nova<span class="text-primary-gradient">Serve</span> AI</span>
                </div>
                <p class="text-secondary small leading-relaxed mb-0">
                    Enterprise AI-driven customer support automation. Providing zero-latency knowledge synthesis, real-time voice interaction, and multi-tier human escalation.
                </p>
            </div>

            <!-- Quick Navigation -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-white fw-bold mb-3">Customer Desk</h6>
                <ul class="list-unstyled footer-menu small">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>chat.php"><?php echo __('nav_chat'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>knowledge.php"><?php echo __('nav_kb'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>faq.php"><?php echo __('nav_faq'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>ticket.php"><?php echo __('nav_tickets'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>my_tickets.php"><?php echo __('nav_my_tickets'); ?></a></li>
                </ul>
            </div>

            <!-- Support & Operations -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="text-white fw-bold mb-3"><?php echo __('contact_us'); ?></h6>
                <ul class="list-unstyled footer-contact small text-secondary">
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-envelope text-primary"></i>
                        <a href="mailto:<?php echo e($contactEmail); ?>" class="text-secondary text-decoration-none"><?php echo e($contactEmail); ?></a>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-phone text-primary"></i>
                        <span><?php echo e($contactPhone); ?></span>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-globe text-primary"></i>
                        <span>Multilingual: EN, MS, ZH</span>
                    </li>
                </ul>
            </div>

            <!-- Hours & Status -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3"><?php echo __('operating_hours'); ?></h6>
                <div class="p-3 rounded-3 bg-card-dark border border-secondary-subtle small">
                    <div class="d-flex align-items-center gap-2 text-success mb-2">
                        <span class="status-indicator-dot"></span>
                        <strong class="text-white">AI Assistant: 24/7/365 Online</strong>
                    </div>
                    <p class="text-secondary mb-1">Human Engineering Desk:</p>
                    <p class="text-light-soft fw-medium mb-0"><?php echo e($businessHours); ?></p>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pt-4 small text-secondary">
            <div>
                &copy; <?php echo date('Y'); ?> <?php echo e($companyName); ?>. <?php echo __('copyright'); ?>
            </div>
            <div class="d-flex gap-3">
                <a href="<?php echo BASE_URL; ?>knowledge.php?slug=terms-of-service" class="text-secondary text-decoration-none">Terms of Service</a>
                <a href="<?php echo BASE_URL; ?>knowledge.php?slug=privacy-policy" class="text-secondary text-decoration-none">Privacy & Security</a>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Container for Dynamic Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="systemToast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                Action completed.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Global App JS -->
<script src="<?php echo ASSETS_URL; ?>js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
