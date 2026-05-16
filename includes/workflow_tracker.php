<?php
/**
 * DNSC REC Detailed Workflow Tracker — Professional Edition
 * Provides high-fidelity tracking of the 8-step institutional review process.
 */

$status = (isset($protocol) && isset($protocol['status'])) ? $protocol['status'] : 'submitted';
$protocol_id = (isset($protocol) && isset($protocol['protocol_id'])) ? $protocol['protocol_id'] : null;

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

// 8-Step Lifecycle Definition (Section III: Review and Approval Workflow)
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
        // Only skip for Exempt/Pending if they haven't needed a revision
        'skip' => in_array($protocol['review_type'] ?? '', ['exempt', 'pending']) && !in_array($status, ['needs_revision', 'revised', 'approved']),
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
        'done' => ($status === 'clearance_released'),
        'active' => ($status === 'approved'),
    ],
];

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
