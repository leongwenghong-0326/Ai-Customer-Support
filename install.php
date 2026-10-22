<?php
/**
 * NovaServe AI - Interactive Web Installation Wizard
 * Sets up database, imports schema and original sample data, creates admin, and locks itself
 */

define('IS_INSTALLER', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/security.php';

$lockFile = CONFIG_PATH . DS . 'installed.lock';
$isLocked = file_exists($lockFile);

$errors = [];
$success = false;

// Auto-detect default port (prioritize 3307 for local XAMPP if open, else 3306)
$defaultPort = 3307;
$fp = @fsockopen('127.0.0.1', 3307, $errno, $errstr, 0.5);
if ($fp) {
    fclose($fp);
    $defaultPort = 3307;
} else {
    $defaultPort = 3306;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    $dbHost = clean_string($_POST['db_host'] ?? '127.0.0.1');
    $dbPort = clean_int($_POST['db_port'] ?? $defaultPort);
    $dbName = clean_string($_POST['db_name'] ?? 'novaserve_db');
    $dbUser = clean_string($_POST['db_user'] ?? 'root');
    $dbPass = $_POST['db_pass'] ?? '';

    $adminName = clean_string($_POST['admin_name'] ?? 'Alexander Vance');
    $adminEmail = clean_string($_POST['admin_email'] ?? 'admin@novaserve.com');
    $adminPass = $_POST['admin_password'] ?? 'Admin@123456';
    $importSample = !empty($_POST['import_sample_data']);

    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $errors[] = 'Please fill out all required database connection parameters.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid administrator email address.';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = 'Administrator password must be at least 8 characters long.';
    }

    if (empty($errors)) {
        try {
            // Step 1: Connect to MySQL server without database first
            $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);

            // Step 2: Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Step 3: Import Schema
            $schemaFile = __DIR__ . '/database/database.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("Schema definition file [database/database.sql] is missing.");
            }
            $schemaSql = file_get_contents($schemaFile);
            $pdo->exec($schemaSql);

            // Step 4: Import Sample Seed Data if selected
            if ($importSample) {
                $sampleFile = __DIR__ . '/database/sample_data.sql';
                if (file_exists($sampleFile)) {
                    $sampleSql = file_get_contents($sampleFile);
                    $pdo->exec($sampleSql);
                }
            } else {
                // Ensure default roles exist if not importing sample data
                $pdo->exec("INSERT IGNORE INTO `roles` (`id`, `role_key`, `name`, `description`) VALUES
                    (1, 'super_admin', 'Super Administrator', 'Full system authority'),
                    (2, 'support_staff', 'Support Representative', 'Ticket and chat management'),
                    (3, 'content_manager', 'Content Specialist', 'Knowledge and FAQ curation'),
                    (4, 'customer', 'Registered Client', 'Customer support user')");
            }

            // Step 5: Insert or Update Super Admin Account
            $adminHash = hash_password($adminPass);
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $adminEmail]);
            $existingAdmin = $stmt->fetch();

            if ($existingAdmin) {
                $stmt = $pdo->prepare("UPDATE users SET full_name = :fn, password_hash = :ph, role_id = 1, status = 'active' WHERE id = :id");
                $stmt->execute(['fn' => $adminName, 'ph' => $adminHash, 'id' => $existingAdmin['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, password_hash, status, created_at) VALUES (1, :fn, :email, :ph, 'active', NOW())");
                $stmt->execute(['fn' => $adminName, 'email' => $adminEmail, 'ph' => $adminHash]);
            }

            // Step 6: Write custom database config file
            $dbCustomContent = "<?php\nreturn [\n" .
                "    'host' => '{$dbHost}',\n" .
                "    'port' => {$dbPort},\n" .
                "    'dbname' => '{$dbName}',\n" .
                "    'user' => '{$dbUser}',\n" .
                "    'pass' => '{$dbPass}'\n" .
                "];\n";
            file_put_contents(CONFIG_PATH . DS . 'db_custom.php', $dbCustomContent);

            // Step 7: Write lock file
            file_put_contents($lockFile, date('Y-m-d H:i:s') . "\nInstalled by: {$adminEmail}");
            $success = true;

        } catch (Exception $e) {
            error_log("Installer exception: " . $e->getMessage());
            $errors[] = "Installation Failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install NovaServe AI Platform</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body { background: #0a0f1d; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="container" style="max-width: 680px;">
    <div class="text-center mb-4">
        <div class="brand-logo-icon mx-auto mb-2" style="width: 54px; height: 54px; font-size: 1.6rem;">
            <i class="fa-solid fa-brain text-primary"></i>
        </div>
        <h3 class="fw-bold text-white mb-1">Nova<span class="text-primary-gradient">Serve</span> AI Installation Wizard</h3>
        <p class="text-secondary small">Automated Database Setup, RAG Knowledge Indexing, and Administrator Provisioning</p>
    </div>

    <div class="card card-tech p-4 p-md-5 shadow-lg">
        <?php if ($isLocked && !$success): ?>
            <!-- Already Installed Notice -->
            <div class="text-center py-4">
                <i class="fa-solid fa-lock text-warning fs-1 mb-3"></i>
                <h4 class="fw-bold text-white mb-2">Platform Already Installed</h4>
                <p class="text-secondary small mb-4">
                    The installation wizard has been securely locked to prevent unauthorized database overwriting. To re-install, manually remove <code>config/installed.lock</code> from the server filesystem.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="login.php" class="btn btn-primary-gradient px-4">Sign In to Platform</a>
                    <a href="index.php" class="btn btn-outline-light-soft px-4">Visit Customer Portal</a>
                </div>
            </div>
        <?php elseif ($success): ?>
            <!-- Success Screen -->
            <div class="text-center py-4">
                <i class="fa-solid fa-circle-check text-success fs-1 mb-3"></i>
                <h4 class="fw-bold text-white mb-2">Installation Completed Successfully!</h4>
                <p class="text-secondary small mb-4">
                    All database tables, default roles, AI settings, and original sample articles have been successfully initialized.
                </p>
                <div class="p-3 bg-dark-glass border border-secondary-subtle rounded-3 text-start small mb-4">
                    <strong class="text-white d-block mb-1">Your Super Administrator Account:</strong>
                    Email: <code><?php echo e($adminEmail); ?></code><br>
                    Password: <code><?php echo e($adminPass); ?></code>
                </div>
                <a href="login.php" class="btn btn-primary-gradient btn-lg w-100">
                    Proceed to Admin Console &rarr;
                </a>
            </div>
        <?php else: ?>
            <!-- Installation Form -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <strong class="d-block mb-1"><i class="fa-solid fa-circle-exclamation me-1"></i> Pre-flight Error:</strong>
                    <ul class="mb-0 ps-3 small">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="install.php" method="POST">
                <h6 class="text-white fw-bold mb-3 border-bottom border-dark-subtle pb-2">
                    <i class="fa-solid fa-database text-primary me-2"></i> 1. MySQL Database Configuration
                </h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label text-white small fw-bold">Database Host</label>
                        <input type="text" name="db_host" value="127.0.0.1" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white small fw-bold">Port</label>
                        <input type="number" name="db_port" value="<?php echo $defaultPort; ?>" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label text-white small fw-bold">Database Name (Will be created automatically)</label>
                        <input type="text" name="db_name" value="novaserve_db" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white small fw-bold">Database Username</label>
                        <input type="text" name="db_user" value="root" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white small fw-bold">Database Password</label>
                        <input type="password" name="db_pass" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3 font-monospace small" placeholder="Leave empty if none">
                    </div>
                </div>

                <h6 class="text-white fw-bold mb-3 border-bottom border-dark-subtle pb-2">
                    <i class="fa-solid fa-user-shield text-primary me-2"></i> 2. Super Administrator Account
                </h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-white small fw-bold">Full Name</label>
                        <input type="text" name="admin_name" value="Alexander Vance" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white small fw-bold">Admin Email</label>
                        <input type="email" name="admin_email" value="admin@novaserve.com" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-white small fw-bold">Admin Password</label>
                        <input type="password" name="admin_password" value="Admin@123456" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none ps-3" required>
                    </div>
                </div>

                <h6 class="text-white fw-bold mb-3 border-bottom border-dark-subtle pb-2">
                    <i class="fa-solid fa-seedling text-primary me-2"></i> 3. Sample Demonstration Seed Data
                </h6>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="import_sample_data" value="1" id="seedCheck" checked>
                    <label class="form-check-label text-light-soft small" for="seedCheck">
                        <strong>Import Original Fictional Demonstration Data</strong> (Includes 5 categories, 11 FAQs, 10 Knowledge Articles, test support tickets, and sample customer conversations)
                    </label>
                </div>

                <button type="submit" class="btn btn-primary-gradient btn-lg w-100 py-2">
                    Install & Initialize NovaServe AI &rarr;
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
