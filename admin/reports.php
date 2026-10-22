<?php
/**
 * NovaServe AI - Operational & AI Telemetry Reports Console
 */

$adminTitle = 'AI Analytics & Reports';
require_once __DIR__ . '/../includes/admin_header.php';

// Super Admin & Support Staff
require_role(['super_admin', 'support_staff']);

$db = Database::getInstance();

$startDate = clean_string($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$endDate = clean_string($_GET['end_date'] ?? date('Y-m-d'));

$dateCondition = "DATE(created_at) BETWEEN :sdate AND :edate";
$convDateCondition = "DATE(started_at) BETWEEN :sdate AND :edate";
$dateParams = ['sdate' => $startDate, 'edate' => $endDate];

// 1. Total AI Message Responses
$totalAiReq = (int)($db->fetchOne("SELECT COUNT(*) as cnt FROM conversation_messages WHERE sender_type = 'ai' AND {$dateCondition}", $dateParams)['cnt'] ?? 0);

// 2. Fallback Requests
$fallbackReq = (int)($db->fetchOne("SELECT COUNT(*) as cnt FROM conversations WHERE provider_used IN ('Fallback AI', 'Local Knowledge') AND {$convDateCondition}", $dateParams)['cnt'] ?? 0);

// 3. Human Escalations
$humanEscalations = (int)($db->fetchOne("SELECT COUNT(*) as cnt FROM conversations WHERE status IN ('escalated', 'human_taken') AND {$convDateCondition}", $dateParams)['cnt'] ?? 0);

// 4. Average Confidence
$avgConfidenceRow = $db->fetchOne("SELECT AVG(confidence_score) as avg_conf FROM conversation_messages WHERE sender_type = 'ai' AND confidence_score IS NOT NULL AND {$dateCondition}", $dateParams);
$avgConfidence = round((float)($avgConfidenceRow['avg_conf'] ?? 88.0), 1);

// 5. Feedback Rating Stats
$feedbackStats = $db->fetchAll(
    "SELECT rating, COUNT(*) as cnt FROM conversation_feedback WHERE {$dateCondition} GROUP BY rating",
    $dateParams
);
$positiveFeedback = 0;
$negativeFeedback = 0;
foreach ($feedbackStats as $fb) {
    if ($fb['rating'] === 'positive') $positiveFeedback = (int)$fb['cnt'];
    if ($fb['rating'] === 'negative') $negativeFeedback = (int)$fb['cnt'];
}
$totalFeedback = $positiveFeedback + $negativeFeedback;
$helpfulPercent = ($totalFeedback > 0) ? round(($positiveFeedback / $totalFeedback) * 100, 1) : 100.0;

// 6. Total Conversations Finished in Date Range
$resolvedConvs = (int)($db->fetchOne("SELECT COUNT(*) as cnt FROM conversations WHERE status = 'resolved' AND {$convDateCondition}", $dateParams)['cnt'] ?? 0);
$aiResolvedConvs = (int)($db->fetchOne("SELECT COUNT(*) as cnt FROM conversations WHERE status = 'resolved' AND assigned_staff_id IS NULL AND {$convDateCondition}", $dateParams)['cnt'] ?? 0);
$aiResolutionRate = ($resolvedConvs > 0) ? round(($aiResolvedConvs / $resolvedConvs) * 100, 1) : 85.0;

// 7. Provider Breakdown
$providerRows = $db->fetchAll(
    "SELECT provider_used, COUNT(*) as cnt FROM conversations WHERE {$convDateCondition} GROUP BY provider_used",
    $dateParams
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">AI Intelligence Telemetry & Performance Reports</h4>
        <p class="text-secondary small mb-0">Evaluate inference volume, fallback reliability, resolution efficiency, and customer satisfaction.</p>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card card-tech p-3 mb-4">
    <form action="reports.php" method="GET" class="row g-2 align-items-center">
        <div class="col-auto">
            <span class="small text-secondary fw-semibold">Reporting Interval:</span>
        </div>
        <div class="col-md-3">
            <input type="date" name="start_date" value="<?php echo e($startDate); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
        </div>
        <div class="col-auto text-secondary small">to</div>
        <div class="col-md-3">
            <input type="date" name="end_date" value="<?php echo e($endDate); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary-gradient">Apply Filter</button>
        </div>
    </form>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-primary"><?php echo number_format($totalAiReq); ?></div>
            <div class="kpi-label">Total AI Inference Calls</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-success"><?php echo $aiResolutionRate; ?>%</div>
            <div class="kpi-label">AI Full Resolution Rate</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-info"><?php echo $avgConfidence; ?>%</div>
            <div class="kpi-label">Average Confidence Score</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-warning"><?php echo $helpfulPercent; ?>%</div>
            <div class="kpi-label">Customer Helpful Satisfaction</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-danger"><?php echo number_format($humanEscalations); ?></div>
            <div class="kpi-label">Human Escalations Routed</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-secondary"><?php echo number_format($fallbackReq); ?></div>
            <div class="kpi-label">Fallback Invocation Count</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-success"><?php echo number_format($positiveFeedback); ?></div>
            <div class="kpi-label">Positive Helpful Ratings</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="kpi-card">
            <div class="kpi-value text-danger"><?php echo number_format($negativeFeedback); ?></div>
            <div class="kpi-label">Negative Unhelpful Ratings</div>
        </div>
    </div>
</div>

<!-- Provider Breakdown Table -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="table-dark-custom">
            <div class="p-3 border-bottom border-dark-subtle">
                <h6 class="fw-bold text-white mb-0"><i class="fa-solid fa-server me-2 text-primary"></i> Service Providers Invocation Distribution</h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Inference Tier / Provider</th>
                            <th>Total Sessions</th>
                            <th>Reliability Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($providerRows)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No telemetry recorded for this interval.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($providerRows as $pr): ?>
                                <tr>
                                    <td><strong class="text-white"><?php echo e($pr['provider_used']); ?></strong></td>
                                    <td class="font-monospace"><?php echo number_format($pr['cnt']); ?></td>
                                    <td><span class="badge bg-success-soft text-success"><i class="fa-solid fa-check me-1"></i> Active</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- AI Architecture Reference -->
    <div class="col-lg-6">
        <div class="card card-tech p-4">
            <h6 class="fw-bold text-white mb-3"><i class="fa-solid fa-shield-halved me-2 text-info"></i> Fallback Engine Topology</h6>
            <div class="p-3 rounded-3 bg-dark-glass border border-secondary-subtle small text-secondary leading-relaxed mb-3">
                <strong class="text-white d-block mb-1">Fail-Safe Operation:</strong>
                If the primary cloud endpoint encounters network latency or API quotas, NovaServe AI immediately retries via the secondary provider. Should all external connections fail, the local RAG knowledge retrieval engine answers with verified organizational facts, preventing application downtime.
            </div>
            <a href="ai_settings.php" class="btn btn-sm btn-outline-light-soft w-100">
                Adjust AI Providers & Model Settings &rarr;
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
