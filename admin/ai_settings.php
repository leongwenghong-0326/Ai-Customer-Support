<?php
/**
 * NovaServe AI - Primary & Secondary Fallback AI Parameter Configuration
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

// Super Admin Only
require_role('super_admin');

$db = Database::getInstance();
$currentUser = current_user();

$successMsg = '';
$errors = [];

// Handle Save POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token expired. Please reload the form.';
    } else {
        $keysToUpdate = [
            'ai_enabled', 'primary_api_url', 'primary_model',
            'fallback_api_url', 'fallback_model', 'temperature',
            'max_tokens', 'confidence_threshold', 'escalation_on_angry',
            'system_prompt'
        ];

        // Only update API keys if non-empty string provided (prevents overwriting with placeholder)
        if (!empty($_POST['primary_api_key'])) {
            $keysToUpdate[] = 'primary_api_key';
        }
        if (!empty($_POST['fallback_api_key'])) {
            $keysToUpdate[] = 'fallback_api_key';
        }

        try {
            foreach ($keysToUpdate as $k) {
                $val = $_POST[$k] ?? '';
                if ($k === 'ai_enabled' || $k === 'escalation_on_angry') {
                    $val = !empty($_POST[$k]) ? '1' : '0';
                }

                $db->query(
                    "INSERT INTO ai_settings (setting_key, setting_value) VALUES (:k, :v)
                     ON DUPLICATE KEY UPDATE setting_value = :v2",
                    ['k' => $k, 'v' => $val, 'v2' => $val]
                );
            }

            log_activity($currentUser['id'], 'AI Configuration Updated', 'Updated primary/fallback API models, thresholds, and system prompts.');
            $successMsg = 'AI inference parameters and fallback configuration saved successfully.';

        } catch (Exception $e) {
            error_log("AI settings save error: " . $e->getMessage());
            $errors[] = 'Database error updating AI settings.';
        }
    }
}

// Load current settings
$currentSettings = [];
$rows = $db->fetchAll("SELECT setting_key, setting_value FROM ai_settings");
foreach ($rows as $r) {
    $currentSettings[$r['setting_key']] = $r['setting_value'];
}

$adminTitle = 'AI Intelligence Configuration';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">AI Intelligence Configuration</h4>
        <p class="text-secondary small mb-0">Control the multi-tier AI inference pipeline, REST credentials, confidence ceilings, and system behavior.</p>
    </div>
</div>

<?php if ($successMsg): ?>
    <div class="alert alert-success d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2 fs-5"></i>
        <div><?php echo e($successMsg); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="ai_settings.php" method="POST">
    <?php echo csrf_field(); ?>

    <div class="row g-4">
        <!-- Global Engine Switch & Fallback Architecture -->
        <div class="col-12">
            <div class="card card-tech p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-white mb-1">Master Engine Status</h5>
                        <p class="text-secondary small mb-0">When disabled, customer chat automatically routes queries to the Local Knowledge Base engine without calling external APIs.</p>
                    </div>
                    <div class="form-check form-switch fs-4">
                        <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="aiEnabledSwitch" <?php echo (($currentSettings['ai_enabled'] ?? '1') === '1') ? 'checked' : ''; ?>>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tier 1: Primary AI Provider -->
        <div class="col-lg-6">
            <div class="card card-tech p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-dark-subtle">
                    <span class="badge bg-primary px-2 py-1 font-monospace">TIER 1</span>
                    <h5 class="fw-bold text-white mb-0">Primary AI Provider (REST API)</h5>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Primary Endpoint URL</label>
                    <input type="url" name="primary_api_url" value="<?php echo e($currentSettings['primary_api_url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent'); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Model Name</label>
                    <input type="text" name="primary_model" value="<?php echo e($currentSettings['primary_model'] ?? 'gemini-3.8-flash'); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Primary API Key (Server-Side Isolated)</label>
                    <input type="password" name="primary_api_key" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" placeholder="<?php echo !empty($currentSettings['primary_api_key']) ? '•••••••••••••••••••••••••••••••• (Saved)' : 'Enter API Key...'; ?>">
                    <small class="text-muted">Kept secure on the server. Never transmitted to client-side browsers.</small>
                </div>
            </div>
        </div>

        <!-- Tier 2: Secondary Fallback AI Provider -->
        <div class="col-lg-6">
            <div class="card card-tech p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-dark-subtle">
                    <span class="badge bg-info text-dark px-2 py-1 font-monospace">TIER 2</span>
                    <h5 class="fw-bold text-white mb-0">Fallback AI Provider (Zero-Downtime Backup)</h5>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Fallback Endpoint URL</label>
                    <input type="url" name="fallback_api_url" value="<?php echo e($currentSettings['fallback_api_url'] ?? 'https://apihub.agnes-ai.com/v1/chat/completions'); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small">
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Fallback Model Name</label>
                    <input type="text" name="fallback_model" value="<?php echo e($currentSettings['fallback_model'] ?? 'agnes-2.5-flash'); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small">
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Fallback API Key</label>
                    <input type="password" name="fallback_api_key" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" placeholder="<?php echo !empty($currentSettings['fallback_api_key']) ? '•••••••••••••••••••••••••••••••• (Saved)' : 'Enter Fallback Key...'; ?>">
                    <small class="text-muted">Invoked automatically if the primary provider experiences connection timeouts.</small>
                </div>
            </div>
        </div>

        <!-- Hyperparameters & Operational Thresholds -->
        <div class="col-12">
            <div class="card card-tech p-4">
                <h5 class="fw-bold text-white mb-3 border-bottom border-dark-subtle pb-2">Hyperparameters & Escalation Triggers</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label text-white small fw-bold">Temperature (Creativity: 0.0 - 1.0)</label>
                        <input type="number" step="0.05" min="0" max="1" name="temperature" value="<?php echo e($currentSettings['temperature'] ?? '0.3'); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
                        <small class="text-muted">Lower values (0.2-0.4) adhere strictly to retrieved knowledge.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-white small fw-bold">Max Tokens per Response</label>
                        <input type="number" step="50" min="100" max="4000" name="max_tokens" value="<?php echo e($currentSettings['max_tokens'] ?? '800'); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-white small fw-bold">Human Escalation Threshold (%)</label>
                        <input type="number" step="1" min="10" max="100" name="confidence_threshold" value="<?php echo e($currentSettings['confidence_threshold'] ?? '65.0'); ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none">
                        <small class="text-muted">Responses scoring below this confidence trigger human assistance advice.</small>
                    </div>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="escalation_on_angry" value="1" id="angryCheck" <?php echo (($currentSettings['escalation_on_angry'] ?? '1') === '1') ? 'checked' : ''; ?>>
                    <label class="form-check-label text-warning small fw-bold" for="angryCheck">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Automatically escalate conversation and alert staff when customer sentiment is classified as Angry
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">System Directive Prompt</label>
                    <textarea name="system_prompt" rows="6" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none font-monospace small" required><?php echo e($currentSettings['system_prompt'] ?? ''); ?></textarea>
                    <small class="text-muted">Establishes persona, polite demeanor, verified context boundaries, and multi-language alignment.</small>
                </div>

                <div class="text-end pt-3 border-top border-dark-subtle">
                    <button type="submit" class="btn btn-primary-gradient px-4 py-2">
                        Save Configuration Parameters
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
