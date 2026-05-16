<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_chair']);
require_once '../config/database.php';
include '../includes/header.php';

// Fetch protocols ready for decision (under review = all reviews complete, revised = resubmitted)
$stmt = $pdo->prepare("
    SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as staff_name,
    (SELECT COUNT(*) FROM reviewer_recommendations WHERE protocol_id = p.protocol_id AND recommendation IN ('Minor Revision', 'Major Revision')) as revision_count
    FROM protocols p 
    JOIN users u ON p.created_by = u.user_id 
    WHERE p.status IN ('under_review', 'revised') 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$protocols = $stmt->fetchAll();
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Waiting for Decision";
        $workspaceSubtitle = "Protocols that finished review and need a final call";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <h2 class="fw-bold text-navy mb-4">Pending Decisions</h2>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <?php if (empty($protocols)): ?>
                        <div class="p-5 text-center">
                            <i class="fas fa-check-circle fa-4x text-success opacity-25 mb-3"></i>
                            <h5 class="text-muted fw-bold">No protocols pending decision</h5>
                            <p class="text-muted small">Everything is up to date.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 px-4 py-3">REC Code</th>
                                        <th class="border-0 py-3">Title</th>
                                        <th class="border-0 py-3">Type</th>
                                        <th class="border-0 text-end px-4 py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($protocols as $p): ?>
                                        <tr>
                                            <td class="px-4 fw-bold text-navy">
                                                <?php echo $p['rec_code']; ?>
                                            </td>
                                            <td>
                                                <?php echo $p['title']; ?>
                                            </td>
                                            <td>
                                                <?php echo strtoupper($p['review_type']); ?>
                                            </td>
                                            <td class="text-end px-4">
                                                <a href="decision?id=<?php echo $p['protocol_id']; ?>"
                                                    class="btn <?php echo ($p['revision_count'] > 0 && $p['status'] !== 'revised') ? 'btn-warning' : 'btn-success'; ?> btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                                    <?php echo ($p['revision_count'] > 0 && $p['status'] !== 'revised') ? 'Needs Revision' : 'Final Decision'; ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
