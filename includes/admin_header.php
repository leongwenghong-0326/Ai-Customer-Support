<?php
/**
 * NovaServe AI - Admin & Staff Control Console Header
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/notification.php';

// Verify permission for admin area
require_role(['super_admin', 'support_staff', 'content_manager']);

$currentUser = current_user();
$unreadNotifs = get_unread_notifications_count($currentUser['id'], $currentUser['role_key']);
$recentNotifs = get_user_notifications($currentUser['id'], $currentUser['role_key'], 8);
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($adminTitle) ? e($adminTitle) . ' | NovaServe Console' : 'NovaServe AI - Control Console'; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- Admin Styling -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/admin.css?v=<?php echo time(); ?>">
</head>
<body class="admin-body">

<div class="admin-wrapper d-flex">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar d-flex flex-column" id="adminSidebar">
        <!-- Sidebar Brand -->
        <div class="sidebar-brand d-flex align-items-center justify-content-between p-3 border-bottom border-dark-subtle">
            <a href="<?php echo BASE_URL; ?>admin/index.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="brand-logo-icon-sm">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a4 4 0 0 0-4 4v1H6a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2H6a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v1a4 4 0 0 0 8 0v-1h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-2v-2h2a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2V6a4 4 0 0 0-4-4z"></path>
                    </svg>
                </div>
                <div class="d-flex flex-column">
                    <span class="fw-bold text-white fs-5">Nova<span class="text-primary-gradient">Serve</span></span>
                    <span class="badge bg-primary-soft text-primary align-self-start" style="font-size: 0.65rem;">CONSOLE</span>
                </div>
            </a>
            <button class="btn btn-sm btn-link text-secondary d-lg-none" id="sidebarCloseBtn">
                <i class="fa-solid fa-xmark fs-5"></i>
            </button>
        </div>

        <!-- Sidebar Navigation -->
        <div class="sidebar-nav flex-grow-1 overflow-y-auto py-3">
            <div class="nav-section-label px-3 mb-2 text-uppercase text-secondary fw-semibold">Overview</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/index.php">
                        <i class="fa-solid fa-chart-line me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'conversations.php' || $currentScript === 'conversation_view.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/conversations.php">
                        <i class="fa-solid fa-headset me-2"></i> Conversations & Takeover
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'tickets.php' || $currentScript === 'ticket_view.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/tickets.php">
                        <i class="fa-solid fa-ticket-simple me-2"></i> Support Tickets
                    </a>
                </li>
            </ul>

            <div class="nav-section-label px-3 mb-2 text-uppercase text-secondary fw-semibold">Content Management</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'knowledge.php' || $currentScript === 'knowledge_form.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/knowledge.php">
                        <i class="fa-solid fa-book-bookmark me-2"></i> Knowledge Base
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentScript === 'faq.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/faq.php">
                        <i class="fa-solid fa-circle-question me-2"></i> FAQ Management
                    </a>
                </li>
            </ul>

            <?php if (has_role('super_admin')): ?>
                <div class="nav-section-label px-3 mb-2 text-uppercase text-secondary fw-semibold">System & AI Controls</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentScript === 'users.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/users.php">
                            <i class="fa-solid fa-users-gear me-2"></i> Users & Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentScript === 'ai_settings.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/ai_settings.php">
                            <i class="fa-solid fa-microchip me-2"></i> AI Configuration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentScript === 'logs.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/logs.php">
                            <i class="fa-solid fa-clock-rotate-left me-2"></i> Audit Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentScript === 'reports.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/reports.php">
                            <i class="fa-solid fa-file-waveform me-2"></i> AI Performance & Reports
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-user p-3 border-top border-dark-subtle d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="user-avatar-circle-sm">
                    <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                </span>
                <div class="d-flex flex-column overflow-hidden">
                    <span class="text-white small fw-bold text-truncate" style="max-width: 120px;"><?php echo e($currentUser['full_name']); ?></span>
                    <span class="text-secondary" style="font-size: 0.7rem;"><?php echo ucfirst(str_replace('_', ' ', $currentUser['role_key'])); ?></span>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-sm btn-outline-danger" title="Sign Out">
                <i class="fa-solid fa-power-off"></i>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="admin-main flex-grow-1 d-flex flex-column">
        <!-- Admin Topbar -->
        <header class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3 bg-dark-glass border-bottom border-dark-subtle">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h5 class="mb-0 text-white fw-bold"><?php echo isset($adminTitle) ? e($adminTitle) : 'Admin Dashboard'; ?></h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Return to Public Portal -->
                <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-sm btn-outline-light-soft d-flex align-items-center gap-2" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span class="d-none d-sm-inline">Customer Portal</span>
                </a>

                <!-- Notification Bell Dropdown -->
                <div class="dropdown" id="adminNotifDropdown">
                    <button class="btn btn-sm btn-outline-light-soft position-relative notif-bell-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="notifBellButton" title="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($unreadNotifs > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge">
                                <?php echo $unreadNotifs > 99 ? '99+' : $unreadNotifs; ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-secondary-subtle notif-dropdown-menu" style="width: 380px; max-width: 90vw; background: #0f172a; border-radius: 12px; overflow: hidden; z-index: 1060;">
                        <!-- Dropdown Header -->
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom border-dark-subtle" style="background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(8px);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-white small">
                                    <i class="fa-solid fa-bell text-primary me-1"></i> Notifications
                                </span>
                                <span class="badge bg-primary-subtle text-primary rounded-pill small" id="notifHeaderCount">
                                    <?php echo $unreadNotifs; ?> unread
                                </span>
                            </div>
                            <?php if ($unreadNotifs > 0): ?>
                                <button type="button" class="btn btn-link btn-sm text-decoration-none text-info p-0 small fw-semibold" id="markAllReadBtn" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-check-double me-1"></i> Mark all read
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Dropdown List Container -->
                        <div class="notif-list-container" id="notifListContainer" style="max-height: 380px; overflow-y: auto;">
                            <?php if (empty($recentNotifs)): ?>
                                <div class="p-4 text-center text-muted small">
                                    <i class="fa-regular fa-bell-slash fs-3 mb-2 d-block text-secondary opacity-50"></i>
                                    No notifications at this time
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentNotifs as $notif): ?>
                                    <a href="<?php echo e($notif['link']); ?>" class="dropdown-item d-flex align-items-start gap-3 px-3 py-2 border-bottom border-dark-subtle notif-item <?php echo ($notif['is_read'] == 0) ? 'unread' : 'text-muted'; ?>" data-id="<?php echo (int)$notif['id']; ?>" style="white-space: normal; text-decoration: none; transition: background-color 0.15s ease;">
                                        <div class="notif-icon-circle flex-shrink-0 mt-1 d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px; background: rgba(255,255,255,0.06);">
                                            <i class="<?php echo e($notif['icon']); ?> fs-6"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="fw-semibold text-white small text-truncate d-block" style="max-width: 210px;"><?php echo e($notif['title']); ?></span>
                                                <small class="text-secondary opacity-75" style="font-size: 0.7rem;"><?php echo e($notif['time_ago']); ?></small>
                                            </div>
                                            <p class="mb-0 text-secondary small text-truncate" style="font-size: 0.78rem; max-width: 280px;"><?php echo e($notif['message']); ?></p>
                                        </div>
                                        <?php if ($notif['is_read'] == 0): ?>
                                            <span class="notif-dot rounded-circle bg-primary flex-shrink-0 align-self-center ms-1" style="width: 7px; height: 7px;"></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Dropdown Footer -->
                        <div class="px-3 py-2 border-top border-dark-subtle d-flex justify-content-between align-items-center" style="background: rgba(30, 41, 59, 0.7);">
                            <a href="<?php echo BASE_URL; ?>admin/conversations.php?filter=escalated" class="text-secondary small text-decoration-none">
                                <i class="fa-solid fa-headset me-1 text-warning"></i> Escalations
                            </a>
                            <a href="<?php echo BASE_URL; ?>admin/tickets.php" class="text-secondary small text-decoration-none">
                                <i class="fa-solid fa-ticket me-1 text-info"></i> All Tickets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Admin Content Area -->
        <main class="admin-content flex-grow-1 p-4 overflow-y-auto">
            <!-- Flash Message in Admin -->
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
