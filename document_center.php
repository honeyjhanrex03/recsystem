<?php
require_once 'includes/auth_check.php';
checkAuth(['admin', 'rec_staff', 'rec_chair', 'rec_member', 'rec_secretary', 'author']);
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch protocols based on user role
if (in_array($user_role, ['admin', 'rec_staff', 'rec_chair', 'rec_secretary'])) {
    $stmt = $pdo->query("SELECT protocol_id, title, rec_code, status FROM protocols ORDER BY created_at DESC");
    $protocols = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT protocol_id, title, rec_code, status FROM protocols WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $protocols = $stmt->fetchAll();
}

// Check if a specific protocol ID is requested or default to the most recent one
$selected_id = $_GET['protocol_id'] ?? '';
if (empty($selected_id) && !empty($protocols)) {
    $selected_id = $protocols[0]['protocol_id'];
}

// Fetch selected protocol details
$current_protocol = null;
if (!empty($selected_id)) {
    $stmt = $pdo->prepare("SELECT * FROM protocols WHERE protocol_id = ?");
    $stmt->execute([$selected_id]);
    $current_protocol = $stmt->fetch();
}

include 'includes/header.php';
?>

<div id="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "REC Forms & Documents Center";
        $workspaceSubtitle = "Generate and download official Research Ethics Committee printable forms";
        include 'includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4 p-md-5">

            <!-- Hero Section -->
            <div class="row mb-4 align-items-center animate-up">
                <div class="col-lg-8">
                    <h2 class="fw-bold text-navy mb-1"><i class="fas fa-file-invoice-dollar me-2 text-gold"></i> REC Document Center</h2>
                    <p class="text-muted mb-0">Select an active research protocol to dynamically generate its corresponding official forms and certificates.</p>
                </div>
            </div>

            <!-- Protocol Selector Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 animate-up" style="animation-delay: 0.05s;">
                <div class="card-body p-4 bg-white">
                    <form method="GET" action="document_center" id="protocolForm" class="row align-items-center g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-navy text-uppercase" style="letter-spacing: 1px;">Active Research Protocol</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-folder text-warning"></i></span>
                                <select name="protocol_id" class="form-select bg-light border-0 py-3" onchange="document.getElementById('protocolForm').submit();" style="font-weight: 500;">
                                    <?php if (empty($protocols)): ?>
                                        <option value="">No protocols submitted yet</option>
                                    <?php else: ?>
                                        <?php foreach ($protocols as $proto): ?>
                                            <option value="<?php echo $proto['protocol_id']; ?>" <?php echo ($selected_id == $proto['protocol_id']) ? 'selected' : ''; ?>>
                                                [<?php echo htmlspecialchars($proto['rec_code'] ?: 'PENDING'); ?>] <?php echo htmlspecialchars(mb_strimwidth($proto['title'], 0, 80, "...")); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <?php if ($current_protocol): ?>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-navy text-uppercase" style="letter-spacing: 1px;">Current Protocol Status</label>
                                <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                    <span class="small fw-bold text-muted">Status:</span>
                                    <?php
                                    $statusMap = [
                                        'submitted' => ['bg-soft-navy text-navy', '📥 Submitted'],
                                        'staff_review' => ['bg-soft-warning text-warning', '🔍 Staff Screening'],
                                        'needs_revision' => ['bg-soft-danger text-danger', '✏️ Needs Revision'],
                                        'initial_review' => ['bg-soft-primary text-primary', '🧑‍💼 Initial Review'],
                                        'confirmed' => ['bg-soft-info text-info', '📋 Confirmed'],
                                        'assigned' => ['bg-soft-secondary text-secondary', '👤 Reviewers Assigned'],
                                        'under_review' => ['bg-soft-warning text-warning', '🔬 Under Review'],
                                        'revised' => ['bg-soft-info text-info', '🔄 Revised'],
                                        'approved' => ['bg-soft-success text-success', '✔️ Approved'],
                                        'rejected' => ['bg-soft-danger text-danger', '❌ Rejected'],
                                        'clearance_released' => ['bg-success text-white', '🎉 Clearance Released']
                                    ];
                                    $statusInfo = $statusMap[$current_protocol['status']] ?? ['bg-light text-dark', $current_protocol['status']];
                                    ?>
                                    <span class="badge <?php echo $statusInfo[0]; ?> rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;">
                                        <?php echo $statusInfo[1]; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (!$current_protocol): ?>
                <!-- No Protocols State -->
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center animate-up" style="animation-delay: 0.1s;">
                    <div class="mx-auto mb-4 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-file-excel fa-3x text-muted opacity-55"></i>
                    </div>
                    <h4 class="fw-bold text-navy">No Research Submission Selected</h4>
                    <p class="text-muted mx-auto" style="max-width: 500px;">You must have an active or submitted research project to generate official review forms, certificates, and clearances.</p>
                    <a href="<?php echo BASE_URL; ?>author/submit" class="btn btn-navy px-4 py-2 rounded-pill mt-3"><i class="fas fa-plus me-2"></i> Submit a Protocol</a>
                </div>
            <?php else: ?>
                <!-- Interactive Form Flow Roadmap Guide -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-soft-navy text-navy animate-up" style="animation-delay: 0.08s;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#roadmapCollapse" aria-expanded="false" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-navy text-white rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                                    <i class="fas fa-map-signs text-gold"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-navy mb-0">📖 5-Stage Official REC Form Flow & Roadmap</h5>
                                    <small class="text-muted">Unsure when each form is used? Click to view the official step-by-step document lifecycle roadmap.</small>
                                </div>
                            </div>
                            <span class="text-navy"><i class="fas fa-chevron-down fa-lg"></i></span>
                        </div>
                        
                        <div class="collapse mt-4" id="roadmapCollapse">
                            <div class="border-top pt-4">
                                <div class="row g-4 justify-content-center">
                                    
                                    <!-- Step 1 -->
                                    <div class="col-md-2 text-center position-relative">
                                        <div class="mx-auto rounded-circle bg-navy text-gold fw-bold d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 50px; height: 50px; border: 3px solid #fff;">1</div>
                                        <h6 class="fw-bold text-navy mb-1" style="font-size: 0.9rem;">Initial Intake</h6>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;">Staff screening checklist for proposals.<br><span class="badge bg-soft-navy text-navy mt-1">REC Form 13</span></p>
                                    </div>
                                    
                                    <!-- Step 2 -->
                                    <div class="col-md-2 text-center position-relative">
                                        <div class="mx-auto rounded-circle bg-navy text-gold fw-bold d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 50px; height: 50px; border: 3px solid #fff;">2</div>
                                        <h6 class="fw-bold text-navy mb-1" style="font-size: 0.9rem;">Ethical Review</h6>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;">Reviewers evaluate scientific & ethical safeguards.<br><span class="badge bg-soft-primary text-primary mt-1">Form 9, 10, 12</span></p>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="col-md-2 text-center position-relative">
                                        <div class="mx-auto rounded-circle bg-navy text-gold fw-bold d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 50px; height: 50px; border: 3px solid #fff;">3</div>
                                        <h6 class="fw-bold text-navy mb-1" style="font-size: 0.9rem;">Revisions</h6>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;">Researcher compliance matrices for amendments.<br><span class="badge bg-soft-danger text-danger mt-1">REC Form 15</span></p>
                                    </div>

                                    <!-- Step 4 -->
                                    <div class="col-md-3 text-center position-relative">
                                        <div class="mx-auto rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 50px; height: 50px; border: 3px solid #fff;"><i class="fas fa-check"></i></div>
                                        <h6 class="fw-bold text-navy mb-1" style="font-size: 0.9rem;">Approval & Clearance</h6>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;">Official permission letters and certificates are released.<br><span class="badge bg-soft-success text-success mt-1">Form 14a, 16, 25</span></p>
                                    </div>

                                    <!-- Step 5 -->
                                    <div class="col-md-2 text-center position-relative">
                                        <div class="mx-auto rounded-circle bg-navy text-gold fw-bold d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 50px; height: 50px; border: 3px solid #fff;">5</div>
                                        <h6 class="fw-bold text-navy mb-1" style="font-size: 0.9rem;">Post-Approval</h6>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;">Mid-point progress and final termination reports.<br><span class="badge bg-soft-secondary text-secondary mt-1">Form 18a, 19</span></p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Categories Panel -->
                <div class="row mb-4 align-items-center animate-up" style="animation-delay: 0.1s;">
                    <div class="col-md-6">
                        <div class="input-group bg-white rounded-pill shadow-sm px-3 py-1">
                            <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="formSearch" class="form-control bg-transparent border-0 shadow-none" placeholder="Search forms by name, code or keyword...">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <div class="btn-group rounded-pill shadow-sm bg-white p-1" role="group">
                            <button type="button" class="btn btn-navy btn-sm px-3 rounded-pill filter-btn active" data-filter="all">All Forms</button>
                            <button type="button" class="btn btn-light btn-sm px-3 rounded-pill filter-btn" data-filter="eval">Evaluations</button>
                            <button type="button" class="btn btn-light btn-sm px-3 rounded-pill filter-btn" data-filter="check">Checklists</button>
                            <button type="button" class="btn btn-light btn-sm px-3 rounded-pill filter-btn" data-filter="cert">Certificates</button>
                        </div>
                    </div>
                </div>

                <!-- Forms Grid -->
                <div class="row g-4" id="formsGrid">
                    <?php
                    $active_id = $current_protocol['protocol_id'];
                    $status = $current_protocol['status'];
                    $review_type = $current_protocol['review_type'];

                    // Define forms metadata dynamically based on the review type to match physical institutional folder structures exactly
                    $forms = [];
                    
                    if ($review_type === 'exempt') {
                        // Exempt Review Forms (Simple 2-step direct track matching exemp folder)
                        $forms = [
                            [
                                'code' => 'REC Form 13',
                                'title' => 'Checklist of Submitted Documents',
                                'category' => 'check',
                                'desc' => 'Official checklist used by Staff to verify the completeness of submitted protocol dossiers and attachments.',
                                'link' => 'forms/form13_checklist.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff'],
                                'active' => true,
                                'active_desc' => 'STEP 1. STAFF_ Checklist of Submitted Documents'
                            ],
                            [
                                'code' => 'REC Form 14a',
                                'title' => 'Certificate of Exemption from Review Template',
                                'category' => 'cert',
                                'desc' => 'Official certificate issued to research protocols that are exempt from technical ethical review.',
                                'link' => 'forms/form14a_exemption.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'rec_secretary', 'author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'STEP 2. STAFF_ Certificate of Exemption from Review Template'
                            ]
                        ];
                    } elseif ($review_type === 'expedited') {
                        // Expedited Review Forms (as shown in the F: exp folder)
                        $forms = [
                            [
                                'code' => 'REC Form 13',
                                'title' => 'Checklist of Submitted Documents',
                                'category' => 'check',
                                'desc' => 'Official checklist used by Staff to verify the completeness of submitted protocol dossiers and attachments.',
                                'link' => 'forms/form13_checklist.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff'],
                                'active' => true,
                                'active_desc' => 'Step 1. STAFF — Always available for screening dossiers'
                            ],
                            [
                                'code' => 'REC Form 10',
                                'title' => 'Study Research Protocol Reviewer Worksheet',
                                'category' => 'eval',
                                'desc' => 'Official worksheet used by assigned Reviewers to evaluate scientific and ethical protocol design.',
                                'link' => 'forms/generate_print?id=' . $active_id,
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'rec_member', 'rec_secretary'],
                                'active' => in_array($status, ['assigned', 'under_review', 'completed', 'needs_revision', 'revised', 'approved', 'clearance_released']),
                                'active_desc' => 'Step 2. REVIEWERS — Active during evaluation stage'
                            ],
                            [
                                'code' => 'REC Form 12',
                                'title' => 'Informed Consent Checklist',
                                'category' => 'check',
                                'desc' => 'Official checklist used by Reviewers to evaluate participant consent disclosures and safeguards.',
                                'link' => 'forms/generate_print?id=' . $active_id,
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'rec_member', 'rec_secretary'],
                                'active' => in_array($status, ['assigned', 'under_review', 'completed', 'needs_revision', 'revised', 'approved', 'clearance_released']),
                                'active_desc' => 'Step 2.1. REVIEWERS — Active during evaluation stage'
                            ],
                            [
                                'code' => 'REC Form 15',
                                'title' => 'Resubmission Form',
                                'category' => 'check',
                                'desc' => 'Compliance matrix passed from Staff to Researcher to address board recommendations and modifications.',
                                'link' => 'forms/form15_resubmission_form.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'author'],
                                'active' => in_array($status, ['needs_revision', 'revised', 'approved', 'clearance_released']),
                                'active_desc' => 'Step 3. STAFF — Active when revision is requested'
                            ],
                            [
                                'code' => 'REC Form 9',
                                'title' => 'Evaluation Form Expedited Review',
                                'category' => 'eval',
                                'desc' => 'Official evaluation worksheet completed by the REC Chair for scientific and ethical assessment of Expedited reviews.',
                                'link' => 'forms/form9_expedited_evaluation.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair'],
                                'active' => in_array($status, ['assigned', 'under_review', 'completed', 'needs_revision', 'revised', 'approved', 'clearance_released']),
                                'active_desc' => 'Step 4. REC CHAIR — Active during evaluation stage'
                            ],
                            [
                                'code' => 'REC Form 25',
                                'title' => 'Ethical Clearance Certificate',
                                'category' => 'cert',
                                'desc' => 'Official institutional certificate generated and signed by Staff and the REC Chair.',
                                'link' => 'forms/form25_clearance.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 5. STAFF&REC CHAIR — Active upon protocol approval'
                            ],
                            [
                                'code' => 'REC Form 16',
                                'title' => 'Approval Letter to the Study Protocol',
                                'category' => 'cert',
                                'desc' => 'Official letter issued and signed by Staff and the REC Chair permitting study implementation.',
                                'link' => 'forms/form16_approval_letter.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 5.1. STAFF&REC CHAIR — Active upon protocol approval'
                            ],
                            [
                                'code' => 'REC Form 18a',
                                'title' => 'Application for Ethics Review Progress Reports',
                                'category' => 'check',
                                'desc' => 'Official mid-point reporting template to be filled out and submitted by the Researcher.',
                                'link' => 'forms/form18a_progress_report.php?id=' . $active_id . '&public=1',
                                'roles' => ['author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 5.2. RESEARCHER — Active upon protocol approval'
                            ],
                            [
                                'code' => 'REC Form 19',
                                'title' => 'Final Report Form',
                                'category' => 'check',
                                'desc' => 'Official completion report template to be completed and submitted by the Researcher.',
                                'link' => 'forms/form19_final_report.php?id=' . $active_id . '&public=1',
                                'roles' => ['author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 5.3. RESEARCHER — Active upon protocol approval'
                            ]
                        ];
                    } else {
                        // Full Board Review (as shown in the Full Board folder)
                        $forms = [
                            [
                                'code' => 'REC Form 13',
                                'title' => 'Checklist of Submitted Documents',
                                'category' => 'check',
                                'desc' => 'Official checklist used by Staff to verify the completeness of submitted protocol dossiers and attachments.',
                                'link' => 'forms/form13_checklist.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff'],
                                'active' => true,
                                'active_desc' => 'Step 1. STAFF — Always available for screening dossiers'
                            ],
                            [
                                'code' => 'REC Form 10',
                                'title' => 'Study Research Protocol Reviewer Worksheet',
                                'category' => 'eval',
                                'desc' => 'Official worksheet used by assigned Reviewers to evaluate scientific and ethical protocol design.',
                                'link' => 'forms/generate_print?id=' . $active_id,
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'rec_member', 'rec_secretary'],
                                'active' => in_array($status, ['assigned', 'under_review', 'completed', 'needs_revision', 'revised', 'approved', 'clearance_released']),
                                'active_desc' => 'Step 2. REVIEWERS — Active during evaluation stage'
                            ],
                            [
                                'code' => 'REC Form 12',
                                'title' => 'Informed Consent Checklist',
                                'category' => 'check',
                                'desc' => 'Official checklist used by Reviewers to evaluate participant consent disclosures and safeguards.',
                                'link' => 'forms/generate_print?id=' . $active_id,
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'rec_member', 'rec_secretary'],
                                'active' => in_array($status, ['assigned', 'under_review', 'completed', 'needs_revision', 'revised', 'approved', 'clearance_released']),
                                'active_desc' => 'Step 2.1. REVIEWERS — Active during evaluation stage'
                            ],
                            [
                                'code' => 'REC Form 15',
                                'title' => 'Resubmission Form',
                                'category' => 'check',
                                'desc' => 'Compliance matrix passed from Staff to Researcher to address board recommendations and modifications.',
                                'link' => 'forms/form15_resubmission_form.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'author'],
                                'active' => in_array($status, ['needs_revision', 'revised', 'approved', 'clearance_released']),
                                'active_desc' => 'Step 3. STAFF to RESEARCHER — Active when revision is requested'
                            ],
                            [
                                'code' => 'REC Form 25',
                                'title' => 'Ethical Clearance Certificate',
                                'category' => 'cert',
                                'desc' => 'Official institutional certificate generated and signed by Staff and the REC Chair.',
                                'link' => 'forms/form25_clearance.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 4. STAFF & REC CHAIR — Active upon protocol approval'
                            ],
                            [
                                'code' => 'REC Form 16',
                                'title' => 'Approval Letter to the Study Protocol',
                                'category' => 'cert',
                                'desc' => 'Official letter issued and signed by Staff and the REC Chair permitting study implementation.',
                                'link' => 'forms/form16_approval_letter.php?id=' . $active_id . '&public=1',
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 5. STAFF & REC CHAIR — Active upon protocol approval'
                            ],
                            [
                                'code' => 'REC Form 18a',
                                'title' => 'Application for Ethics Review Progress Reports',
                                'category' => 'check',
                                'desc' => 'Official mid-point reporting template to be filled out and submitted by the Researcher.',
                                'link' => 'forms/form18a_progress_report.php?id=' . $active_id . '&public=1',
                                'roles' => ['author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 6: RESEARCHER — Active upon protocol approval'
                            ],
                            [
                                'code' => 'REC Form 19',
                                'title' => 'Final Report Form',
                                'category' => 'check',
                                'desc' => 'Official completion report template to be completed and submitted by the Researcher.',
                                'link' => 'forms/form19_final_report.php?id=' . $active_id . '&public=1',
                                'roles' => ['author'],
                                'active' => in_array($status, ['approved', 'clearance_released']),
                                'active_desc' => 'Step 7: RESEARCHER — Active upon protocol approval'
                            ],
                            [
                                'code' => 'PRINT BUNDLE',
                                'title' => 'Official REC Review Dossier Pack',
                                'category' => 'cert',
                                'desc' => 'Combined compilation packet containing all evaluation sheets, reviewer forms, and certificates.',
                                'link' => 'forms/generate_print?id=' . $active_id,
                                'roles' => ['admin', 'rec_staff', 'rec_chair', 'rec_member', 'rec_secretary'],
                                'active' => in_array($status, ['completed', 'needs_revision', 'approved', 'clearance_released']),
                                'active_desc' => 'ADMIN & STAFF ONLY — Generated once evaluations are complete'
                            ]
                        ];
                    }

                    $delay = 0.15;
                    $rendered_count = 0;
                    foreach ($forms as $form):
                        // Check role permissions
                        if (!in_array($user_role, $form['roles'])) continue;
                        
                        // Hide forms completely unless they have been officially received or released
                        if (!$form['active']) continue;
                        
                        $rendered_count++;
                        if ($form['active']) {
                            $cardClass = 'border-start border-4 border-success';
                            $btnClass = 'btn-navy';
                            $statusText = '<span class="badge bg-soft-success text-success rounded-pill fw-bold"><i class="fas fa-check-circle me-1"></i> Ready to Print</span>';
                            $clickAction = "viewForm('" . BASE_URL . $form['link'] . "', '" . htmlspecialchars($form['title']) . "')";
                        } else {
                            $cardClass = 'border-start border-4 border-secondary opacity-75';
                            $btnClass = 'btn-secondary disabled';
                            $statusText = '<span class="badge bg-soft-secondary text-secondary rounded-pill fw-bold"><i class="fas fa-lock me-1"></i> Pending Phase</span>';
                            $clickAction = "Swal.fire({icon: 'info', title: 'Pending Prior Process', html: 'To unlock and populate this document, the previous review milestones must be met first.<br><br><span class=\"badge bg-navy text-white px-3 py-2\">" . htmlspecialchars($form['active_desc']) . "</span>', confirmButtonColor: '#1a2b4b'})";
                        }
                        ?>
                        <div class="col-lg-6 form-card-item animate-up" data-category="<?php echo $form['category']; ?>" style="animation-delay: <?php echo $delay; ?>s;">
                            <div class="card border-0 shadow-sm h-100 overflow-hidden <?php echo $cardClass; ?>" style="border-radius: 20px;">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="badge bg-navy text-white rounded-pill px-3 py-2 fw-bold font-monospace" style="font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($form['code']); ?>
                                            </div>
                                            <?php echo $statusText; ?>
                                        </div>
                                        <h5 class="fw-bold text-navy mb-2"><?php echo htmlspecialchars($form['title']); ?></h5>
                                        <p class="text-muted small mb-4" style="line-height: 1.5;"><?php echo htmlspecialchars($form['desc']); ?></p>
                                    </div>
                                    <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                        <span class="text-muted x-small italic"><?php echo htmlspecialchars($form['active_desc']); ?></span>
                                        <button type="button" 
                                                onclick="<?php echo $clickAction; ?>" 
                                                class="btn <?php echo $btnClass; ?> btn-sm px-4 rounded-pill fw-bold shadow-sm">
                                            <i class="fas fa-eye me-1"></i> Preview & Print
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                        $delay += 0.05;
                    endforeach; 

                    if ($rendered_count === 0) {
                        ?>
                        <div class="col-12 text-center py-5 animate-up">
                            <div class="mx-auto mb-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-file-excel fa-2x text-muted opacity-55"></i>
                            </div>
                            <h5 class="fw-bold text-navy">No Forms Active Yet</h5>
                            <p class="text-muted small mx-auto" style="max-width: 480px;">
                                Based on the current stage of this protocol (Status: <strong><?php echo htmlspecialchars($statusInfo[1] ?? $status); ?></strong>), there are no ethical worksheets, checklists, or certificates finalized or received yet. Documents will automatically appear here once they are registered in their respective workflow phases.
                            </p>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Professional Form Viewer Modal -->
<div class="modal fade" id="formViewerModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 90%; height: 95%;">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden h-100" style="background: rgba(26, 43, 75, 0.98);">
            <div class="modal-header bg-navy text-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-soft-gold rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px;">
                        <i class="fas fa-file-invoice text-gold fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="formViewerTitle">REC Form Document Viewer</h5>
                        <small class="text-gold opacity-75 fw-bold text-uppercase font-monospace" style="font-size: 0.7rem; letter-spacing: 1px;">Official Previewer Panel</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light position-relative" style="height: calc(100% - 140px);">
                <!-- Dynamic Spinner Loader -->
                <div id="viewerLoader" class="position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center gap-3 z-1">
                    <div class="spinner-border text-navy" role="status" style="width: 3rem; height: 3rem;"></div>
                    <span class="fw-bold text-navy small text-uppercase" style="letter-spacing: 1px;">Rendering Document...</span>
                </div>
                <iframe id="formIframe" src="" class="w-100 h-100 border-0 z-2 position-relative" style="display: none;"></iframe>
            </div>
            <div class="modal-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1 text-gold"></i> Use the Print option to save as PDF or print on physical paper.
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="printIframe()" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-print me-2"></i> Print Form
                    </button>
                    <a id="fullscreenBtn" href="#" target="_blank" class="btn btn-navy px-4 py-2 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-external-link-alt me-2"></i> Open Fullscreen
                    </a>
                    <button type="button" class="btn btn-light border px-4 py-2 rounded-pill fw-bold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewForm(url, title) {
    const modalTitle = document.getElementById('formViewerTitle');
    const iframe = document.getElementById('formIframe');
    const loader = document.getElementById('viewerLoader');
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    modalTitle.textContent = title;
    fullscreenBtn.href = url;
    
    // Reset state and show loader
    iframe.style.display = 'none';
    loader.style.display = 'flex';
    
    // Set src
    iframe.src = url;

    // Show modal
    const viewerModal = new bootstrap.Modal(document.getElementById('formViewerModal'));
    viewerModal.show();

    // Hide loader and show iframe when loaded
    iframe.onload = function() {
        loader.style.display = 'none';
        iframe.style.display = 'block';
    };
}

function printIframe() {
    const iframe = document.getElementById('formIframe');
    if (iframe) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }
}

// Clear iframe src when modal is hidden
document.getElementById('formViewerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formIframe').src = '';
});

document.addEventListener('DOMContentLoaded', function () {
    // Live Search Filter
    const searchInput = document.getElementById('formSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const cards = document.querySelectorAll('.form-card-item');
            
            cards.forEach(card => {
                const title = card.querySelector('h5').textContent.toLowerCase();
                const code = card.querySelector('.badge').textContent.toLowerCase();
                const desc = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(query) || code.includes(query) || desc.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Category Tabs Filter
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            // Update active state
            filterBtns.forEach(b => {
                b.classList.remove('btn-navy', 'active');
                b.classList.add('btn-light');
            });
            this.classList.remove('btn-light');
            this.classList.add('btn-navy', 'active');

            const filterValue = this.getAttribute('data-filter');
            const cards = document.querySelectorAll('.form-card-item');

            cards.forEach(card => {
                if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
