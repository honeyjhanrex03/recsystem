<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';
include '../includes/header.php';

// Fetch all users
$stmt = $pdo->query("SELECT * FROM admins ORDER BY role, name ASC");
$users = $stmt->fetchAll();

// Fetch pending password resets
$resets_stmt = $pdo->query("SELECT pr.*, u.name, u.email FROM password_resets pr JOIN admins u ON pr.user_id = u.admin_id WHERE pr.status = 'pending' ORDER BY pr.created_at DESC");
$pendingResetsList = $resets_stmt->fetchAll();
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Identity & Access Management";
        $workspaceSubtitle = "REC Governance & System Personnel Control";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4 p-md-5">
            <?php if (!empty($pendingResetsList)): ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 animate-up">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-key fa-2x me-3 text-warning"></i>
                        <div>
                            <h5 class="fw-bold mb-0">Password Reset Requests</h5>
                            <p class="small mb-0 text-muted">The following personnel require manual password
                                reconfiguration.</p>
                        </div>
                    </div>
                    <div class="list-group">
                        <?php foreach ($pendingResetsList as $pr): ?>
                            <div
                                class="list-group-item bg-white border rounded-3 mb-2 d-flex justify-content-between align-items-center shadow-sm">
                                <div>
                                    <div class="fw-bold text-navy"><?php echo $pr['name']; ?></div>
                                    <div class="small text-muted"><?php echo $pr['email']; ?> • Request ID:
                                        #<?php echo $pr['reset_id']; ?></div>
                                </div>
                                <a href="edit_user?id=<?php echo $pr['user_id']; ?>&resolve_reset=<?php echo $pr['reset_id']; ?>"
                                    class="btn btn-sm btn-navy rounded-pill px-3">
                                    <i class="fas fa-wrench me-1"></i> Change Password
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="row mb-5 align-items-end animate-up">
                <div class="col">
                    <h6 class="text-gold fw-bold text-uppercase small mb-2" style="letter-spacing: 2px;">Governance</h6>
                    <h2 class="fw-bold text-navy mb-0">Identity & Access Management</h2>
                    <p class="text-muted mb-0">Orchestrate user roles and system permissions for the REC board.</p>
                </div>
                <div class="col-auto">
                    <a href="add_user" class="btn btn-navy shadow-sm">
                        <i class="fas fa-plus-circle me-2"></i> Provision Account
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm animate-up" style="animation-delay: 0.2s">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Personnel</th>
                                    <th>Access Credentials</th>
                                    <th>Assigned Role</th>
                                    <th class="text-center">Operational Status</th>
                                    <th class="text-end pe-4">Orchestration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['name']); ?>&background=random&size=32"
                                                    class="rounded-circle me-3 shadow-sm">
                                                <div class="fw-bold text-navy"><?php echo $u['name']; ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-muted"><i
                                                    class="far fa-envelope me-1 opacity-50"></i> <?php echo $u['email']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $roleColor = 'bg-primary-light';
                                            if ($u['role'] == 'admin')
                                                $roleColor = 'bg-danger-light';
                                            if ($u['role'] == 'rec_chair')
                                                $roleColor = 'bg-warning-light';
                                            ?>
                                            <span class="badge <?php echo $roleColor; ?> px-3">
                                                <?php echo strtoupper($u['role']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($u['status'] == 'active'): ?>
                                                <span class="badge bg-success-light text-success">
                                                    <i class="fas fa-check-circle me-1"></i> LOGGED ON
                                                </span>
                                            <?php elseif ($u['status'] == 'pending'): ?>
                                                <span class="badge bg-warning-light text-warning pulse">
                                                    <i class="fas fa-clock me-1"></i> WAITING APPROVAL
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-light text-danger">
                                                    <i class="fas fa-ban me-1"></i> SUSPENDED
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2 text-nowrap">
                                                <?php if ($u['status'] == 'pending'): ?>
                                                    <button
                                                        onclick="confirmApprove(<?php echo $u['admin_id']; ?>, '<?php echo htmlspecialchars($u['name']); ?>')"
                                                        class="btn btn-sm btn-success rounded-pill px-3 shadow-sm"
                                                        title="Approve">
                                                        <i class="fas fa-check-double me-1"></i> Approve Asset
                                                    </button>
                                                <?php endif; ?>
                                                <a href="edit_user?id=<?php echo $u['admin_id']; ?>"
                                                    class="btn btn-sm btn-light rounded-pill px-3 shadow-sm" title="Modify">
                                                    <i class="fas fa-pen-nib text-warning"></i>
                                                </a>
                                                <button
                                                    onclick="confirmToggle(<?php echo $u['admin_id']; ?>, '<?php echo $u['status']; ?>')"
                                                    class="btn btn-sm btn-light rounded-pill px-3 shadow-sm"
                                                    title="<?php echo $u['status'] == 'active' ? 'Suspend' : ($u['status'] == 'pending' ? 'Activate' : 'Resume'); ?>">
                                                    <i
                                                        class="fas fa-power-off <?php echo $u['status'] == 'active' ? 'text-danger' : 'text-success'; ?>"></i>
                                                </button>
                                                <?php if ($u['admin_id'] != $_SESSION['user_id']): ?>
                                                    <button
                                                        onclick="confirmDelete(<?php echo $u['admin_id']; ?>, '<?php echo htmlspecialchars($u['name']); ?>')"
                                                        class="btn btn-sm btn-light rounded-pill px-3 shadow-sm"
                                                        title="Delete Account">
                                                        <i class="fas fa-trash-alt text-danger"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmToggle(id, currentStatus) {
        Swal.fire({
            title: 'Account Oversight',
            text: `Confirm the suspension or reactivation of this personnel asset.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1a2b4b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Confirm Action',
            background: '#ffffff',
            customClass: {
                confirmButton: 'rounded-pill px-4',
                cancelButton: 'rounded-pill px-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `toggle_status?id=${id}`;
            }
        });
    }

    function confirmApprove(id, name) {
        Swal.fire({
            title: 'Approve Account?',
            html: `You are about to authorize <strong>${name}</strong> as a legitimate system operative.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-check me-2"></i> Approve User',
            cancelButtonText: 'Cancel',
            background: '#ffffff',
            customClass: {
                confirmButton: 'rounded-pill px-4',
                cancelButton: 'rounded-pill px-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `approve_user?id=${id}`;
            }
        });
    }

    function confirmDelete(id, name) {
        Swal.fire({
            title: 'Delete Account?',
            html: `This will <strong>permanently remove</strong> <em>${name}</em> from the system. This action <strong>cannot be undone</strong>.`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt me-2"></i> Yes, Delete',
            cancelButtonText: 'Cancel',
            background: '#ffffff',
            customClass: {
                confirmButton: 'rounded-pill px-4',
                cancelButton: 'rounded-pill px-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete_user?id=${id}`;
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>
