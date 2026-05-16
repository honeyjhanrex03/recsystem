<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_chair']);
require_once '../config/database.php';
include '../includes/header.php';

// Fetch all protocols with reviewer progress and names
$stmt = $pdo->query("
    SELECT p.*,
           COALESCE(CONCAT(u.last_name, ', ', u.first_name), 'Institutional Personnel') as staff_name,
           COUNT(a.assignment_id) as total_reviewers,
           SUM(a.status = 'completed') as done_reviewers,
           GROUP_CONCAT(r.name SEPARATOR ', ') as reviewer_names
    FROM protocols p
    LEFT JOIN users u ON p.created_by = u.user_id
    LEFT JOIN reviewer_assignments a ON p.protocol_id = a.protocol_id
    LEFT JOIN admins r ON a.reviewer_id = r.admin_id
    GROUP BY p.protocol_id
    ORDER BY p.created_at DESC
");
$protocols = $stmt->fetchAll();
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Review Management";
        $workspaceSubtitle = "Assign Reviewers & Make Decisions";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4 p-md-5">
            <div class="row mb-5 align-items-end animate-up">
                <div class="col">
                    <h6 class="text-gold fw-bold text-uppercase small mb-2" style="letter-spacing:2px;">Management</h6>
                    <h2 class="fw-bold text-navy mb-0">Manage Reviews</h2>
                    <p class="text-muted mb-0">Assign reviewers and make your final decision on protocols.</p>
                </div>
                <!-- Stats summary -->
                <div class="col-auto d-none d-md-flex gap-3">
                    <?php
                    $counts = ['initial_review' => 0, 'assigned' => 0, 'under_review' => 0, 'approved' => 0];
                    foreach ($protocols as $pp) {
                        if (isset($counts[$pp['status']]))
                            $counts[$pp['status']]++;
                    }
                    $statItems = [
                        ['Awaiting Assignment', $counts['initial_review'], 'bg-info-light', 'fa-inbox'],
                        ['Under Review', $counts['assigned'] + $counts['under_review'], 'bg-warning-light', 'fa-magnifying-glass'],
                        ['Approved', $counts['approved'], 'bg-success-light', 'fa-check'],
                    ];
                    foreach ($statItems as [$lbl, $cnt, $cls, $ico]):
                        ?>
                        <div class="card border-0 shadow-sm rounded-3 px-3 py-2 text-center <?php echo $cls; ?>"
                            style="min-width:90px;">
                            <i class="fas <?php echo $ico; ?> mb-1"></i>
                            <div class="fw-bold fs-5"><?php echo $cnt; ?></div>
                            <div style="font-size:0.65rem;"><?php echo $lbl; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm animate-up" style="animation-delay:0.1s">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">REC Code</th>
                                    <th>Protocol Title</th>
                                    <th>Review Type</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Progress</th>
                                    <th class="text-end pe-4">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($protocols as $pr): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-navy">
                                            <i class="fas fa-barcode me-2 opacity-25"></i>
                                            <?php 
                                            if(empty($pr['rec_code']) || strpos($pr['rec_code'] ?? '', 'PENDING') !== false || strpos($pr['rec_code'] ?? '', 'ATC') !== false) {
                                                echo '<span class="text-muted small fw-normal"><i>PENDING CODE</i></span>';
                                            } else {
                                                echo htmlspecialchars($pr['rec_code']); 
                                            }
                                            ?>
                                            <div class="small text-muted fw-normal mt-1">by
                                                <?php echo htmlspecialchars($pr['staff_name']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-navy text-truncate" style="max-width:280px;"
                                                title="<?php echo htmlspecialchars($pr['title']); ?>">
                                                <?php echo htmlspecialchars($pr['title']); ?>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <?php echo date('M d, Y', strtotime($pr['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge text-uppercase"
                                                style="border:1px solid #cbd5e1; color:#64748b; font-size:0.65rem;">
                                                <?php 
                                                $rtMap = ['pending'=>'PENDING REVIEW TYPE', 'exempt'=>'EXEMPTED FROM REVIEW', 'expedited'=>'EXPEDITED REVIEW', 'full_board'=>'FULL REVIEW'];
                                                echo $rtMap[$pr['review_type']] ?? strtoupper(str_replace('_', ' ', $pr['review_type'])); 
                                                ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $statusMap = [
                                                'submitted' => ['bg-info-light', '📬 Submitted'],
                                                'staff_review' => ['bg-secondary-light text-secondary', '📋 Staff Review'],
                                                'needs_revision' => ['bg-danger-light', '↩ Needs Revision'],
                                                'initial_review' => ['bg-primary-light', '⚖️ Initial Review'],
                                                'confirmed' => ['bg-success-light text-success', '✅ Confirmed'],
                                                'assigned' => ['bg-info text-white', '👥 Assigned'],
                                                'under_review' => ['bg-warning-light', '🔍 Under Review'],
                                                'revised' => ['bg-info-light', '🔄 Revised'],
                                                'approved' => ['bg-success-light', '✅ Approved'],
                                                'rejected' => ['bg-danger text-white', '❌ Rejected'],
                                                'clearance_released' => ['bg-success text-white', '🎉 Clearance Released']
                                            ];
                                            $statusValue = $pr['status'] ?: 'unknown';
                                            [$cls, $label] = $statusMap[$statusValue] ?? ['bg-secondary text-white', strtoupper(str_replace('_', ' ', $statusValue))];
                                            ?>
                                            <span
                                                class="badge <?php echo $cls; ?> rounded-pill"><?php echo $label; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($pr['total_reviewers'] > 0): ?>
                                                <div class="small text-muted mb-1">
                                                    <?php echo (int) $pr['done_reviewers']; ?> /
                                                    <?php echo (int) $pr['total_reviewers']; ?> done
                                                </div>
                                                <div class="progress mb-2" style="height:6px; width:100px; margin:0 auto;">
                                                    <div class="progress-bar bg-navy"
                                                         style="width:<?php echo ($pr['total_reviewers'] > 0 ? round(($pr['done_reviewers'] / $pr['total_reviewers']) * 100) : 0); ?>%">
                                                    </div>
                                                </div>
                                                <div class="x-small text-muted" style="font-size: 0.65rem; max-width: 120px; margin: 0 auto; line-height: 1.1;">
                                                    <i class="fas fa-users-viewfinder me-1"></i> <?php echo htmlspecialchars($pr['reviewer_names']); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($pr['review_type'] === 'pending' && in_array($pr['status'], ['submitted', 'staff_review', 'initial_review'])): ?>
                                                <!-- Must pick review type first -->
                                                <a href="confirm_protocol?id=<?php echo $pr['protocol_id']; ?>"
                                                    class="btn btn-warning btn-sm px-4 rounded-pill shadow-sm fw-bold text-navy" title="Pick Review Type First">
                                                    <i class="fas fa-clipboard-check me-1"></i> Pick Review Type
                                                </a>
                                            <?php elseif (in_array($pr['status'], ['submitted', 'staff_review', 'initial_review'])): ?>
                                                <!-- Review type already set, can confirm or assign -->
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <a href="confirm_protocol?id=<?php echo $pr['protocol_id']; ?>"
                                                        class="btn btn-outline-navy btn-sm px-3 rounded-pill shadow-sm" title="Change Review Type">
                                                        <i class="fas fa-check-circle"></i>
                                                    </a>
                                                    <a href="assign?id=<?php echo $pr['protocol_id']; ?>"
                                                        class="btn btn-navy btn-sm px-4 rounded-pill shadow-sm">
                                                        <i class="fas fa-user-plus me-1"></i> Assign
                                                    </a>
                                                </div>
                                            <?php elseif ($pr['status'] === 'confirmed'): ?>
                                                <a href="assign?id=<?php echo $pr['protocol_id']; ?>"
                                                    class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                                                    <i class="fas fa-user-plus me-1"></i> Assign Reviewers
                                                </a>
                                            <?php elseif (in_array($pr['status'], ['assigned', 'under_review', 'revised'])): ?>
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <a href="assign?id=<?php echo $pr['protocol_id']; ?>" 
                                                       class="btn btn-outline-navy btn-sm px-2 rounded-pill shadow-sm" title="Re-assign or Add Reviewers">
                                                        <i class="fas fa-user-plus"></i>
                                                    </a>
                                                    <a href="decision?id=<?php echo $pr['protocol_id']; ?>"
                                                        class="btn btn-success btn-sm px-4 rounded-pill shadow-sm">
                                                        <i class="fas fa-gavel me-1"></i> Check & Decision
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <a href="../shared_view?id=<?php echo $pr['protocol_id']; ?>"
                                                    class="btn btn-light btn-sm px-4 rounded-pill border shadow-sm text-navy fw-bold">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($protocols) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                                            <p>No protocols submitted yet.</p>
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
