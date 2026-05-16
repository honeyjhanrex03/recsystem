<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_member', 'rec_chair', 'rec_secretary', 'rec_staff']);
require_once '../config/database.php';
include '../includes/header.php';

// Fetch assignments for this member
$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT p.*, a.status as assignment_status, a.deadline 
                        FROM protocols p 
                        JOIN reviewer_assignments a ON p.protocol_id = a.protocol_id 
                        WHERE a.reviewer_id = ? 
                        ORDER BY a.deadline ASC");
$stmt->execute([$uid]);
$assignments = $stmt->fetchAll();
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "REC Member Evaluation Hub";
        $workspaceSubtitle = "REC Peer Review & Ethical Assessment Center";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4 p-md-5">
            <div class="row mb-5 align-items-end animate-up">
                <div class="col">
                    <h6 class="text-gold fw-bold text-uppercase small mb-2" style="letter-spacing: 2px;">Research
                        Registry</h6>
                    <h2 class="fw-bold text-navy mb-0">Assigned Ethical Reviews</h2>
                    <p class="text-muted mb-0">Provide technical and ethical analysis for assigned research dossiers.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm animate-up" style="animation-delay: 0.1s">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Reference</th>
                                    <th>Title Domain</th>
                                    <th>Submission Framework</th>
                                    <th class="text-center">Evaluation Priority</th>
                                    <th class="text-end pe-4">Orchestration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($assignments) > 0): ?>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-navy">
                                                <i
                                                    class="fas fa-file-signature me-2 opacity-25"></i><?php echo $a['rec_code']; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-navy text-truncate" style="max-width: 300px;">
                                                    <?php echo $a['title']; ?>
                                                </div>
                                                <div
                                                    class="small mt-1 <?php echo (strtotime($a['deadline']) < time() && $a['assignment_status'] == 'pending') ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                                    <i class="far fa-clock me-1"></i> Deadline:
                                                    <?php echo $a['deadline'] ? date('M d, Y', strtotime($a['deadline'])) : 'To be set by Staff'; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-outline-secondary text-uppercase"
                                                    style="border: 1px solid #cbd5e1; color: #64748b; font-size: 0.65rem;">
                                                    <?php 
                                                    $rtMap = ['pending'=>'PENDING REVIEW TYPE', 'exempt'=>'EXEMPTED FROM REVIEW', 'expedited'=>'EXPEDITED REVIEW', 'full_board'=>'FULL REVIEW'];
                                                    echo $rtMap[$a['review_type']] ?? strtoupper($a['review_type']); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($a['assignment_status'] == 'pending'): ?>
                                                    <span class="badge bg-warning-light text-warning">
                                                        <i class="fas fa-bolt me-1"></i> URGENT ACTION
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-light text-success">
                                                        <i class="fas fa-check-circle me-1"></i> COMPLETED
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($a['assignment_status'] == 'pending'): ?>
                                                    <a href="review?id=<?php echo $a['protocol_id']; ?>"
                                                        class="btn btn-navy btn-sm px-4 rounded-pill shadow-sm">
                                                        <i class="fas fa-pen-nib me-2"></i> Start Evaluation
                                                    </a>
                                                <?php else: ?>
                                                    <a href="../shared_view?id=<?php echo $a['protocol_id']; ?>"
                                                        class="btn btn-light btn-sm px-4 rounded-pill border shadow-sm text-navy fw-bold">
                                                        Inspect Submission
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="opacity-10 mb-3"><i class="fas fa-check-double fa-4x text-navy"></i>
                                            </div>
                                            <h5 class="text-muted fw-bold">Workload Clear</h5>
                                            <p class="text-muted small">No pending assignments found in your evaluation
                                                queue.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
