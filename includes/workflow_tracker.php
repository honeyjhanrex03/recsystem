<?php
/**
 * DNSC REC Detailed Workflow Tracker — Professional Edition
 * Provides high-fidelity tracking of the 8-step institutional review process.
 */

$status = (isset($protocol) && isset($protocol['status'])) ? $protocol['status'] : 'submitted';
$protocol_id = (isset($protocol) && isset($protocol['protocol_id'])) ? $protocol['protocol_id'] : null;

// Handle Acknowledge Receipt post action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'acknowledge_clearance') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $sess_user_id = $_SESSION['user_id'] ?? null;
    $sess_user_role = $_SESSION['role'] ?? null;
    
    if ($sess_user_role === 'author' && !empty($protocol_id)) {
        // Fetch Lead Researcher Name
        $stmtP = $pdo->prepare("SELECT project_leader FROM protocols WHERE protocol_id = ?");
        $stmtP->execute([$protocol_id]);
        $proto_leader = $stmtP->fetchColumn() ?: 'Researcher';
        
        $actionMsg = "Lead Researcher " . $proto_leader . " officially acknowledged receipt of Ethical Clearance (Form 25) and Approval Letter (Form 16) and confirmed all signatures.";
        
        $stmtL = $pdo->prepare("INSERT INTO audit_logs (user_id, protocol_id, action, timestamp) VALUES (?, ?, ?, NOW())");
        $stmtL->execute([$sess_user_id, $protocol_id, $actionMsg]);
        
        // Auto-update status to 'clearance_released' to mark the process complete
        $stmtU = $pdo->prepare("UPDATE protocols SET status = 'clearance_released' WHERE protocol_id = ?");
        $stmtU->execute([$protocol_id]);
        
        // Refresh page to apply changes
        echo "<script>window.location.href = window.location.href;</script>";
        exit();
    }
}

// Check if Acknowledged
$isAcknowledged = false;
$has18 = false;
$has19 = false;
if (!empty($protocol_id)) {
    $stmtAck = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE protocol_id = ? AND action LIKE '%officially acknowledged receipt of Ethical Clearance%'");
    $stmtAck->execute([$protocol_id]);
    $isAcknowledged = ($stmtAck->fetchColumn() > 0);

    $stmtChk18 = $pdo->prepare("SELECT COUNT(*) FROM form18a_responses WHERE protocol_id = ?");
    $stmtChk18->execute([$protocol_id]);
    $has18 = ($stmtChk18->fetchColumn() > 0);

    $stmtChk19 = $pdo->prepare("SELECT COUNT(*) FROM form19_responses WHERE protocol_id = ?");
    $stmtChk19->execute([$protocol_id]);
    $has19 = ($stmtChk19->fetchColumn() > 0);
}

// Reviewer Completion Logic
$totalReviewers = 0;
$doneReviewers = 0;
$reviewerStates = [];
if (isset($protocol_id)) {
    $stmtW = $pdo->prepare("SELECT u.name, a.status FROM reviewer_assignments a JOIN admins u ON a.reviewer_id = u.admin_id WHERE a.protocol_id = ?");
    $stmtW->execute([$protocol_id]);
    $res = $stmtW->fetchAll();
    $totalReviewers = count($res);
    foreach($res as $r) {
        if($r['status'] == 'completed') $doneReviewers++;
        $reviewerStates[] = $r;
    }
}

// Dynamic Review-Type Lifecycle Definition (Section III: Review and Approval Workflow)
$review_type = isset($protocol['review_type']) ? $protocol['review_type'] : 'pending';

if ($review_type === 'exempt') {
    // Exempt Review: Simple direct track (No reviewer assignment, no peer evaluation, no progress reports)
    $steps = [
        [
            'label' => 'Submission',
            'sublabel' => 'Committee Protocol Dossier',
            'icon' => 'fa-file-import',
            'done' => ($status !== 'submitted'),
            'active' => ($status === 'submitted'),
        ],
        [
            'label' => 'Initial Check',
            'sublabel' => 'REC Staff Screening',
            'icon' => 'fa-clipboard-check',
            'done' => !in_array($status, ['submitted', 'staff_review']),
            'active' => ($status === 'staff_review'),
        ],
        [
            'label' => 'Initial Review',
            'sublabel' => 'REC Chair Assessment',
            'icon' => 'fa-user-tie',
            'done' => !in_array($status, ['submitted', 'staff_review', 'initial_review']),
            'active' => ($status === 'initial_review'),
        ],
        [
            'label' => 'Final Decision',
            'sublabel' => 'Chair Exemption Approval',
            'icon' => 'fa-gavel',
            'done' => in_array($status, ['approved', 'clearance_released']),
            'active' => in_array($status, ['revised', 'for_decision', 'completed']) && !in_array($status, ['needs_revision', 'under_review']),
        ],
        [
            'label' => 'Clearance',
            'sublabel' => 'Certificate of Exemption',
            'icon' => 'fa-certificate',
            'done' => in_array($status, ['approved', 'clearance_released']),
            'active' => false,
        ],
    ];
} else {
    // Expedited & Full Board Review: Comprehensive 10-stage evaluation & post-approval monitoring pipeline
    $steps = [
        [
            'label' => 'Submission',
            'sublabel' => 'Committee Protocol Dossier',
            'icon' => 'fa-file-import',
            'done' => ($status !== 'submitted'),
            'active' => ($status === 'submitted'),
        ],
        [
            'label' => 'Initial Check',
            'sublabel' => 'REC Staff Screening',
            'icon' => 'fa-clipboard-check',
            'done' => !in_array($status, ['submitted', 'staff_review']),
            'active' => ($status === 'staff_review'),
        ],
        [
            'label' => 'Initial Review',
            'sublabel' => 'REC Chair Assessment',
            'icon' => 'fa-user-tie',
            'done' => !in_array($status, ['submitted', 'staff_review', 'initial_review']),
            'active' => ($status === 'initial_review'),
        ],
        [
            'label' => 'Assignment',
            'sublabel' => 'Reviewer Panel Selection',
            'icon' => 'fa-users-cog',
            'done' => !in_array($status, ['submitted', 'staff_review', 'initial_review', 'confirmed']),
            'active' => ($status === 'confirmed'),
        ],
        [
            'label' => 'Peer Review',
            'sublabel' => $totalReviewers > 0 ? "$doneReviewers / $totalReviewers Evaluations Done" : 'Technical Evaluation',
            'icon' => 'fa-magnifying-glass-chart',
            'done' => in_array($status, ['under_review', 'needs_revision', 'revised', 'approved', 'clearance_released']),
            'active' => in_array($status, ['assigned']),
        ],
        [
            'label' => 'Revision Cycle',
            'sublabel' => ($status === 'revised' || $status === 'approved') ? 'Revised Dossier Received' : 'Mandatory Revision Process',
            'icon' => 'fa-rotate-right',
            'done' => in_array($status, ['revised', 'approved', 'clearance_released']),
            'active' => in_array($status, ['under_review', 'needs_revision']),
        ],
        [
            'label' => 'Final Decision',
            'sublabel' => 'Board Final Assessment',
            'icon' => 'fa-gavel',
            'done' => in_array($status, ['approved', 'clearance_released']),
            'active' => in_array($status, ['revised', 'for_decision', 'completed']) && !in_array($status, ['needs_revision', 'under_review']),
        ],
        [
            'label' => 'Clearance',
            'sublabel' => 'Ethical Clearance & ID',
            'icon' => 'fa-certificate',
            'done' => in_array($status, ['approved', 'clearance_released']),
            'active' => false,
        ],
        [
            'label' => 'Monitoring',
            'sublabel' => 'Progress Report (F18a)',
            'icon' => 'fa-chart-line',
            'done' => $has18,
            'active' => in_array($status, ['approved', 'clearance_released']) && !$has18,
        ],
        [
            'label' => 'Completion',
            'sublabel' => 'Final Report (F19)',
            'icon' => 'fa-check-double',
            'done' => $has19,
            'active' => in_array($status, ['approved', 'clearance_released']) && $has18 && !$has19,
        ]
    ];
}

// Fetch Audit Timeline for the "Detailed" view
$stmtL = $pdo->prepare("SELECT * FROM audit_logs WHERE protocol_id = ? ORDER BY timestamp DESC LIMIT 5");
$stmtL->execute([$protocol_id]);
$logs = $stmtL->fetchAll();
?>

<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-header bg-white py-4 px-4 border-bottom d-flex align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-route me-2"></i> Review & Approval Pipeline</h5>
            <p class="text-muted small mb-0">Step-by-step institutional progress monitoring</p>
        </div>
        <span class="badge ms-auto bg-navy text-white rounded-pill px-3 py-2" style="font-size: 0.7rem;">
            CURRENT: <?php echo strtoupper(str_replace('_', ' ', $status)); ?>
        </span>
    </div>
    <div class="card-body p-4 p-md-5">
        <div class="workflow-scroll-wrapper mb-5">
            <div class="d-flex align-items-start position-relative workflow-steps">
                <?php foreach ($steps as $i => $step): ?>
                    <?php if (!empty($step['skip'])): ?><?php continue; ?><?php endif; ?>
                    <?php 
                        $stateClass = $step['done'] ? 'done' : ($step['active'] ? 'active' : 'pending');
                        if (!empty($step['warning']) && $step['done']) $stateClass = 'warning-done';
                    ?>
                    <div class="workflow-step flex-fill text-center position-relative <?php echo $stateClass; ?>">
                        <?php if ($i < count($steps) - 1): ?>
                            <div class="workflow-line <?php echo $step['done'] ? 'done' : ''; ?>"></div>
                        <?php endif; ?>

                        <div class="workflow-bubble mx-auto mb-3">
                            <?php if ($step['done']): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas <?php echo $step['icon']; ?>"></i>
                            <?php endif; ?>
                        </div>

                        <div class="workflow-content">
                            <div class="workflow-label fw-bold mb-1">
                                <?php echo $step['label']; ?>
                            </div>
                            <div class="workflow-sublabel text-muted px-2">
                                <?php echo $step['sublabel']; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Released Official Documents Panel for Approved/Released protocols -->
        <?php if (in_array($status, ['approved', 'clearance_released'])): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 5px solid #22c55e !important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success text-white rounded-3 me-3" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fas fa-file-shield"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-success mb-1">Official Certificates & Letters Issued</h6>
                        <p class="text-muted small mb-0">Your protocol has officially been approved by the REC Board. You can view, download, and print your documents below.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <a href="<?php echo BASE_URL; ?>forms/form25_clearance.php?id=<?php echo $protocol_id; ?>&public=1" target="_blank" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fas fa-award"></i> Print Clearance (F25)
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="<?php echo BASE_URL; ?>forms/form16_approval_letter.php?id=<?php echo $protocol_id; ?>&public=1" target="_blank" class="btn btn-outline-success bg-white w-100 py-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fas fa-envelope-open-text"></i> Approval Letter (F16)
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="d-flex flex-column gap-2">
                            <a href="<?php echo BASE_URL; ?>forms/form18a_progress_report.php?id=<?php echo $protocol_id; ?>&public=1" target="_blank" class="btn btn-navy text-white w-100 py-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background:#1a2b4b;">
                                <i class="fas fa-chart-line"></i> Print Progress (F18a) <?php echo $has18 ? '✅' : '⚠️'; ?>
                            </a>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'author'): ?>
                                <a href="<?php echo BASE_URL; ?>author/submit_report.php?id=<?php echo $protocol_id; ?>&type=progress" class="btn btn-sm btn-outline-navy rounded-pill fw-bold" style="color:#1a2b4b; border-color:#1a2b4b;">
                                    <i class="fas <?php echo $has18 ? 'fa-edit' : 'fa-plus'; ?> me-1"></i> <?php echo $has18 ? 'Edit Progress Data' : 'Submit Progress Data'; ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-navy text-white rounded-pill p-2 text-center" style="font-size:0.75rem;">
                                    <?php echo $has18 ? 'Data Submitted' : 'No Data Submitted'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="d-flex flex-column gap-2">
                            <a href="<?php echo BASE_URL; ?>forms/form19_final_report.php?id=<?php echo $protocol_id; ?>&public=1" target="_blank" class="btn btn-outline-navy bg-white w-100 py-3 rounded-pill fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="color:#1a2b4b; border-color:#1a2b4b;">
                                <i class="fas fa-check-double"></i> Print Final (F19) <?php echo $has19 ? '✅' : '⚠️'; ?>
                            </a>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'author'): ?>
                                <a href="<?php echo BASE_URL; ?>author/submit_report.php?id=<?php echo $protocol_id; ?>&type=final" class="btn btn-sm btn-navy text-white rounded-pill fw-bold" style="background:#1a2b4b;">
                                    <i class="fas <?php echo $has19 ? 'fa-edit' : 'fa-plus'; ?> me-1"></i> <?php echo $has19 ? 'Edit Final Data' : 'Submit Final Data'; ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-secondary text-white rounded-pill p-2 text-center" style="font-size:0.75rem;">
                                    <?php echo $has19 ? 'Data Submitted' : 'No Data Submitted'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Digital Acknowledgment Registry -->
                <?php if ($isAcknowledged): ?>
                    <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 text-success small fw-bold" style="border-top-color: rgba(34, 197, 94, 0.2) !important;">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 0.75rem;">
                            <i class="fas fa-check"></i>
                        </div>
                        <span>Clearance Receipt Digitally Acknowledged & Signed by Researcher in Registry</span>
                    </div>
                <?php else: ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'author'): ?>
                        <div class="mt-4 pt-3 border-top d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 bg-white p-3 rounded-4 shadow-sm" style="border: 1px solid #fed7aa !important;">
                            <div class="d-flex align-items-center">
                                <div class="text-warning me-3" style="font-size: 1.5rem;"><i class="fas fa-exclamation-triangle animate-bounce"></i></div>
                                <div>
                                    <strong class="text-navy d-block">⚠️ Action Required: Confirm & Acknowledge Receipt</strong>
                                    <span class="text-muted small">You are required to officially confirm receipt of these documents for your registry. This action acts as your secure digital acknowledgment signature.</span>
                                </div>
                            </div>
                            <div>
                                <form method="POST" action="" class="m-0">
                                    <input type="hidden" name="action" value="acknowledge_clearance">
                                    <button type="submit" class="btn btn-warning px-4 py-2.5 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2" style="background: #f59e0b; color: #fff; border: none; font-size: 0.9rem;">
                                        <i class="fas fa-signature"></i> Confirm Receipt & Sign
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 text-warning small fw-bold" style="border-top-color: rgba(245, 158, 11, 0.2) !important;">
                            <div class="spinner-border spinner-border-sm text-warning" role="status" style="width: 16px; height: 16px;"></div>
                            <span>Awaiting Researcher's Digital Receipt Acknowledgment & Signature</span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity History -->
        <?php if (!empty($logs)): ?>
        <div class="bg-light rounded-4 p-4 mt-2">
            <h6 class="fw-bold text-navy mb-3"><i class="fas fa-history me-2"></i> Activity Log</h6>
            <div class="timeline-simple">
                <?php foreach($logs as $log): ?>
                    <div class="d-flex gap-3 mb-3">
                        <div class="text-muted small" style="width: 100px;"><?php echo date('M d, H:i', strtotime($log['timestamp'])); ?></div>
                        <div class="flex-grow-1 small text-navy">
                            <?php echo htmlspecialchars($log['action']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .workflow-scroll-wrapper {
        overflow-x: auto;
        padding-bottom: 10px;
    }

    .workflow-steps {
        min-width: 600px; /* Ensure labels don't squish on desktop/tablet */
    }

    .workflow-bubble {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        border: 2px solid #e2e8f0;
        background: #f8fafc;
        color: #94a3b8;
        position: relative;
        z-index: 2;
        transition: all 0.3s;
    }

    .workflow-step.done .workflow-bubble {
        background: #1a2b4b;
        border-color: #1a2b4b;
        color: #fff;
    }

    .workflow-step.warning-done .workflow-bubble {
        background: #f59e0b;
        border-color: #f59e0b;
        color: #fff;
    }

    .workflow-step.active .workflow-bubble {
        background: #f1c40f;
        border-color: #f1c40f;
        color: #1a2b4b;
        box-shadow: 0 0 0 5px rgba(241, 196, 15, 0.2);
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 5px rgba(241, 196, 15, 0.2); }
        50% { box-shadow: 0 0 0 10px rgba(241, 196, 15, 0.08); }
    }

    .workflow-line {
        position: absolute;
        top: 22px;
        left: calc(50% + 22px);
        width: calc(100% - 44px);
        height: 2px;
        background: #e2e8f0;
        z-index: 1;
    }

    .workflow-line.done {
        background: #1a2b4b;
    }

    .workflow-label {
        font-size: 0.85rem;
        color: #1a2b4b;
    }

    .workflow-sublabel {
        font-size: 0.7rem;
        line-height: 1.3;
    }

    /* Mobile Responsive Logic */
    @media (max-width: 768px) {
        .workflow-scroll-wrapper {
            overflow-x: visible;
        }
        .workflow-steps {
            min-width: 0;
            flex-direction: column;
            gap: 20px;
        }
        .workflow-step {
            display: flex;
            align-items: center;
            text-align: left !important;
            width: 100%;
        }
        .workflow-content {
            margin-left: 15px;
        }
        .workflow-bubble {
            margin: 0 !important;
            flex-shrink: 0;
        }
        .workflow-line {
            left: 21px !important;
            top: 44px !important;
            width: 2px !important;
            height: 20px !important;
        }
    }
</style>
