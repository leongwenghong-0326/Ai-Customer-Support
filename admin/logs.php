<?php
/**
 * NovaServe AI - Audit & Security Activity Logs Console
 */

$adminTitle = 'System Audit Logs';
require_once __DIR__ . '/../includes/admin_header.php';

// Super Admin Only
require_role('super_admin');

$db = Database::getInstance();
$search = clean_string($_GET['q'] ?? '');
$actionFilter = clean_string($_GET['action'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(l.action LIKE :q1 OR l.description LIKE :q2 OR l.ip_address LIKE :q3 OR u.full_name LIKE :q4)";
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = '%' . $search . '%';
    $params['q3'] = '%' . $search . '%';
    $params['q4'] = '%' . $search . '%';
}

if (!empty($actionFilter)) {
    $where[] = "l.action = :act";
    $params['act'] = $actionFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
$logs = $db->fetchAll(
    "SELECT l.*, u.full_name as user_name, u.email as user_email 
     FROM activity_logs l
     LEFT JOIN users u ON l.user_id = u.id
     {$whereSql}
     ORDER BY l.created_at DESC, l.id DESC
     LIMIT 100",
    $params
);

$actions = $db->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">System Security & Audit Trail</h4>
        <p class="text-secondary small mb-0">Immutable tracking of operator logins, escalations, credential changes, and system modifications.</p>
    </div>
</div>

<!-- Search & Action Filters -->
<div class="card card-tech p-3 mb-4">
    <form action="logs.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-card-dark border-secondary-subtle text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" name="q" value="<?php echo e($search); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-2" placeholder="Search by description, IP address, user...">
            </div>
        </div>
        <div class="col-md-4">
            <select name="action" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none">
                <option value="">-- All Actions --</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?php echo e($a['action']); ?>" <?php echo ($actionFilter === $a['action']) ? 'selected' : ''; ?>>
                        <?php echo e($a['action']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-light-soft">Filter</button>
            <?php if (!empty($search) || !empty($actionFilter)): ?>
                <a href="logs.php" class="btn btn-link text-secondary text-decoration-none small">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="table-dark-custom shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-4">Timestamp</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-secondary">No audit logs matching query.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="ps-4 text-secondary small font-monospace" style="white-space: nowrap;">
                                <?php echo date('Y-m-d H:i:s', strtotime($l['created_at'])); ?>
                            </td>
                            <td>
                                <span class="badge bg-card-dark border border-secondary-subtle text-light-soft"><?php echo e($l['action']); ?></span>
                            </td>
                            <td class="small leading-relaxed" style="max-width: 400px;">
                                <?php echo e($l['description']); ?>
                            </td>
                            <td>
                                <?php if ($l['user_name']): ?>
                                    <strong class="text-white d-block small"><?php echo e($l['user_name']); ?></strong>
                                    <span class="text-muted" style="font-size:0.75rem;"><?php echo e($l['user_email']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">System / Visitor</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small font-monospace"><?php echo e($l['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
