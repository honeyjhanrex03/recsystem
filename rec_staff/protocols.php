<?php
require_once '../includes/auth_check.php';
checkAuth(['rec_staff', 'rec_chair', 'rec_secretary']);
require_once '../config/database.php';
include '../includes/header.php';

// Fetch all protocols in the system for Staff management
$stmt = $pdo->query("SELECT * FROM protocols ORDER BY created_at DESC");
$protocols = $stmt->fetchAll();
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Manage Submissions";
        $workspaceSubtitle = "View and manage all research submissions";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4 p-md-5">

            <div class="row mb-5 align-items-end animate-up">
                <div class="col">
                    <h6 class="text-gold fw-bold text-uppercase small mb-2" style="letter-spacing:2px;">Registry</h6>
                    <h2 class="fw-bold text-navy mb-0">Research Registry</h2>
                    <p class="text-muted mb-0">Monitor and manage all research ethics files in the system.</p>
                </div>
                <?php if($_SESSION['role'] == 'rec_staff'): ?>
                <div class="col-auto">
                    <a href="add_protocol" class="btn btn-navy shadow-sm">
                        <i class="fas fa-plus-circle me-2"></i> New Submission
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Official 8-Step Workflow Legend -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 animate-up" style="animation-delay:0.05s; background: #fdfdfd;">
                <div class="mb-3">
                    <small class="text-navy fw-bold text-uppercase" style="font-size:0.75rem; letter-spacing:2px;">
                        <i class="fas fa-route me-2"></i> Review Pipeline
                    </small>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-soft-info text-info rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-file-arrow-up me-1"></i> 1. Submitting
                    </span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:0.6rem;"></i>
                    <span class="badge bg-soft-secondary text-secondary rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-clipboard-list me-1"></i> 2. Staff Check
                    </span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:0.6rem;"></i>
                    <span class="badge bg-soft-primary text-primary rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-user-tie me-1"></i> 3. REC Chair Review
                    </span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:0.6rem;"></i>
                    <span class="badge bg-soft-navy text-navy rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-users-cog me-1"></i> 4. Assignment
                    </span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:0.6rem;"></i>
                    <span class="badge bg-soft-gold text-gold rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-microscope me-1"></i> 5. Peer Review
                    </span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:0.6rem;"></i>
                    <span class="badge bg-soft-warning text-warning rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-rotate-right me-1"></i> 6. Resubmission
                    </span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:0.6rem;"></i>
                    <span class="badge bg-soft-navy text-navy rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-gavel me-1"></i> 7. Decision
                    </span>
                    <i class="fas fa-chevron-right text-muted" style="font-size:0.6rem;"></i>
                    <span class="badge bg-soft-success text-success rounded-pill px-3 py-2 border fw-semibold">
                        <i class="fas fa-certificate me-1"></i> 8. Release
                    </span>
                </div>
            </div>


            <div class="card border-0 shadow-sm animate-up" style="animation-delay:0.1s">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>REC Code</th>
                                    <th>Protocol Title</th>
                                    <th>Lead Researcher</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($protocols) > 0): ?>
                                    <?php foreach ($protocols as $protocol): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-navy">
                                                <i class="fas fa-barcode me-2 opacity-25"></i>
                                                <?php 
                                                if(empty($protocol['rec_code']) || strpos($protocol['rec_code'] ?? '', 'PENDING') !== false || strpos($protocol['rec_code'] ?? '', 'ATC') !== false) {
                                                    echo '<span class="text-muted small"><i>PENDING ASSIGNMENT</i></span>';
                                                } else {
                                                    echo htmlspecialchars($protocol['rec_code']); 
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-navy text-truncate" style="max-width:320px;"
                                                    title="<?php echo htmlspecialchars($protocol['title']); ?>">
                                                    <?php echo htmlspecialchars($protocol['title']); ?>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    <span class="badge text-uppercase"
                                                        style="font-size:0.6rem; border:1px solid #cbd5e1; color:#64748b;">
                                                        <?php 
                                                        $rtMap = ['pending'=>'PENDING REVIEW TYPE', 'exempt'=>'EXEMPTED FROM REVIEW', 'expedited'=>'EXPEDITED REVIEW', 'full_board'=>'FULL REVIEW'];
                                                        echo $rtMap[$protocol['review_type']] ?? strtoupper(str_replace('_', ' ', $protocol['review_type'])); 
                                                        ?>
                                                    </span>
                                                    &bull; <?php echo date('M d, Y', strtotime($protocol['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small fw-bold text-navy">
                                                    <i
                                                        class="fas fa-user-tie me-2 opacity-50"></i><?php echo htmlspecialchars($protocol['project_leader']); ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $statusMap = [
                                                    'submitted' => ['bg-info-light', '📬 Submitted'],
                                                    'staff_review' => ['bg-secondary-light text-secondary', '� Staff Review'],
                                                    'needs_revision' => ['bg-danger-light', '↩ Needs Revision'],
                                                    'initial_review' => ['bg-primary-light', '⚖️ Initial Review'],
                                                    'confirmed' => ['bg-success-light text-success', '✅ REC Chair Confirmed'],
                                                    'assigned' => ['bg-info text-white', '👥 Assigned'],
                                                    'under_review' => ['bg-warning-light', '🔍 Under Review'],
                                                    'revised' => ['bg-info-light', '🔄 Revised'],
                                                    'approved' => ['bg-success-light', '✅ Approved'],
                                                    'rejected' => ['bg-danger text-white', '❌ Rejected'],
                                                    'clearance_released' => ['bg-success text-white', '🎉 Clearance Released']
                                                ];
                                                [$cls, $label] = $statusMap[$protocol['status']] ?? ['bg-secondary text-white', strtoupper(str_replace('_', ' ', $protocol['status']))];
                                                ?>
                                                <span
                                                    class="badge <?php echo $cls; ?> rounded-pill"><?php echo $label; ?></span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <!-- Always: View -->
                                                    <a href="../shared_view?id=<?php echo $protocol['protocol_id']; ?>"
                                                        class="btn btn-sm btn-light rounded-pill px-3 shadow-sm"
                                                        title="View Details">
                                                        <i class="fas fa-eye text-primary"></i>
                                                    </a>

                                                    <!-- REC Form 13 Checklist (Staff/REC Chair) - Always available -->
                                                    <?php if ($_SESSION['role'] == 'rec_staff' || $_SESSION['role'] == 'rec_chair'): ?>
                                                        <a href="../forms/form13_checklist?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-light rounded-pill px-3 shadow-sm"
                                                            target="_blank"
                                                            title="REC Form 13 Checklist">
                                                            <i class="fas fa-list-check text-navy"></i> <span class="d-none d-xl-inline">13</span>
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- EXEMPT Lane Forms -->
                                                    <?php if ($protocol['review_type'] == 'exempt' && in_array($protocol['status'], ['approved', 'clearance_released'])): ?>
                                                        <a href="../forms/form14a_exemption?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm"
                                                            target="_blank"
                                                            title="REC Form 14a Exemption Certificate">
                                                            <i class="fas fa-certificate"></i> 14a
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- EXPEDITED/FULL Lane Forms -->
                                                    <?php if ($protocol['review_type'] != 'exempt' && in_array($protocol['status'], ['approved', 'clearance_released'])): ?>
                                                        <a href="../forms/form9_expedited_evaluation?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                                                            target="_blank"
                                                            title="REC Form 09 Evaluation Sheet">
                                                            <i class="fas fa-file-invoice"></i> 09
                                                        </a>
                                                        <a href="../forms/form16_approval_letter?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-sm"
                                                            target="_blank"
                                                            title="REC Form 16 Approval Letter">
                                                            <i class="fas fa-envelope-open-text"></i> 16
                                                        </a>
                                                        <a href="../forms/form25_clearance?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm"
                                                            target="_blank"
                                                            title="REC Form 25 Ethical Clearance">
                                                            <i class="fas fa-award"></i> 25
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- Edit: only if staff and still submitted, staff_review or needs revision -->
                                                    <?php if ($_SESSION['role'] == 'rec_staff' && in_array($protocol['status'], ['submitted', 'staff_review', 'needs_revision'])): ?>
                                                        <a href="edit_protocol?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-light rounded-pill px-3 shadow-sm" title="Edit">
                                                              <i class="fas fa-pen-nib text-warning"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                     <!-- Update Status: only if staff or chair -->
                                                     <?php if ($_SESSION['role'] != 'rec_secretary' && $protocol['status'] !== 'confirmed'): ?>
                                                         <?php if (in_array($protocol['status'], ['assigned', 'under_review']) && $_SESSION['role'] == 'rec_staff'): ?>
                                                            <a href="update_status?id=<?php echo $protocol['protocol_id']; ?>"
                                                                class="btn btn-sm btn-navy rounded-pill px-3 shadow-sm" title="Set/Update Deadline">
                                                                <i class="fas fa-calendar-day"></i> <span class="d-none d-xl-inline">Deadline</span>
                                                            </a>
                                                         <?php endif; ?>
                                                         
                                                         <a href="update_status?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-info rounded-pill px-3 shadow-sm text-white" title="Update Status">
                                                            <i class="fas fa-check-circle"></i>
                                                         </a>
                                                     <?php endif; ?>

                                                    <!-- Special: Release Clearance if Approved -->
                                                      <?php if ($_SESSION['role'] == 'rec_staff' && $protocol['status'] == 'approved'): ?>
                                                          <a href="update_status?id=<?php echo $protocol['protocol_id']; ?>&action=release_clearance"
                                                              class="btn btn-sm text-white rounded-pill px-3 shadow-sm fw-bold" 
                                                              style="background: #10b981; border: none; font-size: 0.8rem;"
                                                              onclick="event.preventDefault(); const url = this.href; Swal.fire({title: 'Release Ethical Clearance?', html: 'You are about to officially release the **Ethical Clearance Certificate (REC Form 25)** for this protocol.<br><br><small class=\'text-muted\'>This will complete the Clearance milestone and notify the researcher.</small>', icon: 'question', showCancelButton: true, confirmButtonColor: '#10b981', cancelButtonColor: '#64748b', confirmButtonText: '<i class=\'fas fa-paper-plane me-2\'></i> Yes, Release!', cancelButtonText: 'Cancel'}).then((result) => { if (result.isConfirmed) { window.location.href = url; } })"
                                                              title="Release Official REC Form 25">
                                                              <i class="fas fa-paper-plane me-1"></i> Release
                                                          </a>
                                                      <?php endif; ?>

                                                     <!-- Special: Generate REC Code if confirmed and staff -->
                                                    <?php if ($_SESSION['role'] == 'rec_staff' && $protocol['status'] == 'confirmed' && (empty($protocol['rec_code']) || strpos($protocol['rec_code'], 'PENDING') !== false)): ?>
                                                        <a href="update_status?id=<?php echo $protocol['protocol_id']; ?>&action=generate_code"
                                                            class="btn btn-sm btn-warning rounded-pill px-3 shadow-sm text-navy fw-bold" title="Generate Official REC Code">
                                                            <i class="fas fa-barcode"></i> Code
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- Print: only when approved or clearance released -->
                                                    <?php if (in_array($protocol['status'], ['approved', 'clearance_released'])): ?>
                                                        <a href="../forms/generate_print?id=<?php echo $protocol['protocol_id']; ?>"
                                                            class="btn btn-sm btn-navy rounded-pill px-3 shadow-sm"
                                                            target="_blank"
                                                            title="Print REC Forms">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="opacity-10 mb-3"><i class="fas fa-folder-open fa-4x text-navy"></i>
                                            </div>
                                            <h5 class="text-muted fw-bold">No Protocols Yet</h5>
                                            <p class="text-muted small">Click <strong>New Submission</strong> to start your
                                                first protocol submission.</p>
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
