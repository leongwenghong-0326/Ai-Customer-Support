<?php
/**
 * NovaServe AI - Admin User & Staff Directory Management
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

$roleFilter = clean_string($_GET['role'] ?? '');
$errors = [];

// Handle Create / Edit User POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token expired.';
    }

    $userId = clean_int($_POST['user_id'] ?? 0);
    $fullName = clean_string($_POST['full_name'] ?? '', 100);
    $email = clean_string($_POST['email'] ?? '', 150);
    $roleId = clean_int($_POST['role_id'] ?? 4);
    $status = in_array($_POST['status'] ?? '', ['active', 'suspended', 'pending'], true) ? $_POST['status'] : 'active';
    $password = $_POST['password'] ?? '';

    if (mb_strlen($fullName) < 2) $errors[] = 'Full name must have at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

    // Check duplicate email
    $checkSql = "SELECT id FROM users WHERE email = :email" . ($userId > 0 ? " AND id != {$userId}" : "");
    $existing = $db->fetchOne($checkSql, ['email' => $email]);
    if ($existing) $errors[] = 'Email is already in use by another account.';

    if (empty($errors)) {
        try {
            if ($userId > 0) {
                // Edit
                $updateData = [
                    'full_name' => $fullName,
                    'email' => $email,
                    'role_id' => $roleId,
                    'status' => $status
                ];
                if (!empty($password)) {
                    if (strlen($password) < 8) {
                        $errors[] = 'Password must be at least 8 characters.';
                    } else {
                        $updateData['password_hash'] = hash_password($password);
                    }
                }
                if (empty($errors)) {
                    $db->update('users', $updateData, 'id = :id', ['id' => $userId]);
                    log_activity($currentUser['id'], 'User Updated', "Modified user profile #{$userId} ({$email}).");
                    $_SESSION['flash_success'] = 'User details saved successfully.';
                    header('Location: users.php');
                    exit;
                }
            } else {
                // Create
                if (strlen($password) < 8) {
                    $errors[] = 'Initial password must have at least 8 characters.';
                } else {
                    $newId = $db->insert('users', [
                        'full_name' => $fullName,
                        'email' => $email,
                        'role_id' => $roleId,
                        'status' => $status,
                        'password_hash' => hash_password($password),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    log_activity($currentUser['id'], 'User Created', "Created new staff/user account #{$newId} ({$email}).");
                    $_SESSION['flash_success'] = 'New user account created successfully.';
                    header('Location: users.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("User save error: " . $e->getMessage());
            $errors[] = 'Database error updating user directory.';
        }
    }
}

$roles = $db->fetchAll("SELECT * FROM roles ORDER BY id ASC");

$adminTitle = 'User & Staff Directory';
require_once __DIR__ . '/../includes/admin_header.php';
$where = [];
$params = [];

if (!empty($roleFilter)) {
    $where[] = "r.role_key = :rk";
    $params['rk'] = $roleFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
$users = $db->fetchAll(
    "SELECT u.*, r.name as role_name, r.role_key 
     FROM users u 
     JOIN roles r ON u.role_id = r.id 
     {$whereSql} 
     ORDER BY u.id ASC",
    $params
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-white mb-1">Users & Staff Directory</h4>
        <p class="text-secondary small mb-0">Govern system operators, support representatives, content curators, and registered clients.</p>
    </div>
    <button class="btn btn-primary-gradient d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserModal()">
        <i class="fa-solid fa-user-plus"></i> Add User / Staff
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Role Filter Tabs -->
<div class="d-flex gap-2 flex-wrap mb-4">
    <a href="users.php" class="btn btn-sm <?php echo empty($roleFilter) ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
        All Directory (<?php echo count($users); ?>)
    </a>
    <a href="users.php?role=super_admin" class="btn btn-sm <?php echo ($roleFilter === 'super_admin') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
        Super Admins
    </a>
    <a href="users.php?role=support_staff" class="btn btn-sm <?php echo ($roleFilter === 'support_staff') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
        Support Staff
    </a>
    <a href="users.php?role=content_manager" class="btn btn-sm <?php echo ($roleFilter === 'content_manager') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
        Content Managers
    </a>
    <a href="users.php?role=customer" class="btn btn-sm <?php echo ($roleFilter === 'customer') ? 'btn-primary-gradient' : 'btn-outline-light-soft'; ?> rounded-pill px-3">
        Customers
    </a>
</div>

<!-- Users Table -->
<div class="table-dark-custom shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="ps-4">User</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th>Registered</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $roleBadge = match($u['role_key']) {
                        'super_admin' => 'bg-danger text-white',
                        'support_staff' => 'bg-primary text-white',
                        'content_manager' => 'bg-info text-dark',
                        default => 'bg-secondary text-white'
                    };
                ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="user-avatar-circle-sm" style="width:30px;height:30px;font-size:0.8rem;">
                                    <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                </span>
                                <strong class="text-white"><?php echo e($u['full_name']); ?></strong>
                            </div>
                        </td>
                        <td class="text-secondary small font-monospace"><?php echo e($u['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $roleBadge; ?>"><?php echo e($u['role_name']); ?></span>
                        </td>
                        <td>
                            <span class="badge <?php echo ($u['status'] === 'active') ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td class="small text-secondary"><?php echo $u['last_login_at'] ? date('M d, H:i', strtotime($u['last_login_at'])) : 'Never'; ?></td>
                        <td class="small text-secondary"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td class="pe-4 text-end">
                            <button class="btn btn-sm btn-outline-light-soft" onclick='editUser(<?php echo json_encode($u); ?>)'>
                                <i class="fa-solid fa-user-pen me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-card-dark border border-secondary-subtle text-white shadow-lg">
            <div class="modal-header border-bottom border-dark-subtle">
                <h5 class="modal-title fw-bold" id="userModalTitle">Manage User Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="users.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="user_id" id="modalUserId" value="0">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="modalFullName" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="modalEmail" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Role Assignment <span class="text-danger">*</span></label>
                            <select name="role_id" id="modalRoleId" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo e($r['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Account Status <span class="text-danger">*</span></label>
                            <select name="status" id="modalStatus" class="form-select bg-card-dark border-secondary-subtle text-white shadow-none" required>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold" id="modalPassLabel">Set Password</label>
                        <input type="password" name="password" id="modalPassword" class="form-control bg-card-dark border-secondary-subtle text-white shadow-none" placeholder="Min. 8 characters">
                        <small class="text-muted" id="modalPassHelp">Leave blank to keep existing password unchanged when editing.</small>
                    </div>
                </div>

                <div class="modal-footer border-top border-dark-subtle">
                    <button type="button" class="btn btn-outline-light-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-gradient px-4">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetUserModal() {
    document.getElementById('userModalTitle').innerText = 'Add New User / Staff';
    document.getElementById('modalUserId').value = '0';
    document.getElementById('modalFullName').value = '';
    document.getElementById('modalEmail').value = '';
    document.getElementById('modalRoleId').value = '2'; // default to staff
    document.getElementById('modalStatus').value = 'active';
    document.getElementById('modalPassword').value = '';
    document.getElementById('modalPassword').required = true;
    document.getElementById('modalPassHelp').style.display = 'none';
}

function editUser(u) {
    document.getElementById('userModalTitle').innerText = 'Edit User: ' + u.full_name;
    document.getElementById('modalUserId').value = u.id;
    document.getElementById('modalFullName').value = u.full_name;
    document.getElementById('modalEmail').value = u.email;
    document.getElementById('modalRoleId').value = u.role_id;
    document.getElementById('modalStatus').value = u.status;
    document.getElementById('modalPassword').value = '';
    document.getElementById('modalPassword').required = false;
    document.getElementById('modalPassHelp').style.display = 'block';
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
