<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';
include '../includes/header.php';

$stmt = $pdo->query("SELECT a.*, COALESCE(u.name, 'Author System') as user_name FROM audit_logs a LEFT JOIN admins u ON a.user_id = u.admin_id ORDER BY a.timestamp DESC LIMIT 100");
$logs = $stmt->fetchAll();
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "System Audit Trail";
        $workspaceSubtitle = "Real-time Monitoring of Institutional Actions";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <h2 class="fw-bold text-navy mb-4">Activity Logs</h2>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4 py-3">Timestamp</th>
                                    <th class="border-0 py-3">User</th>
                                    <th class="border-0 py-3">Action</th>
                                    <th class="border-0 text-end px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td class="px-4 small">
                                            <?php echo $l['timestamp']; ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php echo $l['user_name'] ?? 'System'; ?>
                                        </td>
                                        <td>
                                            <?php echo $l['action']; ?>
                                        </td>
                                        <td class="text-end px-4"><span
                                                class="badge bg-success-light text-success">Success</span></td>
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
<?php include '../includes/footer.php'; ?>
