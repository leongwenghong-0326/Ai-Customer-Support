<?php
/**
 * NovaServe AI - Admin & Staff Ticket Dispatch & Management Center
 */

$adminTitle = 'Support Tickets';
require_once __DIR__ . '/../includes/admin_header.php';

$db = Database::getInstance();

$statusFilter = clean_string($_GET['status'] ?? '');
$priorityFilter = clean_string($_GET['priority'] ?? '');

$where = [];
$params = [];

if (in_array($statusFilter, ['open', 'pending', 'in_progress', 'resolved', 'closed'], true)) {
    $where[] = "t.status = :st";
    $params['st'] = $statusFilter;
}

if (in_array($priorityFilter, ['low', 'medium', 'high', 'urgent'], true)) {
    $where[] = "t.priority = :pr";
    $params['pr'] = $priorityFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
$tickets = $db->fetchAll(
    "SELECT t.*, u.full_name as customer_name, u.email as customer_email,
            c.name as category_name, s.full_name as staff_name
     FROM support_tickets t
     LEFT JOIN users u ON t.customer_id = u.id
     LEFT JOIN categories c ON t.category_id = c.id
     LEFT JOIN users s ON t.assigned_staff_id = s.id
     {$whereSql}
     ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.updated_at DESC",
    $params
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">Customer Support Tickets</h4>
        <p class="text-secondary small mb-0">Dispatch, assign, and manage enterprise support requests and engineering queues.</p>
    </div>

    <!-- Filter Buttons -->
    <div class="d-flex gap-2 flex-wrap">
        <a href="tickets.php" class="btn btn-sm <?php echo (empty($statusFilter) && empty($priorityFilter)) ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            All Tickets
        </a>
        <a href="tickets.php?status=open" class="btn btn-sm <?php echo ($statusFilter === 'open') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Open
        </a>
        <a href="tickets.php?status=in_progress" class="btn btn-sm <?php echo ($statusFilter === 'in_progress') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            In Progress
        </a>
        <a href="tickets.php?priority=urgent" class="btn btn-sm <?php echo ($priorityFilter === 'urgent') ? 'btn-danger' : 'btn-outline-danger'; ?> rounded-pill px-3">
            <i class="fa-solid fa-flag me-1"></i> Urgent Only
        </a>
        <a href="tickets.php?status=resolved" class="btn btn-sm <?php echo ($statusFilter === 'resolved') ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-3">
            Resolved
        </a>
    </div>
</div>

<div class="table-dark-custom shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-4">Ticket Number</th>
                    <th>Customer</th>
                    <th>Category</th>
                    <th>Subject</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned Staff</th>
                    <th>Updated</th>
                    <th class="pe-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-secondary">
                            <i class="fa-solid fa-ticket fs-2 d-block mb-2 text-muted"></i>
                            No support tickets located under this filter criteria.
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
                        $priorityBadge = match($t['priority']) {
                            'urgent' => 'bg-danger text-white',
                            'high' => 'bg-warning text-dark',
                            'medium' => 'bg-primary-soft text-primary',
                            default => 'bg-secondary text-white'
                        };
                    ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-primary">
                                <a href="ticket_view.php?id=<?php echo $t['id']; ?>" class="text-primary text-decoration-none">
                                    <?php echo e($t['ticket_number']); ?>
                                </a>
                            </td>
                            <td>
                                <strong class="text-white d-block"><?php echo e($t['customer_name']); ?></strong>
                                <small class="text-muted"><?php echo e($t['customer_email']); ?></small>
                            </td>
                            <td><span class="small text-secondary"><?php echo e($t['category_name']); ?></span></td>
                            <td class="text-truncate" style="max-width: 200px;">
                                <a href="ticket_view.php?id=<?php echo $t['id']; ?>" class="text-white text-decoration-none">
                                    <?php echo e($t['subject']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?php echo $priorityBadge; ?>"><?php echo ucfirst($t['priority']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?> px-2 py-1">
                                    <?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $t['staff_name'] ? e($t['staff_name']) : '<span class="text-muted small">Unassigned</span>'; ?>
                            </td>
                            <td class="small text-secondary"><?php echo date('M d, H:i', strtotime($t['updated_at'])); ?></td>
                            <td class="pe-4 text-end">
                                <a href="ticket_view.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-light-soft px-3">
                                    Manage &rarr;
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
