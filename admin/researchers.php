<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

// Handle search/filter
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$query = "SELECT u.*,
    (SELECT COUNT(*) FROM protocols p WHERE p.created_by = u.user_id) AS total_submissions,
    (SELECT COUNT(*) FROM protocols p WHERE p.created_by = u.user_id AND p.status = 'clearance_released') AS cleared_submissions,
    (SELECT COUNT(*) FROM protocols p WHERE p.created_by = u.user_id AND p.status IN ('submitted','staff_review','initial_review','confirmed','assigned','under_review','completed','for_decision')) AS active_submissions
    FROM users u WHERE 1=1";

$params = [];
if ($search) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($statusFilter) {
    $query .= " AND u.status = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY u.last_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$researchers = $stmt->fetchAll();

// Stats
$totalR    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeR   = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$pendingR  = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$suspendedR = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

include '../includes/header.php';
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php
        $workspaceTitle = "Researcher Management";
        $workspaceSubtitle = "Committee Registry & Submission Intelligence";
        include '../includes/topbar.php';
        ?>

        <div class="container-fluid p-4 p-md-5">

            <!-- Page Header -->
            <div class="row align-items-center mb-5">
                <div class="col">
                    <span class="text-gold fw-bold text-uppercase small" style="letter-spacing:2px;">
                        <i class="fas fa-flask me-2"></i>Committee Registry
                    </span>
                    <h2 class="fw-bold text-navy mb-1 mt-1">Researcher Management</h2>
                    <p class="text-muted mb-0">View, manage, and oversee all registered research authors in the system.</p>
                </div>

            </div>

            <!-- Stat Cards -->
            <div class="row g-4 mb-5">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="card-glass-accent"></div>
                        <div class="p-4 pt-5 position-relative">
                            <span class="badge bg-soft-navy text-navy rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold">TOTAL</span>
                            <div class="stat-icon-wrapper bg-soft-navy text-navy mb-3">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h2 class="display-5 fw-extrabold text-navy mb-0"><?php echo $totalR; ?></h2>
                            <p class="text-muted small mb-3">Registered researchers</p>
                            <div class="progress rounded-pill" style="height:5px;">
                                <div class="progress-bar bg-navy" style="width:100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="card-glass-accent"></div>
                        <div class="p-4 pt-5 position-relative">
                            <span class="badge bg-soft-success text-success rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold">ACTIVE</span>
                            <div class="stat-icon-wrapper bg-soft-success text-success mb-3">
                                <i class="fas fa-circle-check"></i>
                            </div>
                            <h2 class="display-5 fw-extrabold text-navy mb-0"><?php echo $activeR; ?></h2>
                            <p class="text-muted small mb-3">Active accounts</p>
                            <div class="progress rounded-pill" style="height:5px; background:rgba(16,185,129,0.1);">
                                <div class="progress-bar bg-success" style="width:<?php echo $totalR > 0 ? round(($activeR/$totalR)*100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="card-glass-accent"></div>
                        <div class="p-4 pt-5 position-relative">
                            <span class="badge bg-soft-warning text-warning rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold">PENDING</span>
                            <div class="stat-icon-wrapper bg-soft-warning text-warning mb-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h2 class="display-5 fw-extrabold text-navy mb-0"><?php echo $pendingR; ?></h2>
                            <p class="text-muted small mb-3">Awaiting approval</p>
                            <div class="progress rounded-pill" style="height:5px; background:rgba(245,158,11,0.1);">
                                <div class="progress-bar bg-warning" style="width:<?php echo $totalR > 0 ? round(($pendingR/$totalR)*100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card-premium border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <div class="card-glass-accent"></div>
                        <div class="p-4 pt-5 position-relative">
                            <span class="badge bg-soft-danger text-danger rounded-pill position-absolute top-0 end-0 mt-3 me-3 x-small fw-bold">SUSPENDED</span>
                            <div class="stat-icon-wrapper bg-soft-danger text-danger mb-3">
                                <i class="fas fa-ban"></i>
                            </div>
                            <h2 class="display-5 fw-extrabold text-navy mb-0"><?php echo $suspendedR; ?></h2>
                            <p class="text-muted small mb-3">Suspended accounts</p>
                            <div class="progress rounded-pill" style="height:5px; background:rgba(239,68,68,0.1);">
                                <div class="progress-bar bg-danger" style="width:<?php echo $totalR > 0 ? round(($suspendedR/$totalR)*100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Bar -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select bg-light">
                            <option value="">All Statuses</option>
                            <option value="active" <?php if ($statusFilter === 'active') echo 'selected'; ?>>Active</option>
                            <option value="pending" <?php if ($statusFilter === 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="suspended" <?php if ($statusFilter === 'suspended') echo 'selected'; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-navy rounded-pill px-4">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <?php if ($search || $statusFilter): ?>
                            <a href="researchers" class="btn btn-light border rounded-pill px-3 ms-2 text-muted">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Researcher Table -->
            <div class="card border-0 shadow-sm animate-up">
                <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-navy">
                        <i class="fas fa-list-ul me-2 text-gold"></i>
                        All Researchers
                        <span class="badge bg-soft-navy text-navy rounded-pill ms-2"><?php echo count($researchers); ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">Researcher</th>
                                    <th>Email</th>
                                    <th class="text-center">Submissions</th>
                                    <th class="text-center">Cleared</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($researchers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-ghost fa-2x mb-3 d-block opacity-25"></i>
                                            No researchers found<?php echo ($search || $statusFilter) ? ' matching your criteria.' : ' in the system.'; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($researchers as $r): ?>
                                    <?php $fullName = trim($r['last_name'] . ', ' . $r['first_name'] . ' ' . ($r['middle_initial'] ?? '')); ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['first_name'].' '.$r['last_name']); ?>&background=1a2b4b&color=fff&size=36&bold=true"
                                                    class="rounded-circle shadow-sm" width="36" height="36">
                                                <div>
                                                    <div class="fw-bold text-navy small"><?php echo htmlspecialchars($fullName); ?></div>
                                                    <div class="text-muted" style="font-size:0.75rem;">ID #<?php echo $r['user_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="small text-muted"><i class="far fa-envelope me-1 opacity-50"></i><?php echo htmlspecialchars($r['email']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-soft-navy text-navy rounded-pill px-3">
                                                <?php echo $r['total_submissions']; ?> total
                                            </span>
                                            <?php if ($r['active_submissions'] > 0): ?>
                                                <br><small class="text-warning fw-semibold mt-1" style="font-size:0.7rem;">
                                                    <i class="fas fa-circle-dot me-1"></i><?php echo $r['active_submissions']; ?> in review
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($r['cleared_submissions'] > 0): ?>
                                                <span class="badge bg-soft-success text-success rounded-pill px-3">
                                                    <i class="fas fa-certificate me-1"></i><?php echo $r['cleared_submissions']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($r['status'] === 'active'): ?>
                                                <span class="badge bg-soft-success text-success rounded-pill px-3">
                                                    <i class="fas fa-check-circle me-1"></i>Active
                                                </span>
                                            <?php elseif ($r['status'] === 'pending'): ?>
                                                <span class="badge bg-soft-warning text-warning rounded-pill px-3 pulse">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-soft-danger text-danger rounded-pill px-3">
                                                    <i class="fas fa-ban me-1"></i>Suspended
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <?php if ($r['status'] === 'pending'): ?>
                                                    <button onclick="approveResearcher(<?php echo $r['user_id']; ?>, '<?php echo htmlspecialchars($fullName); ?>')"
                                                        class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                <?php endif; ?>
                                                <a href="edit_researcher?id=<?php echo $r['user_id']; ?>"
                                                    class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm" title="Edit Profile">
                                                    <i class="fas fa-pen-nib text-warning"></i>
                                                </a>
                                                <button onclick="toggleResearcher(<?php echo $r['user_id']; ?>, '<?php echo $r['status']; ?>', '<?php echo htmlspecialchars($fullName); ?>')"
                                                    class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm"
                                                    title="<?php echo $r['status'] === 'active' ? 'Suspend' : 'Reactivate'; ?>">
                                                    <i class="fas fa-power-off <?php echo $r['status'] === 'active' ? 'text-danger' : 'text-success'; ?>"></i>
                                                </button>
                                                <button onclick="deleteResearcher(<?php echo $r['user_id']; ?>, '<?php echo htmlspecialchars($fullName); ?>')"
                                                    class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm" title="Delete">
                                                    <i class="fas fa-trash-alt text-danger"></i>
                                                </button>
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
function approveResearcher(id, name) {
    Swal.fire({
        title: 'Approve Researcher?',
        html: `Grant access to <strong>${name}</strong>? They will be able to login and submit protocols.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-check me-2"></i>Approve Account',
        cancelButtonText: 'Cancel',
        background: '#ffffff',
        customClass: { confirmButton: 'rounded-pill px-4', cancelButton: 'rounded-pill px-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `toggle_researcher?id=${id}&action=approve`;
        }
    });
}

function toggleResearcher(id, currentStatus, name) {
    const isActive = currentStatus === 'active';
    Swal.fire({
        title: isActive ? 'Suspend Account?' : 'Reactivate Account?',
        html: isActive
            ? `<strong>${name}</strong> will be suspended and cannot log in.`
            : `<strong>${name}</strong> will be reactivated and can log in again.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: isActive ? '#dc2626' : '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: isActive ? '<i class="fas fa-ban me-2"></i>Suspend' : '<i class="fas fa-check me-2"></i>Reactivate',
        cancelButtonText: 'Cancel',
        background: '#ffffff',
        customClass: { confirmButton: 'rounded-pill px-4', cancelButton: 'rounded-pill px-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `toggle_researcher?id=${id}&action=toggle`;
        }
    });
}

function deleteResearcher(id, name) {
    Swal.fire({
        title: 'Delete Researcher?',
        html: `This will <strong>permanently remove</strong> <em>${name}</em> and all their protocol submissions. <br><br><span class="text-danger fw-bold">This cannot be undone.</span>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-trash-alt me-2"></i>Yes, Delete',
        cancelButtonText: 'Cancel',
        background: '#ffffff',
        customClass: { confirmButton: 'rounded-pill px-4', cancelButton: 'rounded-pill px-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `toggle_researcher?id=${id}&action=delete`;
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
