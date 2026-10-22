<?php
/**
 * NovaServe AI - Public Portal Header Component
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';

$currentUser = current_user();
$currentLang = get_current_lang();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?php echo e($currentLang); ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | NovaServe AI' : 'NovaServe AI - Intelligent Customer Support'; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Public Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-glass sticky-top py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>index.php">
            <div class="brand-logo-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a4 4 0 0 0-4 4v1H6a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2H6a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v1a4 4 0 0 0 8 0v-1h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-2v-2h2a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2V6a4 4 0 0 0-4-4z"></path>
                    <circle cx="9" cy="10" r="1.5" fill="#3b82f6"></circle>
                    <circle cx="15" cy="14" r="1.5" fill="#3b82f6"></circle>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-title">Nova<span class="text-primary-gradient">Serve</span></span>
                <span class="brand-badge">AI</span>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                        <i class="fa-solid fa-house-chimney me-1"></i> <?php echo __('nav_home'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'chat.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>chat.php">
                        <i class="fa-solid fa-comments me-1"></i> <?php echo __('nav_chat'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'knowledge.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>knowledge.php">
                        <i class="fa-solid fa-book-bookmark me-1"></i> <?php echo __('nav_kb'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'faq.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>faq.php">
                        <i class="fa-solid fa-circle-question me-1"></i> <?php echo __('nav_faq'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'ticket.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>ticket.php">
                        <i class="fa-solid fa-ticket-simple me-1"></i> <?php echo __('nav_tickets'); ?>
                    </a>
                </li>
            </ul>

            <!-- Right Options: Language Selector & User Profile/Auth -->
            <div class="d-flex align-items-center gap-2">
                <!-- Language Selector Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light-soft dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-globe"></i>
                        <span class="text-uppercase"><?php echo e($currentLang); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item <?php echo ($currentLang === 'en') ? 'active' : ''; ?>" href="?lang=en">English (EN)</a></li>
                        <li><a class="dropdown-item <?php echo ($currentLang === 'ms') ? 'active' : ''; ?>" href="?lang=ms">Bahasa Melayu (MS)</a></li>
                        <li><a class="dropdown-item <?php echo ($currentLang === 'zh') ? 'active' : ''; ?>" href="?lang=zh">简体中文 (ZH)</a></li>
                    </ul>
                </div>

                <?php if ($currentUser): ?>
                    <!-- Logged In User Menu -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-user-profile dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar-circle">
                                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                            </span>
                            <span class="d-none d-md-inline font-medium"><?php echo e($currentUser['full_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2">
                            <li class="px-3 py-2 border-bottom">
                                <p class="mb-0 fw-bold"><?php echo e($currentUser['full_name']); ?></p>
                                <small class="text-muted"><?php echo e($currentUser['email']); ?></small>
                                <span class="badge bg-primary-soft text-primary d-block mt-1"><?php echo ucfirst(str_replace('_', ' ', $currentUser['role_key'])); ?></span>
                            </li>
                            <?php if (has_role(['super_admin', 'support_staff', 'content_manager'])): ?>
                                <li>
                                    <a class="dropdown-item text-primary fw-semibold mt-1" href="<?php echo BASE_URL; ?>admin/index.php">
                                        <i class="fa-solid fa-gauge-high me-2"></i> <?php echo __('nav_admin'); ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>my_tickets.php">
                                    <i class="fa-solid fa-list-check me-2"></i> <?php echo __('nav_my_tickets'); ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php">
                                    <i class="fa-solid fa-user-gear me-2"></i> Profile & Security
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> <?php echo __('nav_logout'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-sm btn-outline-light px-3">
                        <?php echo __('nav_login'); ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-sm btn-primary-gradient px-3">
                        <?php echo __('nav_register'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- System Flash Messages -->
<div class="container mt-3">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2 fs-5"></i>
            <div><?php echo e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
            <div><?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>
