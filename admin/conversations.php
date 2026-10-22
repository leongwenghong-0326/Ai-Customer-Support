<?php
/**
 * NovaServe AI - Admin & Staff Conversation Management Center
 */

$adminTitle = 'Conversations & Takeover';
require_once __DIR__ . '/../includes/admin_header.php';

$db = Database::getInstance();
$filter = clean_string($_GET['filter'] ?? '');

$where = [];
$params = [];

if (in_array($filter, ['active', 'escalated', 'human_taken', 'resolved', 'closed'], true)) {
    $where[] = "c.status = :st";
    $params['st'] = $filter;
}

$whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
$conversations = $db->fetchAll(
    "SELECT c.*, u.full_name as customer_name, u.email as customer_email, s.full_name as staff_name 
     FROM conversations c
     LEFT JOIN users u ON c.customer_id = u.id
     LEFT JOIN users s ON c.assigned_staff_id = s.id
     {$whereSql}
     ORDER BY c.last_activity_at DESC, c.id DESC",
    $params
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">Customer Conversations</h4>
        <p class="text-secondary small mb-0">Monitor live AI customer dialogs, inspect intent & sentiment, and take over escalated threads.</p>
    </div>

    <!-- Filter Pills -->
    <div class="d-flex gap-2 flex-wrap">
        <a href="conversations.php" class="btn btn-sm <?php echo empty($filter) ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            All Threads
        </a>
        <a href="conversations.php?filter=escalated" class="btn btn-sm <?php echo ($filter === 'escalated') ? 'btn-danger' : 'btn-outline-danger'; ?> rounded-pill px-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Escalated
        </a>
        <a href="conversations.php?filter=active" class="btn btn-sm <?php echo ($filter === 'active') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Active AI
        </a>
        <a href="conversations.php?filter=human_taken" class="btn btn-sm <?php echo ($filter === 'human_taken') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Human Taken
        </a>
        <a href="conversations.php?filter=resolved" class="btn btn-sm <?php echo ($filter === 'resolved') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
            Resolved
        </a>
    </div>
</div>

<!-- Conversations Table Card -->
<div class="table-dark-custom shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Customer</th>
                    <th>Intent</th>
                    <th>Sentiment</th>
                    <th>Confidence</th>
                    <th>AI Provider</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th class="pe-4 text-end">Takeover / Inspect</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($conversations)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-secondary">
                            <i class="fa-solid fa-comments fs-2 d-block mb-2 text-muted"></i>
                            No conversation threads match this filter state.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): 
                        $badgeClass = match($conv['status']) {
                            'active' => 'bg-info-soft text-info',
                            'escalated' => 'bg-danger text-white animate-pulse',
                            'human_taken' => 'bg-primary-soft text-primary',
                            'resolved' => 'bg-success-soft text-success',
                            default => 'bg-secondary text-white'
                        };
                        $sentimentBadge = match($conv['overall_sentiment']) {
                            'Angry' => 'bg-danger text-white',
                            'Negative' => 'bg-warning text-dark',
                            'Positive' => 'bg-success text-white',
                            default => 'bg-secondary text-white'
                        };
                    ?>
                        <tr class="<?php echo ($conv['status'] === 'escalated') ? 'table-danger-subtle' : ''; ?>">
                            <td class="ps-4 font-monospace fw-bold text-primary">#<?php echo $conv['id']; ?></td>
                            <td>
                                <strong class="text-white d-block"><?php echo e($conv['customer_name'] ?? 'Guest Visitor'); ?></strong>
                                <small class="text-muted"><?php echo e($conv['customer_email'] ?? 'Web Chat'); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-card-dark border border-secondary-subtle text-light-soft"><?php echo e($conv['primary_intent'] ?? 'General'); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $sentimentBadge; ?>"><?php echo e($conv['overall_sentiment']); ?></span>
                            </td>
                            <td>
                                <span class="font-monospace <?php echo ($conv['confidence_score'] >= 80) ? 'text-success' : (($conv['confidence_score'] >= 65) ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo $conv['confidence_score']; ?>%
                                </span>
                            </td>
                            <td class="small text-secondary font-monospace"><?php echo e($conv['provider_used']); ?></td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?> px-2 py-1">
                                    <?php echo ucfirst(str_replace('_', ' ', $conv['status'])); ?>
                                </span>
                            </td>
                            <td class="small text-secondary"><?php echo date('M d, H:i', strtotime($conv['last_activity_at'])); ?></td>
                            <td class="pe-4 text-end">
                                <a href="conversation_view.php?id=<?php echo $conv['id']; ?>" class="btn btn-sm btn-outline-light-soft px-3">
                                    <?php echo ($conv['status'] === 'escalated') ? '<i class="fa-solid fa-headset text-danger me-1"></i> Take Over' : 'Inspect'; ?>
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
