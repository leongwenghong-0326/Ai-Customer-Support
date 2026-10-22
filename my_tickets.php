<?php
/**
 * NovaServe AI - Customer 'My Tickets' Tracking Dashboard
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$currentUser = current_user();
$db = Database::getInstance();

$statusFilter = clean_string($_GET['status'] ?? '');
$where = ["t.customer_id = :cid"];
$params = ['cid' => $currentUser['id']];

if (in_array($statusFilter, ['open', 'pending', 'in_progress', 'resolved', 'closed'], true)) {
    $where[] = "t.status = :st";
    $params['st'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);
$tickets = $db->fetchAll(
    "SELECT t.*, c.name as category_name, u.full_name as staff_name 
     FROM support_tickets t
     LEFT JOIN categories c ON t.category_id = c.id
     LEFT JOIN users u ON t.assigned_staff_id = u.id
     WHERE {$whereSql}
     ORDER BY t.updated_at DESC, t.id DESC",
    $params
);

// Calculate Customer Resolution Stats
$statsRows = $db->fetchAll(
    "SELECT status, COUNT(*) as cnt FROM support_tickets WHERE customer_id = :cid GROUP BY status",
    ['cid' => $currentUser['id']]
);
$resolvedCount = 0;
$openCount = 0;
$totalCount = 0;
foreach ($statsRows as $sr) {
    $c = (int)$sr['cnt'];
    $totalCount += $c;
    if (in_array($sr['status'], ['resolved', 'closed'], true)) {
        $resolvedCount += $c;
    } else {
        $openCount += $c;
    }
}

$pageTitle = 'My Support Tickets';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary-subtle">
        <div>
            <h2 class="fw-bold text-white mb-1"><?php echo __('nav_my_tickets'); ?></h2>
            <p class="text-secondary small mb-0">Track engineering progress, reply to staff updates, and view resolutions.</p>
        </div>
        <a href="ticket.php" class="btn btn-primary-gradient d-flex align-items-center gap-2">
            <i class="fa-solid fa-plus"></i> Open New Ticket
        </a>
    </div>

    <!-- Issue Resolution Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-tech p-3 border-secondary-subtle">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary small fw-bold text-uppercase">Total Issues Submitted</span>
                        <h3 class="fw-bold text-white mb-0 mt-1"><?php echo $totalCount; ?></h3>
                    </div>
                    <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fa-solid fa-ticket fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-tech p-3 border-success-subtle" style="background: rgba(16, 185, 129, 0.05);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-success small fw-bold text-uppercase">Problems Resolved</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?php echo $resolvedCount; ?></h3>
                    </div>
                    <div class="rounded-circle bg-success-soft text-success d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-tech p-3 border-warning-subtle" style="background: rgba(245, 158, 11, 0.05);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-warning small fw-bold text-uppercase">In Progress / Unresolved</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1"><?php echo $openCount; ?></h3>
                    </div>
                    <div class="rounded-circle bg-warning-soft text-warning d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fa-solid fa-spinner fa-spin-pulse fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Filter Buttons -->
    <div class="d-flex gap-2 flex-wrap mb-4">
        <a href="my_tickets.php" class="btn btn-sm <?php echo empty($statusFilter) ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            All Tickets
        </a>
        <a href="my_tickets.php?status=open" class="btn btn-sm <?php echo ($statusFilter === 'open') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Open
        </a>
        <a href="my_tickets.php?status=in_progress" class="btn btn-sm <?php echo ($statusFilter === 'in_progress') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            In Progress
        </a>
        <a href="my_tickets.php?status=pending" class="btn btn-sm <?php echo ($statusFilter === 'pending') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Pending Staff
        </a>
        <a href="my_tickets.php?status=resolved" class="btn btn-sm <?php echo ($statusFilter === 'resolved') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Resolved
        </a>
        <a href="my_tickets.php?status=closed" class="btn btn-sm <?php echo ($statusFilter === 'closed') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Closed
        </a>
    </div>

    <!-- Tickets Table Card -->
    <div class="card card-tech overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-secondary small text-uppercase" style="border-bottom: 1px solid var(--border-glass);">
                        <th class="ps-4">Ticket Number</th>
                        <th>Subject & Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned Staff</th>
                        <th>Last Activity</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="fa-solid fa-folder-open fs-2 mb-2 d-block text-muted"></i>
                                No support tickets found under this status.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): 
                            $badgeClass = match($t['status']) {
                                'open' => 'bg-primary-soft text-primary',
                                'in_progress' => 'bg-warning-soft text-warning',
                                'pending' => 'bg-info-soft text-info',
                                'resolved' => 'bg-success-soft text-success',
                                'closed' => 'bg-secondary text-white',
                                default => 'bg-secondary text-white'
                            };
                            $priorityClass = match($t['priority']) {
                                'urgent' => 'text-danger fw-bold',
                                'high' => 'text-warning fw-semibold',
                                'medium' => 'text-primary',
                                default => 'text-secondary'
                            };
                        ?>
                            <tr>
                                <td class="ps-4 fw-bold font-monospace text-primary">
                                    <a href="ticket_view.php?number=<?php echo urlencode($t['ticket_number']); ?>" class="text-primary text-decoration-none">
                                        <?php echo e($t['ticket_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-white"><?php echo e($t['subject']); ?></div>
                                    <small class="text-muted"><?php echo e($t['category_name']); ?></small>
                                </td>
                                <td>
                                    <span class="<?php echo $priorityClass; ?>">
                                        <i class="fa-solid fa-flag me-1"></i><?php echo ucfirst($t['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'resolved'): ?>
                                        <span class="badge bg-success-soft text-success border border-success-subtle px-2 py-1">
                                            <i class="fa-solid fa-circle-check me-1"></i> Resolved
                                        </span>
                                    <?php elseif ($t['status'] === 'open'): ?>
                                        <span class="badge bg-primary-soft text-primary border border-primary-subtle px-2 py-1">
                                            <i class="fa-solid fa-clock me-1"></i> Open (Unresolved)
                                        </span>
                                    <?php elseif ($t['status'] === 'in_progress'): ?>
                                        <span class="badge bg-warning-soft text-warning border border-warning-subtle px-2 py-1">
                                            <i class="fa-solid fa-spinner fa-spin-pulse me-1"></i> In Progress
                                        </span>
                                    <?php elseif ($t['status'] === 'closed'): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1">
                                            <i class="fa-solid fa-lock me-1"></i> Closed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-info-soft text-info border border-info-subtle px-2 py-1">
                                            <i class="fa-solid fa-user-clock me-1"></i> <?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $t['staff_name'] ? e($t['staff_name']) : '<span class="text-muted small">Pending Assignment</span>'; ?>
                                </td>
                                <td class="text-secondary small">
                                    <?php echo date('M d, H:i', strtotime($t['updated_at'])); ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="ticket_view.php?number=<?php echo urlencode($t['ticket_number']); ?>" class="btn btn-sm btn-outline-light-soft">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
