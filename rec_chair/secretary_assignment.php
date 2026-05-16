<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_chair']);
require_once '../config/database.php';

$success = "";
$error = "";

// Handle Assignment
if (isset($_GET['action']) && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $action = $_GET['action'];
    try {
        if ($action === 'set_secretary') {
            $stmt = $pdo->prepare("UPDATE admins SET role = 'rec_secretary' WHERE admin_id = ?");
            $stmt->execute([$target_id]);
            $success = "User assigned as REC Secretary.";
        } elseif ($action === 'remove_secretary') {
            $stmt = $pdo->prepare("UPDATE admins SET role = 'rec_member' WHERE admin_id = ?");
            $stmt->execute([$target_id]);
            $success = "REC Secretary role removed (reverted to REC Member).";
        }
        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $log->execute([$_SESSION['user_id'], $success . " (User ID: $target_id)"]);
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all REC Members and Secretaries
$stmt = $pdo->query("SELECT * FROM admins WHERE role IN ('rec_member', 'rec_secretary') AND status = 'active' ORDER BY role DESC, name ASC");
$users = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "REC Secretary Settings";
        $workspaceSubtitle = "Assign members to help monitor research submissions";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <div class="row mb-4 animate-up">
                <div class="col-md-8">
                    <h2 class="fw-bold text-navy">Assign REC Secretary</h2>
                    <p class="text-muted">As REC Chair, you can designate REC Members to also act as Secretaries. Secretaries have monitoring access to all submitted protocols.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 animate-up">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="ps-4 py-3">Name</th>
                                    <th class="py-3">Current Role</th>
                                    <th class="py-3">Email</th>
                                    <th class="text-end pe-4 py-3">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-navy"><?php echo htmlspecialchars($u['name']); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'rec_secretary'): ?>
                                                <span class="badge bg-gold text-dark px-3 rounded-pill">
                                                    <i class="fas fa-star me-1"></i> REC SECRETARY
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted px-3 rounded-pill">
                                                    REC MEMBER
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($u['role'] === 'rec_secretary'): ?>
                                                <button onclick="confirmAction(<?php echo $u['admin_id']; ?>, 'remove_secretary')" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                    Remove REC Secretary Role
                                                </button>
                                            <?php else: ?>
                                                <button onclick="confirmAction(<?php echo $u['admin_id']; ?>, 'set_secretary')" class="btn btn-sm btn-navy rounded-pill px-3 shadow-sm">
                                                    Assign as REC Secretary
                                                </button>
                                            <?php endif; ?>
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
    function confirmAction(id, action) {
        let msg = action === 'set_secretary' ? "Assign this user as REC Secretary?" : "Remove REC Secretary role from this user?";
        Swal.fire({
            title: 'Confirm Assignment',
            text: msg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1a2b4b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `secretary_assignment?id=${id}&action=${action}`;
            }
        });
    }
</script>

<?php if ($success): ?>
    <script>Swal.fire({ title: 'Success', text: '<?php echo $success; ?>', icon: 'success' });</script>
<?php endif; ?>
<?php if ($error): ?>
    <script>Swal.fire({ title: 'Error', text: '<?php echo addslashes($error); ?>', icon: 'error' });</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
